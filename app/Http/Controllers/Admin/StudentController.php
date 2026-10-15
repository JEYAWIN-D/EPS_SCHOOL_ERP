<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\FeeReminderMail;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\NotificationTemplate;
use App\Models\ScholarshipScheme;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentConcession;
use App\Models\StudentDisciplinary;
use App\Models\StudentDocument;
use App\Models\StudentEnrollment;
use App\Models\StudentMedicalRecord;
use App\Models\StudentPromotion;
use App\Models\StudentVaccination;
use App\Models\User;
use App\Models\Employee;
use App\Exports\StudentsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    /**
     * Verify that the requested student ID is within the user's allowed scope.
     */
    private function assertStudentInScope(Request $request, int $studentId): void
    {
        if (!$request->is_scoped) {
            return;
        }

        // Parent / student scoping
        if (!empty($request->scope_student_ids) && !in_array($studentId, $request->scope_student_ids)) {
            abort(403, 'Unauthorized access: you do not have permission to view or modify this student.');
        }

        // Teacher scoping (restricted to assigned classes)
        if ($request->is_scoped && empty($request->scope_student_ids)) {
            if (empty($request->scope_class_ids)) {
                abort(403, 'Unauthorized access: you have no assigned classes.');
            }

            $isInClass = DB::table('student_enrollments')
                ->where('student_id', $studentId)
                ->where('status', 'active')
                ->whereIn('class_id', $request->scope_class_ids)
                ->exists();

            if (!$isInClass) {
                abort(403, 'Unauthorized access: this student is not in your assigned classes.');
            }
        }
    }

    public function index(Request $request)
    {
        $currentYear = AcademicYear::current();
        $sort        = $request->input('sort', 'latest');

        $query = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $v = strtolower(trim($request->search));
                $q->where(function ($sq) use ($v) {
                    $sq->whereRaw("LOWER(first_name) LIKE ?", ["%$v%"])
                      ->orWhereRaw("LOWER(last_name) LIKE ?", ["%$v%"])
                      ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", ["%$v%"])
                      ->orWhereRaw("LOWER(CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name)) LIKE ?", ["%$v%"])
                      ->orWhereRaw("LOWER(admission_no) LIKE ?", ["%$v%"])
                      ->orWhereRaw("LOWER(roll_number) LIKE ?", ["%$v%"])
                      ->orWhereRaw("LOWER(mobile) LIKE ?", ["%$v%"])
                      ->orWhereRaw("LOWER(father_name) LIKE ?", ["%$v%"])
                      ->orWhereRaw("LOWER(father_mobile) LIKE ?", ["%$v%"])
                      ->orWhereHas('currentEnrollment', function ($eq) use ($v) {
                          $eq->whereRaw("LOWER(roll_number) LIKE ?", ["%$v%"]);
                      });
                });
            })
            ->when($request->filled('class_id'), fn($q) => $q->whereHas('currentEnrollment', fn($q) => $q->where('class_id', $request->class_id)))
            ->when($request->filled('section'), fn($q) => $q->whereHas('currentEnrollment.section', function ($sq) use ($request) {
                $rawSec = trim($request->section);
                $cleanSec = trim(preg_replace('/^section\s*/i', '', $rawSec));
                $sq->whereRaw("UPPER(name) = ?", [strtoupper($cleanSec)])
                  ->orWhereRaw("UPPER(name) = ?", ['SECTION ' . strtoupper($cleanSec)])
                  ->orWhereRaw("UPPER(name) LIKE ?", ['%' . strtoupper($cleanSec) . '%']);
            }))
            ->when($request->filled('gender'), fn($q) => $q->where('gender', $request->gender))
            ->when($request->filled('student_type'), fn($q) => $q->where('student_type', $request->student_type))
            ->when($request->filled('status') && $request->status !== 'all', fn($q) => $q->where('status', $request->status))
            ->when(!$request->has('status'), fn($q) => $q->where('status', 'active'))
            // ScopeToUser: restrict class_teachers/subject_teachers to their assigned classes
            ->when($request->is_scoped && empty($request->scope_student_ids), function($q) use ($request) {
                if (!empty($request->scope_class_ids)) {
                    $q->whereHas('currentEnrollment', fn($sq) => $sq->whereIn('class_id', $request->scope_class_ids));
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

        // Sorting
        switch ($sort) {
            case 'name_asc':
                $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('first_name', 'desc')->orderBy('last_name', 'desc');
                break;
            case 'oldest':
                $query->orderBy('id', 'asc');
                break;
            case 'roll_asc':
                $query->orderBy('roll_number', 'asc')->orderBy('id', 'desc');
                break;
            case 'adm_asc':
                $query->orderBy('admission_no', 'asc')->orderBy('id', 'desc');
                break;
            case 'latest':
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        $students = $query->paginate(25)->withQueryString();
        $classes  = Classes::activeCached();

        // Distinct section names (cached 1 hour)
        $sectionNames = \Illuminate\Support\Facades\Cache::remember('active_section_names', 3600, function () {
            $names = Section::where('is_active', true)
                ->pluck('name')
                ->map(function ($n) {
                    $clean = trim(preg_replace('/^section\s*/i', '', $n));
                    return strtoupper($clean);
                })
                ->unique()
                ->sort()
                ->values();

            return $names->isNotEmpty() ? $names : collect(['A', 'B', 'C', 'D']);
        });

        return view('students.index', compact('students', 'classes', 'sectionNames', 'currentYear'));
    }

    public function create(Request $request)
    {
        $classes      = Classes::active()->get();
        $academicYear = AcademicYear::current();
        $prefill      = null;
        if ($request->enquiry_id) {
            $prefill = \App\Models\AdmissionEnquiry::find($request->enquiry_id);
        }
        return view('students.create', compact('classes', 'academicYear', 'prefill'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'          => 'required|string|max:60',
            'middle_name'         => 'nullable|string|max:60',
            'last_name'           => 'required|string|max:60',
            'dob'                 => 'required|date|before:today',
            'gender'              => 'required|in:male,female,other',
            'blood_group'         => 'nullable|string|max:5',
            'religion'            => 'nullable|string|max:50',
            'caste'               => 'nullable|string|max:50',
            'category'            => 'nullable|in:general,obc,sc,st,ews,minority',
            'nationality'         => 'nullable|string|max:50',
            'mother_tongue'       => 'nullable|string|max:50',
            'aadhaar_number'      => 'nullable|string|max:12',
            'mobile'              => 'nullable|string|max:15',
            'email'               => 'nullable|email|max:100',
            'residential_address' => 'nullable|string|max:500',
            'permanent_address'   => 'nullable|string|max:500',
            'pincode'             => 'nullable|string|max:10',
            'student_type'        => 'nullable|in:day_scholar,hosteller,day_boarder',
            'is_disabled'         => 'nullable|boolean',
            'disability_description' => 'nullable|string|max:500',
            'annual_family_income'=> 'nullable|numeric|min:0',
            'father_name'         => 'required|string|max:100',
            'father_mobile'       => 'nullable|string|max:15',
            'father_email'        => 'nullable|email|max:100',
            'father_occupation'   => 'nullable|string|max:100',
            'mother_name'         => 'required|string|max:100',
            'mother_mobile'       => 'nullable|string|max:15',
            'mother_occupation'   => 'nullable|string|max:100',
            'guardian_name'       => 'nullable|string|max:100',
            'guardian_mobile'     => 'nullable|string|max:15',
            'class_id'            => 'required|exists:classes,id',
            'section_id'          => 'nullable|exists:sections,id',
            'roll_number'         => 'nullable|string|max:20',
            'house'               => 'nullable|string|max:50',
        ]);

        $currentYear = AcademicYear::current();

        $newStudentId = null;
        $feeHeadNames = [];

        DB::transaction(function () use ($validated, $request, $currentYear, &$newStudentId, &$feeHeadNames) {
            $admissionNumber = Student::generateAdmissionNumber($validated['gender'] ?? $request->gender);

            $student = Student::create(array_merge($validated, [
                'admission_no' => $admissionNumber,
                'admission_date'   => today(),
                'status'           => 'active',
                'student_type'     => $validated['student_type'] ?? 'day_scholar',
                'is_disabled'      => $request->boolean('is_disabled'),
            ]));
            $newStudentId = $student->id;
            AuditLog::record('student_created', $student, [], ['admission_no' => $student->admission_no, 'first_name' => $student->first_name, 'last_name' => $student->last_name]);

            if ($currentYear) {
                StudentEnrollment::create([
                    'student_id'      => $student->id,
                    'academic_year_id'=> $currentYear->id,
                    'class_id'        => $validated['class_id'],
                    'section_id'      => $validated['section_id'] ?? null,
                    'roll_number'     => $validated['roll_number'] ?? null,
                    'house'           => $validated['house'] ?? null,
                    'status'          => 'active',
                ]);

                // Auto-assign: look up fee structure for this class in current year
                $feeHeads = \App\Models\FeeStructure::with('feeHead')
                    ->where('class_id', $validated['class_id'])
                    ->where('academic_year_id', $currentYear->id)
                    ->where('is_active', true)
                    ->get();
                $feeHeadNames = $feeHeads->map(fn($fs) => ($fs->feeHead?->name ?? 'Fee') . ' ₹' . number_format($fs->amount, 0))->values()->toArray();
            }
        });

        $feeMessage = count($feeHeadNames) > 0
            ? ' Fee structure auto-applied: ' . implode(', ', $feeHeadNames) . '.'
            : ' No fee structure found for this class — please assign fees manually.';

        return redirect()->route('students.show', $newStudentId)
            ->with('success', 'Student admitted successfully.' . $feeMessage);
    }

    public function show(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);

        $student = Student::with([
            'enrollments.class',
            'enrollments.section',
            'enrollments.academicYear',
            'transportRoute',
            'transportStop',
        ])->findOrFail($id);

        $year = AcademicYear::current();

        // Fee quick stats
        $feeChargedFromTable = DB::table('student_fee_charges')
            ->where('student_id', $id)
            ->when($year, fn($q) => $q->where('academic_year_id', $year->id))
            ->sum('amount');

        $feeCharged = max((float)$feeChargedFromTable, (float)($student->total_admission_fee ?? 0));

        $feePaid = DB::table('fee_payments')
            ->where('student_id', $id)->where('is_cancelled', false)
            ->when($year, fn($q) => $q->where('academic_year_id', $year->id))
            ->sum('total_paid');
        $feeBalance = max(0, $feeCharged - $feePaid);

        // Fixed Total Working Days per academic year (default 220 days standard session)
        $fixedAnnualDays = (int) \App\Models\SchoolSetting::get('academic_total_working_days', 220);
        if ($fixedAnnualDays <= 0) $fixedAnnualDays = 220;

        // Attendance stats for current year
        $attQuery = \App\Models\AttendanceRecord::where('student_id', $id)
            ->when($year, fn($q) => $q->where('academic_year_id', $year->id));

        $attPresent = (clone $attQuery)->where('status', 'present')->count();
        $attLate    = (clone $attQuery)->where('status', 'late')->count();
        $attHalfDay = (clone $attQuery)->where('status', 'half_day')->count();
        $attAbsent  = (clone $attQuery)->where('status', 'absent')->count();
        $attLeave   = (clone $attQuery)->where('status', 'leave')->count();
        $attTotal   = (clone $attQuery)->count();

        // Total working days conducted to date (max of distinct class dates or student recorded count)
        $totalClassDays = \App\Models\AttendanceRecord::where('academic_year_id', $year?->id)
            ->where('class_id', $student->currentEnrollment?->class_id)
            ->select('date')->distinct()->count();
        $totalDaysConducted = max($totalClassDays, $attTotal);

        // Effective present days (Present + Late + 0.5 * HalfDay)
        $effectivePresent = $attPresent + $attLate + ($attHalfDay * 0.5);
        $attPct = $totalDaysConducted > 0 ? round(($effectivePresent / $totalDaysConducted) * 100, 1) : null;
        $annualTargetPct = round(($effectivePresent / $fixedAnnualDays) * 100, 1);
        $statutoryMinDaysRequired = ceil($fixedAnnualDays * 0.75); // 75% requirement

        // Fetch current year records for history & monthly breakdown
        $allAttRecords = (clone $attQuery)->orderByDesc('date')->get();
        $recentAttendanceRecords = $allAttRecords->take(25);

        // Monthly Attendance Breakdown in PHP (Postgres & MySQL compatible)
        $monthlyAttendance = $allAttRecords
            ->groupBy(fn($r) => \Carbon\Carbon::parse($r->date)->format('Y-m'))
            ->take(6)
            ->map(function ($records, $monthKey) {
                $total = $records->count();
                $presentCount = $records->whereIn('status', ['present', 'late'])->count()
                    + ($records->where('status', 'half_day')->count() * 0.5);
                $absentCount = $records->where('status', 'absent')->count();
                $monthName = \Carbon\Carbon::parse($records->first()->date)->format('M Y');
                return (object)[
                    'month_key'     => $monthKey,
                    'month_name'    => $monthName,
                    'total'         => $total,
                    'present_count' => $presentCount,
                    'absent_count'  => $absentCount,
                ];
            })->values();

        // Recent payments (last 3)
        $recentPayments = DB::table('fee_payments')
            ->where('student_id', $id)->where('is_cancelled', false)
            ->orderByDesc('payment_date')->limit(3)->get();

        // Student Documents & QR Portal Data
        $studentDocuments = StudentDocument::where('student_id', $id)->latest()->get();
        $documentCategories = \App\Http\Controllers\Public\StudentDocumentUploadController::getDocumentCategories();
        $qrUrl = route('public.student.documents', ['token' => $student->document_token]);
        $qrSvg = QrCode::size(140)->margin(1)->generate($qrUrl);

        return response()
            ->view('students.show', compact(
                'student', 'feeCharged', 'feePaid', 'feeBalance',
                'attPresent', 'attAbsent', 'attLate', 'attHalfDay', 'attLeave',
                'attTotal', 'totalDaysConducted', 'fixedAnnualDays', 'effectivePresent',
                'attPct', 'annualTargetPct', 'statutoryMinDaysRequired',
                'recentAttendanceRecords', 'monthlyAttendance',
                'recentPayments',
                'studentDocuments', 'documentCategories', 'qrUrl', 'qrSvg'
            ))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function feeStatusJson(int $id)
    {
        $student = Student::findOrFail($id);
        $year = AcademicYear::current();

        $feeChargedFromTable = DB::table('student_fee_charges')
            ->where('student_id', $id)
            ->when($year, fn($q) => $q->where('academic_year_id', $year->id))
            ->sum('amount');

        $feeCharged = max((float)$feeChargedFromTable, (float)($student->total_admission_fee ?? 0));

        $feePaid = (float) DB::table('fee_payments')
            ->where('student_id', $id)->where('is_cancelled', false)
            ->when($year, fn($q) => $q->where('academic_year_id', $year->id))
            ->sum('total_paid');

        $feeBalance = max(0, $feeCharged - $feePaid);

        $recentPayments = DB::table('fee_payments')
            ->where('student_id', $id)->where('is_cancelled', false)
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->limit(3)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'date' => \Carbon\Carbon::parse($p->payment_date)->format('d M Y'),
                    'amount' => (float)$p->total_paid,
                    'amount_formatted' => '₹' . number_format($p->total_paid),
                ];
            });

        return response()->json([
            'success' => true,
            'feeCharged' => $feeCharged,
            'feeChargedFormatted' => '₹' . number_format($feeCharged),
            'feePaid' => $feePaid,
            'feePaidFormatted' => '₹' . number_format($feePaid),
            'feeBalance' => $feeBalance,
            'feeBalanceFormatted' => $feeBalance > 0 ? ('₹' . number_format($feeBalance)) : 'Fully Paid',
            'isPaid' => $feeBalance <= 0,
            'paymentStatus' => $student->payment_status,
            'recentPayments' => $recentPayments,
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function edit(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);

        $student  = Student::findOrFail($id);
        $classes  = Classes::active()->get();
        $sections = $student->currentEnrollment
            ? Section::where('class_id', $student->currentEnrollment->class_id)->get()
            : collect();

        $employees = Employee::active()->orderBy('first_name')->get();
        return view('students.edit', compact('student', 'classes', 'sections', 'employees'));
    }

    public function update(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);

        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'first_name'          => 'required|string|max:60',
            'middle_name'         => 'nullable|string|max:60',
            'last_name'           => 'required|string|max:60',
            'dob'                 => 'required|date|before:today',
            'gender'              => 'required|in:male,female,other',
            'blood_group'         => 'nullable|string|max:5',
            'religion'            => 'nullable|string|max:50',
            'caste'               => 'nullable|string|max:50',
            'category'            => 'nullable|in:general,obc,sc,st,ews,minority',
            'mobile'              => 'nullable|string|max:15',
            'email'               => 'nullable|email|max:100',
            'residential_address' => 'nullable|string|max:500',
            'permanent_address'   => 'nullable|string|max:500',
            'pincode'             => 'nullable|string|max:10',
            'student_type'        => 'nullable|in:day_scholar,hosteller,day_boarder',
            'is_disabled'         => 'nullable|boolean',
            'disability_description' => 'nullable|string|max:500',
            'annual_family_income'=> 'nullable|numeric|min:0',
            'father_name'         => 'required|string|max:100',
            'father_mobile'       => 'nullable|string|max:15',
            'father_email'        => 'nullable|email|max:100',
            'father_occupation'   => 'nullable|string|max:100',
            'mother_name'         => 'required|string|max:100',
            'mother_mobile'       => 'nullable|string|max:15',
            'mother_occupation'   => 'nullable|string|max:100',
            'guardian_name'       => 'nullable|string|max:100',
            'guardian_mobile'     => 'nullable|string|max:15',
            'previous_school_name'  => 'nullable|string|max:150',
            'previous_school_board' => 'nullable|string|max:100',
            'tc_number'             => 'nullable|string|max:50',
            'tc_date'               => 'nullable|date',
            'previous_percentage'   => 'nullable|numeric|min:0|max:100',
            'migration_certificate_number' => 'nullable|string|max:50',
            'migration_certificate_date'   => 'nullable|date',
            'parent_employee_id'           => 'nullable|exists:employees,id',
        ]);

        $oldEmployeeId = $student->parent_employee_id;
        $student->update(array_merge($validated, ['is_disabled' => $request->boolean('is_disabled')]));
        AuditLog::record('student_updated', $student, [], ['admission_no' => $student->admission_no, 'status' => $student->status]);

        if ($request->filled('house')) {
            $student->currentEnrollment?->update(['house' => $request->house]);
        }

        // Auto-apply staff ward concession when parent_employee_id is set
        if ($request->filled('parent_employee_id') && $oldEmployeeId !== (int) $request->parent_employee_id) {
            $this->applyStaffWardConcession($student->id);
        }

        return redirect()->route('students.show', $student->id)
            ->with('success', 'Student profile updated.');
    }

    public function destroy(int $id)
    {
        abort_unless(auth()->user()->can('delete students'), 403);
        $student = Student::findOrFail($id);
        AuditLog::record('student_deleted', $student, ['admission_no' => $student->admission_no], []);
        $student->update(['status' => 'inactive']);
        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Student deactivated.');
    }

    public function showTCForm(int $id)
    {
        $student    = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($id);
        if ($student->status !== 'active') {
            return redirect()->route('students.show', $student->id)
                ->with('error', 'Transfer Certificate (TC) generation is locked. Student admission must be fully approved by Principal and confirmed by Admin before a TC can be issued.');
        }
        $enrollment = $student->currentEnrollment;

        return view('students.tc-form', compact('student', 'enrollment'));
    }

    public function generateTC(Request $request, int $id)
    {
        $student      = Student::with(['currentEnrollment.class', 'currentEnrollment.section', 'enrollments.academicYear'])->findOrFail($id);
        if ($student->status !== 'active') {
            return redirect()->route('students.show', $student->id)
                ->with('error', 'Transfer Certificate (TC) generation is locked. Student admission must be fully approved by Principal and confirmed by Admin before a TC can be issued.');
        }
        $school       = \App\Models\SchoolSetting::first();
        $enrollment   = $student->currentEnrollment;
        $academicYear = \App\Models\AcademicYear::current();

        // Compute TC number and write back to student record
        if (!$student->tc_number) {
            $tcCount  = Student::whereNotNull('tc_number')->count();
            $tcNumber = 'TC-' . date('Y') . '-' . str_pad($tcCount + 1, 4, '0', STR_PAD_LEFT);
            $student->update(['tc_number' => $tcNumber, 'tc_date' => today()->toDateString()]);
        } else {
            $tcNumber = $student->tc_number;
        }

        // Attendance stats
        $totalDays   = \App\Models\AttendanceRecord::where('student_id', $student->id)->count();
        $presentDays = \App\Models\AttendanceRecord::where('student_id', $student->id)->where('status', 'present')->count();

        // Embed signature/stamp as base64
        $signatureBase64 = null;
        $stampBase64     = null;
        if ($school?->signature_path && \Storage::disk('public')->exists($school->signature_path)) {
            $mime = str_contains($school->signature_path, '.png') ? 'image/png' : 'image/jpeg';
            $signatureBase64 = 'data:' . $mime . ';base64,' . base64_encode(\Storage::disk('public')->get($school->signature_path));
        }
        if ($school?->stamp_path && \Storage::disk('public')->exists($school->stamp_path)) {
            $mime = str_contains($school->stamp_path, '.png') ? 'image/png' : 'image/jpeg';
            $stampBase64 = 'data:' . $mime . ';base64,' . base64_encode(\Storage::disk('public')->get($school->stamp_path));
        }

        // DOB in words
        $dobInWords = $this->dateToWords($student->dob);

        // Reason for leaving
        $reason = $request->input('reason', '');
        if ($reason === 'Other' && $request->filled('custom_reason')) {
            $reason = trim($request->input('custom_reason'));
        }

        $tcData = [
            'leaving_date'  => $request->input('leaving_date', now()->format('Y-m-d')) ? \Carbon\Carbon::parse($request->input('leaving_date', now()))->format('d/m/Y') : now()->format('d/m/Y'),
            'reason'        => $reason,
            'conduct'       => $request->input('conduct', 'Good'),
            'progress'      => $request->input('progress', 'Good'),
            'remarks'       => $request->input('remarks', ''),
            'days_attended' => $presentDays ?: '—',
            'total_days'    => $totalDays   ?: '—',
            'dob_in_words'  => $dobInWords,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.tc', compact(
            'student', 'school', 'enrollment', 'academicYear',
            'tcNumber', 'tcData', 'signatureBase64', 'stampBase64'
        ))->setPaper('A4', 'portrait');

        return $pdf->stream('tc-' . ($student->admission_no ?? $student->id) . '.pdf');
    }

    private function generateAdmissionNumber(?string $gender = null): string
    {
        return Student::generateAdmissionNumber($gender);
    }

    public function export(Request $request)
    {
        return Excel::download(new StudentsExport($request->class_id, $request->section_id), 'students.xlsx');
    }

    public function documents(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);
        $student   = Student::findOrFail($id);
        $documents = StudentDocument::where('student_id', $id)->latest()->get();
        return view('students.documents', compact('student', 'documents'));
    }

    public function uploadDocument(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);

        $request->validate([
            'document_type' => 'required|string|max:100',
            'document'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'expiry_date'   => 'nullable|date',
        ]);

        $student  = Student::findOrFail($id);
        $path     = $request->file('document')->store("students/{$id}/documents", 'local');

        StudentDocument::create([
            'student_id'    => $id,
            'document_type' => $request->document_type,
            'file_path'     => $path,
            'original_name' => $request->file('document')->getClientOriginalName(),
            'status'        => 'pending',
            'expiry_date'   => $request->expiry_date,
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function downloadDocument(Request $request, int $id, int $docId)
    {
        $this->assertStudentInScope($request, $id);

        $student = Student::findOrFail($id);
        $doc     = StudentDocument::where('student_id', $id)->findOrFail($docId);

        if ($doc->file_path && Storage::disk('local')->exists($doc->file_path)) {
            return Storage::disk('local')->download($doc->file_path, $doc->original_name ?? basename($doc->file_path));
        }

        // Fallback for legacy documents uploaded to public disk
        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            return Storage::disk('public')->download($doc->file_path, $doc->original_name ?? basename($doc->file_path));
        }

        abort(404, 'Document file not found.');
    }

    public function verifyDocument(int $docId)
    {
        StudentDocument::findOrFail($docId)->update(['status' => 'verified', 'verified_by' => Auth::id(), 'verified_at' => now()]);
        return back()->with('success', 'Document verified.');
    }

    public function rejectDocument(Request $request, int $docId)
    {
        $doc = StudentDocument::findOrFail($docId);
        $this->assertStudentInScope($request, $doc->student_id);

        $doc->update([
            'status'      => 'rejected',
            'remarks'     => $request->remarks ?: 'Document rejected by admissions verification staff. Please upload a clear original copy.',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Document status updated to Rejected.');
    }

    public function deleteDocument(Request $request, int $docId)
    {
        $doc = StudentDocument::findOrFail($docId);
        $this->assertStudentInScope($request, $doc->student_id);

        if ($doc->file_path) {
            if (Storage::disk('local')->exists($doc->file_path)) {
                Storage::disk('local')->delete($doc->file_path);
            } elseif (Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
        }

        $doc->delete();
        return back()->with('success', 'Document deleted.');
    }

    public function medical(int $id)
    {
        $student     = Student::findOrFail($id);
        $medical     = StudentMedicalRecord::where('student_id', $id)->first();
        $vaccinations = StudentVaccination::where('student_id', $id)->orderByDesc('date_given')->get();
        return view('students.medical', compact('student', 'medical', 'vaccinations'));
    }

    public function saveMedical(Request $request, int $id)
    {
        StudentMedicalRecord::updateOrCreate(['student_id' => $id], $request->only(['blood_group', 'height', 'weight', 'allergies', 'medical_conditions', 'medications', 'emergency_contact', 'doctor_name', 'doctor_contact']));
        return back()->with('success', 'Medical record saved.');
    }

    public function addVaccination(Request $request, int $id)
    {
        $request->validate(['vaccine_name' => 'required|string|max:100', 'date_given' => 'required|date|before_or_equal:today']);
        StudentVaccination::create(['student_id' => $id, 'vaccine_name' => $request->vaccine_name, 'date_given' => $request->date_given, 'dose' => $request->dose, 'given_by' => $request->given_by]);
        return back()->with('success', 'Vaccination record added.');
    }

    public function disciplinary(int $id)
    {
        $student  = Student::findOrFail($id);
        $incidents = StudentDisciplinary::where('student_id', $id)->latest('incident_date')->get();
        return view('students.disciplinary', compact('student', 'incidents'));
    }

    public function addDisciplinary(Request $request, int $id)
    {
        $request->validate([
            'incident_date'   => 'required|date|before_or_equal:today',
            'incident_type'   => 'required|string',
            'description'     => 'required|string|min:5',
            'action_type'     => 'nullable|in:warning,suspension,expulsion,positive_award,other',
            'suspension_from' => 'nullable|date|required_if:action_type,suspension',
            'suspension_to'   => 'nullable|date|after_or_equal:suspension_from',
            'award_name'      => 'nullable|string|max:100|required_if:action_type,positive_award',
        ]);
        $record = StudentDisciplinary::create(array_merge(
            $request->only(['incident_date', 'incident_type', 'description', 'action_taken', 'action_type', 'suspension_from', 'suspension_to', 'award_name', 'parent_notified']),
            ['student_id' => $id, 'reported_by' => Auth::id()]
        ));

        // Email parent notification if action_type is not a positive award
        if ($request->action_type !== 'positive_award') {
            $student = Student::with(['user', 'parent'])->find($id);
            $email   = $student?->user?->email ?? $student?->parent?->email ?? null;
            if ($email) {
                $tpl = NotificationTemplate::forEvent('discipline_action', 'email');
                $school = \App\Models\SchoolSetting::first();
                $subject = $tpl?->subject ?: 'Disciplinary Action Notice – ' . ($school?->school_name ?? config('app.name'));
                $vars = [
                    'parent_name'   => $student->parent?->name ?? 'Parent/Guardian',
                    'student_name'  => $student->full_name,
                    'action_type'   => ucfirst(str_replace('_', ' ', $request->action_type ?? 'Action')),
                    'date'          => \Carbon\Carbon::parse($request->incident_date)->format('d M Y'),
                    'school_name'   => $school?->school_name ?? config('app.name'),
                ];
                $body = $tpl ? $tpl->getRenderedBody($vars)
                    : "Dear {$vars['parent_name']},\n\nA disciplinary action has been recorded for {$vars['student_name']}.\n\nAction: {$vars['action_type']}\nDate: {$vars['date']}\n\nPlease contact the school for further details.\n\n{$vars['school_name']}";
                try {
                    Mail::to($email)->send(new FeeReminderMail($subject, $body));
                } catch (\Exception $e) {
                    // fail silently
                }
            }
        }

        return back()->with('success', 'Record saved.');
    }

    public function concessions(int $id)
    {
        $student     = Student::findOrFail($id);
        $schemes     = ScholarshipScheme::where('is_active', true)->get();
        $concessions = StudentConcession::where('student_id', $id)->with('scheme')->latest()->get();
        return view('students.concessions', compact('student', 'schemes', 'concessions'));
    }

    public function grantConcession(Request $request, int $id)
    {
        $request->validate(['concession_type' => 'required|string', 'value_type' => 'required|in:percentage,amount', 'value' => 'required|numeric|min:0']);
        StudentConcession::create(array_merge($request->only(['scholarship_scheme_id', 'concession_type', 'value_type', 'value', 'valid_from', 'valid_to', 'remarks']), ['student_id' => $id, 'granted_by' => Auth::id(), 'status' => 'active']));
        return back()->with('success', 'Concession granted.');
    }

    public function revokeConcession(int $cId)
    {
        StudentConcession::findOrFail($cId)->update(['status' => 'revoked']);
        return back()->with('success', 'Concession revoked.');
    }

    public function promotions(Request $request)
    {
        $classes       = Classes::active()->get();
        $currentYear   = AcademicYear::current();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $promotions    = StudentPromotion::with(['student', 'fromClass', 'toClass', 'fromAcademicYear', 'toAcademicYear'])->latest()->paginate(25);
        $preselectIds  = $request->preselect_ids
            ? array_filter(array_map('intval', explode(',', $request->preselect_ids)))
            : [];
        $fromClassStudents = collect();
        if ($request->class_id) {
            $fromClassStudents = StudentEnrollment::with('student')
                ->where('class_id', $request->class_id)
                ->where('status', 'active')
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->get();
        }
        return view('students.promotions', compact('classes', 'currentYear', 'academicYears', 'promotions', 'preselectIds', 'fromClassStudents'));
    }

    public function processPromotion(Request $request)
    {
        $request->validate(['from_class_id' => 'required|exists:classes,id', 'to_class_id' => 'required|exists:classes,id', 'student_ids' => 'required|array']);
        $currentYear = AcademicYear::current();
        DB::transaction(function () use ($request, $currentYear) {
            foreach ($request->student_ids as $studentId) {
                $enrollment = StudentEnrollment::where('student_id', $studentId)->where('status', 'active')->first();
                if (!$enrollment) continue;
                StudentPromotion::create(['student_id' => $studentId, 'from_class_id' => $request->from_class_id, 'to_class_id' => $request->to_class_id, 'from_academic_year_id' => $currentYear?->id, 'promoted_by' => Auth::id(), 'promoted_on' => today()]);
                $enrollment->update(['class_id' => $request->to_class_id]);
            }
        });
        return back()->with('success', count($request->student_ids) . ' students promoted.');
    }

    public function rolloverReport(Request $request)
    {
        $years = \App\Models\AcademicYear::orderByDesc('start_date')->get();
        $currentYear = AcademicYear::current();
        $selectedYearId = $request->academic_year_id ?? $currentYear?->id;

        $report = collect();
        if ($selectedYearId) {
            $promotions = StudentPromotion::with(['student', 'fromClass', 'toClass'])
                ->where('from_academic_year_id', $selectedYearId)
                ->get();

            $grouped = $promotions->groupBy('from_class_id');
            foreach (Classes::active()->get() as $class) {
                $classPromotions = $grouped->get($class->id, collect());
                $totalEnrolled = StudentEnrollment::where('class_id', $class->id)
                    ->where('academic_year_id', $selectedYearId)->count();
                $promoted  = $classPromotions->where('promotion_type', '!=', 'detained')->count();
                $detained  = $classPromotions->where('promotion_type', 'detained')->count();
                $notRolled = $totalEnrolled - $classPromotions->count();
                $rate      = $totalEnrolled > 0 ? round(($promoted / $totalEnrolled) * 100, 1) : 0;

                if ($totalEnrolled > 0) {
                    $report->push([
                        'class'         => $class,
                        'enrolled'      => $totalEnrolled,
                        'promoted'      => $promoted,
                        'detained'      => $detained,
                        'not_processed' => $notRolled,
                        'promotion_rate'=> $rate,
                        'complete'      => $notRolled === 0,
                    ]);
                }
            }
        }

        $selectedYear = $years->firstWhere('id', $selectedYearId);
        return view('students.rollover-report', compact('years', 'selectedYear', 'report'));
    }

    public function getWingMetadata($classModel = null, $classNameStr = null): array
    {
        $name = strtolower(trim($classModel?->name ?? $classNameStr ?? ''));
        $numeric = (int)($classModel?->numeric_value ?? 0);

        // 1. Kindergarten Wing (KG / Nursery / Playgroup / LKG / UKG / Pre-KG)
        if (preg_match('/(kg|nursery|play|pre|lkg|ukg|kindergarten)/i', $name) || ($numeric === 0 && preg_match('/(kg|nurs|play|pre)/i', $name))) {
            return [
                'key'             => 'kg',
                'name'            => 'Kindergarten Wing',
                'short_name'      => 'Kindergarten (Pre-KG – UKG)',
                'tag'             => 'KG WING',
                'badge_color'     => 'bg-amber-100 text-amber-900 border-amber-300 font-bold',
                'primary_color'   => '#d97706',
                'header_gradient' => 'linear-gradient(135deg, #b45309 0%, #f59e0b 100%)',
                'card_border'     => '#f59e0b',
                'accent'          => '#d97706',
            ];
        }

        // Determine numeric level
        $num = 0;
        if ($numeric >= 1 && $numeric <= 12) {
            $num = $numeric;
        } else {
            if (preg_match('/\b(1[0-2]|[1-9])\b/i', $name, $matches)) {
                $num = (int)$matches[1];
            }
        }

        // 2. Primary Wing (Class 1st – 5th)
        if (($num >= 1 && $num <= 5) || preg_match('/(primary|1st|2nd|3rd|4th|5th|class 1\b|class 2\b|class 3\b|class 4\b|class 5\b|std 1\b|std 2\b|std 3\b|std 4\b|std 5\b)/i', $name)) {
            return [
                'key'             => 'primary',
                'name'            => 'Primary Wing',
                'short_name'      => 'Primary (Class 1st – 5th)',
                'tag'             => 'PRIMARY WING',
                'badge_color'     => 'bg-emerald-100 text-emerald-900 border-emerald-300 font-bold',
                'primary_color'   => '#059669',
                'header_gradient' => 'linear-gradient(135deg, #065f46 0%, #10b981 100%)',
                'card_border'     => '#10b981',
                'accent'          => '#059669',
            ];
        }

        // 3. Middle Wing (Class 6th – 8th)
        if (($num >= 6 && $num <= 8) || preg_match('/(middle|6th|7th|8th|class 6\b|class 7\b|class 8\b|std 6\b|std 7\b|std 8\b)/i', $name)) {
            return [
                'key'             => 'middle',
                'name'            => 'Middle Wing',
                'short_name'      => 'Middle (Class 6th – 8th)',
                'tag'             => 'MIDDLE WING',
                'badge_color'     => 'bg-cyan-100 text-cyan-900 border-cyan-300 font-bold',
                'primary_color'   => '#0284c7',
                'header_gradient' => 'linear-gradient(135deg, #0369a1 0%, #0ea5e9 100%)',
                'card_border'     => '#0ea5e9',
                'accent'          => '#0284c7',
            ];
        }

        // 4. Higher Secondary Wing (Class 9th – 10th)
        if (($num >= 9 && $num <= 10) || preg_match('/(high|secondary|9th|10th|class 9\b|class 10\b|std 9\b|std 10\b)/i', $name)) {
            return [
                'key'             => 'higher_secondary',
                'name'            => 'Higher Secondary Wing',
                'short_name'      => 'Higher Secondary (Class 9th – 10th)',
                'tag'             => 'HIGHER SEC WING',
                'badge_color'     => 'bg-indigo-100 text-indigo-900 border-indigo-300 font-bold',
                'primary_color'   => '#4338ca',
                'header_gradient' => 'linear-gradient(135deg, #312e81 0%, #6366f1 100%)',
                'card_border'     => '#6366f1',
                'accent'          => '#4338ca',
            ];
        }

        // 5. Senior Secondary Wing (Class 11th – 12th)
        return [
            'key'             => 'senior_secondary',
            'name'            => 'Senior Secondary Wing',
            'short_name'      => 'Senior Secondary (Class 11th – 12th)',
            'tag'             => 'SENIOR SEC WING',
            'badge_color'     => 'bg-purple-100 text-purple-900 border-purple-300 font-bold',
            'primary_color'   => '#7c3aed',
            'header_gradient' => 'linear-gradient(135deg, #581c87 0%, #8b5cf6 100%)',
            'card_border'     => '#8b5cf6',
            'accent'          => '#7c3aed',
        ];
    }

    public function idCards(Request $request)
    {
        $classes  = Classes::active()->get();
        $currentYear = AcademicYear::current();
        $wingFilter = $request->get('wing', 'all');
        $classFilter = $request->get('class_id');
        $sectionFilter = $request->get('section_id');

        // Attach wing meta to all classes
        $classes->each(function($cls) {
            $cls->wing_meta = $this->getWingMetadata($cls);
        });

        $sections = collect();
        if ($classFilter) {
            $sections = Section::where('class_id', $classFilter)->get();
        }

        $q = StudentEnrollment::with(['student', 'class', 'section'])
            ->where('status', 'active')
            ->whereHas('student', fn($sq) => $sq->where('status', 'active'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id));

        if ($classFilter) {
            $q->where('class_id', $classFilter);
        }
        if ($sectionFilter) {
            $q->where('section_id', $sectionFilter);
        }

        $enrollments = $q->get();

        // Map wing metadata to each enrollment
        $enrollments->each(function($e) {
            $e->wing_meta = $this->getWingMetadata($e->class);
        });

        // Filter by wing if specified
        $filteredEnrollments = $enrollments;
        if ($wingFilter && $wingFilter !== 'all') {
            $filteredEnrollments = $enrollments->filter(fn($e) => $e->wing_meta['key'] === $wingFilter)->values();
        }

        $school = \App\Models\SchoolSetting::first();

        // Wing counts
        $wingCounts = [
            'all'              => $enrollments->count(),
            'kg'               => $enrollments->filter(fn($e) => $e->wing_meta['key'] === 'kg')->count(),
            'primary'          => $enrollments->filter(fn($e) => $e->wing_meta['key'] === 'primary')->count(),
            'middle'           => $enrollments->filter(fn($e) => $e->wing_meta['key'] === 'middle')->count(),
            'higher_secondary' => $enrollments->filter(fn($e) => $e->wing_meta['key'] === 'higher_secondary')->count(),
            'senior_secondary' => $enrollments->filter(fn($e) => $e->wing_meta['key'] === 'senior_secondary')->count(),
        ];

        return view('students.id-cards', compact(
            'classes', 'sections', 'filteredEnrollments', 'enrollments',
            'currentYear', 'school', 'wingFilter', 'classFilter', 'sectionFilter', 'wingCounts'
        ));
    }

    public function singleIdCard(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);
        $student = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($id);
        
        // Strict locking: ID card can only be generated after Principal approval and Admin final confirmation
        if ($student->status !== 'active') {
            return redirect()->route('students.show', $student->id)
                ->with('error', 'ID Card generation is locked. Student admission must be fully approved by the Principal and confirmed by Admin before an official ID card can be generated.');
        }

        $enrollment = $student->currentEnrollment;
        $classModel = $enrollment?->class;
        $wingMeta = $this->getWingMetadata($classModel);
        $school = \App\Models\SchoolSetting::first();
        $currentYear = AcademicYear::current();

        // Base64 QR code
        $qrData = implode(' | ', array_filter([
            'ID: ' . $student->admission_number,
            'Name: ' . $student->full_name,
            'Class: ' . ($classModel?->name ?? '') . ' ' . ($enrollment?->section?->name ?? ''),
            'Blood: ' . ($student->blood_group ?? ''),
        ]));

        try {
            $qrCode = base64_encode(QrCode::format('png')->size(90)->generate($qrData));
        } catch (\Exception $e) {
            $qrCode = null;
        }

        return view('students.single-id-card', compact('student', 'enrollment', 'classModel', 'wingMeta', 'school', 'currentYear', 'qrCode'));
    }

    public function visitorCard(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);
        $student = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($id);
        $school = \App\Models\SchoolSetting::first();
        $currentYear = AcademicYear::current();

        $verifyUrl = route('public.visitor-card.verify', ['token' => $student->parent_visitor_pass_token]);
        $qrCodeSvg = '';
        try {
            $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(160)->margin(1)->generate($verifyUrl);
        } catch (\Throwable $e) {
            $qrCodeSvg = '';
        }

        return view('students.visitor-card', compact('student', 'school', 'currentYear', 'verifyUrl', 'qrCodeSvg'));
    }

    public function downloadIdCards(Request $request)
    {
        $currentYear = AcademicYear::current();
        $wingFilter  = $request->get('wing', 'all');
        $q = StudentEnrollment::with(['student', 'class', 'section'])
            ->where('status', 'active')
            ->whereHas('student', fn($sq) => $sq->where('status', 'active'));
        if ($request->class_id) $q->where('class_id', $request->class_id);
        if ($request->section_id) $q->where('section_id', $request->section_id);
        if ($currentYear) $q->where('academic_year_id', $currentYear->id);

        $enrollments = $q->get();
        $enrollments->each(function($e) {
            $e->wing_meta = $this->getWingMetadata($e->class);
        });

        if ($wingFilter && $wingFilter !== 'all') {
            $enrollments = $enrollments->filter(fn($e) => $e->wing_meta['key'] === $wingFilter)->values();
        }

        $students = $enrollments->map(fn($e) => $e->student)->filter();
        $school   = \App\Models\SchoolSetting::first();

        // Pre-generate QR codes for each student
        $qrCodes = [];
        foreach ($students as $s) {
            try {
                $qrData = implode(' | ', array_filter([
                    'ID: ' . $s->admission_number,
                    'Name: ' . $s->full_name,
                    $s->student_id ?? '',
                ]));
                $qrCodes[$s->id] = base64_encode(QrCode::format('png')->size(80)->generate($qrData));
            } catch (\Exception $e) {
                $qrCodes[$s->id] = null;
            }
        }

        // Pass template settings to PDF
        $template = $school;
        $pdf = Pdf::loadView('pdf.student-id-card', compact('school', 'students', 'enrollments', 'currentYear', 'qrCodes', 'template', 'wingFilter'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('student-id-cards-' . ($wingFilter ?: 'all') . '.pdf');
    }

    public function idCardTemplate()
    {
        $school = \App\Models\SchoolSetting::first();
        return view('students.id-card-template', compact('school'));
    }

    public function saveIdCardTemplate(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'id_card_header_color' => 'nullable|string|max:7',
            'id_card_text_color'   => 'nullable|string|max:7',
            'id_card_bg_color'     => 'nullable|string|max:7',
            'id_card_header_text'  => 'nullable|string|max:100',
            'id_card_footer_text'  => 'nullable|string|max:200',
        ]);

        $school = \App\Models\SchoolSetting::first();
        $data = [
            'id_card_header_color'    => $request->id_card_header_color ?? '#1e3a5f',
            'id_card_text_color'      => $request->id_card_text_color ?? '#1e3a5f',
            'id_card_bg_color'        => $request->id_card_bg_color ?? '#ffffff',
            'id_card_header_text'     => $request->id_card_header_text,
            'id_card_footer_text'     => $request->id_card_footer_text,
            'id_card_show_blood_group'=> $request->boolean('id_card_show_blood_group'),
            'id_card_show_dob'        => $request->boolean('id_card_show_dob'),
            'id_card_show_qr'         => $request->boolean('id_card_show_qr'),
            'id_card_show_mobile'     => $request->boolean('id_card_show_mobile'),
            'id_card_show_address'    => $request->boolean('id_card_show_address'),
            'id_card_show_photo'      => $request->boolean('id_card_show_photo', true),
        ];

        if ($school) {
            $school->update($data);
        } else {
            \App\Models\SchoolSetting::create(array_merge(['school_name' => 'DASA EduERP'], $data));
        }

        return back()->with('success', 'ID card template saved.');
    }

    public function shortageLetter(int $id)
    {
        $student    = Student::with('currentEnrollment.class')->findOrFail($id);
        $enrollment = $student->currentEnrollment;
        $currentYear = AcademicYear::current();
        $totalDays  = \App\Models\AttendanceRecord::where('class_id', $enrollment?->class_id)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->select('date')->distinct()->count();
        $present = \App\Models\AttendanceRecord::where('student_id', $id)->where('status', 'present')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))->count();
        $stats  = ['total_days' => $totalDays, 'present' => $present, 'absent' => $totalDays - $present, 'percentage' => $totalDays > 0 ? round($present / $totalDays * 100, 1) : 0];
        $school = (object)['name' => config('school.name', 'School Name'), 'address' => config('school.address', ''), 'logo' => null];
        $pdf = Pdf::loadView('pdf.shortage-letter', compact('school', 'student', 'enrollment', 'stats'));
        return $pdf->download('shortage-letter-' . $student->admission_number . '.pdf');
    }

    public function searchJson(Request $request)
    {
        $q       = $request->get('q', '');
        $exclude = $request->get('exclude');
        $students = Student::where('status', 'active')
            ->where(fn($query) => $query
                ->where('first_name', 'like', "%$q%")
                ->orWhere('last_name', 'like', "%$q%")
                ->orWhere('admission_no', 'like', "%$q%")
            )
            ->when($exclude, fn($query) => $query->where('id', '!=', $exclude))
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'admission_no'])
            ->map(fn($s) => [
                'id'               => $s->id,
                'full_name'        => $s->first_name . ' ' . $s->last_name,
                'admission_number' => $s->admission_number,
            ]);
        return response()->json($students);
    }

    public function siblings(int $id)
    {
        $student  = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($id);
        $siblings = Student::with(['currentEnrollment.class'])
            ->where('sibling_group_id', $student->sibling_group_id)
            ->where('id', '!=', $student->id)
            ->get();
        return view('students.siblings', compact('student', 'siblings'));
    }

    public function linkSibling(Request $request, int $id)
    {
        $student = Student::findOrFail($id);
        $target  = Student::findOrFail($request->validate(['sibling_id' => 'required|exists:students,id'])['sibling_id']);

        if ($target->id === $student->id) {
            return back()->with('error', 'Cannot link a student to themselves.');
        }

        // Determine or create a sibling group ID
        $groupId = $student->sibling_group_id ?? $target->sibling_group_id ?? Student::max('sibling_group_id') + 1;

        // Update all affected students to same group
        $idsToGroup = [$student->id, $target->id];
        if ($student->sibling_group_id) {
            Student::where('sibling_group_id', $student->sibling_group_id)->pluck('id')->each(fn($i) => $idsToGroup[] = $i);
        }
        if ($target->sibling_group_id) {
            Student::where('sibling_group_id', $target->sibling_group_id)->pluck('id')->each(fn($i) => $idsToGroup[] = $i);
        }
        $idsToGroup = array_unique($idsToGroup);
        Student::whereIn('id', $idsToGroup)->update(['sibling_group_id' => $groupId]);

        // Auto-apply sibling concession scheme (if any active sibling-type scheme exists)
        $this->applySiblingConcessions($idsToGroup);

        return back()->with('success', "{$target->full_name} linked as sibling.");
    }

    public function unlinkSibling(Request $request, int $id, int $siblingId)
    {
        Student::findOrFail($siblingId)->update(['sibling_group_id' => null]);
        return back()->with('success', 'Sibling unlinked.');
    }

    private function applySiblingConcessions(array $studentIds): void
    {
        $scheme = ScholarshipScheme::where('is_active', true)
            ->where(fn($q) => $q->where('type', 'sibling')->orWhere('criteria_type', 'sibling'))
            ->first();
        if (!$scheme) return;

        $year = AcademicYear::current();
        foreach ($studentIds as $sid) {
            $exists = StudentConcession::where('student_id', $sid)->where('scholarship_id', $scheme->id)->exists();
            if (!$exists) {
                StudentConcession::create([
                    'student_id'         => $sid,
                    'scholarship_id'     => $scheme->id,
                    'academic_year_id'   => $year?->id,
                    'concession_type'    => 'scholarship',
                    'value_type'         => $scheme->type === 'percentage' ? 'percentage' : 'flat',
                    'value'              => $scheme->value,
                    'applicable_fee_heads' => $scheme->applicable_fee_heads,
                    'valid_from'         => $year?->start_date ?? now(),
                    'valid_to'           => $year?->end_date ?? now()->addYear(),
                    'granted_by'         => auth()->id(),
                    'remarks'            => 'Auto-applied: sibling concession',
                ]);
            }
        }
    }

    private function applyStaffWardConcession(int $studentId): void
    {
        $scheme = ScholarshipScheme::where('is_active', true)
            ->where(fn($q) => $q->where('type', 'staff_ward')->orWhere('criteria_type', 'staff_ward'))
            ->first();
        if (!$scheme) return;

        $year = AcademicYear::current();
        $exists = StudentConcession::where('student_id', $studentId)
            ->where('scholarship_id', $scheme->id)
            ->where('academic_year_id', $year?->id)->exists();
        if (!$exists) {
            StudentConcession::create([
                'student_id'           => $studentId,
                'scholarship_id'       => $scheme->id,
                'academic_year_id'     => $year?->id,
                'concession_type'      => 'staff_ward',
                'value_type'           => 'percentage',
                'value'                => $scheme->value,
                'applicable_fee_heads' => $scheme->applicable_fee_heads,
                'valid_from'           => $year?->start_date ?? now(),
                'valid_to'             => $year?->end_date ?? now()->addYear(),
                'granted_by'           => auth()->id(),
                'remarks'              => 'Auto-applied: staff ward concession',
                'status'               => 'active',
            ]);
        }
    }

    public function warningLetter(int $id, int $incidentId)
    {
        $student  = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($id);
        $incident = StudentDisciplinary::with('reportedBy')->where('student_id', $id)->findOrFail($incidentId);
        $school   = \App\Models\SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.warning-letter', compact('student', 'incident', 'school'));
        return $pdf->stream('warning-letter-' . $student->admission_number . '.pdf');
    }

    public function tcRegister(Request $request)
    {
        $query = Student::whereNotNull('tc_number')
            ->with(['currentEnrollment.class'])
            ->when($request->search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%$v%")
                  ->orWhere('last_name', 'like', "%$v%")
                  ->orWhere('tc_number', 'like', "%$v%")
                  ->orWhere('admission_no', 'like', "%$v%");
            }))
            ->orderByDesc('tc_date');

        $records = $query->paginate(25)->withQueryString();
        return view('students.tc-register', compact('records'));
    }

    public function directory(Request $request)
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::active()->get();
        $query = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])
            ->when($request->class_id, fn($q, $v) => $q->whereHas('currentEnrollment', fn($q) => $q->where('class_id', $v)))
            ->where('status', 'active')
            ->orderBy('first_name');

        $students = $query->paginate(40)->withQueryString();
        $view = $request->get('view', 'grid');
        return view('students.directory', compact('students', 'classes', 'currentYear', 'view'));
    }

    // ── TC Request Workflow ───────────────────────────────────

    public function tcRequests(Request $request)
    {
        $requests = \App\Models\TcRequest::with(['student', 'requestedByUser'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()->paginate(25)->withQueryString();
        return view('students.tc-requests', compact('requests'));
    }

    public function storeTcRequest(Request $request)
    {
        $request->validate(['student_id' => 'required|exists:students,id', 'reason' => 'nullable|string|max:500']);
        \App\Models\TcRequest::create([
            'student_id'          => $request->student_id,
            'requested_by_type'   => 'admin',
            'requested_by_user'   => Auth::id(),
            'reason'              => $request->reason,
            'status'              => 'pending',
        ]);
        return back()->with('success', 'TC request submitted.');
    }

    public function approveTcRequest(Request $request, int $id)
    {
        $tcr   = \App\Models\TcRequest::findOrFail($id);
        $stage = $request->stage; // 'hod' or 'principal'

        if ($stage === 'hod') {
            $tcr->update(['status' => 'hod_approved', 'hod_approved_by' => Auth::id(), 'hod_approved_at' => now()]);
        } elseif ($stage === 'principal') {
            $tcr->update(['status' => 'principal_approved', 'principal_approved_by' => Auth::id(), 'principal_approved_at' => now()]);
        } elseif ($stage === 'issue') {
            // Prevent duplicate TC: check if another issued TC exists for this student
            $alreadyIssued = \App\Models\TcRequest::where('student_id', $tcr->student_id)
                ->where('status', 'issued')
                ->where('id', '!=', $id)
                ->exists();
            if ($alreadyIssued && !$request->boolean('force_issue')) {
                return back()->with('error', 'A TC has already been issued for this student. To issue a duplicate, tick the override checkbox.');
            }
            $tcr->update(['status' => 'issued', 'issued_by' => Auth::id(), 'issued_at' => now()]);
            // Mark student as Left/TC-issued
            if ($tcr->student_id) {
                Student::where('id', $tcr->student_id)->update(['status' => 'left', 'leaving_date' => now()->toDateString()]);
            }
        }
        return back()->with('success', 'TC request updated.');
    }

    public function rejectTcRequest(Request $request, int $id)
    {
        $request->validate(['reason' => 'required|string|min:5']);
        \App\Models\TcRequest::findOrFail($id)->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
            'rejected_by'      => Auth::id(),
        ]);
        return back()->with('success', 'TC request rejected.');
    }

    // ── Mark Student as Left / Transferred ───────────────────

    public function markAsLeft(Request $request, int $id)
    {
        $request->validate([
            'leaving_date'   => 'required|date',
            'leaving_reason' => 'nullable|string|max:255',
        ]);
        $student = Student::findOrFail($id);
        $student->update([
            'status'         => 'left',
            'leaving_date'   => $request->leaving_date,
            'leaving_reason' => $request->leaving_reason,
        ]);
        // Deactivate current enrollment
        StudentEnrollment::where('student_id', $id)->where('status', 'active')
            ->update(['status' => 'transferred', 'updated_at' => now()]);
        return back()->with('success', 'Student marked as left/transferred.');
    }

    // ── Document Expiry Tracking ──────────────────────────────

    public function documentExpiryReport(Request $request)
    {
        $daysAhead = max(1, (int)($request->days ?? 30));
        $cutoff    = now()->addDays($daysAhead)->toDateString();

        $expiring = StudentDocument::with('student')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $cutoff)
            ->whereDate('expiry_date', '>=', today())
            ->orderBy('expiry_date')
            ->get();

        $expired = StudentDocument::with('student')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->orderBy('expiry_date', 'desc')
            ->take(50)->get();

        return view('students.document-expiry', compact('expiring', 'expired', 'daysAhead'));
    }

    public function uploadPhoto(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);
        $student = Student::findOrFail($id);

        if ($request->filled('cropped_image')) {
            // Base64 data URI from Cropper.js
            $data = $request->input('cropped_image');
            if (preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $data, $type)) {
                $imageData = base64_decode(substr($data, strpos($data, ',') + 1));
                if ($imageData !== false && strlen($imageData) <= 2097152) { // 2MB max
                    $ext  = in_array(strtolower($type[1]), ['png', 'webp']) ? strtolower($type[1]) : 'jpg';
                    $path = "students/{$id}/photo.{$ext}";
                    Storage::disk('public')->put($path, $imageData);
                    $student->update(['photo' => $path]);
                }
            }
        } elseif ($request->hasFile('photo_file')) {
            $request->validate([
                'photo_file' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);
            $file = $request->file('photo_file');
            $ext  = in_array(strtolower($file->extension()), ['png', 'webp']) ? strtolower($file->extension()) : 'jpg';
            $path = "students/{$id}/photo.{$ext}";
            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
            $student->update(['photo' => $path]);
        }

        return redirect()->route('students.edit', $id)->with('success', 'Photo updated successfully.');
    }

    // ── Bulk Student Import ──────────────────────────────────

    public function importForm(Request $request)
    {
        $classes  = Classes::active()->get();
        $sections = Section::all();
        return view('students.import', compact('classes', 'sections'));
    }

    public function importTemplate()
    {
        $headers = [
            'First Name',
            'Middle Name',
            'Last Name',
            'Date of Birth',
            'Gender',
            'Student Type',
            'Blood Group',
            'Category',
            'Religion',
            'Mother Tongue',
            'Aadhaar Number',
            'Mobile',
            'Email',
            'Residential Address',
            'Permanent Address',
            'Pincode',
            'Person with Disability (PwD)',
            "Father's Name",
            "Father's Mobile",
            "Father's Occupation",
            "Father's Email",
            "Mother's Name",
            "Mother's Mobile",
            "Mother's Occupation",
            'Annual Family Income (₹)',
            'Guardian Name',
            'Guardian Mobile',
            'Class',
            'Section',
            'Roll Number',
            'House',
        ];

        $sampleRows = [
            [
                'John', 'A.', 'Doe', '2010-05-15', 'male',
                'day_scholar', 'O+', 'general', 'Hindu', 'English',
                '123456789012', '9876543210', 'john@example.com',
                '123 Main St, City', '123 Main St, City', '500001', 'No',
                'Robert Doe', '9876543210', 'Engineer', 'robert@example.com',
                'Mary Doe', '9876543211', 'Teacher', '500000',
                'James Doe', '9876543212',
                '10', 'A', '101', 'Red'
            ],
            [
                'Jane', '', 'Smith', '2010-08-22', 'female',
                'hosteller', 'A+', 'obc', 'Christian', 'Hindi',
                '987654321098', '9876543213', 'jane@example.com',
                '456 Oak Ave, City', '456 Oak Ave, City', '500002', 'No',
                'James Smith', '9876543213', 'Doctor', 'james@example.com',
                'Sarah Smith', '9876543214', 'Lawyer', '750000',
                '', '',
                '10', 'B', '102', 'Blue'
            ]
        ];

        return Excel::download(new \App\Exports\ArrayExport($sampleRows, $headers), 'students_import_template.xlsx');
    }

    public function processImport(Request $request)
    {
        set_time_limit(0); // large imports — remove PHP timeout

        $request->validate([
            'file'       => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'class_id'   => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        if ($ext === 'csv') {
            $rowsRaw = array_map('str_getcsv', file($file->getRealPath()));
            if (count($rowsRaw) < 2) {
                return back()->with('error', 'CSV file is empty or invalid.');
            }
            $header = array_map('trim', array_shift($rowsRaw));
            $rows   = $rowsRaw;
        } else {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheetData   = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            if (count($sheetData) < 2) {
                return back()->with('error', 'Spreadsheet is empty or invalid.');
            }
            $header = array_map('trim', array_shift($sheetData));
            $rows   = $sheetData;
        }

        // Clean header strings
        $headerNormalized = array_map(function($h) {
            $h = strtolower(trim((string)$h));
            $h = preg_replace('/[^a-z0-9]+/', '_', $h);
            return trim($h, '_');
        }, $header);

        $headerCount    = count($headerNormalized);
        $currentYear    = AcademicYear::current();
        $imported       = 0;
        $errors         = [];

        $allClasses     = Classes::all()->keyBy(fn($c) => strtolower(trim($c->name)));
        $allSections    = Section::all()->groupBy('class_id');
        $firstActiveCls = Classes::active()->first() ?? Classes::first();

        // Cache for fuzzy class-name lookups (hit DB at most once per unique name)
        $classNameCache = [];

        // Pre-fetch admission-number counters for boys (EPSB) and girls (EPSG) ONCE — avoids per-row DB query and duplicate numbers
        $nextSeqBoys  = Student::getNextSequenceNumber('EPSB');
        $nextSeqGirls = Student::getNextSequenceNumber('EPSG');

        foreach ($rows as $i => $row) {
            if (!is_array($row) || empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
                continue;
            }

            // Ensure row length matches header length exactly
            $paddedRow = array_pad($row, $headerCount, null);
            $rowValues = array_slice($paddedRow, 0, $headerCount);

            $data = [];
            foreach ($headerNormalized as $colIdx => $key) {
                $val = $rowValues[$colIdx];
                $cleanVal = is_string($val) ? trim($val) : $val;
                if ($key !== '') {
                    $data[$key] = $cleanVal;
                }
                $data['__col_' . $colIdx] = $cleanVal;
            }

            // Flexible field lookup function
            $getField = function(array $candidateKeys) use ($data) {
                foreach ($candidateKeys as $k) {
                    if (isset($data[$k]) && $data[$k] !== '' && $data[$k] !== null) {
                        return (string)$data[$k];
                    }
                }
                foreach ($data as $dKey => $dVal) {
                    if (str_starts_with($dKey, '__col_')) continue;
                    if ($dVal !== '' && $dVal !== null) {
                        foreach ($candidateKeys as $k) {
                            if (str_contains($dKey, $k) || str_contains($k, $dKey)) {
                                return (string)$dVal;
                            }
                        }
                    }
                }
                return '';
            };

            // Extract Name
            $rawName = $getField(['first_name', 'student_name', 'name', 'full_name', 'student', 'candidate_name', 'firstname']);
            
            // Positional fallback if headers didn't match
            if (!$rawName) {
                foreach ($rowValues as $cell) {
                    $cellStr = trim((string)$cell);
                    if ($cellStr !== '' && !is_numeric($cellStr) && strlen($cellStr) > 1 && !preg_match('/^(male|female|other|active|inactive|general|obc|sc|st|day_scholar|hosteller|day_boarder)$/i', $cellStr)) {
                        $rawName = $cellStr;
                        break;
                    }
                }
            }

            if (!$rawName) {
                $errors[] = "Row " . ($i + 2) . ": Student name is required.";
                continue;
            }

            $nameParts  = preg_split('/\s+/', trim($rawName));
            $firstName  = $getField(['first_name', 'firstname']) ?: ($nameParts[0] ?? $rawName);
            $middleName = $getField(['middle_name', 'middlename']) ?: (count($nameParts) > 2 ? $nameParts[1] : null);
            $lastName   = $getField(['last_name', 'lastname', 'surname']) ?: (count($nameParts) > 1 ? implode(' ', array_slice($nameParts, count($nameParts) > 2 ? 2 : 1)) : 'Student');

            // Roll number & House extracted early for smart Class/Section inference
            $rollNo = $getField(['roll_number', 'roll_no', 'roll', 'roll_num', 'rollno', 'sr_no']) ?: null;
            $house  = $getField(['house', 'house_name', 'student_house', 'house_color']) ?: null;

            // Determine Class
            $classId  = $request->class_id;
            $classVal = strtolower(trim($getField(['class', 'class_name', 'classname', 'standard', 'grade', 'std'])));

            // Infer class from roll number if classVal is empty (e.g. PKGA003 -> Pre-KG)
            if (!$classVal && $rollNo) {
                $upperRoll = strtoupper((string)$rollNo);
                if (str_starts_with($upperRoll, 'PKG')) {
                    $classVal = 'pre-kg';
                } elseif (str_starts_with($upperRoll, 'LKG')) {
                    $classVal = 'lkg';
                } elseif (str_starts_with($upperRoll, 'UKG')) {
                    $classVal = 'ukg';
                }
            }

            if (!$classId && $classVal) {
                // Alias normalization for Pre-KG & preschool classes
                if (in_array($classVal, ['prekg', 'pre-kg', 'pre kg', 'pkg', 'pre_kg', 'nursery', 'preprimary', 'pre_primary'])) {
                    $classVal = 'pre-kg';
                }

                if (isset($allClasses[$classVal])) {
                    $classId = $allClasses[$classVal]->id;
                } else {
                    $cls = Classes::whereRaw('LOWER(name) = ?', [$classVal])
                        ->orWhereRaw('LOWER(name) = ?', [str_replace('-', '', $classVal)])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$classVal}%"])
                        ->first();

                    if ($cls) {
                        $classId = $cls->id;
                    } else {
                        // Create missing class dynamically
                        $newCls = Classes::create([
                            'name'          => strtoupper($classVal),
                            'numeric_value' => 0,
                            'sort_order'    => 0,
                            'is_active'     => true,
                        ]);
                        $classId = $newCls->id;
                        $allClasses[$classVal] = $newCls;
                    }
                }
            }

            if (!$classId) {
                $classId = $firstActiveCls?->id;
            }

            // Determine Section
            $sectionId = $request->section_id;
            $secVal    = strtolower(trim($getField(['section', 'section_name', 'sec', 'section_id'])));

            // If section header has prefixes like PKGA, PKGB -> extract "a", "b"
            if ($secVal && preg_match('/^[a-z]+([a-z])$/i', $secVal, $sm) && strlen($secVal) > 2) {
                $secVal = strtolower($sm[1]);
            }

            // Infer section from Roll Number if secVal is empty (e.g. PKGA003 -> Section A, PKGD009 -> Section D)
            if (!$secVal && $rollNo) {
                $upperRoll = strtoupper((string)$rollNo);
                if (preg_match('/^(?:PKG|LKG|UKG)([A-Z])\d+/i', $upperRoll, $rm)) {
                    $secVal = strtolower($rm[1]);
                }
            }

            if (!$sectionId && $secVal && $classId) {
                $classSections = $allSections[$classId] ?? Section::where('class_id', $classId)->get();
                $sec = $classSections->first(fn($s) =>
                    strtolower(trim($s->name)) === $secVal
                    || strtolower(trim($s->name)) === "section {$secVal}"
                    || (string)$s->id === $secVal
                );

                if ($sec) {
                    $sectionId = $sec->id;
                } else {
                    // Create section for this class if missing
                    $newSec = Section::create([
                        'class_id'         => $classId,
                        'academic_year_id' => $currentYear?->id ?? AcademicYear::first()?->id,
                        'name'             => strtoupper($secVal),
                        'capacity'         => 40,
                    ]);
                    $sectionId = $newSec->id;
                    if (isset($allSections[$classId])) {
                        $allSections[$classId]->push($newSec);
                    } else {
                        $allSections[$classId] = collect([$newSec]);
                    }
                }
            }

            if (!$sectionId && $classId) {
                $classSections = $allSections[$classId] ?? Section::where('class_id', $classId)->get();
                $firstSec = $classSections->first();
                if (!$firstSec) {
                    $firstSec = Section::create([
                        'class_id'         => $classId,
                        'academic_year_id' => $currentYear?->id ?? AcademicYear::first()?->id,
                        'name'             => 'A',
                        'capacity'         => 40
                    ]);
                    $allSections[$classId] = collect([$firstSec]);
                }
                $sectionId = $firstSec->id;
            }

            // Parse Date of Birth
            $dobRaw = $getField(['dob', 'date_of_birth', 'birth_date', 'bday', 'd_o_b']);
            $dob = null;
            if ($dobRaw) {
                try {
                    if (is_numeric($dobRaw) && (float)$dobRaw > 20000) {
                        $dob = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dobRaw)->format('Y-m-d');
                    } else {
                        $dob = \Carbon\Carbon::parse($dobRaw)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    $dob = null;
                }
            }
            if (!$dob) {
                $dob = '2015-01-01';
            }

            // Parse Passport Expiry
            $passportExpiryRaw = $getField(['passport_expiry', 'passport_expiry_date']);
            $passportExpiry = null;
            if ($passportExpiryRaw) {
                try {
                    if (is_numeric($passportExpiryRaw) && (float)$passportExpiryRaw > 20000) {
                        $passportExpiry = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($passportExpiryRaw)->format('Y-m-d');
                    } else {
                        $passportExpiry = \Carbon\Carbon::parse($passportExpiryRaw)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    $passportExpiry = null;
                }
            }

            // Parse Gender
            $genderVal = strtolower($getField(['gender', 'sex', 'gender_m_f']));
            $gender    = 'male';
            if (str_contains($genderVal, 'fem') || $genderVal === 'f' || $genderVal === 'girl') {
                $gender = 'female';
            } elseif (str_contains($genderVal, 'oth') || $genderVal === 't') {
                $gender = 'other';
            }

            // Parse Student Type
            $typeVal     = strtolower($getField(['student_type', 'type', 'residential_type']));
            $studentType = 'day_scholar';
            if (str_contains($typeVal, 'hostel') || str_contains($typeVal, 'resident')) { $studentType = 'hosteller'; }
            elseif (str_contains($typeVal, 'boarder'))                                  { $studentType = 'day_boarder'; }

            // Parse Blood Group
            $bloodGroup = strtoupper(preg_replace('/\s+/', '', $getField(['blood_group', 'blood_grp', 'bg', 'blood_type'])));
            if (!in_array($bloodGroup, ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])) {
                $bloodGroup = null;
            }

            // Parse Category
            $catVal   = strtolower($getField(['category', 'caste_category', 'cat', 'caste', 'reservation_category']));
            $category = in_array($catVal, ['general', 'obc', 'sc', 'st', 'ews', 'minority']) ? $catVal : 'general';

            // Parse Income
            $familyIncomeRaw = preg_replace('/[^0-9.]/', '', $getField(['annual_family_income', 'family_income', 'income', 'annual_income', 'yearly_income']));
            $familyIncome    = is_numeric($familyIncomeRaw) ? (float)$familyIncomeRaw : null;

            // Extract all remaining fields
            $religion         = $getField(['religion', 'faith', 'community']) ?: null;
            $motherTongue     = $getField(['mother_tongue', 'tongue', 'language', 'native_language']) ?: null;
            $aadhaarNo        = preg_replace('/[^0-9]/', '', $getField(['aadhaar_number', 'aadhaar_no', 'aadhaar', 'adhar', 'aadhar', 'uid'])) ?: null;
            $passportNumber   = $getField(['passport_number', 'passport_no', 'passport']) ?: null;
            $mobile           = preg_replace('/[^0-9+]/', '', $getField(['mobile', 'phone', 'contact', 'mobile_no', 'phone_no', 'student_mobile', 'cell'])) ?: null;
            $email            = $getField(['email', 'student_email', 'email_address', 'mail']) ?: null;
            $resAddress       = $getField(['residential_address', 'address', 'current_address', 'location', 'present_address']) ?: null;
            $permAddress      = $getField(['permanent_address', 'perm_address', 'permanent_addr', 'native_address']) ?: $resAddress;
            $pincode          = $getField(['pincode', 'pin_code', 'zip', 'zipcode', 'postal_code', 'pin']) ?: null;

            $isDisabled       = in_array(strtolower($getField(['person_with_disability_pwd', 'person_with_disability', 'is_disabled', 'pwd', 'disabled', 'disability', 'handicapped'])), ['1', 'true', 'yes', 'y']);
            $disabilityDesc   = $isDisabled ? ($getField(['disability_description', 'disability_desc', 'disability_detail']) ?: 'Yes') : null;

            $fatherName       = $getField(['father_s_name', 'father_name', 'father', 'parent_name', 'fathername', 'dad_name']) ?: 'Father';
            $fatherMobile     = preg_replace('/[^0-9+]/', '', $getField(['father_s_mobile', 'father_mobile', 'father_phone', 'parent_mobile', 'father_contact'])) ?: $mobile;
            $fatherOccupation = $getField(['father_s_occupation', 'father_occupation', 'father_job', 'father_profession']) ?: null;
            $fatherEmail      = $getField(['father_s_email', 'father_email', 'parent_email', 'dad_email']) ?: null;

            $motherName       = $getField(['mother_s_name', 'mother_name', 'mother', 'mothername', 'mom_name']) ?: 'Mother';
            $motherMobile     = preg_replace('/[^0-9+]/', '', $getField(['mother_s_mobile', 'mother_mobile', 'mother_phone', 'mother_contact'])) ?: null;
            $motherOccupation = $getField(['mother_s_occupation', 'mother_occupation', 'mother_job', 'mother_profession']) ?: null;
            $motherEmail      = $getField(['mother_s_email', 'mother_email', 'mother_mail', 'mom_email']) ?: null;

            $guardianName     = $getField(['guardian_name', 'guardian', 'local_guardian']) ?: null;
            $guardianMobile   = preg_replace('/[^0-9+]/', '', $getField(['guardian_mobile', 'guardian_phone', 'guardian_contact'])) ?: null;

            // Roll number preserved as raw string (e.g. "PKGA001 -10")
            $rollNo      = $getField(['roll_number', 'roll_no', 'roll', 'roll_num', 'rollno', 'sr_no']) ?: null;
            $house       = $getField(['house', 'house_name', 'student_house', 'house_color']) ?: null;
            $customAdmNo = $getField(['admission_no', 'admission_number', 'adm_no', 'admission_id']);

            // Generate admission number in memory — no per-row DB query, guarantees uniqueness
            if ($customAdmNo) {
                $admissionNo = $customAdmNo;
            } else {
                $isGirl = (str_contains(strtolower((string)$gender), 'fem') || strtolower((string)$gender) === 'f' || strtolower((string)$gender) === 'girl');
                if ($isGirl) {
                    $admissionNo = 'EPSG' . str_pad((string)$nextSeqGirls++, 4, '0', STR_PAD_LEFT);
                } else {
                    $admissionNo = 'EPSB' . str_pad((string)$nextSeqBoys++, 4, '0', STR_PAD_LEFT);
                }
            }

            try {
                DB::transaction(function () use (
                    $admissionNo, $firstName, $middleName, $lastName, $dob, $gender, $studentType,
                    $bloodGroup, $religion, $motherTongue, $category, $aadhaarNo, $passportNumber, $passportExpiry,
                    $mobile, $email, $resAddress, $permAddress, $pincode, $isDisabled, $disabilityDesc,
                    $fatherName, $fatherMobile, $fatherOccupation, $fatherEmail,
                    $motherName, $motherMobile, $motherOccupation, $motherEmail, $familyIncome,
                    $guardianName, $guardianMobile, $rollNo, $house, $classId, $sectionId, $currentYear, &$imported
                ) {

                    $student = Student::create([
                        'admission_no'             => $admissionNo,
                        'admission_date'           => today(),
                        'roll_number'              => $rollNo,
                        'first_name'               => $firstName,
                        'middle_name'              => $middleName,
                        'last_name'                => $lastName,
                        'dob'                      => $dob,
                        'gender'                   => $gender,
                        'student_type'             => $studentType,
                        'blood_group'              => $bloodGroup,
                        'religion'                 => $religion,
                        'mother_tongue'            => $motherTongue,
                        'category'                 => $category,
                        'aadhaar_no'               => $aadhaarNo,
                        'passport_number'          => $passportNumber,
                        'passport_expiry'          => $passportExpiry,
                        'mobile'                   => $mobile,
                        'email'                    => $email,
                        'residential_address'      => $resAddress,
                        'permanent_address'        => $permAddress,
                        'pincode'                  => $pincode,
                        'is_disabled'              => $isDisabled,
                        'disability_description'   => $disabilityDesc,
                        'father_name'              => $fatherName,
                        'father_mobile'            => $fatherMobile,
                        'father_occupation'        => $fatherOccupation,
                        'father_email'             => $fatherEmail,
                        'mother_name'              => $motherName,
                        'mother_mobile'            => $motherMobile,
                        'mother_occupation'        => $motherOccupation,
                        'mother_email'             => $motherEmail,
                        'annual_family_income'     => $familyIncome,
                        'guardian_name'            => $guardianName,
                        'guardian_mobile'          => $guardianMobile,
                        'status'                   => 'active',
                    ]);

                    if ($currentYear && $classId) {
                        StudentEnrollment::create([
                            'student_id'       => $student->id,
                            'academic_year_id' => $currentYear->id,
                            'class_id'         => $classId,
                            'section_id'       => $sectionId,
                            'roll_number'      => $rollNo,
                            'house'            => $house,
                            'status'           => 'active',
                        ]);
                    }

                    $imported++;
                });
            } catch (\Exception $e) {
                // Roll back sequence counter so the number isn't skipped
                if (!$customAdmNo) {
                    $nextSeq--;
                }
                $errors[] = "Row " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        $msg = "Imported {$imported} student(s) successfully.";
        if (count($errors) > 0) {
            $msg .= " " . count($errors) . " row(s) encountered issues: " . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('students.index')->with(count($errors) > 0 && $imported == 0 ? 'error' : 'success', $msg);
    }

    private function dateToWords(?\Carbon\Carbon $date): string
    {
        if (!$date) return '—';

        $days = [
            1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Fifth',
            6 => 'Sixth', 7 => 'Seventh', 8 => 'Eighth', 9 => 'Ninth', 10 => 'Tenth',
            11 => 'Eleventh', 12 => 'Twelfth', 13 => 'Thirteenth', 14 => 'Fourteenth', 15 => 'Fifteenth',
            16 => 'Sixteenth', 17 => 'Seventeenth', 18 => 'Eighteenth', 19 => 'Nineteenth', 20 => 'Twentieth',
            21 => 'Twenty-First', 22 => 'Twenty-Second', 23 => 'Twenty-Third', 24 => 'Twenty-Fourth', 25 => 'Twenty-Fifth',
            26 => 'Twenty-Sixth', 27 => 'Twenty-Seventh', 28 => 'Twenty-Eighth', 29 => 'Twenty-Ninth', 30 => 'Thirtieth',
            31 => 'Thirty-First'
        ];

        $dayWord = $days[$date->day] ?? $date->day;
        $monthWord = $date->format('F');
        $yearWord = $this->numberToWords($date->year);

        return trim($dayWord . ' ' . $monthWord . ' ' . $yearWord);
    }

    private function numberToWords(int $num): string
    {
        $ones = [
            0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
        ];
        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        ];

        if ($num < 20) return $ones[$num];
        if ($num < 100) return $tens[(int)($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
        if ($num < 1000) return $ones[(int)($num / 100)] . ' Hundred' . ($num % 100 ? ' ' . $this->numberToWords($num % 100) : '');
        if ($num < 1000000) return $this->numberToWords((int)($num / 1000)) . ' Thousand' . ($num % 1000 ? ' ' . $this->numberToWords($num % 1000) : '');

        return (string)$num;
    }

    /**
     * Verify payment receipt and mark student admission / term fee as paid.
     * Updates student paid amount, pending amount, payment status, terms schedule,
     * and records FeePayment transaction in the database.
     */
    public function markAdmissionFeePaid(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'amount_received' => 'required|numeric|min:1',
            'payment_mode'    => 'required|string',
            'payment_account' => 'nullable|string',
            'payment_date'    => 'required|date',
            'transaction_id'  => 'nullable|string|max:100',
            'term_number'     => 'nullable|integer',
            'notes'           => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($student, $validated) {
            $amount = (float)$validated['amount_received'];
            $terms = is_array($student->admission_fee_terms) ? $student->admission_fee_terms : [];
            $rem = $amount;
            $termNamePaid = 'Admission Balance';

            if (!empty($validated['term_number'])) {
                // Apply to specific term
                foreach ($terms as &$t) {
                    if (($t['term_number'] ?? null) == $validated['term_number']) {
                        $pendingInTerm = max(0, (float)($t['pending'] ?? ((float)$t['amount'] - (float)($t['paid'] ?? 0))));
                        $paidThis = min($rem, $pendingInTerm);
                        $t['paid'] = round((float)($t['paid'] ?? 0) + $paidThis, 2);
                        $t['pending'] = max(0, round((float)($t['amount'] ?? 0) - (float)$t['paid'], 2));
                        $t['status'] = $t['pending'] <= 0 ? 'paid' : ($t['paid'] > 0 ? 'partially_paid' : 'pending');
                        $t['payment_mode'] = $validated['payment_mode'];
                        $t['payment_date'] = $validated['payment_date'];
                        $termNamePaid = $t['name'] ?? ('Term ' . $validated['term_number']);
                        $rem -= $paidThis;
                        break;
                    }
                }
            } else {
                // Apply sequentially across pending terms
                foreach ($terms as &$t) {
                    if ($rem <= 0) break;
                    $pendingInTerm = max(0, (float)($t['pending'] ?? ((float)$t['amount'] - (float)($t['paid'] ?? 0))));
                    if ($pendingInTerm > 0) {
                        $paidThis = min($rem, $pendingInTerm);
                        $t['paid'] = round((float)($t['paid'] ?? 0) + $paidThis, 2);
                        $t['pending'] = max(0, round((float)($t['amount'] ?? 0) - (float)$t['paid'], 2));
                        $t['status'] = $t['pending'] <= 0 ? 'paid' : ($t['paid'] > 0 ? 'partially_paid' : 'pending');
                        $t['payment_mode'] = $validated['payment_mode'];
                        $t['payment_date'] = $validated['payment_date'];
                        $termNamePaid = $t['name'] ?? 'Admission Fee Schedule';
                        $rem -= $paidThis;
                    }
                }
            }

            $currentPaid = (float)($student->admission_paid_amount ?? 0);
            $newPaidTotal = round($currentPaid + $amount, 2);
            $totalAdmissionFee = (float)($student->total_admission_fee ?? $newPaidTotal);
            $newPendingTotal = max(0, round($totalAdmissionFee - $newPaidTotal, 2));
            $newStatus = $newPendingTotal <= 0 ? 'paid' : 'partially_paid';

            $student->admission_paid_amount = $newPaidTotal;
            $student->admission_pending_amount = $newPendingTotal;
            $student->payment_status = $newStatus;
            $student->admission_fee_terms = $terms;
            $student->save();

            // Record FeePayment entry
            $currentYear = AcademicYear::current();
            $enrollment = $student->currentEnrollment;
            $feePaymentMode = strtolower($validated['payment_mode']) === 'upi' ? 'upi' : 'cash';
            $paymentAccount = $validated['payment_account'] ?? ($feePaymentMode === 'upi' ? 'upi' : 'cash_box_1');

            $payment = \App\Models\FeePayment::create([
                'student_id'       => $student->id,
                'enrollment_id'    => $enrollment?->id,
                'academic_year_id' => $enrollment?->academic_year_id ?? $currentYear?->id,
                'receipt_number'   => 'REC-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'payment_date'     => $validated['payment_date'],
                'amount'           => $amount,
                'amount_paid'      => $amount,
                'total_paid'       => $amount,
                'payment_mode'     => $feePaymentMode,
                'payment_account'  => $paymentAccount,
                'term_name'        => $termNamePaid,
                'transaction_id'   => $validated['transaction_id'] ?? null,
                'remarks'          => $validated['notes'] ?: 'Fee payment verified & marked as paid at desk',
                'collected_by'     => Auth::id(),
            ]);

            AuditLog::record('admission_fee_payment_collected', $student, [], [
                'student_id'      => $student->id,
                'amount_received' => $amount,
                'receipt_number'  => $payment->receipt_number,
                'new_status'      => $newStatus,
            ]);

            return back()->with('success', 'Fee payment of ₹' . number_format($amount, 2) . ' verified and recorded successfully! Receipt #' . $payment->receipt_number . '. Student status updated to ' . ($newStatus === 'paid' ? 'Paid' : 'Partially Paid') . '.');
        });
    }
}
