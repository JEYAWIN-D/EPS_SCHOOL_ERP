<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\ExtraClass;
use App\Models\Homework;
use App\Models\PeriodReport;
use App\Models\Section;
use App\Models\StaffDuty;
use App\Models\StaffFreePeriodTask;
use App\Models\StaffTestReminder;
use App\Models\Subject;
use App\Models\Substitution;
use App\Models\Task;
use App\Models\Timetable;
use App\Services\StaffTimetableService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffTimetableController extends Controller
{
    protected StaffTimetableService $timetableService;

    public function __construct(StaffTimetableService $timetableService)
    {
        $this->timetableService = $timetableService;
    }

    /**
     * Display the Staff Timetable Hub with search filters, daily/weekly matrix,
     * free periods, extra classes, and academic duties.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']) ||
            $user->can('manage academics') ||
            $user->can('edit academics');

        $authEmployeeId = PeriodReportController::resolveAuthEmployeeId();

        // Target Date
        $targetDateStr = $request->input('date', today()->toDateString());
        try {
            $targetDate = Carbon::parse($targetDateStr);
        } catch (\Throwable) {
            $targetDate = today();
        }

        // View Mode: 'daily', 'weekly', 'calendar'
        $viewMode = $request->input('view', 'weekly');
        if (!in_array($viewMode, ['daily', 'weekly', 'calendar'])) {
            $viewMode = 'weekly';
        }

        // Search and Filter criteria
        $search = trim($request->input('search', ''));
        $departmentId = $request->input('department_id');
        $designationId = $request->input('designation_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $subjectId = $request->input('subject_id');
        $academicYearId = $request->input('academic_year_id');

        // Build list of teaching staff for search / selector dropdown
        $staffQuery = Employee::where('is_active', true)
            ->where(function ($q) {
                $q->where('employee_type', 'teaching')
                  ->orWhereNull('employee_type');
            })
            ->with(['department', 'designation'])
            ->orderBy('first_name');

        if ($search !== '') {
            $staffQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('employee_code', 'ilike', "%{$search}%")
                  ->orWhere('official_email', 'ilike', "%{$search}%")
                  ->orWhere('mobile', 'ilike', "%{$search}%");
            });
        }

        if ($departmentId) {
            $staffQuery->where('department_id', $departmentId);
        }

        if ($designationId) {
            $staffQuery->where('designation_id', $designationId);
        }

        // Filter by class / section / subject allocations
        if ($classId || $sectionId || $subjectId) {
            $matchedTeacherIds = Timetable::query()
                ->when($classId, fn($q) => $q->where('class_id', $classId))
                ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
                ->where('is_active', true)
                ->whereNotNull('teacher_id')
                ->distinct()
                ->pluck('teacher_id');

            $staffQuery->whereIn('id', $matchedTeacherIds);
        }

        $teachingStaffList = $staffQuery->take(100)->get();

        // Determine which employee's timetable to display
        $selectedStaffId = $request->input('staff_id');

        // Security check: If standard teacher without admin rights, enforce viewing own timetable
        if (!$isElevated && $authEmployeeId) {
            $selectedStaffId = $authEmployeeId;
        }

        // Fallback to first available or auth employee if none selected
        if (!$selectedStaffId) {
            if ($authEmployeeId && $teachingStaffList->contains('id', $authEmployeeId)) {
                $selectedStaffId = $authEmployeeId;
            } elseif ($teachingStaffList->isNotEmpty()) {
                $selectedStaffId = $teachingStaffList->first()->id;
            } else {
                $selectedStaffId = Employee::where('is_active', true)->value('id');
            }
        }

        $selectedStaff = $selectedStaffId
            ? Employee::with(['department', 'designation', 'user'])->find($selectedStaffId)
            : null;

        $daySchedule = null;
        $weeklyMatrix = null;
        $pendingReports = collect();
        $upcomingTests = collect();
        $recentHomework = collect();
        $freeTasks = collect();
        $extraClasses = collect();
        $assignedDuties = collect();
        $stats = [
            'teaching_periods_today' => 0,
            'free_periods_today'     => 0,
            'extra_classes_today'    => 0,
            'substitutions_today'    => 0,
            'weekly_hours'           => 0,
        ];

        if ($selectedStaff) {
            // 1. Day Schedule
            $daySchedule = $this->timetableService->getDaySchedule($selectedStaff, $targetDate);

            // 2. Weekly Matrix
            $weeklyMatrix = $this->timetableService->getWeeklyMatrix($selectedStaff, $targetDate);

            // Summary stats
            $stats['teaching_periods_today'] = $daySchedule['metrics']['teaching_periods'] ?? 0;
            $stats['free_periods_today']     = $daySchedule['metrics']['free_periods'] ?? 0;
            $stats['extra_classes_today']    = $daySchedule['metrics']['extra_classes'] ?? 0;
            $stats['substitutions_today']    = $daySchedule['metrics']['substitutions'] ?? 0;
            $stats['weekly_hours']           = $weeklyMatrix['summary']['weekly_teaching_hours'] ?? 0;

            // 3. Pending & Recent Period Reports (last 10)
            $pendingReports = PeriodReport::with(['subject', 'class', 'section'])
                ->where('teacher_id', $selectedStaff->id)
                ->orderByDesc('date')
                ->take(10)
                ->get();

            // 4. Upcoming Test Reminders (scheduled on or after today)
            $upcomingTests = StaffTestReminder::with(['subject', 'class', 'section'])
                ->where('teacher_id', $selectedStaff->id)
                ->whereDate('test_date', '>=', today()->subDays(1))
                ->orderBy('test_date')
                ->take(8)
                ->get();

            // 5. Recent Homework / Assignments (assigned_by is foreign key to employees/users)
            $recentHomework = Homework::with(['class', 'section', 'subject'])
                ->where('assigned_by', $selectedStaff->id)
                ->orderByDesc('assigned_date')
                ->take(8)
                ->get();

            // 6. Free Period Tasks for the current week
            $startOfWeek = $targetDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $endOfWeek = $targetDate->copy()->endOfWeek(Carbon::SATURDAY)->toDateString();
            $freeTasks = StaffFreePeriodTask::where('teacher_id', $selectedStaff->id)
                ->whereBetween('date', [$startOfWeek, $endOfWeek])
                ->orderBy('date')
                ->orderBy('period_number')
                ->get();

            // 7. Extra classes scheduled around this date
            $extraClasses = ExtraClass::with(['class', 'section', 'subject'])
                ->where('teacher_id', $selectedStaff->id)
                ->whereDate('date', '>=', today()->subDays(7))
                ->orderByDesc('date')
                ->take(10)
                ->get();

            // 8. Staff duties scheduled around this date
            $assignedDuties = StaffDuty::where('teacher_id', $selectedStaff->id)
                ->whereDate('date', '>=', today()->subDays(7))
                ->orderByDesc('date')
                ->take(10)
                ->get();
        }

        // Support dropdown datasets
        $departments = Department::orderBy('name')->get();
        $designations = Designation::orderBy('name')->get();
        $classes = Classes::with('sections')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        return view('staff-timetable.index', compact(
            'teachingStaffList',
            'selectedStaff',
            'daySchedule',
            'weeklyMatrix',
            'targetDate',
            'viewMode',
            'stats',
            'pendingReports',
            'upcomingTests',
            'recentHomework',
            'freeTasks',
            'extraClasses',
            'assignedDuties',
            'departments',
            'designations',
            'classes',
            'subjects',
            'academicYears',
            'isElevated',
            'authEmployeeId'
        ));
    }

    /**
     * Dedicated shortcut route for individual teachers to jump straight to their own schedule.
     */
    public function myTimetable(Request $request): RedirectResponse
    {
        $authEmpId = PeriodReportController::resolveAuthEmployeeId();
        if (!$authEmpId) {
            return redirect()->route('dashboard')->with('error', 'Your login account is not linked to any active staff profile.');
        }

        return redirect()->route('staff-timetable.index', [
            'staff_id' => $authEmpId,
            'view'     => $request->input('view', 'weekly'),
            'date'     => $request->input('date', today()->toDateString()),
        ]);
    }

    /**
     * Schedule an Extra Class with automatic collision detection.
     */
    public function storeExtraClass(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id'    => 'required|exists:employees,id',
            'class_id'      => 'required|exists:classes,id',
            'section_id'    => 'required|exists:sections,id',
            'subject_id'    => 'required|exists:subjects,id',
            'date'          => 'required|date_format:Y-m-d',
            'period_number' => 'required|integer|min:1|max:8',
            'room'          => 'nullable|string|max:100',
            'reason'        => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']) || $user->can('edit academics');
        $authEmpId = PeriodReportController::resolveAuthEmployeeId();

        // RBAC: Teachers can only schedule/request extra classes for themselves
        if (!$isElevated && (int)$validated['teacher_id'] !== (int)$authEmpId) {
            return $this->respondError($request, 'Unauthorized: You can only schedule extra classes for your own timetable.', 403);
        }

        // Perform Conflict Detection Engine
        $conflictCheck = $this->timetableService->detectConflicts([
            'teacher_id'    => $validated['teacher_id'],
            'date'          => $validated['date'],
            'period_number' => $validated['period_number'],
            'class_id'      => $validated['class_id'],
            'section_id'    => $validated['section_id'],
            'room'          => $validated['room'] ?? null,
        ]);

        if ($conflictCheck['has_conflict']) {
            $errorMessage = implode(' | ', $conflictCheck['conflicts']);
            return $this->respondError($request, "Conflict detected: {$errorMessage}", 422, $conflictCheck['conflicts']);
        }

        $standardSlots = StaffTimetableService::getStandardPeriodsConfig();
        $slotTiming = $standardSlots[$validated['period_number']] ?? null;

        $extraClass = ExtraClass::create([
            'teacher_id'        => $validated['teacher_id'],
            'class_id'          => $validated['class_id'],
            'section_id'        => $validated['section_id'],
            'subject_id'        => $validated['subject_id'],
            'academic_year_id'  => AcademicYear::where('is_active', true)->value('id'),
            'date'              => $validated['date'],
            'period_number'     => $validated['period_number'],
            'start_time'        => $slotTiming ? $slotTiming['start_time'] : null,
            'end_time'          => $slotTiming ? $slotTiming['end_time'] : null,
            'room'              => $validated['room'] ?: 'Assigned Classroom',
            'reason'            => $validated['reason'] ?: 'Curriculum revision & syllabus coverage',
            'approval_status'   => $isElevated ? 'approved' : 'pending',
            'approved_by'       => $isElevated ? $user->id : null,
            'approved_at'       => $isElevated ? now() : null,
            'completion_status' => 'scheduled',
            'created_by'        => $user->id,
        ]);

        // Audit Log
        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'EXTRA_CLASS_SCHEDULED',
            'model_type' => 'ExtraClass',
            'model_id'   => $extraClass->id,
            'old_values' => [],
            'new_values' => $extraClass->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $message = $isElevated
            ? "Extra Class successfully scheduled and approved for Period {$validated['period_number']} on {$validated['date']}."
            : "Extra Class request submitted for administrative approval.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'extra_class' => $extraClass]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Admin approval workflow for requested Extra Classes.
     */
    public function approveExtraClass(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'principal']) && !$user->can('edit academics')) {
            return $this->respondError($request, 'Unauthorized: Only administrators or academic coordinators can approve extra classes.', 403);
        }

        $extraClass = ExtraClass::with(['teacher', 'class', 'section', 'subject'])->findOrFail($id);

        // Re-check conflict to prevent stale bookings
        $conflictCheck = $this->timetableService->detectConflicts([
            'teacher_id'    => $extraClass->teacher_id,
            'date'          => $extraClass->date->toDateString(),
            'period_number' => $extraClass->period_number,
            'class_id'      => $extraClass->class_id,
            'section_id'    => $extraClass->section_id,
            'room'          => $extraClass->room,
        ]);

        if ($conflictCheck['has_conflict']) {
            $errorMessage = implode(' | ', $conflictCheck['conflicts']);
            return $this->respondError($request, "Cannot approve: A schedule conflict has developed: {$errorMessage}", 422);
        }

        $extraClass->update([
            'approval_status' => 'approved',
            'approved_by'     => $user->id,
            'approved_at'     => now(),
        ]);

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'EXTRA_CLASS_APPROVED',
            'model_type' => 'ExtraClass',
            'model_id'   => $extraClass->id,
            'old_values' => ['approval_status' => 'pending'],
            'new_values' => ['approval_status' => 'approved', 'approved_by' => $user->id],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $message = "Extra class for {$extraClass->teacher?->first_name} has been approved and added to the official timetable.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Reject an Extra Class request.
     */
    public function rejectExtraClass(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'principal']) && !$user->can('edit academics')) {
            return $this->respondError($request, 'Unauthorized.', 403);
        }

        $extraClass = ExtraClass::findOrFail($id);
        $extraClass->update(['approval_status' => 'rejected']);

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'EXTRA_CLASS_REJECTED',
            'model_type' => 'ExtraClass',
            'model_id'   => $extraClass->id,
            'old_values' => ['approval_status' => $extraClass->getOriginal('approval_status')],
            'new_values' => ['approval_status' => 'rejected'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Extra class request rejected.']);
        }

        return redirect()->back()->with('info', 'Extra class request rejected.');
    }

    /**
     * Reserve / Book an activity during an unallocated Free Period.
     */
    public function storeFreePeriodTask(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id'    => 'required|exists:employees,id',
            'date'          => 'required|date_format:Y-m-d',
            'period_number' => 'required|integer|min:1|max:8',
            'activity_type' => 'required|string|max:50',
            'title'         => 'required|string|max:255',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']) || $user->can('edit academics');
        $authEmpId = PeriodReportController::resolveAuthEmployeeId();

        if (!$isElevated && (int)$validated['teacher_id'] !== (int)$authEmpId) {
            return $this->respondError($request, 'Unauthorized: You can only schedule tasks in your own free periods.', 403);
        }

        // Verify that this slot is currently unallocated
        $teacher = Employee::findOrFail($validated['teacher_id']);
        $daySched = $this->timetableService->getDaySchedule($teacher, $validated['date']);
        $slot = $daySched['periods'][$validated['period_number']] ?? null;

        if ($slot && $slot['period_type'] !== 'free_period') {
            return $this->respondError($request, "Cannot book activity: Period {$validated['period_number']} is currently occupied by a {$slot['period_label']} ({$slot['subject_name']}).", 422);
        }

        // Create or update free period task
        $task = StaffFreePeriodTask::updateOrCreate(
            [
                'teacher_id'    => $validated['teacher_id'],
                'date'          => $validated['date'],
                'period_number' => $validated['period_number'],
            ],
            [
                'activity_type' => $validated['activity_type'],
                'title'         => $validated['title'],
                'notes'         => $validated['notes'] ?? null,
                'status'        => 'planned',
                'created_by'    => $user->id,
            ]
        );

        $message = "Free Period {$validated['period_number']} reserved for: {$task->activity_label}.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'task' => $task]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Delete/Clear a booked Free Period activity.
     */
    public function deleteFreePeriodTask(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $task = StaffFreePeriodTask::findOrFail($id);
        $user = Auth::user();
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']) || $user->can('edit academics');
        $authEmpId = PeriodReportController::resolveAuthEmployeeId();

        if (!$isElevated && (int)$task->teacher_id !== (int)$authEmpId) {
            return $this->respondError($request, 'Unauthorized.', 403);
        }

        $periodNum = $task->period_number;
        $task->delete();

        $message = "Period {$periodNum} restored to available free period.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Create a Class Test / Examination Reminder.
     */
    public function storeTestReminder(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id'                  => 'required|exists:employees,id',
            'class_id'                    => 'required|exists:classes,id',
            'section_id'                  => 'required|exists:sections,id',
            'subject_id'                  => 'required|exists:subjects,id',
            'test_title'                  => 'required|string|max:255',
            'test_type'                   => 'required|string|in:class_test,unit_test,model_exam,term_exam,revision_test',
            'test_date'                   => 'required|date_format:Y-m-d',
            'syllabus_details'            => 'nullable|string|max:1000',
            'max_marks'                   => 'nullable|integer|min:5|max:200',
            'paper_submission_deadline'   => 'nullable|date_format:Y-m-d',
            'evaluation_deadline'         => 'nullable|date_format:Y-m-d',
            'result_publication_deadline' => 'nullable|date_format:Y-m-d',
            'instructions'                => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']) || $user->can('edit academics');
        $authEmpId = PeriodReportController::resolveAuthEmployeeId();

        if (!$isElevated && (int)$validated['teacher_id'] !== (int)$authEmpId) {
            return $this->respondError($request, 'Unauthorized: You can only create test reminders for your assigned classes.', 403);
        }

        $reminder = StaffTestReminder::create([
            'teacher_id'                  => $validated['teacher_id'],
            'class_id'                    => $validated['class_id'],
            'section_id'                  => $validated['section_id'],
            'subject_id'                  => $validated['subject_id'],
            'academic_year_id'            => AcademicYear::where('is_active', true)->value('id'),
            'test_title'                  => $validated['test_title'],
            'test_type'                   => $validated['test_type'],
            'test_date'                   => $validated['test_date'],
            'syllabus_details'            => $validated['syllabus_details'] ?? null,
            'max_marks'                   => $validated['max_marks'] ?? 25,
            'paper_submission_deadline'   => $validated['paper_submission_deadline'] ?? null,
            'evaluation_deadline'         => $validated['evaluation_deadline'] ?? null,
            'result_publication_deadline' => $validated['result_publication_deadline'] ?? null,
            'status'                      => 'scheduled',
            'instructions'                => $validated['instructions'] ?? null,
            'created_by'                  => $user->id,
        ]);

        $message = "Test reminder '{$reminder->test_title}' created successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'reminder' => $reminder]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Delete Test Reminder.
     */
    public function deleteTestReminder(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $reminder = StaffTestReminder::findOrFail($id);
        $user = Auth::user();
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']) || $user->can('edit academics');
        $authEmpId = PeriodReportController::resolveAuthEmployeeId();

        if (!$isElevated && (int)$reminder->teacher_id !== (int)$authEmpId) {
            return $this->respondError($request, 'Unauthorized.', 403);
        }

        $reminder->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Test reminder deleted.']);
        }

        return redirect()->back()->with('success', 'Test reminder deleted.');
    }

    /**
     * Assign Administrative Duty or Exam Duty to a Staff Member.
     */
    public function storeDuty(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'principal']) && !$user->can('edit academics')) {
            return $this->respondError($request, 'Unauthorized: Only administrators can assign staff duties.', 403);
        }

        $validated = $request->validate([
            'teacher_id'    => 'required|exists:employees,id',
            'duty_type'     => 'required|in:exam_duty,meeting,remedial,practical_lab,activity_period,leave_unavailable',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string|max:1000',
            'date'          => 'required|date_format:Y-m-d',
            'period_number' => 'required|integer|min:1|max:8',
            'room'          => 'nullable|string|max:100',
        ]);

        // Conflict check
        $conflictCheck = $this->timetableService->detectConflicts([
            'teacher_id'    => $validated['teacher_id'],
            'date'          => $validated['date'],
            'period_number' => $validated['period_number'],
        ]);

        if ($conflictCheck['has_conflict']) {
            $errorMessage = implode(' | ', $conflictCheck['conflicts']);
            return $this->respondError($request, "Conflict detected: {$errorMessage}", 422);
        }

        $duty = StaffDuty::create([
            'teacher_id'       => $validated['teacher_id'],
            'academic_year_id' => AcademicYear::where('is_active', true)->value('id'),
            'duty_type'        => $validated['duty_type'],
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'date'             => $validated['date'],
            'day_of_week'      => Carbon::parse($validated['date'])->dayOfWeekIso,
            'period_number'    => $validated['period_number'],
            'room'             => $validated['room'] ?: 'Campus Center',
            'status'           => 'scheduled',
            'assigned_by'      => $user->id,
        ]);

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'STAFF_DUTY_ASSIGNED',
            'model_type' => 'StaffDuty',
            'model_id'   => $duty->id,
            'old_values' => [],
            'new_values' => $duty->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $message = "Staff duty '{$duty->title}' successfully assigned.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'duty' => $duty]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Delete / Cancel an assigned Staff Duty.
     */
    public function deleteDuty(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'principal']) && !$user->can('edit academics')) {
            return $this->respondError($request, 'Unauthorized.', 403);
        }

        $duty = StaffDuty::findOrFail($id);
        $duty->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Staff duty cancelled.']);
        }

        return redirect()->back()->with('success', 'Staff duty cancelled.');
    }

    /**
     * Staff Workload & Academic Analytics Dashboard.
     */
    public function analytics(Request $request): View
    {
        $departmentId = $request->input('department_id');
        $academicYearId = $request->input('academic_year_id');

        $analytics = $this->timetableService->getWorkloadAnalytics($academicYearId, $departmentId);
        $departments = Department::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        return view('staff-timetable.analytics', compact('analytics', 'departments', 'academicYears', 'departmentId'));
    }

    /**
     * Printable PDF Export View for an individual teacher's timetable.
     */
    public function exportPdf(Request $request, int $teacherId): View|RedirectResponse
    {
        $user = Auth::user();
        $isElevated = $user->hasAnyRole(['super_admin', 'admin', 'principal']) ||
            $user->can('manage academics') ||
            $user->can('edit academics');
        $authEmpId = PeriodReportController::resolveAuthEmployeeId();

        if (!$isElevated && (int)$teacherId !== (int)$authEmpId) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $teacher = Employee::with(['department', 'designation'])->findOrFail($teacherId);
        $targetDate = $request->has('date') ? Carbon::parse($request->date) : today();
        $weeklyMatrix = $this->timetableService->getWeeklyMatrix($teacher, $targetDate);

        return view('staff-timetable.pdf', compact('teacher', 'weeklyMatrix', 'targetDate'));
    }

    /**
     * AJAX API: Find available free teaching staff for a given date and period slot.
     */
    public function apiFreeStaff(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());
        $periodNumber = (int) $request->input('period_number', 1);
        $excludeTeacherId = $request->input('exclude_teacher_id');

        $freeStaff = $this->timetableService->getAvailableFreeStaff($date, $periodNumber, $excludeTeacherId);

        return response()->json([
            'success' => true,
            'date'    => $date,
            'period'  => $periodNumber,
            'count'   => $freeStaff->count(),
            'staff'   => $freeStaff->map(fn($t) => [
                'id'            => $t->id,
                'name'          => $t->first_name . ' ' . $t->last_name,
                'employee_code' => $t->employee_code,
                'department'    => $t->department?->name ?? 'General',
                'designation'   => $t->designation?->name ?? 'Faculty',
            ]),
        ]);
    }

    /**
     * AJAX API: Pre-validate timetable collisions before form submission.
     */
    public function apiConflictCheck(Request $request): JsonResponse
    {
        $check = $this->timetableService->detectConflicts([
            'teacher_id'    => $request->input('teacher_id'),
            'date'          => $request->input('date'),
            'period_number' => $request->input('period_number'),
            'class_id'      => $request->input('class_id'),
            'section_id'    => $request->input('section_id'),
            'room'          => $request->input('room'),
        ]);

        return response()->json([
            'success'      => true,
            'has_conflict' => $check['has_conflict'],
            'conflicts'    => $check['conflicts'],
        ]);
    }

    /**
     * Helper to return standard error response based on request format.
     */
    protected function respondError(Request $request, string $message, int $statusCode = 400, array $errors = []): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors'  => $errors,
            ], $statusCode);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
