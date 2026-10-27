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
use App\Models\House;
use App\Models\StudentAllotmentHistory;
use App\Models\FeePayment;
use App\Models\User;
use App\Models\Employee;
use App\Services\StudentRollNumberService;
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
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $currentYear   = AcademicYear::current();

        // Compute student counts per academic session
        $yearCounts = DB::table('student_enrollments')
            ->join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->whereNull('students.deleted_at')
            ->select('student_enrollments.academic_year_id', DB::raw('count(*) as count'))
            ->groupBy('student_enrollments.academic_year_id')
            ->pluck('count', 'academic_year_id');

        foreach ($academicYears as $ay) {
            $ay->student_count = $yearCounts[$ay->id] ?? 0;
        }

        $allStudentsCount = DB::table('students')->whereNull('deleted_at')->count();

        // Handle academic_year_id selection ('all' or specific ID)
        $rawYear = $request->input('academic_year_id');
        if ($rawYear === 'all') {
            $selectedYearId = null;
            $selectedYear   = null;
        } elseif ($request->filled('academic_year_id')) {
            $selectedYearId = (int)$rawYear;
            $selectedYear   = $academicYears->firstWhere('id', $selectedYearId);
        } else {
            $selectedYearId = $currentYear?->id ?? $academicYears->first()?->id;
            $selectedYear   = $academicYears->firstWhere('id', $selectedYearId) ?? $currentYear;
        }

        $sort = $request->input('sort', 'latest');

        // Dynamic Statistics Query for selected academic year & scoping
        $enrBase = DB::table('student_enrollments')
            ->join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->whereNull('students.deleted_at')
            ->when($selectedYearId, fn($q) => $q->where('student_enrollments.academic_year_id', $selectedYearId));

        if ($request->is_scoped && empty($request->scope_student_ids)) {
            if (!empty($request->scope_class_ids)) {
                $enrBase->whereIn('student_enrollments.class_id', $request->scope_class_ids);
            } else {
                $enrBase->whereRaw('1 = 0');
            }
        }

        $totalStudents = (clone $enrBase)->count();
        $activeStudents = (clone $enrBase)->where('students.status', 'active')->count();
        $newlyAdmittedStudents = (clone $enrBase)->where(function($q) {
            $q->where('students.admission_date', '>=', now()->subDays(60))
              ->orWhere('students.created_at', '>=', now()->subDays(60));
        })->count();

        $maleCount   = (clone $enrBase)->where('students.gender', 'male')->count();
        $femaleCount = (clone $enrBase)->where('students.gender', 'female')->count();
        $otherGenderCount = max(0, $totalStudents - ($maleCount + $femaleCount));

        $classesCount  = (clone $enrBase)->distinct('student_enrollments.class_id')->count('student_enrollments.class_id');
        $sectionsCount = (clone $enrBase)->whereNotNull('student_enrollments.section_id')->distinct('student_enrollments.section_id')->count('student_enrollments.section_id');

        $withoutRollCount = (clone $enrBase)->where(function($q) {
            $q->whereNull('student_enrollments.roll_number')->orWhere('student_enrollments.roll_number', '');
        })->count();

        $withoutSectionCount = (clone $enrBase)->whereNull('student_enrollments.section_id')->count();

        $withoutHouseCount = (clone $enrBase)->where(function($q) {
            $q->whereNull('student_enrollments.house_id')
              ->where(function($sq) {
                  $sq->whereNull('student_enrollments.house')->orWhere('student_enrollments.house', '');
              });
        })->count();

        $transferredCount = (clone $enrBase)->where('students.status', 'transferred')->count();
        $tcIssuedCount    = (clone $enrBase)->where(function($q) {
            $q->whereIn('students.status', ['left', 'tc_issued'])
              ->orWhereNotNull('students.leaving_date');
        })->count();

        $incompleteProfilesCount = (clone $enrBase)->where(function($q) {
            $q->whereNull('students.dob')
              ->orWhereNull('students.father_mobile')
              ->orWhereNull('students.residential_address')
              ->orWhereNull('students.photo');
        })->count();

        $metrics = [
            'total'              => $totalStudents,
            'active'             => $activeStudents,
            'newly_admitted'     => $newlyAdmittedStudents,
            'male'               => $maleCount,
            'female'             => $femaleCount,
            'other_gender'       => $otherGenderCount,
            'classes_count'      => $classesCount,
            'sections_count'     => $sectionsCount,
            'without_roll'       => $withoutRollCount,
            'without_section'    => $withoutSectionCount,
            'without_house'      => $withoutHouseCount,
            'transferred'        => $transferredCount,
            'tc_issued'          => $tcIssuedCount,
            'incomplete_profile' => $incompleteProfilesCount,
        ];

        // Class-Wise Student Overview (Hierarchy: Academic Year -> Class -> Section, Pre-KG to 12th)
        $allActiveClasses = Classes::active()->with(['sections.classTeacher'])->orderBy('sort_order')->get();
        $classOverview = $allActiveClasses->map(function ($cls) use ($selectedYearId) {
            $classEnrs = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->where('student_enrollments.class_id', $cls->id)
                ->whereNull('students.deleted_at')
                ->when($selectedYearId, fn($q) => $q->where('student_enrollments.academic_year_id', $selectedYearId))
                ->select('student_enrollments.*', 'students.gender', 'students.dob', 'students.photo', 'students.father_mobile')
                ->get();

            $total = $classEnrs->count();
            $male = $classEnrs->where('gender', 'male')->count();
            $female = $classEnrs->where('gender', 'female')->count();
            $unassigned = $classEnrs->whereNull('section_id')->count();
            $withoutRoll = $classEnrs->filter(fn($e) => empty($e->roll_number))->count();

            $sectionsData = $cls->sections->map(function ($sec) use ($classEnrs) {
                $secEnrs = $classEnrs->where('section_id', $sec->id);
                $secTotal = $secEnrs->count();
                $secMale = $secEnrs->where('gender', 'male')->count();
                $secFemale = $secEnrs->where('gender', 'female')->count();
                $secWithoutRoll = $secEnrs->filter(fn($e) => empty($e->roll_number))->count();
                $secIncomplete = $secEnrs->filter(fn($e) => empty($e->dob) || empty($e->photo) || empty($e->father_mobile))->count();
                $capacity = (int)($sec->capacity ?: 40);
                $available = max(0, $capacity - $secTotal);

                return (object)[
                    'id'           => $sec->id,
                    'name'         => $sec->name,
                    'teacher_name' => $sec->classTeacher?->name ?? 'Not Assigned',
                    'total'        => $secTotal,
                    'male'         => $secMale,
                    'female'       => $secFemale,
                    'capacity'     => $capacity,
                    'available'    => $available,
                    'without_roll' => $secWithoutRoll,
                    'incomplete'   => $secIncomplete,
                ];
            });

            return (object)[
                'id'             => $cls->id,
                'name'           => $cls->name,
                'display_name'   => $cls->display_name,
                'total_sections' => $cls->sections->count(),
                'total_students' => $total,
                'male'           => $male,
                'female'         => $female,
                'unassigned'     => $unassigned,
                'without_roll'   => $withoutRoll,
                'sections'       => $sectionsData,
            ];
        });

        // Fast mapping of class ID -> array of sections for dynamic frontend JS selector
        $classSectionMap = [];
        foreach ($classOverview as $cls) {
            $classSectionMap[$cls->id] = $cls->sections->map(fn($s) => [
                'id'    => $s->id,
                'name'  => $s->name,
                'total' => $s->total,
            ])->values()->all();
        }

        // 15-Column Student Roster Query
        $query = Student::query()
            ->with([
                'enrollments' => function ($eq) use ($selectedYearId) {
                    $eq->where('status', 'active')
                       ->when($selectedYearId, fn($yq) => $yq->where('academic_year_id', $selectedYearId))
                       ->latest()
                       ->with(['class', 'section', 'houseGroup', 'academicYear']);
                },
                'currentEnrollment.class',
                'currentEnrollment.section',
                'currentEnrollment.houseGroup',
                'currentEnrollment.academicYear'
            ]);

        // Filter through enrollments matching selected session and class/section/house criteria
        $query->whereHas('enrollments', function ($eq) use ($request, $selectedYearId) {
            $eq->where('status', 'active');
            if ($selectedYearId) {
                $eq->where('academic_year_id', $selectedYearId);
            }
            if ($request->filled('class_id')) {
                $eq->where('class_id', $request->class_id);
            }
            if ($request->filled('section_id')) {
                $eq->where('section_id', $request->section_id);
            } elseif ($request->filled('section')) {
                $rawSec = trim($request->section);
                $cleanSec = trim(preg_replace('/^section\s*/i', '', $rawSec));
                $eq->whereHas('section', function ($sq) use ($cleanSec) {
                    $sq->whereRaw("UPPER(TRIM(REPLACE(name, 'Section', ''))) = ?", [strtoupper($cleanSec)])
                       ->orWhereRaw("UPPER(name) = ?", [strtoupper($cleanSec)])
                       ->orWhereRaw("UPPER(name) LIKE ?", ['%' . strtoupper($cleanSec) . '%']);
                });
            }
            if ($request->filled('house_id')) {
                if ($request->house_id === 'unallotted') {
                    $eq->whereNull('house_id')->where(fn($sq) => $sq->whereNull('house')->orWhere('house', ''));
                } else {
                    $house = House::find($request->house_id);
                    $eq->where(function ($hq) use ($request, $house) {
                        $hq->where('house_id', $request->house_id);
                        if ($house) {
                            $hq->orWhereRaw('UPPER(house) = ?', [strtoupper($house->code)]);
                        }
                    });
                }
            }
            if ($request->filled('missing_data')) {
                match ($request->missing_data) {
                    'without_roll' => $eq->where(fn($sq) => $sq->whereNull('roll_number')->orWhere('roll_number', '')),
                    'without_section' => $eq->whereNull('section_id'),
                    'without_house' => $eq->whereNull('house_id')->where(fn($sq) => $sq->whereNull('house')->orWhere('house', '')),
                    default => null,
                };
            }
            if ($request->is_scoped && empty($request->scope_student_ids) && !empty($request->scope_class_ids)) {
                $eq->whereIn('class_id', $request->scope_class_ids);
            }
        });

        // Direct Student table filters
        $query->when($request->filled('search'), function ($q) use ($request, $selectedYearId) {
            $v = strtolower(trim($request->search));
            $q->where(function ($sq) use ($v, $selectedYearId) {
                $sq->whereRaw("LOWER(first_name) LIKE ?", ["%$v%"])
                  ->orWhereRaw("LOWER(last_name) LIKE ?", ["%$v%"])
                  ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", ["%$v%"])
                  ->orWhereRaw("LOWER(CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name)) LIKE ?", ["%$v%"])
                  ->orWhereRaw("LOWER(admission_no) LIKE ?", ["%$v%"])
                  ->orWhereRaw("LOWER(roll_number) LIKE ?", ["%$v%"])
                  ->orWhereRaw("LOWER(mobile) LIKE ?", ["%$v%"])
                  ->orWhereRaw("LOWER(father_name) LIKE ?", ["%$v%"])
                  ->orWhereRaw("LOWER(father_mobile) LIKE ?", ["%$v%"])
                  ->orWhereRaw("LOWER(mother_name) LIKE ?", ["%$v%"])
                  ->orWhereHas('enrollments', function ($eq) use ($v, $selectedYearId) {
                      $eq->where('status', 'active')
                         ->when($selectedYearId, fn($yq) => $yq->where('academic_year_id', $selectedYearId))
                         ->whereRaw("LOWER(roll_number) LIKE ?", ["%$v%"]);
                  });
            });
        })
        ->when($request->filled('gender'), fn($q) => $q->where('gender', $request->gender))
        ->when($request->filled('student_type'), fn($q) => $q->where('student_type', $request->student_type))
        ->when($request->filled('status') && $request->status !== 'all', fn($q) => $q->where('status', $request->status))
        ->when(!$request->has('status'), fn($q) => $q->where('status', 'active'))
        ->when($request->filled('admission_date_from'), fn($q) => $q->whereDate('admission_date', '>=', $request->admission_date_from))
        ->when($request->filled('admission_date_to'), fn($q) => $q->whereDate('admission_date', '<=', $request->admission_date_to))
        ->when($request->filled('missing_data'), function ($q) use ($request) {
            match ($request->missing_data) {
                'without_photo' => $q->where(fn($sq) => $sq->whereNull('photo')->orWhere('photo', '')),
                'incomplete_profile' => $q->where(fn($sq) => $sq->whereNull('dob')->orWhereNull('father_mobile')->orWhereNull('residential_address')->orWhereNull('photo')),
                default => null,
            };
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

        $perPage = min(100, max(10, (int)$request->input('per_page', 25)));
        $students = $query->paginate($perPage)->withQueryString();

        $classes = Classes::activeCached();
        $houses = House::allCached();

        // Distinct section names
        $sectionNames = \Illuminate\Support\Facades\Cache::remember('active_section_names', 3600, function () {
            $names = Section::where('is_active', true)
                ->pluck('name')
                ->map(fn($n) => strtoupper(trim(preg_replace('/^section\s*/i', '', $n))))
                ->unique()->sort()->values();
            return $names->isNotEmpty() ? $names : collect(['A', 'B', 'C', 'D']);
        });

        return view('students.index', compact(
            'students', 'classes', 'sectionNames', 'currentYear',
            'academicYears', 'selectedYear', 'selectedYearId', 'allStudentsCount',
            'houses', 'metrics', 'classOverview', 'classSectionMap'
        ));
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

        $targetAadhaar = $validated['aadhaar_number'] ?? $request->input('aadhaar_no');
        if (!empty($targetAadhaar)) {
            $existing = Student::findStudentByAadhaar($targetAadhaar);
            if ($existing) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'aadhaar_number' => "Student Aadhaar Number is already used for student: {$existing['name']} (Admn: {$existing['admission_no']}, Class: {$existing['class']})."
                ]);
            }
        }

        if ($request->filled('emis_no')) {
            $existingEmis = Student::findStudentByEmis($request->input('emis_no'));
            if ($existingEmis) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'emis_no' => "EMIS / PEN Number is already used for student: {$existingEmis['name']} (Admn: {$existingEmis['admission_no']}, Class: {$existingEmis['class']})."
                ]);
            }
        }

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
                $rollNo = $validated['roll_number'] ?? null;
                if (!$rollNo) {
                    $cls = Classes::find($validated['class_id']);
                    $sec = !empty($validated['section_id']) ? Section::find($validated['section_id']) : null;
                    $cleanClassName = StudentRollNumberService::getClassCode($cls);
                    $cleanSecName   = StudentRollNumberService::getSectionCode($sec);
                    $classSecCount  = StudentEnrollment::where('class_id', $validated['class_id'])
                        ->when($sec, fn($q) => $q->where('section_id', $sec->id))
                        ->count() + 1;
                    $rollNo = StudentRollNumberService::formatRollNumber($cleanClassName, $cleanSecName, $classSecCount);
                    $student->roll_number = $rollNo;
                    $student->save();
                }

                StudentEnrollment::create([
                    'student_id'      => $student->id,
                    'academic_year_id'=> $currentYear->id,
                    'class_id'        => $validated['class_id'],
                    'section_id'      => $validated['section_id'] ?? null,
                    'roll_number'     => $rollNo,
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
            'enrollments.houseGroup',
            'transportRoute',
            'transportStop',
            'siblings',
            'allotmentHistories.changedBy',
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

        // All fee payments for full fee statement
        $allFeePayments = FeePayment::with(['collectedBy', 'feeHead'])
            ->where('student_id', $id)
            ->where('is_cancelled', false)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $totalFeesPayable = (float)($student->total_admission_fee ?? 0);
        if ($totalFeesPayable <= 0 && !empty($student->admission_fee_terms)) {
            $totalFeesPayable = array_sum(array_column($student->admission_fee_terms, 'amount'));
        }
        if ($totalFeesPayable <= 0) {
            $totalFeesPayable = (float)$feeCharged;
        }

        $totalPaid = (float)$allFeePayments->sum('total_paid');
        $totalAdjusted = (float)$allFeePayments->sum('amount_adjusted');
        if ($totalAdjusted <= 0) {
            $totalAdjusted = min($totalPaid, $totalFeesPayable);
        }

        $excessAmount = (float)($student->excess_amount ?? 0);
        if ($excessAmount <= 0) {
            $excessAmount = (float)DB::table('fee_advance_payments')
                ->where('student_id', $student->id)
                ->sum('remaining_amount');
        }

        $outstandingBalance = (float)($student->admission_pending_amount ?? max(0, $totalFeesPayable - $totalAdjusted));

        // Recent payments (last 3)
        $recentPayments = $allFeePayments->take(3);

        // Student Documents & QR Portal Data
        $studentDocuments = StudentDocument::where('student_id', $id)->latest()->get();
        $documentCategories = \App\Http\Controllers\Public\StudentDocumentUploadController::getDocumentCategories();
        $qrUrl = route('public.student.documents', ['token' => $student->document_token]);
        $qrSvg = QrCode::size(140)->margin(1)->generate($qrUrl);

        // Additional Module Information
        $disciplinaryRecords = StudentDisciplinary::where('student_id', $id)->latest()->get();
        $medicalRecord       = StudentMedicalRecord::where('student_id', $id)->first();
        $vaccinations        = StudentVaccination::where('student_id', $id)->get();
        $libraryIssues       = \App\Models\BookIssue::with('book')->where('student_id', $id)->latest()->take(10)->get();
        $houses              = House::where('is_active', true)->orderBy('name')->get();
        $sections            = Section::where('class_id', $student->currentEnrollment?->class_id)->where('is_active', true)->orderBy('name')->get();

        return response()
            ->view('students.show', compact(
                'student', 'feeCharged', 'feePaid', 'feeBalance',
                'totalFeesPayable', 'totalPaid', 'totalAdjusted', 'excessAmount', 'outstandingBalance',
                'allFeePayments',
                'attPresent', 'attAbsent', 'attLate', 'attHalfDay', 'attLeave',
                'attTotal', 'totalDaysConducted', 'fixedAnnualDays', 'effectivePresent',
                'attPct', 'annualTargetPct', 'statutoryMinDaysRequired',
                'recentAttendanceRecords', 'monthlyAttendance',
                'recentPayments',
                'studentDocuments', 'documentCategories', 'qrUrl', 'qrSvg',
                'disciplinaryRecords', 'medicalRecord', 'vaccinations', 'libraryIssues',
                'houses', 'sections'
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
        if ($feeCharged <= 0 && !empty($student->admission_fee_terms)) {
            $feeCharged = (float)array_sum(array_column($student->admission_fee_terms, 'amount'));
        }

        $allPayments = DB::table('fee_payments')
            ->where('student_id', $id)->where('is_cancelled', false)
            ->when($year, fn($q) => $q->where('academic_year_id', $year->id))
            ->get();

        $feePaid = (float) $allPayments->sum('total_paid');
        $feeAdjusted = (float) $allPayments->sum('amount_adjusted');
        if ($feeAdjusted <= 0) {
            $feeAdjusted = min($feePaid, $feeCharged);
        }

        $excessAmount = (float)($student->excess_amount ?? 0);
        if ($excessAmount <= 0) {
            $excessAmount = (float)DB::table('fee_advance_payments')
                ->where('student_id', $student->id)
                ->sum('remaining_amount');
        }

        $feeBalance = (float)($student->admission_pending_amount ?? max(0, $feeCharged - $feeAdjusted));

        $recentPayments = DB::table('fee_payments')
            ->where('student_id', $id)->where('is_cancelled', false)
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'receipt_number' => $p->receipt_number,
                    'date' => \Carbon\Carbon::parse($p->payment_date)->format('d M Y'),
                    'amount' => (float)$p->total_paid,
                    'amount_formatted' => '₹' . number_format($p->total_paid, 2),
                    'adjusted_formatted' => '₹' . number_format((float)($p->amount_adjusted ?? $p->total_paid), 2),
                    'excess_formatted' => '₹' . number_format((float)($p->excess_amount ?? 0), 2),
                ];
            });

        return response()->json([
            'success' => true,
            'feeCharged' => $feeCharged,
            'feeChargedFormatted' => '₹' . number_format($feeCharged, 2),
            'feePaid' => $feePaid,
            'feePaidFormatted' => '₹' . number_format($feePaid, 2),
            'feeAdjusted' => $feeAdjusted,
            'feeAdjustedFormatted' => '₹' . number_format($feeAdjusted, 2),
            'excessAmount' => $excessAmount,
            'excessAmountFormatted' => '₹' . number_format($excessAmount, 2),
            'feeBalance' => $feeBalance,
            'feeBalanceFormatted' => $feeBalance > 0 ? ('₹' . number_format($feeBalance, 2)) : 'Fully Paid',
            'isPaid' => $feeBalance <= 0,
            'paymentStatus' => $student->payment_status,
            'terms' => $student->admission_fee_terms ?? [],
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
        $student = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($id);
        if ($student->status !== 'active') {
            return redirect()->route('students.show', $student->id)
                ->with('error', 'Transfer Certificate (TC) generation is locked. Student admission must be fully approved by Principal and confirmed by Admin before a TC can be issued.');
        }
        $enrollment   = $student->currentEnrollment;
        $school       = \App\Models\SchoolSetting::first();
        $academicYear = \App\Models\AcademicYear::current();

        // Attendance stats
        $totalDays   = \App\Models\AttendanceRecord::where('student_id', $student->id)->count();
        $presentDays = \App\Models\AttendanceRecord::where('student_id', $student->id)->where('status', 'present')->count();

        // TC count
        $tcCount  = Student::whereNotNull('tc_number')->count();
        $tcNumber = $student->tc_number ?: ('TC-' . date('Y') . '-' . str_pad($tcCount + 1, 4, '0', STR_PAD_LEFT));

        // Smart defaults for subjects based on class level
        $className = strtoupper($enrollment?->class?->name ?? '');
        if (in_array($className, ['XI', 'XII', '11', '12', 'CLASS XI', 'CLASS XII'])) {
            $defaultSubjects = '1. English Core  2. Mathematics  3. Physics  4. Chemistry  5. Computer Science';
        } elseif (in_array($className, ['IX', 'X', '9', '10', 'CLASS IX', 'CLASS X'])) {
            $defaultSubjects = '1. English  2. Mathematics  3. Science  4. Social Science  5. Tamil / Hindi';
        } else {
            $defaultSubjects = '1. English  2. Mathematics  3. Environmental Studies  4. Social Science  5. Language II';
        }

        // DOB in words
        $dobInWords = $this->dateToWords($student->dob);

        // ECA / Games activities formatted cleanly as string
        $gamesActivities = '';
        if (is_array($student->selected_eca)) {
            $formattedEca = array_map(function($item) {
                return ucwords(str_replace('_', ' ', trim((string)$item)));
            }, array_filter($student->selected_eca));
            $gamesActivities = implode(', ', $formattedEca);
        } elseif (is_string($student->selected_eca)) {
            $gamesActivities = trim($student->selected_eca);
        }
        if (empty($gamesActivities)) {
            $gamesActivities = 'Physical Education, Yoga & School Games';
        }

        $defaults = [
            'tc_number'           => $tcNumber,
            'tc_date'             => $student->tc_date ? \Carbon\Carbon::parse($student->tc_date)->format('Y-m-d') : today()->format('Y-m-d'),
            'application_date'    => today()->subDays(3)->format('Y-m-d'),
            'leaving_date'        => $student->leaving_date ? \Carbon\Carbon::parse($student->leaving_date)->format('Y-m-d') : today()->format('Y-m-d'),
            'admission_no'        => (string)($student->admission_no ?? ''),
            'emis_no'             => (string)($student->emis_no ?: ($student->id ? '33100' . str_pad($student->id, 6, '0', STR_PAD_LEFT) : '')),
            'aadhaar_no'          => (string)($student->aadhaar_no ?? ''),
            'full_name'           => strtoupper((string)$student->full_name),
            'father_name'         => ucwords(strtolower((string)($student->father_name ?? ''))),
            'mother_name'         => ucwords(strtolower((string)($student->mother_name ?? ''))),
            'nationality'         => ucwords(strtolower((string)($student->nationality ?: 'Indian'))),
            'religion'            => ucwords(strtolower((string)($student->religion ?: 'Hindu'))),
            'caste'               => (string)($student->caste ?? ''),
            'category'            => strtoupper((string)($student->category ?: 'General')),
            'dob'                 => $student->dob ? $student->dob->format('Y-m-d') : '',
            'dob_in_words'        => (string)$dobInWords,
            'place_of_birth'      => (string)($student->place_of_birth ?: ($student->city ? ($student->city . ($student->state ? ', ' . $student->state : ', Tamil Nadu')) : 'Erode, Tamil Nadu')),
            'admission_date'      => $student->admission_date ? $student->admission_date->format('Y-m-d') : ($student->created_at ? $student->created_at->format('Y-m-d') : today()->format('Y-m-d')),
            'admitted_class'      => (string)($student->admitted_class ?: ($enrollment?->class?->name ?? 'I')),
            'last_class'          => (string)(($enrollment?->class?->name ?? 'XI') . ($enrollment?->section ? ' - Section ' . $enrollment->section->name : '')),
            'exam_result'         => (string)('Passed ' . ($enrollment?->class?->name ?? 'Class') . ' Annual Examination'),
            'whether_failed'      => 'No',
            'subjects'            => (string)$defaultSubjects,
            'qualified_promotion' => 'Yes, Qualified for promotion to the next higher class',
            'dues_cleared'        => 'All dues cleared up to ' . now()->format('F Y'),
            'fee_concession'      => (string)($student->concession_type ? ($student->concession_type . ' concession availed') : 'No'),
            'total_working_days'  => $totalDays > 0 ? (int)$totalDays : 210,
            'days_present'        => $presentDays > 0 ? (int)$presentDays : 198,
            'ncc_scout'           => 'N/A',
            'games_activities'    => $gamesActivities,
            'conduct'             => 'Good',
            'progress'            => 'Good',
            'reason'              => (string)($student->leaving_reason ?: "Parent's Request"),
            'remarks'             => 'Conduct and Character have been Good during the period of study.',
        ];

        return view('students.tc-form', compact('student', 'enrollment', 'school', 'academicYear', 'defaults'));
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

        // Reason for leaving
        $reason = $request->input('reason', '');
        if ($reason === 'Other' && $request->filled('custom_reason')) {
            $reason = trim($request->input('custom_reason'));
        }

        // DOB resolution
        $dobInput = $request->input('dob') ? \Carbon\Carbon::parse($request->input('dob')) : $student->dob;
        $dobInWords = $request->filled('dob_in_words') ? trim($request->input('dob_in_words')) : $this->dateToWords($dobInput);

        // Games and activities formatting
        $gamesInput = $request->input('games_activities');
        if (is_array($gamesInput)) {
            $gamesActivities = implode(', ', array_map('trim', array_filter($gamesInput)));
        } else {
            $gamesActivities = trim((string)$gamesInput);
        }
        if (empty($gamesActivities)) {
            if (is_array($student->selected_eca)) {
                $gamesActivities = implode(', ', array_map(fn($i) => ucwords(str_replace('_', ' ', trim((string)$i))), array_filter($student->selected_eca)));
            } elseif (is_string($student->selected_eca)) {
                $gamesActivities = trim($student->selected_eca);
            }
        }
        if (empty($gamesActivities)) {
            $gamesActivities = 'General Physical Education & School Games';
        }

        // Compile complete TC payload from manual inputs with fallbacks
        $tcCount  = Student::whereNotNull('tc_number')->count();
        $tcNumber = trim($request->input('tc_number') ?: ($student->tc_number ?: ('TC-' . date('Y') . '-' . str_pad($tcCount + 1, 4, '0', STR_PAD_LEFT))));
        $tcDate   = $request->input('tc_date') ? \Carbon\Carbon::parse($request->input('tc_date'))->format('Y-m-d') : today()->format('Y-m-d');

        $tcData = [
            'tc_number'           => $tcNumber,
            'tc_date'             => \Carbon\Carbon::parse($tcDate)->format('d/m/Y'),
            'application_date'    => $request->input('application_date') ? \Carbon\Carbon::parse($request->input('application_date'))->format('d/m/Y') : now()->subDays(3)->format('d/m/Y'),
            'leaving_date'        => $request->input('leaving_date') ? \Carbon\Carbon::parse($request->input('leaving_date'))->format('d/m/Y') : now()->format('d/m/Y'),
            'admission_no'        => trim($request->input('admission_no') ?: ($student->admission_no ?? '—')),
            'emis_no'             => trim($request->input('emis_no') ?: ($student->emis_no ?? '—')),
            'aadhaar_no'          => trim($request->input('aadhaar_no') ?: ($student->aadhaar_no ?? '—')),
            'full_name'           => strtoupper(trim($request->input('full_name') ?: $student->full_name)),
            'father_name'         => ucwords(strtolower(trim($request->input('father_name') ?: ($student->father_name ?? '—')))),
            'mother_name'         => ucwords(strtolower(trim($request->input('mother_name') ?: ($student->mother_name ?? '—')))),
            'nationality'         => ucwords(strtolower(trim($request->input('nationality') ?: ($student->nationality ?: 'Indian')))),
            'religion'            => ucwords(strtolower(trim($request->input('religion') ?: ($student->religion ?? '—')))),
            'caste'               => trim($request->input('caste') ?: ($student->caste ?? '')),
            'category'            => strtoupper(trim($request->input('category') ?: ($student->category ?: 'General'))),
            'dob_formatted'       => $dobInput ? $dobInput->format('d / m / Y') : '—',
            'dob_in_words'        => $dobInWords,
            'place_of_birth'      => trim($request->input('place_of_birth') ?: ($student->place_of_birth ?? '—')),
            'admission_date'      => $request->input('admission_date') ? \Carbon\Carbon::parse($request->input('admission_date'))->format('d/m/Y') : ($student->admission_date ? $student->admission_date->format('d/m/Y') : '—'),
            'admitted_class'      => trim($request->input('admitted_class') ?: ($student->admitted_class ?? ($enrollment?->class?->name ?? '—'))),
            'last_class'          => trim($request->input('last_class') ?: (($enrollment?->class?->name ?? '—') . ($enrollment?->section ? ' - ' . $enrollment->section->name : ''))),
            'exam_result'         => trim($request->input('exam_result') ?: ('Passed ' . ($enrollment?->class?->name ?? 'Class') . ' Annual Examination')),
            'whether_failed'      => trim($request->input('whether_failed', 'No')),
            'subjects'            => trim($request->input('subjects') ?: '1. English Core  2. Mathematics  3. Physics  4. Chemistry  5. Computer Science'),
            'qualified_promotion' => trim($request->input('qualified_promotion') ?: 'Yes, Qualified for promotion to the next higher class'),
            'dues_cleared'        => trim($request->input('dues_cleared') ?: ('All dues cleared up to ' . now()->format('F Y'))),
            'fee_concession'      => trim($request->input('fee_concession', 'No')),
            'total_working_days'  => trim($request->input('total_working_days') ?: '210'),
            'days_present'        => trim($request->input('days_present') ?: '198'),
            'ncc_scout'           => trim($request->input('ncc_scout', 'N/A')),
            'games_activities'    => $gamesActivities,
            'conduct'             => trim($request->input('conduct', 'Good')),
            'progress'            => trim($request->input('progress', 'Good')),
            'reason'              => $reason ?: "Parent's Request",
            'remarks'             => trim($request->input('remarks', 'Conduct and Character have been Good during the period of study.')),
        ];

        // Save overrides to student record if authorized user requested
        $updateFields = [
            'tc_number'      => $tcNumber,
            'tc_date'        => $tcDate,
            'leaving_date'   => $request->input('leaving_date', today()->toDateString()),
            'leaving_reason' => $reason,
        ];

        if ($request->boolean('save_to_profile')) {
            if ($request->filled('mother_name'))    $updateFields['mother_name']    = trim($request->input('mother_name'));
            if ($request->filled('father_name'))    $updateFields['father_name']    = trim($request->input('father_name'));
            if ($request->filled('religion'))       $updateFields['religion']       = trim($request->input('religion'));
            if ($request->filled('caste'))          $updateFields['caste']          = trim($request->input('caste'));
            if ($request->filled('category')) {
                $cat = strtolower(trim($request->input('category')));
                if (in_array($cat, ['general', 'sc', 'st', 'obc', 'ews', 'minority'])) {
                    $updateFields['category'] = $cat;
                }
            }
            if ($request->filled('place_of_birth') && \Illuminate\Support\Facades\Schema::hasColumn('students', 'place_of_birth')) {
                $updateFields['place_of_birth'] = trim($request->input('place_of_birth'));
            }
            if ($request->filled('emis_no'))        $updateFields['emis_no']        = trim($request->input('emis_no'));
            if ($request->filled('aadhaar_no'))     $updateFields['aadhaar_no']     = trim($request->input('aadhaar_no'));
            if ($request->filled('nationality'))    $updateFields['nationality']    = trim($request->input('nationality'));
        }

        $student->update($updateFields);

        // Embed Base64 assets
        $logoBase64      = null;
        $sealBase64      = null;
        $signatureBase64 = null;
        $stampBase64     = null;

        $logoPath = public_path('images/school-crest-transparent.png');
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $sealPath = public_path('images/school-seal-badge.png');
        if (file_exists($sealPath)) {
            $sealBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($sealPath));
        }

        if ($school?->signature_path && \Storage::disk('public')->exists($school->signature_path)) {
            $mime = str_contains($school->signature_path, '.png') ? 'image/png' : 'image/jpeg';
            $signatureBase64 = 'data:' . $mime . ';base64,' . base64_encode(\Storage::disk('public')->get($school->signature_path));
        }
        if ($school?->stamp_path && \Storage::disk('public')->exists($school->stamp_path)) {
            $mime = str_contains($school->stamp_path, '.png') ? 'image/png' : 'image/jpeg';
            $stampBase64 = 'data:' . $mime . ';base64,' . base64_encode(\Storage::disk('public')->get($school->stamp_path));
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.tc', compact(
            'student', 'school', 'enrollment', 'academicYear',
            'tcNumber', 'tcData', 'logoBase64', 'sealBase64', 'signatureBase64', 'stampBase64'
        ))->setPaper('A4', 'portrait');

        return $pdf->stream('TC-' . ($student->admission_no ?? $student->id) . '.pdf');
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
        $request->validate([
            'from_class_id'        => 'required|exists:classes,id',
            'to_class_id'          => 'required|exists:classes,id',
            'student_ids'          => 'nullable|array',
            'student_ids.*'        => 'exists:students,id',
            'to_academic_year_id'  => 'nullable|exists:academic_years,id',
            'generate_roll_numbers'=> 'nullable|boolean',
        ]);

        $currentYear = AcademicYear::current();
        $targetYearId = $request->input('to_academic_year_id') ?: ($currentYear?->id);

        $studentIds = $request->input('student_ids');
        if (empty($studentIds)) {
            $studentIds = StudentEnrollment::where('class_id', $request->from_class_id)
                ->where('status', 'active')
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->pluck('student_id')
                ->toArray();
        }

        if (empty($studentIds)) {
            return back()->with('error', 'No active students found in the selected class to promote.');
        }

        $rollService = app(StudentRollNumberService::class);

        // If target year is different, run independent next-year rollover with roll generation
        if ($targetYearId && $currentYear && (int)$targetYearId !== (int)$currentYear->id) {
            $result = $rollService->generateNextYearRollNumbers(
                (int)$request->from_class_id,
                (int)$request->to_class_id,
                (int)$currentYear->id,
                (int)$targetYearId,
                $studentIds,
                true
            );
            return back()->with('success', count($studentIds) . " students promoted to {$result['target_class']} ({$result['target_year']}) with independent class lists and alphabetical roll numbers generated!");
        }

        DB::transaction(function () use ($request, $currentYear, $studentIds) {
            foreach ($studentIds as $studentId) {
                $enrollment = StudentEnrollment::where('student_id', $studentId)->where('status', 'active')->first();
                if (!$enrollment) continue;
                StudentPromotion::create([
                    'student_id'            => $studentId,
                    'from_class_id'         => $request->from_class_id,
                    'to_class_id'           => $request->to_class_id,
                    'from_academic_year_id' => $currentYear?->id,
                    'to_academic_year_id'   => $currentYear?->id,
                    'promoted_by'           => Auth::id(),
                    'promoted_at'           => today(),
                    'status'                => 'promoted',
                ]);
                $enrollment->update(['class_id' => $request->to_class_id]);
            }
        });

        if ($request->boolean('generate_roll_numbers', true)) {
            $rollService->applyRegeneration((int)$request->to_class_id, null, $currentYear?->id);
        }

        return back()->with('success', count($studentIds) . ' students promoted successfully with alphabetical roll numbers generated.');
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
        $classes  = Classes::active()->orderBy('numeric_value')->orderBy('name')->get();
        $currentYear = AcademicYear::current();
        $wingFilter = $request->get('wing', 'all');
        $classFilter = $request->get('class_id');
        $sectionFilter = $request->get('section_id');
        $style = in_array($request->get('style'), ['portrait', 'landscape']) ? $request->get('style') : 'landscape';
        $side = in_array($request->get('side'), ['both', 'front', 'back']) ? $request->get('side') : 'both';
        $search = $request->get('search');

        // Attach wing meta to all classes
        $classes->each(function($cls) {
            $cls->wing_meta = $this->getWingMetadata($cls);
        });

        $sections = collect();
        if ($classFilter) {
            $sections = Section::where('class_id', $classFilter)->orderBy('name')->get();
        }

        $q = StudentEnrollment::with(['student', 'class', 'section'])
            ->where('status', 'active')
            ->whereHas('student', fn($sq) => $sq->where('status', 'active'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id));

        // Enforce ScopeToUser: restrict class/subject teachers or parents to assigned scope
        if ($request->is_scoped) {
            if (!empty($request->scope_student_ids)) {
                $q->whereIn('student_id', $request->scope_student_ids);
            } elseif (!empty($request->scope_class_ids)) {
                $q->whereIn('class_id', $request->scope_class_ids);
            } else {
                $q->whereRaw('1 = 0');
            }
        }

        if ($classFilter) {
            $q->where('class_id', $classFilter);
        }
        if ($sectionFilter) {
            $q->where('section_id', $sectionFilter);
        }
        if ($search) {
            $q->whereHas('student', function($sq) use ($search) {
                $sq->where(function($query) use ($search) {
                    $query->where('first_name', 'ilike', "%{$search}%")
                          ->orWhere('last_name', 'ilike', "%{$search}%")
                          ->orWhere('admission_no', 'ilike', "%{$search}%")
                          ->orWhere('roll_number', 'ilike', "%{$search}%");
                });
            });
        }

        $enrollments = $q->get();

        // Map wing metadata & QR codes to each enrollment
        $enrollments->each(function($e) {
            $e->wing_meta = $this->getWingMetadata($e->class);
            $s = $e->student;
            if ($s) {
                $qrData = implode(' | ', array_filter([
                    'ID: ' . ($s->admission_number ?: $s->admission_no),
                    'Name: ' . $s->full_name,
                    'Class: ' . ($e->class?->name ?? '') . ($e->section ? '-' . $e->section->name : ''),
                    'Blood: ' . ($s->blood_group ?? ''),
                    'Parent Ph: ' . ($s->father_mobile ?? $s->mother_mobile ?? $s->mobile ?? ''),
                ]));
                try {
                    $s->_qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(90)->margin(0)->generate($qrData);
                } catch (\Throwable) {
                    $s->_qrCodeSvg = '';
                }
            }
        });

        // Filter by wing if specified
        $filteredEnrollments = $enrollments;
        if ($wingFilter && $wingFilter !== 'all') {
            $filteredEnrollments = $enrollments->filter(fn($e) => $e->wing_meta['key'] === $wingFilter)->values();
        }

        $school = \App\Models\SchoolSetting::first();
        $targetClass = $classFilter ? Classes::find($classFilter) : null;
        $targetSection = $sectionFilter ? Section::find($sectionFilter) : null;

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
            'currentYear', 'school', 'wingFilter', 'classFilter', 'sectionFilter',
            'wingCounts', 'style', 'side', 'search', 'targetClass', 'targetSection'
        ));
    }

    public function singleIdCard(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);
        $student = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($id);
        
        $enrollment = $student->currentEnrollment;
        $classModel = $enrollment?->class;
        $wingMeta = $this->getWingMetadata($classModel);
        $school = \App\Models\SchoolSetting::first();
        $currentYear = AcademicYear::current();
        $style = in_array($request->get('style'), ['portrait', 'landscape']) ? $request->get('style') : 'landscape';
        $side = in_array($request->get('side'), ['both', 'front', 'back']) ? $request->get('side') : 'both';

        $qrData = implode(' | ', array_filter([
            'ID: ' . ($student->admission_number ?: $student->admission_no),
            'Name: ' . $student->full_name,
            'Class: ' . ($classModel?->name ?? '') . ' ' . ($enrollment?->section?->name ?? ''),
            'Blood: ' . ($student->blood_group ?? ''),
            'Parent Ph: ' . ($student->father_mobile ?? $student->mother_mobile ?? $student->mobile ?? ''),
        ]));

        try {
            $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(95)->margin(0)->generate($qrData);
        } catch (\Throwable $e) {
            $qrCodeSvg = '';
        }

        return view('students.single-id-card', compact('student', 'enrollment', 'classModel', 'wingMeta', 'school', 'currentYear', 'qrCodeSvg', 'style', 'side'));
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
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $currentYear = AcademicYear::current();
        $classFilter = $request->get('class_id');
        $sectionFilter = $request->get('section_id');
        $wingFilter  = $request->get('wing', 'all');
        $style = in_array($request->get('style'), ['portrait', 'landscape']) ? $request->get('style') : 'landscape';
        $side = in_array($request->get('side'), ['both', 'front', 'back']) ? $request->get('side') : 'both';
        $search = $request->get('search');
        $singleStudentId = $request->get('student_id');

        $q = StudentEnrollment::with(['student', 'class', 'section'])
            ->where('status', 'active')
            ->whereHas('student', fn($sq) => $sq->where('status', 'active'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id));

        if ($singleStudentId) {
            $this->assertStudentInScope($request, (int)$singleStudentId);
            $q->where('student_id', $singleStudentId);
        } else {
            // Enforce ScopeToUser: restrict class/subject teachers or parents to assigned scope
            if ($request->is_scoped) {
                if (!empty($request->scope_student_ids)) {
                    $q->whereIn('student_id', $request->scope_student_ids);
                } elseif (!empty($request->scope_class_ids)) {
                    $q->whereIn('class_id', $request->scope_class_ids);
                } else {
                    $q->whereRaw('1 = 0');
                }
            }

            if ($classFilter) $q->where('class_id', $classFilter);
            if ($sectionFilter) $q->where('section_id', $sectionFilter);
            if ($search) {
                $q->whereHas('student', function($sq) use ($search) {
                    $sq->where(function($query) use ($search) {
                        $query->where('first_name', 'ilike', "%{$search}%")
                              ->orWhere('last_name', 'ilike', "%{$search}%")
                              ->orWhere('admission_no', 'ilike', "%{$search}%")
                              ->orWhere('roll_number', 'ilike', "%{$search}%");
                    });
                });
            }
        }

        $enrollments = $q->get();
        $enrollments->each(function($e) {
            $e->wing_meta = $this->getWingMetadata($e->class);
            $s = $e->student;
            if ($s) {
                $qrData = implode(' | ', array_filter([
                    'ID: ' . ($s->admission_number ?: $s->admission_no),
                    'Name: ' . $s->full_name,
                    'Class: ' . ($e->class?->name ?? '') . ($e->section ? '-' . $e->section->name : ''),
                    'Blood: ' . ($s->blood_group ?? ''),
                    'Parent Ph: ' . ($s->father_mobile ?? $s->mother_mobile ?? $s->mobile ?? ''),
                ]));
                try {
                    $s->_qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(85)->margin(0)->generate($qrData);
                } catch (\Throwable) {
                    $s->_qrCodeSvg = '';
                }
            }
        });

        if (!$classFilter && $wingFilter && $wingFilter !== 'all') {
            $enrollments = $enrollments->filter(fn($e) => $e->wing_meta['key'] === $wingFilter)->values();
        }

        $school = \App\Models\SchoolSetting::first();
        $targetClass = $classFilter ? Classes::find($classFilter) : null;
        $targetSection = $sectionFilter ? Section::find($sectionFilter) : null;

        $pdf = Pdf::loadView('pdf.student-id-card', compact('school', 'enrollments', 'currentYear', 'style', 'side', 'targetClass', 'targetSection'));
        
        if ($style === 'landscape') {
            $pdf->setPaper('A4', 'landscape');
        } else {
            $pdf->setPaper('A4', 'portrait');
        }

        $label = $targetClass ? ('Class-' . str_replace(' ', '-', $targetClass->name)) : ($wingFilter !== 'all' ? $wingFilter : 'All-Students');
        if ($targetSection) {
            $label .= '-Sec-' . $targetSection->name;
        }

        return $pdf->download("student-id-cards-{$label}-{$style}-{$side}.pdf");
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
     * Authorize action for roll number management.
     */
    private function assertCanManageRollNumbers(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->hasRole('student') || $user->hasRole('parent')) {
            abort(403, 'Unauthorized access: Students and parents cannot manage or regenerate roll numbers.');
        }
    }

    /**
     * Main UI for Roll Number and Section Manager.
     */
    public function rollNumberManager(Request $request, StudentRollNumberService $rollService)
    {
        $this->assertCanManageRollNumbers();

        $classes = Classes::active()->orderBy('id')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $currentYear = AcademicYear::current();

        $selectedYearId = $request->input('academic_year_id') ? (int)$request->input('academic_year_id') : null;
        if (!$selectedYearId) {
            $hasCurrEnr = $currentYear ? StudentEnrollment::where('academic_year_id', $currentYear->id)->where('status', 'active')->exists() : false;
            if ($hasCurrEnr) {
                $selectedYearId = $currentYear->id;
            } else {
                $latestActiveYear = StudentEnrollment::where('status', 'active')->latest('academic_year_id')->value('academic_year_id');
                $selectedYearId = $latestActiveYear ?: ($currentYear?->id);
            }
        }

        $selectedClassId = $request->input('class_id') ? (int)$request->input('class_id') : null;
        if (!$selectedClassId) {
            $firstClassWithStudents = StudentEnrollment::where('academic_year_id', $selectedYearId)
                ->where('status', 'active')
                ->value('class_id');
            $selectedClassId = $firstClassWithStudents ?: ($classes->first()?->id);
        }

        $selectedSectionId = $request->input('section_id') ? (int)$request->input('section_id') : null;

        // Active sections for the selected class
        $sections = $selectedClassId
            ? Section::where('class_id', $selectedClassId)->where('is_active', true)->orderBy('name')->get()
            : collect();

        // Auto-load preview for the resolved class and section
        $initialPreview = null;
        if ($selectedClassId) {
            try {
                $initialPreview = $rollService->previewRegeneration(
                    $selectedClassId,
                    $selectedSectionId,
                    $selectedYearId,
                    $request->input('prefix_style', 'standard'),
                    (int)$request->input('start_seq', 1),
                    $request->input('custom_prefix')
                );
            } catch (\Throwable $e) {
                // Ignore initial preview failure if no records found
            }
        }

        return view('students.roll-numbers', compact(
            'classes',
            'sections',
            'academicYears',
            'currentYear',
            'selectedClassId',
            'selectedSectionId',
            'selectedYearId',
            'initialPreview'
        ));
    }

    /**
     * Preview roll number regeneration.
     */
    public function previewRollNumbers(Request $request, StudentRollNumberService $rollService)
    {
        $this->assertCanManageRollNumbers();

        $request->validate([
            'class_id'         => 'required|exists:classes,id',
            'section_id'       => 'nullable|exists:sections,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'prefix_style'     => 'nullable|string|in:standard,roman,custom',
            'start_seq'        => 'nullable|integer|min:1',
            'custom_prefix'    => 'nullable|string|max:10',
        ]);

        try {
            $preview = $rollService->previewRegeneration(
                (int)$request->class_id,
                $request->filled('section_id') ? (int)$request->section_id : null,
                $request->filled('academic_year_id') ? (int)$request->academic_year_id : null,
                $request->input('prefix_style', 'standard'),
                (int)$request->input('start_seq', 1),
                $request->input('custom_prefix')
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'preview' => $preview,
                ]);
            }

            return redirect()->route('students.roll-numbers.index', [
                'class_id'         => $request->class_id,
                'section_id'       => $request->section_id,
                'academic_year_id' => $request->academic_year_id,
                'prefix_style'     => $request->prefix_style,
                'start_seq'        => $request->start_seq,
                'custom_prefix'    => $request->custom_prefix,
                'auto_preview'     => 1,
            ]);
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'Preview failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute authorized roll number regeneration.
     * Permanent admission numbers remain strictly untouched.
     */
    public function generateRollNumbers(Request $request, StudentRollNumberService $rollService)
    {
        $this->assertCanManageRollNumbers();

        $request->validate([
            'class_id'         => 'required|exists:classes,id',
            'section_id'       => 'nullable|exists:sections,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'prefix_style'     => 'nullable|string|in:standard,roman,custom',
            'start_seq'        => 'nullable|integer|min:1',
            'custom_prefix'    => 'nullable|string|max:10',
            'confirm'          => 'required',
        ]);

        try {
            $result = $rollService->applyRegeneration(
                (int)$request->class_id,
                $request->filled('section_id') ? (int)$request->section_id : null,
                $request->filled('academic_year_id') ? (int)$request->academic_year_id : null,
                $request->input('prefix_style', 'standard'),
                (int)$request->input('start_seq', 1),
                $request->input('custom_prefix'),
                Auth::id()
            );

            $msg = "Alphabetical roll numbers successfully generated for {$result['class_name']} ({$result['affected_count']} students updated across {$result['section_count']} section(s)). Permanent admission numbers were strictly preserved.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => $msg,
                    'result'  => $result,
                ]);
            }

            return redirect()->route('students.roll-numbers.index', [
                'class_id'         => $request->class_id,
                'section_id'       => $request->section_id,
                'academic_year_id' => $request->academic_year_id,
                'auto_preview'     => 1,
            ])->with('success', $msg);
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'Regeneration failed: ' . $e->getMessage());
        }
    }

    /**
     * Allocate sections to students (Balanced or Alphabetical split) and generate roll numbers.
     */
    public function allocateSections(Request $request, StudentRollNumberService $rollService)
    {
        $this->assertCanManageRollNumbers();

        $request->validate([
            'class_id'         => 'required|exists:classes,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'strategy'         => 'required|in:balance,alphabetical_split',
            'section_ids'      => 'nullable|array',
            'section_ids.*'    => 'exists:sections,id',
            'generate_rolls'   => 'nullable|boolean',
        ]);

        try {
            $result = $rollService->allocateSections(
                (int)$request->class_id,
                $request->filled('academic_year_id') ? (int)$request->academic_year_id : null,
                $request->input('strategy', 'balance'),
                $request->input('section_ids', []),
                $request->boolean('generate_rolls', true)
            );

            $msg = "Sections successfully allocated to {$result['allocated_count']} students across {$result['sections_used']} sections with fresh sequential roll numbers!";

            return redirect()->route('students.roll-numbers.index', [
                'class_id'         => $request->class_id,
                'academic_year_id' => $request->academic_year_id,
                'auto_preview'     => 1,
            ])->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Section allocation failed: ' . $e->getMessage());
        }
    }

    /**
     * Independent class and section roll number generation for the next academic year.
     */
    public function generateNextYearRollNumbers(Request $request, StudentRollNumberService $rollService)
    {
        $this->assertCanManageRollNumbers();

        $request->validate([
            'from_class_id'   => 'required|exists:classes,id',
            'to_class_id'     => 'required|exists:classes,id',
            'from_year_id'    => 'required|exists:academic_years,id',
            'to_year_id'      => 'required|exists:academic_years,id|different:from_year_id',
            'student_ids'     => 'nullable|array',
            'student_ids.*'   => 'exists:students,id',
            'retain_sections' => 'nullable|boolean',
        ]);

        try {
            $result = $rollService->generateNextYearRollNumbers(
                (int)$request->from_class_id,
                (int)$request->to_class_id,
                (int)$request->from_year_id,
                (int)$request->to_year_id,
                $request->input('student_ids', []),
                $request->boolean('retain_sections', true)
            );

            $msg = "Successfully promoted {$result['promoted_count']} students to {$result['target_class']} ({$result['target_year']}) with independent class lists and alphabetical roll numbers generated!";

            return redirect()->route('students.roll-numbers.index', [
                'class_id'         => $request->to_class_id,
                'academic_year_id' => $request->to_year_id,
                'auto_preview'     => 1,
            ])->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Next year generation failed: ' . $e->getMessage());
        }
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

    // ═════════════════════════════════════════════════════════════════════════
    // DEDICATED HOUSE ALLOTMENT MODULE
    // ═════════════════════════════════════════════════════════════════════════

    public function houseAllotmentIndex(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $currentYear   = AcademicYear::current();
        $selectedYearId = $request->filled('academic_year_id')
            ? (int)$request->academic_year_id
            : ($currentYear?->id ?? $academicYears->first()?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $currentYear;

        $classes = Classes::active()->orderBy('sort_order')->get();
        $houses  = House::with(['captain', 'viceCaptain', 'teacher'])->where('is_active', true)->orderBy('name')->get();

        // Calculate House Strengths
        $houseStats = $houses->map(function ($h) use ($selectedYearId) {
            $enrs = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->whereNull('students.deleted_at')
                ->where('student_enrollments.status', 'active')
                ->when($selectedYearId, fn($q) => $q->where('student_enrollments.academic_year_id', $selectedYearId))
                ->where(function ($q) use ($h) {
                    $q->where('student_enrollments.house_id', $h->id)
                      ->orWhereRaw('UPPER(student_enrollments.house) = ?', [strtoupper($h->code)]);
                })
                ->select('student_enrollments.id', 'students.gender')
                ->get();

            $total = $enrs->count();
            $male = $enrs->where('gender', 'male')->count();
            $female = $enrs->where('gender', 'female')->count();

            return (object)[
                'house'     => $h,
                'total'     => $total,
                'male'      => $male,
                'female'    => $female,
                'capacity'  => $h->capacity ?: 'Unlimited',
                'available' => $h->capacity ? max(0, $h->capacity - $total) : 'Unlimited',
            ];
        });

        // Unallotted Students Count
        $unallottedCount = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->whereNull('students.deleted_at')
            ->where('student_enrollments.status', 'active')
            ->when($selectedYearId, fn($q) => $q->where('student_enrollments.academic_year_id', $selectedYearId))
            ->whereNull('student_enrollments.house_id')
            ->where(fn($sq) => $sq->whereNull('student_enrollments.house')->orWhere('student_enrollments.house', ''))
            ->count();

        // Query Students for House Roster
        $query = StudentEnrollment::with(['student', 'class', 'section', 'houseGroup'])
            ->whereHas('student')
            ->where('student_enrollments.status', 'active')
            ->when($selectedYearId, fn($q) => $q->where('student_enrollments.academic_year_id', $selectedYearId))
            ->when($request->filled('class_id'), fn($q) => $q->where('student_enrollments.class_id', $request->class_id))
            ->when($request->filled('section_id'), fn($q) => $q->where('student_enrollments.section_id', $request->section_id))
            ->when($request->filled('gender'), fn($q) => $q->whereHas('student', fn($sq) => $sq->where('gender', $request->gender)))
            ->when($request->filled('house_filter'), function ($q) use ($request) {
                if ($request->house_filter === 'unallotted') {
                    $q->whereNull('student_enrollments.house_id')
                      ->where(fn($sq) => $sq->whereNull('student_enrollments.house')->orWhere('student_enrollments.house', ''));
                } else {
                    $hId = (int)$request->house_filter;
                    $h = House::find($hId);
                    $q->where(function ($sq) use ($hId, $h) {
                        $sq->where('student_enrollments.house_id', $hId);
                        if ($h) {
                            $sq->orWhereRaw('UPPER(student_enrollments.house) = ?', [strtoupper($h->code)]);
                        }
                    });
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = strtolower(trim($request->search));
                $q->whereHas('student', function ($sq) use ($term) {
                    $sq->whereRaw("LOWER(first_name) LIKE ?", ["%$term%"])
                      ->orWhereRaw("LOWER(last_name) LIKE ?", ["%$term%"])
                      ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", ["%$term%"])
                      ->orWhereRaw("LOWER(admission_no) LIKE ?", ["%$term%"])
                      ->orWhereRaw("LOWER(roll_number) LIKE ?", ["%$term%"]);
                });
            });

        $students = $query->orderBy('class_id')->orderBy('section_id')->paginate(50)->withQueryString();

        // Recent Allotment History
        $recentHistory = StudentAllotmentHistory::with(['student', 'changedBy'])
            ->where('allotment_type', 'house')
            ->latest()
            ->limit(15)
            ->get();

        $staffUsers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['teacher', 'admin', 'principal']))->orderBy('name')->get();

        return view('students.house-allotment', compact(
            'academicYears', 'selectedYear', 'selectedYearId',
            'classes', 'houses', 'houseStats', 'unallottedCount',
            'students', 'recentHistory', 'staffUsers'
        ));
    }

    public function assignHouse(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'house_id'   => 'required|exists:houses,id',
            'reason'     => 'nullable|string|max:255',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $house   = House::findOrFail($validated['house_id']);
        $enrollment = $student->currentEnrollment;

        if (!$enrollment) {
            return back()->with('error', 'Student does not have an active enrollment record.');
        }

        $oldHouseName = $enrollment->houseGroup?->name ?: ($enrollment->house ?: 'Unallotted');
        $newHouseName = $house->name;

        DB::transaction(function () use ($enrollment, $house, $student, $oldHouseName, $newHouseName, $validated) {
            $enrollment->update([
                'house_id' => $house->id,
                'house'    => $house->code,
            ]);

            StudentAllotmentHistory::log(
                $student->id,
                'house',
                $oldHouseName,
                $newHouseName,
                $validated['reason'] ?? 'Individual house allotment',
                $enrollment->academic_year_id
            );
        });

        return back()->with('success', "Student {$student->full_name} allotted to {$house->name} successfully.");
    }

    public function bulkAssignHouse(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'house_id'    => 'required|exists:houses,id',
            'reason'      => 'nullable|string|max:255',
        ]);

        $house = House::findOrFail($validated['house_id']);
        $count = 0;

        DB::transaction(function () use ($validated, $house, &$count) {
            foreach ($validated['student_ids'] as $stId) {
                $enrollment = StudentEnrollment::where('student_id', $stId)->where('status', 'active')->latest()->first();
                if ($enrollment) {
                    $oldHouse = $enrollment->houseGroup?->name ?: ($enrollment->house ?: 'Unallotted');
                    $enrollment->update([
                        'house_id' => $house->id,
                        'house'    => $house->code,
                    ]);

                    StudentAllotmentHistory::log(
                        $stId,
                        'house',
                        $oldHouse,
                        $house->name,
                        $validated['reason'] ?? 'Bulk house allotment',
                        $enrollment->academic_year_id
                    );
                    $count++;
                }
            }
        });

        return back()->with('success', "Successfully allotted {$count} students to {$house->name}.");
    }

    public function removeHouseAllotment(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'reason'     => 'required|string|min:5|max:255',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $enrollment = $student->currentEnrollment;

        if (!$enrollment) {
            return back()->with('error', 'Student active enrollment not found.');
        }

        $oldHouseName = $enrollment->houseGroup?->name ?: ($enrollment->house ?: 'Unallotted');

        DB::transaction(function () use ($enrollment, $student, $oldHouseName, $validated) {
            $enrollment->update([
                'house_id' => null,
                'house'    => null,
            ]);

            StudentAllotmentHistory::log(
                $student->id,
                'house',
                $oldHouseName,
                'Unallotted',
                $validated['reason'],
                $enrollment->academic_year_id
            );
        });

        return back()->with('success', "House allotment removed for {$student->full_name}.");
    }

    public function autoBalanceHouses(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id'         => 'nullable|exists:classes,id',
        ]);

        $activeHouses = House::where('is_active', true)->orderBy('name')->get();
        if ($activeHouses->isEmpty()) {
            return back()->with('error', 'No active houses configured for distribution.');
        }

        $query = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->whereNull('students.deleted_at')
            ->where('student_enrollments.status', 'active')
            ->where('student_enrollments.academic_year_id', $validated['academic_year_id'])
            ->whereNull('student_enrollments.house_id')
            ->where(fn($sq) => $sq->whereNull('student_enrollments.house')->orWhere('student_enrollments.house', ''))
            ->when(!empty($validated['class_id']), fn($q) => $q->where('student_enrollments.class_id', $validated['class_id']))
            ->select('student_enrollments.*', 'students.gender', 'students.id as st_id');

        $unallotted = $query->get();
        if ($unallotted->isEmpty()) {
            return back()->with('info', 'No unallotted students found matching the selected criteria.');
        }

        // Initialize counts per house
        $houseCounts = [];
        foreach ($activeHouses as $h) {
            $countM = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->where('student_enrollments.academic_year_id', $validated['academic_year_id'])
                ->where(fn($q) => $q->where('house_id', $h->id)->orWhereRaw('UPPER(house) = ?', [strtoupper($h->code)]))
                ->where('students.gender', 'male')
                ->count();
            $countF = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->where('student_enrollments.academic_year_id', $validated['academic_year_id'])
                ->where(fn($q) => $q->where('house_id', $h->id)->orWhereRaw('UPPER(house) = ?', [strtoupper($h->code)]))
                ->where('students.gender', 'female')
                ->count();

            $houseCounts[$h->id] = [
                'model'  => $h,
                'male'   => $countM,
                'female' => $countF,
                'total'  => $countM + $countF,
            ];
        }

        $distributedCount = 0;

        DB::transaction(function () use ($unallotted, &$houseCounts, $validated, &$distributedCount) {
            // Group by gender to balance boys and girls evenly
            $males = $unallotted->where('gender', 'male');
            $females = $unallotted->where('gender', 'female');
            $others = $unallotted->whereNotIn('gender', ['male', 'female']);

            // Distribute Males
            foreach ($males as $enr) {
                // Find house with minimum male count
                uasort($houseCounts, fn($a, $b) => $a['male'] <=> $b['male'] ?: $a['total'] <=> $b['total']);
                $bestHouseId = array_key_first($houseCounts);
                $targetHouse = $houseCounts[$bestHouseId]['model'];

                StudentEnrollment::where('id', $enr->id)->update([
                    'house_id' => $targetHouse->id,
                    'house'    => $targetHouse->code,
                ]);

                StudentAllotmentHistory::log(
                    $enr->st_id,
                    'house',
                    'Unallotted',
                    $targetHouse->name,
                    'Balanced automated house allocation',
                    $validated['academic_year_id']
                );

                $houseCounts[$bestHouseId]['male']++;
                $houseCounts[$bestHouseId]['total']++;
                $distributedCount++;
            }

            // Distribute Females
            foreach ($females as $enr) {
                // Find house with minimum female count
                uasort($houseCounts, fn($a, $b) => $a['female'] <=> $b['female'] ?: $a['total'] <=> $b['total']);
                $bestHouseId = array_key_first($houseCounts);
                $targetHouse = $houseCounts[$bestHouseId]['model'];

                StudentEnrollment::where('id', $enr->id)->update([
                    'house_id' => $targetHouse->id,
                    'house'    => $targetHouse->code,
                ]);

                StudentAllotmentHistory::log(
                    $enr->st_id,
                    'house',
                    'Unallotted',
                    $targetHouse->name,
                    'Balanced automated house allocation',
                    $validated['academic_year_id']
                );

                $houseCounts[$bestHouseId]['female']++;
                $houseCounts[$bestHouseId]['total']++;
                $distributedCount++;
            }

            // Distribute Others
            foreach ($others as $enr) {
                uasort($houseCounts, fn($a, $b) => $a['total'] <=> $b['total']);
                $bestHouseId = array_key_first($houseCounts);
                $targetHouse = $houseCounts[$bestHouseId]['model'];

                StudentEnrollment::where('id', $enr->id)->update([
                    'house_id' => $targetHouse->id,
                    'house'    => $targetHouse->code,
                ]);

                StudentAllotmentHistory::log(
                    $enr->st_id,
                    'house',
                    'Unallotted',
                    $targetHouse->name,
                    'Balanced automated house allocation',
                    $validated['academic_year_id']
                );

                $houseCounts[$bestHouseId]['total']++;
                $distributedCount++;
            }
        });

        return back()->with('success', "Auto-balanced and allotted {$distributedCount} students across {$activeHouses->count()} houses with gender balance.");
    }

    public function storeOrUpdateHouse(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'id'                      => 'nullable|exists:houses,id',
            'name'                    => 'required|string|max:100',
            'code'                    => 'required|string|max:20',
            'color'                   => 'required|string|max:20',
            'captain_student_id'      => 'nullable|exists:students,id',
            'vice_captain_student_id' => 'nullable|exists:students,id',
            'teacher_id'              => 'nullable|exists:users,id',
            'capacity'                => 'nullable|integer|min:0',
            'description'             => 'nullable|string|max:500',
        ]);

        if (!empty($validated['id'])) {
            $house = House::findOrFail($validated['id']);
            $house->update($validated);
            $msg = "House {$house->name} updated successfully.";
        } else {
            $currentYear = AcademicYear::current();
            $house = House::create(array_merge($validated, [
                'academic_year_id' => $currentYear?->id,
                'is_active'        => true,
            ]));
            $msg = "House {$house->name} created successfully.";
        }

        \Illuminate\Support\Facades\Cache::forget('all_houses_list');
        return back()->with('success', $msg);
    }

    public function exportHouses(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $yearId = $request->academic_year_id ?: AcademicYear::current()?->id;

        $enrollments = StudentEnrollment::with(['student', 'class', 'section', 'houseGroup'])
            ->whereHas('student')
            ->where('status', 'active')
            ->when($yearId, fn($q) => $q->where('academic_year_id', $yearId))
            ->when($request->filled('class_id'), fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->filled('house_filter'), function ($q) use ($request) {
                if ($request->house_filter === 'unallotted') {
                    $q->whereNull('house_id')->where(fn($sq) => $sq->whereNull('house')->orWhere('house', ''));
                } else {
                    $hId = (int)$request->house_filter;
                    $q->where(fn($sq) => $sq->where('house_id', $hId)->orWhere('house', House::find($hId)?->code));
                }
            })
            ->get();

        $csv = "Admission No,Roll No,Student Name,Class,Section,Gender,House Name,House Code\n";
        foreach ($enrollments as $enr) {
            $st = $enr->student;
            $hName = $enr->houseGroup?->name ?: ($enr->house ?: 'Unallotted');
            $hCode = $enr->houseGroup?->code ?: ($enr->house ?: 'NONE');

            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $st->admission_no,
                $enr->roll_number ?: '',
                str_replace('"', '""', $st->full_name),
                $enr->class?->name ?: '',
                $enr->section?->name ?: '',
                ucfirst($st->gender ?: ''),
                str_replace('"', '""', $hName),
                $hCode
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="house_allotment_' . date('Ymd_His') . '.csv"');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // DEDICATED SECTION ALLOTMENT MODULE
    // ═════════════════════════════════════════════════════════════════════════

    public function sectionAllotmentIndex(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $currentYear   = AcademicYear::current();
        $selectedYearId = $request->filled('academic_year_id')
            ? (int)$request->academic_year_id
            : ($currentYear?->id ?? $academicYears->first()?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $currentYear;

        $classes = Classes::active()->orderBy('sort_order')->get();
        $selectedClassId = $request->filled('class_id')
            ? (int)$request->class_id
            : ($classes->first()?->id);
        $selectedClass = $classes->firstWhere('id', $selectedClassId);

        // Sections in the selected class
        $sections = Section::with('classTeacher')
            ->where('class_id', $selectedClassId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Calculate statistics for each section
        $sectionStats = $sections->map(function ($sec) use ($selectedYearId) {
            $enrs = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->whereNull('students.deleted_at')
                ->where('student_enrollments.section_id', $sec->id)
                ->where('student_enrollments.status', 'active')
                ->when($selectedYearId, fn($q) => $q->where('student_enrollments.academic_year_id', $selectedYearId))
                ->select('student_enrollments.id', 'student_enrollments.roll_number', 'students.gender', 'students.dob', 'students.photo', 'students.father_mobile')
                ->get();

            $total = $enrs->count();
            $male = $enrs->where('gender', 'male')->count();
            $female = $enrs->where('gender', 'female')->count();
            $withoutRoll = $enrs->filter(fn($e) => empty($e->roll_number))->count();
            $incomplete = $enrs->filter(fn($e) => empty($e->dob) || empty($e->photo) || empty($e->father_mobile))->count();
            $capacity = (int)($sec->capacity ?: 40);
            $available = max(0, $capacity - $total);

            return (object)[
                'section'      => $sec,
                'total'        => $total,
                'male'         => $male,
                'female'       => $female,
                'capacity'     => $capacity,
                'available'    => $available,
                'without_roll' => $withoutRoll,
                'incomplete'   => $incomplete,
                'is_full'      => $total >= $capacity,
            ];
        });

        // Unassigned Students in this class
        $unassignedStudents = StudentEnrollment::with(['student', 'class'])
            ->whereHas('student')
            ->where('class_id', $selectedClassId)
            ->whereNull('section_id')
            ->where('status', 'active')
            ->when($selectedYearId, fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->get();

        // Assigned Students in this class (filterable by section)
        $assignedStudentsQuery = StudentEnrollment::with(['student', 'section', 'class', 'houseGroup'])
            ->whereHas('student')
            ->where('class_id', $selectedClassId)
            ->whereNotNull('section_id')
            ->where('status', 'active')
            ->when($selectedYearId, fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->when($request->filled('section_id'), fn($q) => $q->where('section_id', $request->section_id));

        $assignedStudents = $assignedStudentsQuery->orderBy('section_id')->paginate(50)->withQueryString();

        // Recent Section Allotment History
        $recentHistory = StudentAllotmentHistory::with(['student', 'changedBy'])
            ->where('allotment_type', 'section')
            ->latest()
            ->limit(15)
            ->get();

        return view('students.section-allotment', compact(
            'academicYears', 'selectedYear', 'selectedYearId',
            'classes', 'selectedClass', 'selectedClassId',
            'sections', 'sectionStats', 'unassignedStudents',
            'assignedStudents', 'recentHistory'
        ));
    }

    public function assignSection(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'section_id' => 'required|exists:sections,id',
            'override_capacity' => 'nullable|boolean',
        ]);

        $section = Section::findOrFail($validated['section_id']);
        $student = Student::findOrFail($validated['student_id']);
        $enrollment = $student->currentEnrollment;

        if (!$enrollment) {
            return back()->with('error', 'Active enrollment record not found.');
        }

        // Check Capacity
        $currentCount = StudentEnrollment::where('section_id', $section->id)->where('status', 'active')->count();
        $capacity = (int)($section->capacity ?: 40);
        if ($currentCount >= $capacity && empty($validated['override_capacity'])) {
            return back()->with('warning', "Section {$section->name} is at full capacity ({$currentCount}/{$capacity}). Check 'Override Capacity Limit' to proceed.");
        }

        $oldSecName = $enrollment->section?->name ?: 'Unassigned';

        DB::transaction(function () use ($enrollment, $section, $student, $oldSecName) {
            $enrollment->update(['section_id' => $section->id]);

            StudentAllotmentHistory::log(
                $student->id,
                'section',
                $oldSecName,
                $section->name,
                'Single section assignment',
                $enrollment->academic_year_id
            );
        });

        return back()->with('success', "Student {$student->full_name} assigned to Section {$section->name}.");
    }

    public function bulkAssignSections(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'student_ids'       => 'required|array|min:1',
            'student_ids.*'     => 'exists:students,id',
            'section_id'        => 'required|exists:sections,id',
            'override_capacity' => 'nullable|boolean',
        ]);

        $section = Section::findOrFail($validated['section_id']);
        $currentCount = StudentEnrollment::where('section_id', $section->id)->where('status', 'active')->count();
        $capacity = (int)($section->capacity ?: 40);
        $toAdd = count($validated['student_ids']);

        if (($currentCount + $toAdd) > $capacity && empty($validated['override_capacity'])) {
            return back()->with('warning', "Cannot assign {$toAdd} students. Section {$section->name} currently has {$currentCount}/{$capacity} seats filled. Check override to force assignment.");
        }

        $assignedCount = 0;

        DB::transaction(function () use ($validated, $section, &$assignedCount) {
            foreach ($validated['student_ids'] as $stId) {
                $enrollment = StudentEnrollment::where('student_id', $stId)->where('status', 'active')->latest()->first();
                if ($enrollment) {
                    $oldSecName = $enrollment->section?->name ?: 'Unassigned';
                    $enrollment->update(['section_id' => $section->id]);

                    StudentAllotmentHistory::log(
                        $stId,
                        'section',
                        $oldSecName,
                        $section->name,
                        'Bulk section allotment',
                        $enrollment->academic_year_id
                    );
                    $assignedCount++;
                }
            }
        });

        return back()->with('success', "Assigned {$assignedCount} students to Section {$section->name} successfully.");
    }

    public function transferSection(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'student_id'        => 'required|exists:students,id',
            'to_section_id'     => 'required|exists:sections,id',
            'reason'            => 'required|string|min:4|max:255',
            'override_capacity' => 'nullable|boolean',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $toSection = Section::findOrFail($validated['to_section_id']);
        $enrollment = $student->currentEnrollment;

        if (!$enrollment) {
            return back()->with('error', 'Active enrollment record not found.');
        }

        if ($enrollment->section_id === $toSection->id) {
            return back()->with('info', "Student is already enrolled in Section {$toSection->name}.");
        }

        // Check Capacity
        $currentCount = StudentEnrollment::where('section_id', $toSection->id)->where('status', 'active')->count();
        $capacity = (int)($toSection->capacity ?: 40);
        if ($currentCount >= $capacity && empty($validated['override_capacity'])) {
            return back()->with('warning', "Destination Section {$toSection->name} is full ({$currentCount}/{$capacity}). Check override to proceed.");
        }

        $oldSecName = $enrollment->section?->name ?: 'Unassigned';

        DB::transaction(function () use ($enrollment, $toSection, $student, $oldSecName, $validated) {
            $enrollment->update([
                'section_id'  => $toSection->id,
                // Clear roll number upon section transfer so it can be reassigned properly
                'roll_number' => null,
            ]);

            StudentAllotmentHistory::log(
                $student->id,
                'section',
                $oldSecName,
                $toSection->name,
                $validated['reason'],
                $enrollment->academic_year_id
            );
        });

        return back()->with('success', "Student {$student->full_name} transferred from Section {$oldSecName} to Section {$toSection->name}. Note: Roll number has been reset for new section numbering.");
    }

    public function autoBalanceSections(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'class_id'         => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $sections = Section::where('class_id', $validated['class_id'])->where('is_active', true)->orderBy('name')->get();
        if ($sections->isEmpty()) {
            return back()->with('error', 'No active sections found for this class.');
        }

        $unassigned = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->whereNull('students.deleted_at')
            ->where('student_enrollments.class_id', $validated['class_id'])
            ->where('student_enrollments.academic_year_id', $validated['academic_year_id'])
            ->where('student_enrollments.status', 'active')
            ->whereNull('student_enrollments.section_id')
            ->select('student_enrollments.*', 'students.gender', 'students.id as st_id')
            ->get();

        if ($unassigned->isEmpty()) {
            return back()->with('info', 'No unassigned students found in this class.');
        }

        // Initialize section counts
        $secCounts = [];
        foreach ($sections as $s) {
            $countM = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->where('student_enrollments.section_id', $s->id)
                ->where('student_enrollments.academic_year_id', $validated['academic_year_id'])
                ->where('students.gender', 'male')
                ->count();
            $countF = StudentEnrollment::join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->where('student_enrollments.section_id', $s->id)
                ->where('student_enrollments.academic_year_id', $validated['academic_year_id'])
                ->where('students.gender', 'female')
                ->count();

            $secCounts[$s->id] = [
                'model'    => $s,
                'male'     => $countM,
                'female'   => $countF,
                'total'    => $countM + $countF,
                'capacity' => (int)($s->capacity ?: 40),
            ];
        }

        $distributedCount = 0;

        DB::transaction(function () use ($unassigned, &$secCounts, $validated, &$distributedCount) {
            $males = $unassigned->where('gender', 'male');
            $females = $unassigned->where('gender', 'female');
            $others = $unassigned->whereNotIn('gender', ['male', 'female']);

            foreach ($males as $enr) {
                uasort($secCounts, fn($a, $b) => $a['male'] <=> $b['male'] ?: $a['total'] <=> $b['total']);
                $bestSecId = array_key_first($secCounts);
                $targetSec = $secCounts[$bestSecId]['model'];

                StudentEnrollment::where('id', $enr->id)->update(['section_id' => $targetSec->id]);

                StudentAllotmentHistory::log(
                    $enr->st_id,
                    'section',
                    'Unassigned',
                    $targetSec->name,
                    'Balanced automated section allocation',
                    $validated['academic_year_id']
                );

                $secCounts[$bestSecId]['male']++;
                $secCounts[$bestSecId]['total']++;
                $distributedCount++;
            }

            foreach ($females as $enr) {
                uasort($secCounts, fn($a, $b) => $a['female'] <=> $b['female'] ?: $a['total'] <=> $b['total']);
                $bestSecId = array_key_first($secCounts);
                $targetSec = $secCounts[$bestSecId]['model'];

                StudentEnrollment::where('id', $enr->id)->update(['section_id' => $targetSec->id]);

                StudentAllotmentHistory::log(
                    $enr->st_id,
                    'section',
                    'Unassigned',
                    $targetSec->name,
                    'Balanced automated section allocation',
                    $validated['academic_year_id']
                );

                $secCounts[$bestSecId]['female']++;
                $secCounts[$bestSecId]['total']++;
                $distributedCount++;
            }

            foreach ($others as $enr) {
                uasort($secCounts, fn($a, $b) => $a['total'] <=> $b['total']);
                $bestSecId = array_key_first($secCounts);
                $targetSec = $secCounts[$bestSecId]['model'];

                StudentEnrollment::where('id', $enr->id)->update(['section_id' => $targetSec->id]);

                StudentAllotmentHistory::log(
                    $enr->st_id,
                    'section',
                    'Unassigned',
                    $targetSec->name,
                    'Balanced automated section allocation',
                    $validated['academic_year_id']
                );

                $secCounts[$bestSecId]['total']++;
                $distributedCount++;
            }
        });

        return back()->with('success', "Auto-balanced and allotted {$distributedCount} students across {$sections->count()} sections with gender balance.");
    }

    public function exportSections(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $yearId = $request->academic_year_id ?: AcademicYear::current()?->id;

        $enrollments = StudentEnrollment::with(['student', 'class', 'section'])
            ->whereHas('student')
            ->where('status', 'active')
            ->when($yearId, fn($q) => $q->where('academic_year_id', $yearId))
            ->when($request->filled('class_id'), fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->filled('section_id'), fn($q) => $q->where('section_id', $request->section_id))
            ->get();

        $csv = "Admission No,Roll No,Student Name,Class,Section,Gender,Parent Mobile,Status\n";
        foreach ($enrollments as $enr) {
            $st = $enr->student;
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $st->admission_no,
                $enr->roll_number ?: '',
                str_replace('"', '""', $st->full_name),
                $enr->class?->name ?: '',
                $enr->section?->name ?: 'Unassigned',
                ucfirst($st->gender ?: ''),
                $st->father_mobile ?: $st->mobile,
                ucfirst($enr->status ?: '')
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="section_allotment_' . date('Ymd_His') . '.csv"');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // ROLL NUMBER ENHANCEMENTS
    // ═════════════════════════════════════════════════════════════════════════

    public function updateSingleRollNumber(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $validated = $request->validate([
            'student_id'       => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'roll_number'      => 'required|string|max:20',
        ]);

        $enrollment = StudentEnrollment::where('student_id', $validated['student_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('status', 'active')
            ->firstOrFail();

        // Check uniqueness within the same class, section, and academic year
        $existing = StudentEnrollment::where('class_id', $enrollment->class_id)
            ->where('section_id', $enrollment->section_id)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('id', '!=', $enrollment->id)
            ->where('roll_number', trim($validated['roll_number']))
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Roll number '{$validated['roll_number']}' is already assigned to student (Admn: {$existing->student?->admission_no}) in this section.",
            ], 422);
        }

        $oldRoll = $enrollment->roll_number ?: 'None';
        $newRoll = trim($validated['roll_number']);

        DB::transaction(function () use ($enrollment, $oldRoll, $newRoll, $validated) {
            $enrollment->update(['roll_number' => $newRoll]);
            Student::where('id', $validated['student_id'])->update(['roll_number' => $newRoll]);

            StudentAllotmentHistory::log(
                $validated['student_id'],
                'roll_number',
                $oldRoll,
                $newRoll,
                'Manual roll number update',
                $validated['academic_year_id']
            );
        });

        return response()->json([
            'success'     => true,
            'message'     => 'Roll number updated successfully.',
            'roll_number' => $newRoll,
        ]);
    }

    public function exportRollNumbers(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $yearId = $request->academic_year_id ?: AcademicYear::current()?->id;

        $enrollments = StudentEnrollment::with(['student', 'class', 'section'])
            ->whereHas('student')
            ->where('status', 'active')
            ->when($yearId, fn($q) => $q->where('academic_year_id', $yearId))
            ->when($request->filled('class_id'), fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->filled('section_id'), fn($q) => $q->where('section_id', $request->section_id))
            ->orderBy('class_id')
            ->orderBy('section_id')
            ->orderBy('roll_number')
            ->get();

        $csv = "Roll No,Admission No,Student Name,Class,Section,Gender,Date of Birth\n";
        foreach ($enrollments as $enr) {
            $st = $enr->student;
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $enr->roll_number ?: '',
                $st->admission_no,
                str_replace('"', '""', $st->full_name),
                $enr->class?->name ?: '',
                $enr->section?->name ?: 'Unassigned',
                ucfirst($st->gender ?: ''),
                $st->dob ? $st->dob->format('d/m/Y') : ''
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="roll_numbers_' . date('Ymd_His') . '.csv"');
    }

    public function printRollNumbers(Request $request)
    {
        $this->assertCanManageRollNumbers();

        $yearId = $request->academic_year_id ?: AcademicYear::current()?->id;
        $academicYear = AcademicYear::find($yearId) ?? AcademicYear::current();
        $class = Classes::find($request->class_id);
        $section = Section::find($request->section_id);

        $enrollments = StudentEnrollment::with(['student', 'class', 'section'])
            ->whereHas('student')
            ->where('status', 'active')
            ->when($yearId, fn($q) => $q->where('academic_year_id', $yearId))
            ->when($request->filled('class_id'), fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->filled('section_id'), fn($q) => $q->where('section_id', $request->section_id))
            ->orderBy('roll_number')
            ->get();

        return view('students.print-roll-numbers', compact('enrollments', 'academicYear', 'class', 'section'));
    }

    // ═════════════════════════════════════════════════════════════════════════
    // PROFILE PRINT & ALLOTMENT HISTORY
    // ═════════════════════════════════════════════════════════════════════════

    public function printProfile(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);

        $student = Student::with([
            'currentEnrollment.class',
            'currentEnrollment.section',
            'currentEnrollment.houseGroup',
            'currentEnrollment.academicYear',
            'transportRoute',
            'transportStop',
        ])->findOrFail($id);

        $academicYear = AcademicYear::current();
        $documents = StudentDocument::where('student_id', $id)->get();

        return view('students.print-profile', compact('student', 'academicYear', 'documents'));
    }

    public function auditHistoryJson(Request $request, int $id)
    {
        $this->assertStudentInScope($request, $id);

        $histories = StudentAllotmentHistory::with(['changedBy', 'academicYear'])
            ->where('student_id', $id)
            ->latest()
            ->get()
            ->map(fn($h) => [
                'id'            => $h->id,
                'type'          => ucfirst($h->allotment_type),
                'from'          => $h->from_value ?: '—',
                'to'            => $h->to_value ?: '—',
                'reason'        => $h->reason ?: 'Standard update',
                'changed_by'    => $h->changedBy?->name ?? 'System',
                'academic_year' => $h->academicYear?->name ?? '—',
                'date'          => $h->created_at->format('d M Y, h:i A'),
            ]);

        return response()->json(['success' => true, 'histories' => $histories]);
    }
}
