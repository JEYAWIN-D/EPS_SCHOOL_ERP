<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Employee;
use App\Models\PeriodReport;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeriodReportController extends Controller
{
    /**
     * Resolve the employee ID for the authenticated user.
     */
    public static function resolveAuthEmployeeId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if ($user->employee_id) {
            return (int) $user->employee_id;
        }

        if ($user->employee && $user->employee->id) {
            return (int) $user->employee->id;
        }

        $byUserId = Employee::where('user_id', $user->id)->value('id');
        if ($byUserId) {
            return (int) $byUserId;
        }

        if (!empty($user->username)) {
            $byCode = Employee::where('employee_code', $user->username)->value('id');
            if ($byCode) {
                return (int) $byCode;
            }
        }

        // Fallback: match by email in Employee record
        $emp = Employee::where('official_email', $user->email)
            ->orWhere('personal_email', $user->email)
            ->orWhere('email', $user->email)
            ->first();

        return $emp?->id;
    }

    /**
     * Get report data for a specific timetable slot and date.
     */
    public function getReport(Request $request, int $timetableId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $timetable = Timetable::with(['class', 'section', 'subject', 'teacher'])->findOrFail($timetableId);
        $authEmployeeId = self::resolveAuthEmployeeId();

        // If user is not elevated, verify they are assigned to this slot
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']);
        if (!$isElevated) {
            if (!$authEmployeeId || (int)$timetable->teacher_id !== (int)$authEmployeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You are not assigned to this timetable period.',
                ], 403);
            }
        }

        $date = $request->input('date', today()->toDateString());

        // Find existing report
        $report = PeriodReport::where('timetable_id', $timetable->id)
            ->whereDate('date', $date)
            ->with(['teacher', 'subject', 'class', 'section'])
            ->first();

        // If not found by timetable_id, search by teacher + class + section + period_number + date
        if (!$report && $timetable->teacher_id) {
            $report = PeriodReport::where('teacher_id', $timetable->teacher_id)
                ->where('class_id', $timetable->class_id)
                ->where('section_id', $timetable->section_id)
                ->where('period_number', (string)$timetable->period_number)
                ->whereDate('date', $date)
                ->with(['teacher', 'subject', 'class', 'section'])
                ->first();
        }

        // Active student count for this section
        $totalStudents = StudentEnrollment::where('class_id', $timetable->class_id)
            ->where('section_id', $timetable->section_id)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'success'        => true,
            'timetable'      => [
                'id'            => $timetable->id,
                'period_number' => $timetable->period_number,
                'day_of_week'   => (int) $timetable->day_of_week,
                'day_name'      => [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'][(int)$timetable->day_of_week] ?? Carbon::parse($date)->format('l'),
                'class_id'      => $timetable->class_id,
                'class_name'    => $timetable->class?->name ?? '',
                'section_id'    => $timetable->section_id,
                'section_name'  => $timetable->section?->name ?? '',
                'subject_id'    => $timetable->subject_id,
                'subject_name'  => $timetable->subject?->name ?? '',
                'teacher_id'    => $timetable->teacher_id,
                'teacher_name'  => $timetable->teacher ? ($timetable->teacher->first_name . ' ' . $timetable->teacher->last_name) : '',
                'start_time'    => $timetable->start_time ? Carbon::parse($timetable->start_time)->format('h:i A') : '',
                'end_time'      => $timetable->end_time ? Carbon::parse($timetable->end_time)->format('h:i A') : '',
                'room'          => $timetable->room,
            ],
            'total_students' => $totalStudents,
            'date'           => $date,
            'report'         => $report ? [
                'id'            => $report->id,
                'present_count' => $report->student_present_count,
                'absent_count'  => $report->student_absent_count,
                'description'   => $report->description,
                'created_at'    => $report->created_at?->format('Y-m-d H:i:s'),
                'updated_at'    => $report->updated_at?->format('Y-m-d H:i:s'),
            ] : null,
        ]);
    }

    /**
     * Store or update a period report for a teacher's assigned timetable period.
     */
    public function saveReport(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'timetable_id'  => 'required|exists:timetables,id',
            'class_id'      => 'required|exists:classes,id',
            'section_id'    => 'required|exists:sections,id',
            'subject_id'    => 'required|exists:subjects,id',
            'period_number' => 'required',
            'day'           => 'nullable|string',
            'date'          => 'required|date',
            'present_count' => 'required|integer|min:0',
            'absent_count'  => 'required|integer|min:0',
            'description'   => 'required|string|max:3000',
        ]);

        $timetable = Timetable::with(['class', 'section', 'subject', 'teacher'])->findOrFail($validated['timetable_id']);
        $authEmployeeId = self::resolveAuthEmployeeId();
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']);

        // Teacher security verification:
        // Must verify that the logged-in teacher is actually assigned to that period
        // Validate class, section, subject, period against actual timetable assignment
        if (!$isElevated) {
            if (!$authEmployeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your user account is not linked to an employee/staff profile.',
                ], 403);
            }

            if ((int)$timetable->teacher_id !== (int)$authEmployeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You are not assigned to this timetable period. You cannot submit reports for another teacher.',
                ], 403);
            }
        }

        // Validate that class_id and section_id match the timetable record
        if ((int)$timetable->class_id !== (int)$validated['class_id'] ||
            (int)$timetable->section_id !== (int)$validated['section_id'] ||
            (int)$timetable->period_number !== (int)$validated['period_number']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid timetable period mapping parameters.',
            ], 422);
        }

        // Check active student strength if available:
        // present_count + absent_count <= total students
        $totalStudents = StudentEnrollment::where('class_id', $timetable->class_id)
            ->where('section_id', $timetable->section_id)
            ->where('status', 'active')
            ->count();

        $presentCount = (int)$validated['present_count'];
        $absentCount = (int)$validated['absent_count'];

        if ($totalStudents > 0 && ($presentCount + $absentCount) > $totalStudents) {
            return response()->json([
                'success' => false,
                'message' => "Validation Error: Present ({$presentCount}) + Absent ({$absentCount}) = " . ($presentCount + $absentCount) . " exceeds total student strength ({$totalStudents} students) for Class {$timetable->class?->name} Section {$timetable->section?->name}.",
            ], 422);
        }

        $effectiveTeacherId = $isElevated ? ($timetable->teacher_id ?: $authEmployeeId) : $authEmployeeId;
        $currentYear = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::latest('id')->first();

        // Prevent duplicate records:
        // Check for existing report for the same teacher, date, and timetable slot
        $report = PeriodReport::where('teacher_id', $effectiveTeacherId)
            ->whereDate('date', $validated['date'])
            ->where(function ($q) use ($timetable) {
                $q->where('timetable_id', $timetable->id)
                  ->orWhere(function ($sub) use ($timetable) {
                      $sub->where('class_id', $timetable->class_id)
                          ->where('section_id', $timetable->section_id)
                          ->where('period_number', (string)$timetable->period_number);
                  });
            })
            ->first();

        $isUpdate = (bool)$report;

        if (!$report) {
            $report = new PeriodReport();
            $report->teacher_id = $effectiveTeacherId;
            $report->class_id = $timetable->class_id;
            $report->section_id = $timetable->section_id;
            $report->subject_id = $timetable->subject_id ?: $validated['subject_id'];
            $report->timetable_id = $timetable->id;
            $report->academic_year_id = $currentYear?->id;
            $report->date = $validated['date'];
            $report->period_number = (string)$timetable->period_number;
            $report->created_by = $user->id;
        }

        $dayMap = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
        $report->day = $validated['day'] ?? ($dayMap[(int)$timetable->day_of_week] ?? Carbon::parse($validated['date'])->format('l'));
        $report->student_present_count = $presentCount;
        $report->student_absent_count = $absentCount;
        $report->description = $validated['description'];
        $report->topic_covered = $validated['description'];
        $report->teaching_report = $validated['description'];
        $report->save();

        $report->load(['teacher', 'subject', 'class', 'section']);

        return response()->json([
            'success' => true,
            'message' => $isUpdate ? 'Period report updated successfully!' : 'Period report saved successfully!',
            'report'  => [
                'id'            => $report->id,
                'class_name'    => $report->class?->name ?? '',
                'section_name'  => $report->section?->name ?? '',
                'subject_name'  => $report->subject?->name ?? '',
                'period_number' => $report->period_number,
                'date'          => Carbon::parse($report->date)->format('Y-m-d'),
                'formatted_date'=> Carbon::parse($report->date)->format('d M Y'),
                'day'           => $report->day ?: Carbon::parse($report->date)->format('l'),
                'present_count' => $report->student_present_count,
                'absent_count'  => $report->student_absent_count,
                'description'   => $report->description,
                'created_at'    => $report->created_at?->format('d M Y h:i A'),
            ],
        ]);
    }

    /**
     * List previously saved period reports.
     * Teachers can only view their own reports.
     * Principals/admins can view all reports or filter by teacher.
     */
    public function listReports(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $authEmployeeId = self::resolveAuthEmployeeId();
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']);

        $query = PeriodReport::with(['teacher', 'subject', 'class', 'section'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if (!$isElevated) {
            // Strictly scope to authenticated teacher's own employee ID
            if (!$authEmployeeId) {
                return response()->json(['success' => true, 'reports' => []]);
            }
            $query->where('teacher_id', $authEmployeeId);
        } else {
            // Elevated roles can filter by teacher if requested
            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->input('teacher_id'));
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->input('section_id'));
        }

        $reports = $query->take(100)->get()->map(function ($r) {
            return [
                'id'            => $r->id,
                'teacher_id'    => $r->teacher_id,
                'teacher_name'  => $r->teacher ? ($r->teacher->first_name . ' ' . $r->teacher->last_name) : 'Faculty',
                'class_id'      => $r->class_id,
                'class_name'    => $r->class?->name ?? '',
                'section_id'    => $r->section_id,
                'section_name'  => $r->section?->name ?? '',
                'subject_id'    => $r->subject_id,
                'subject_name'  => $r->subject?->name ?? 'Subject',
                'period_number' => $r->period_number,
                'date'          => Carbon::parse($r->date)->format('Y-m-d'),
                'formatted_date'=> Carbon::parse($r->date)->format('d M Y'),
                'day'           => $r->day ?: ($r->date ? Carbon::parse($r->date)->format('l') : ''),
                'present_count' => $r->student_present_count,
                'absent_count'  => $r->student_absent_count,
                'description'   => $r->description,
                'created_at'    => $r->created_at?->format('d M Y h:i A'),
            ];
        });

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }
}
