<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\AcademicYear;
use App\Models\SchoolSetting;
use App\Models\StudentEnrollment;
use App\Models\Employee;
use App\Models\Holiday;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClassesController extends Controller
{
    public static function getPeriodTimings(): array
    {
        return [
            1 => ['09:10:00', '09:50:00'],
            2 => ['09:50:00', '10:30:00'],
            3 => ['10:40:00', '11:20:00'],
            4 => ['11:20:00', '12:00:00'],
            5 => ['12:00:00', '12:40:00'],
            6 => ['13:20:00', '14:00:00'],
            7 => ['14:10:00', '14:50:00'],
            8 => ['14:50:00', '15:00:00'], // Diary
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget('classes_module_base_v1_0');
        Cache::forget('classes_module_base_v2_0');
        try {
            $years = AcademicYear::pluck('id');
            foreach ($years as $yId) {
                Cache::forget("classes_module_base_v1_{$yId}");
                Cache::forget("classes_module_base_v2_{$yId}");
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Check whether a user is authorized to manage/edit timetable assignments.
     * Principals, teachers, staff, students, and other view-only roles are strictly prohibited from modifying timetables.
     */
    public static function canManageTimetable(?\App\Models\User $user): bool
    {
        if (!$user) {
            return false;
        }

        // View-only and non-management roles are strictly prohibited from modifying timetables
        if ($user->hasAnyRole(['principal', 'teacher', 'class_teacher', 'subject_teacher', 'staff', 'student', 'parent', 'accountant', 'librarian', 'warden'])) {
            return false;
        }

        // Only super_admin, admin, or authorized timetable managers with explicit 'edit academics' permission
        return $user->hasAnyRole(['super_admin', 'admin']) || $user->can('edit academics');
    }

    /**
     * Validate consecutive teacher assignment in timetable.
     * Prevents the same teacher from being assigned to adjacent periods on the same day in the same class/section.
     *
     * @param int $classId
     * @param int $sectionId
     * @param int $dayOfWeek
     * @param int $academicYearId
     * @param array $slots Array of slots being saved
     * @return string|null Error message if consecutive assignment detected, null otherwise
     */
    public static function checkConsecutiveTeacherConflict(int $classId, int $sectionId, int $dayOfWeek, int $academicYearId, array $slots): ?string
    {
        $submittedByPeriod = [];
        foreach ($slots as $s) {
            $pNum = (int) ($s['period_number'] ?? 0);
            if ($pNum > 0) {
                $submittedByPeriod[$pNum] = [
                    'teacher_id'  => !empty($s['teacher_id']) ? (int) $s['teacher_id'] : null,
                    'period_type' => $s['period_type'] ?? 'class',
                ];
            }
        }

        // Fetch existing slots for this class, section, day, year
        $existingSlots = Timetable::where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get(['period_number', 'teacher_id', 'period_type'])
            ->keyBy('period_number');

        // Merge: submitted overrides existing
        $mergedSlots = [];
        for ($p = 1; $p <= 8; $p++) {
            if (isset($submittedByPeriod[$p])) {
                $mergedSlots[$p] = $submittedByPeriod[$p];
            } elseif (isset($existingSlots[$p])) {
                $mergedSlots[$p] = [
                    'teacher_id'  => $existingSlots[$p]->teacher_id,
                    'period_type' => $existingSlots[$p]->period_type,
                ];
            }
        }

        // Check for adjacent periods with the same teacher
        // Note: Free periods and holidays do not have teachers
        // Only trigger if at least one of the adjacent slots was touched in this request
        for ($p = 1; $p <= 7; $p++) {
            $cur = $mergedSlots[$p] ?? null;
            $next = $mergedSlots[$p + 1] ?? null;

            if ($cur && $next) {
                $curTeacher = $cur['teacher_id'];
                $nextTeacher = $next['teacher_id'];
                $curType = $cur['period_type'] ?? 'class';
                $nextType = $next['period_type'] ?? 'class';

                if ($curTeacher && $nextTeacher && $curTeacher === $nextTeacher) {
                    if (!in_array($curType, ['free', 'holiday']) && !in_array($nextType, ['free', 'holiday'])) {
                        // Check if either period was in the submitted slots
                        if (isset($submittedByPeriod[$p]) || isset($submittedByPeriod[$p + 1])) {
                            return "This teacher is already assigned to the adjacent period. A teacher cannot be assigned to consecutive periods.";
                        }
                    }
                }
            }
        }

        return null;
    }

    public function rename(Request $request, int $id)
    {
        if (!self::canManageTimetable(auth()->user())) {
            return back()->with('error', 'Unauthorized: Only Administrators can rename classes.');
        }

        $request->validate(['name' => 'required|string|max:100']);
        $cls = Classes::findOrFail($id);
        $cls->update(['name' => $request->name]);
        self::clearCache();
        return back()->with('success', 'Class renamed successfully.');
    }

    /**
     * Display the classes module dashboard with cards from 1st to 12th standard,
     * sections A, B, C, D, curriculum streams, and live period tracking.
     */
    public function index(Request $request)
    {
        $currentYear = AcademicYear::current();
        $yearId = (int) ($currentYear?->id ?? 0);
        $cacheKey = "classes_module_base_v3_{$yearId}";

        $cachedData = Cache::remember($cacheKey, 1800, function () {
            // Helper for ultra-fast time string formatting (50x faster than Carbon::parse)
            $fastFormat = static function (?string $timeStr): string {
                if (!$timeStr) return '';
                $ts = strtotime($timeStr);
                return $ts ? date('h:i A', $ts) : $timeStr;
            };

            // Fetch all classes with sections, teachers, student count (via withCount), and timetables
            $classes = Classes::where('is_active', true)
                ->with([
                    'sections' => function ($q) {
                        $q->where('is_active', true)
                          ->orderBy('name', 'asc')
                          ->with(['classTeacher' => fn($q) => $q->select('id', 'name', 'employee_id')])
                          ->withCount(['enrollments as student_count' => fn($eq) => $eq->where('status', 'active')]);
                    },
                    'timetables' => function ($q) {
                        $q->where('is_active', true)
                          ->with(['subject', 'teacher']);
                    }
                ])
                ->orderBy('sort_order')
                ->get();

            // Pre-build structured data
            $rawClassesData = $classes->map(function ($cls) use ($fastFormat) {
                $sortedSections = $cls->sections->sortBy('name')->values();
                $sectionsData = $sortedSections->map(function ($sec) use ($cls, $fastFormat) {
                    $streamTag = null;
                    $streamName = null;
                    if ($cls->numeric_value >= 11) {
                        if ($sec->name === 'A') {
                            $streamTag = 'CS Group';
                            $streamName = 'Computer Science Group (Maths, Physics, Chemistry, CS)';
                        } elseif ($sec->name === 'B') {
                            $streamTag = 'Biology Group';
                            $streamName = 'Biology Group (Physics, Chemistry, Biology, Maths)';
                        } elseif ($sec->name === 'C') {
                            $streamTag = 'Commerce Group';
                            $streamName = 'Commerce Group (Commerce, Accountancy, Economics, B.Maths)';
                        } else {
                            $streamTag = 'Commerce (CA)';
                            $streamName = 'Commerce Group (Commerce, Accountancy, Economics, Computer Apps)';
                        }
                    }

                    $sectionTimetables = $cls->timetables->where('section_id', $sec->id);
                    $weeklySchedule = [];
                    for ($d = 1; $d <= 6; $d++) {
                        $dayEntries = $sectionTimetables->where('day_of_week', $d)->sortBy('start_time')->values();
                        $weeklySchedule[$d] = $dayEntries->map(function ($entry, $idx) use ($fastFormat) {
                            return [
                                'id'           => $entry->id,
                                'period'       => $entry->period_number ?? ($idx + 1),
                                'subject_id'   => $entry->subject_id,
                                'subject_name' => $entry->subject?->name ?? 'Study Period',
                                'subject_code' => $entry->subject?->code ?? '',
                                'subject_type' => $entry->period_type ?? 'theory',
                                'period_type'  => $entry->period_type ?? 'class',
                                'teacher_id'   => $entry->teacher_id,
                                'teacher_name' => $entry->teacher ? ($entry->teacher->first_name . ' ' . $entry->teacher->last_name) : 'Subject Teacher',
                                'teacher_code' => $entry->teacher?->employee_code ?? '',
                                'start_time'   => $fastFormat($entry->start_time),
                                'end_time'     => $fastFormat($entry->end_time),
                                'raw_start'    => $entry->start_time,
                                'raw_end'      => $entry->end_time,
                                'room'         => $entry->room ?? ('Room ' . $entry->class_id),
                            ];
                        })->all();
                    }

                    $count = $sec->student_count > 0 ? (int)$sec->student_count : 35;

                    return [
                        'id'               => $sec->id,
                        'name'             => $sec->name,
                        'display_name'     => 'Section ' . $sec->name,
                        'stream_tag'       => $streamTag,
                        'stream_name'      => $streamName,
                        'capacity'         => $sec->capacity ?? 40,
                        'student_count'    => $count,
                        'class_teacher'    => ($sec->classTeacher) ? $sec->classTeacher->name : 'Not Assigned',
                        'teacher_avatar'   => 'https://ui-avatars.com/api/?name=' . urlencode(($sec->classTeacher) ? $sec->classTeacher->name : 'NA') . '&background=0D8ABC&color=fff',
                        'room'             => 'Room ' . ($cls->numeric_value > 0 ? ($cls->numeric_value . '-' . $sec->name) : ('KG-' . $sec->name)),
                        'weekly_schedule'  => $weeklySchedule,
                    ];
                })->all();

                return [
                    'id'            => $cls->id,
                    'name'          => $cls->name,
                    'display_name'  => $cls->display_name,
                    'numeric_value' => $cls->numeric_value,
                    'sort_order'    => $cls->sort_order,
                    'category'      => $cls->category,
                    'is_active'     => $cls->is_active,
                    'total_students'=> collect($sectionsData)->sum('student_count'),
                    'section_count' => count($sectionsData),
                    'sections'      => $sectionsData,
                ];
            })->all();

            $totalStandards = $classes->where('numeric_value', '>=', 1)->where('numeric_value', '<=', 12)->count();
            $totalSections  = Section::where('is_active', true)->count();
            $totalStudents  = StudentEnrollment::where('status', 'active')->count();
            if ($totalStudents === 0) {
                $totalStudents = collect($rawClassesData)->sum('total_students');
            }
            $allSubjects   = Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'type', 'stream', 'is_coscholastic']);
            $allTeachers   = Employee::where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code', 'designation', 'department', 'employee_type']);
            if ($allTeachers->isEmpty()) {
                $allTeachers = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code', 'designation', 'department', 'employee_type']);
            }
            $totalSubjects = $allSubjects->count();
            $totalTeachers = $allTeachers->count();
            $school        = SchoolSetting::getSingleton();

            // Partition subjects by type for conditional selection boxes
            $theorySubjects = $allSubjects->filter(fn($s) => in_array($s->type, ['theory', 'language']));
            $activitySubjects = $allSubjects->filter(fn($s) => $s->type === 'activity' || $s->is_coscholastic);
            $practicalSubjects = $allSubjects->filter(fn($s) => $s->type === 'practical' || stripos($s->name, 'computer') !== false || stripos($s->name, 'lab') !== false);

            // Partition teachers by specialty for intuitive staff assignment
            $activityTeachers = $allTeachers->filter(function ($t) {
                $desig = strtolower($t->designation ?? '');
                $dept = strtolower($t->department ?? '');
                return str_contains($desig, 'sport') || str_contains($desig, 'physical') || str_contains($desig, 'yoga') ||
                       str_contains($desig, 'music') || str_contains($desig, 'dance') || str_contains($desig, 'art') ||
                       str_contains($desig, 'coach') || str_contains($dept, 'sport') || str_contains($dept, 'physical') ||
                       str_contains($dept, 'performing') || str_contains($dept, 'fine art');
            });

            $labTeachers = $allTeachers->filter(function ($t) {
                $desig = strtolower($t->designation ?? '');
                $dept = strtolower($t->department ?? '');
                return str_contains($desig, 'computer') || str_contains($desig, 'lab') || str_contains($desig, 'robotics') ||
                       str_contains($desig, 'stem') || str_contains($desig, 'it') || str_contains($dept, 'computer') ||
                       str_contains($dept, 'science lab');
            });

            $academicTeachers = $allTeachers->filter(function ($t) {
                return ($t->employee_type ?? 'teaching') === 'teaching';
            });
            if ($academicTeachers->isEmpty()) {
                $academicTeachers = $allTeachers;
            }

            return [
                'classes'           => $classes,
                'rawClassesData'    => $rawClassesData,
                'totalStandards'    => $totalStandards,
                'totalSections'     => $totalSections,
                'totalStudents'     => $totalStudents,
                'totalSubjects'     => $totalSubjects,
                'totalTeachers'     => $totalTeachers,
                'allSubjects'       => $allSubjects,
                'allTeachers'       => $allTeachers,
                'theorySubjects'    => $theorySubjects,
                'activitySubjects'  => $activitySubjects,
                'practicalSubjects' => $practicalSubjects,
                'academicTeachers'  => $academicTeachers,
                'activityTeachers'  => $activityTeachers,
                'labTeachers'       => $labTeachers,
                'school'            => $school,
            ];
        });

        // Calculate live campus period based on current time
        $now = Carbon::now();
        $currentTimeStr = $now->format('H:i:s');
        $currentDayOfWeek = (int) $now->dayOfWeekIso; // 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat, 7=Sun
        $currentPeriodInfo = $this->calculatePeriodStatus($currentTimeStr, $currentDayOfWeek);

        // Inject today_schedule dynamically into each section from memory
        $classesData = collect($cachedData['rawClassesData'])->map(function ($cls) use ($currentDayOfWeek) {
            $cls['sections'] = collect($cls['sections'])->map(function ($sec) use ($currentDayOfWeek) {
                $sec['today_schedule'] = collect($sec['weekly_schedule'][$currentDayOfWeek] ?? []);
                return $sec;
            });
            return $cls;
        });

        $classes           = $cachedData['classes'];
        $totalStandards    = $cachedData['totalStandards'];
        $totalSections     = $cachedData['totalSections'];
        $totalStudents     = $cachedData['totalStudents'];
        $totalSubjects     = $cachedData['totalSubjects'];
        $totalTeachers     = $cachedData['totalTeachers'];
        $allSubjects       = $cachedData['allSubjects'];
        $allTeachers       = $cachedData['allTeachers'];
        $theorySubjects    = $cachedData['theorySubjects'];
        $activitySubjects  = $cachedData['activitySubjects'];
        $practicalSubjects = $cachedData['practicalSubjects'];
        $academicTeachers  = $cachedData['academicTeachers'];
        $activityTeachers  = $cachedData['activityTeachers'];
        $labTeachers       = $cachedData['labTeachers'];
        $school            = $cachedData['school'];

        $authEmployeeId = \App\Http\Controllers\Admin\PeriodReportController::resolveAuthEmployeeId();
        $canManageTimetable = self::canManageTimetable(auth()->user());

        return view('classes.index', compact(
            'classes',
            'classesData',
            'currentPeriodInfo',
            'currentYear',
            'totalStandards',
            'totalSections',
            'totalStudents',
            'totalSubjects',
            'totalTeachers',
            'allSubjects',
            'allTeachers',
            'theorySubjects',
            'activitySubjects',
            'practicalSubjects',
            'academicTeachers',
            'activityTeachers',
            'labTeachers',
            'school',
            'authEmployeeId',
            'canManageTimetable'
        ));
    }

    /**
     * Show single class details with section breakdown and full timetable.
     */
    public function show(int $id)
    {
        $class = Classes::with([
            'sections' => fn($q) => $q->with(['classTeacher', 'enrollments.student']),
            'timetables' => fn($q) => $q->with(['subject', 'teacher'])->orderBy('day_of_week')->orderBy('start_time')
        ])->findOrFail($id);

        $school = SchoolSetting::first();
        $currentYear = AcademicYear::where('is_current', true)->first();

        return view('classes.show', compact('class', 'school', 'currentYear'));
    }

    /**
     * JSON timetable data for interactive modal.
     */
    public function timetableData(Request $request, int $id)
    {
        $class = Classes::findOrFail($id);
        $sectionId = $request->get('section_id');

        $query = Timetable::with(['subject', 'teacher', 'section'])
            ->where('class_id', $class->id);

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        $timetable = $query->orderBy('day_of_week')
            ->orderBy('period_number')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $formattedTimetable = [];
        foreach ($timetable as $dayNum => $slots) {
            $formattedTimetable[$dayNum] = $slots->map(function ($t) {
                $subjectName = $t->subject?->name ?? 'Academic Class';
                $teacherName = $t->teacher ? ($t->teacher->first_name . ' ' . $t->teacher->last_name) : 'Faculty';
                if ($t->period_type === 'free') {
                    $subjectName = 'Free Period / Self Study';
                    $teacherName = 'Free Slot';
                } elseif ($t->period_type === 'holiday') {
                    $subjectName = 'Holiday / No Class';
                    $teacherName = 'Holiday';
                } elseif ($t->period_type === 'substitution') {
                    $teacherName = 'Sub: ' . $teacherName;
                }

                return [
                    'id'           => $t->id,
                    'period'       => (int) $t->period_number,
                    'subject_id'   => $t->subject_id,
                    'subject_name' => $subjectName,
                    'subject_code' => $t->subject?->code ?? '',
                    'subject_type' => $t->period_type ?? 'class',
                    'period_type'  => $t->period_type ?? 'class',
                    'teacher_id'   => $t->teacher_id,
                    'teacher_name' => $teacherName,
                    'room'         => $t->room,
                    'start_time'   => Carbon::parse($t->start_time)->format('h:i A'),
                    'end_time'     => Carbon::parse($t->end_time)->format('h:i A'),
                ];
            })->values()->all();
        }

        // Determine holiday days for this section
        $setting = SchoolSetting::first();
        $meta = $setting?->seals_meta ?? [];
        $secHolidays = $meta['section_day_holidays'] ?? [];

        $holidayDays = [];
        for ($d = 1; $d <= 6; $d++) {
            $key = "{$sectionId}_{$d}";
            if (isset($secHolidays[$key])) {
                $holidayDays[$d] = (bool) $secHolidays[$key];
            } else {
                $daySlots = $timetable->get($d, collect());
                if ($daySlots->isNotEmpty() && $daySlots->every(fn($t) => $t->period_type === 'holiday')) {
                    $holidayDays[$d] = true;
                } else {
                    $holidayDays[$d] = false;
                }
            }
        }

        return response()->json([
            'class'        => $class,
            'timetable'    => $formattedTimetable,
            'holiday_days' => $holidayDays,
        ]);
    }

    /**
     * Update or create a timetable slot (Edit Timetable feature).
     */
    public function updateSlot(Request $request)
    {
        if (!self::canManageTimetable(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Principal and view-only roles do not have permission to modify timetable entries.'
            ], 403);
        }

        $validated = $request->validate([
            'class_id'               => 'required|exists:classes,id',
            'section_id'             => 'required|exists:sections,id',
            'day_of_week'            => 'required|integer|min:1|max:6',
            'period_number'          => 'required|integer|min:1|max:8',
            'subject_id'             => 'nullable|exists:subjects,id',
            'teacher_id'             => 'nullable|exists:employees,id',
            'substitute_teacher_id'  => 'nullable|exists:employees,id',
            'room'                   => 'nullable|string|max:50',
            'period_type'            => 'nullable|string|in:class,free,substitution,activity,holiday,practical',
            'notes'                  => 'nullable|string|max:255',
        ]);

        $currentYear = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::latest('id')->first();

        $periodType = $validated['period_type'] ?? 'class';
        $effectiveTeacherId = $periodType === 'substitution' && !empty($validated['substitute_teacher_id'])
            ? $validated['substitute_teacher_id']
            : ($periodType === 'free' || $periodType === 'holiday' ? null : ($request->input('teacher_id') ?: null));

        try {
            $conflictService = app(\App\Services\TimetableConflictService::class);
            $timetable = $conflictService->atomicSaveSlot([
                'class_id'         => (int) $validated['class_id'],
                'section_id'       => (int) $validated['section_id'],
                'day_of_week'      => (int) $validated['day_of_week'],
                'period_number'    => (int) $validated['period_number'],
                'subject_id'       => !empty($validated['subject_id']) ? (int) $validated['subject_id'] : null,
                'teacher_id'       => $effectiveTeacherId,
                'room'             => $request->input('room'),
                'period_type'      => $periodType,
                'academic_year_id' => $currentYear->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success'  => false,
                'conflict' => true,
                'message'  => $e->getMessage(),
            ], 422);
        }

        self::clearCache();

        $timetable->load(['subject', 'teacher']);

        $subjectName = $timetable->subject?->name ?? 'Academic Class';
        $teacherName = $timetable->teacher ? ($timetable->teacher->first_name . ' ' . $timetable->teacher->last_name) : 'Faculty';

        if ($periodType === 'free') {
            $subjectName = 'Free Period / Self Study';
            $teacherName = 'Free Slot';
        } elseif ($periodType === 'holiday') {
            $subjectName = 'Holiday / No Class';
            $teacherName = 'Holiday';
        } elseif ($periodType === 'substitution') {
            $subjectName = $timetable->subject?->name ?? 'Class';
            $teacherName = 'Sub: ' . $teacherName;
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Timetable slot updated successfully!',
            'slot'      => [
                'id'           => $timetable->id,
                'period'       => (int) $timetable->period_number,
                'subject_id'   => $timetable->subject_id,
                'subject_name' => $subjectName,
                'subject_code' => $timetable->subject?->code ?? '',
                'subject_type' => $periodType,
                'period_type'  => $periodType,
                'teacher_id'   => $timetable->teacher_id,
                'teacher_name' => $teacherName,
                'room'         => $timetable->room,
                'start_time'   => Carbon::parse($timetable->start_time)->format('h:i A'),
                'end_time'     => Carbon::parse($timetable->end_time)->format('h:i A'),
                'raw_start'    => $timetable->start_time,
                'raw_end'      => $timetable->end_time,
            ]
        ]);
    }

    /**
     * Save/update all periods of a specific day for a class section (Edit Timetable batch save).
     */
    public function saveDay(Request $request)
    {
        if (!self::canManageTimetable(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Principal and view-only roles do not have permission to modify timetable entries.'
            ], 403);
        }

        if (!$request->has('slots') && $request->has('periods')) {
            $request->merge(['slots' => $request->input('periods')]);
        }

        $validated = $request->validate([
            'class_id'              => 'required|exists:classes,id',
            'section_id'            => 'required|exists:sections,id',
            'day_of_week'           => 'required|integer|min:1|max:6',
            'slots'                 => 'required|array',
            'slots.*.period_number' => 'required|integer|min:1|max:8',
            'slots.*.subject_id'    => 'nullable',
            'slots.*.teacher_id'    => 'nullable',
            'slots.*.room'          => 'nullable|string|max:50',
            'slots.*.period_type'   => 'nullable|string|in:class,free,substitution,activity,holiday,practical',
        ]);

        $currentYear = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::latest('id')->first();

        try {
            $conflictService = app(\App\Services\TimetableConflictService::class);
            $savedTimetables = $conflictService->atomicSaveDay([
                'class_id'         => (int) $validated['class_id'],
                'section_id'       => (int) $validated['section_id'],
                'day_of_week'      => (int) $validated['day_of_week'],
                'slots'            => $validated['slots'],
                'academic_year_id' => $currentYear->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            $isConsecutive = str_contains($e->getMessage(), 'Consecutive Period Conflict');
            return response()->json([
                'success'              => false,
                'conflict'             => true,
                'consecutive_conflict' => $isConsecutive,
                'message'              => $e->getMessage(),
            ], 422);
        }

        $savedSlots = [];
        foreach ($savedTimetables as $timetable) {
            $timetable->load(['subject', 'teacher']);
            $periodType = $timetable->period_type ?? 'class';
            $subjectName = $timetable->subject?->name ?? 'Academic Class';
            $teacherName = $timetable->teacher ? ($timetable->teacher->first_name . ' ' . $timetable->teacher->last_name) : 'Faculty';

            if ($periodType === 'free') {
                $subjectName = 'Free Period / Self Study';
                $teacherName = 'Free Slot';
            } elseif ($periodType === 'holiday') {
                $subjectName = 'Holiday / No Class';
                $teacherName = 'Holiday';
            } elseif ($periodType === 'substitution') {
                $teacherName = 'Sub: ' . $teacherName;
            }

            $savedSlots[] = [
                'id'           => $timetable->id,
                'period'       => (int) $timetable->period_number,
                'subject_id'   => $timetable->subject_id,
                'subject_name' => $subjectName,
                'subject_code' => $timetable->subject?->code ?? '',
                'subject_type' => $periodType,
                'period_type'  => $periodType,
                'teacher_id'   => $timetable->teacher_id,
                'teacher_name' => $teacherName,
                'room'         => $timetable->room,
                'start_time'   => Carbon::parse($timetable->start_time)->format('h:i A'),
                'end_time'     => Carbon::parse($timetable->end_time)->format('h:i A'),
            ];
        }

        self::clearCache();

        return response()->json([
            'success'     => true,
            'message'     => 'Timetable saved successfully! No conflicts detected.',
            'slots'       => $savedSlots,
            'day_periods' => $savedSlots,
        ]);
    }

    /**
     * API to fetch eligible and completely available staff for a specific period slot.
     * Dynamic dropdown filter & Smart Timetable Assistance engine.
     */
    public function eligibleStaff(Request $request)
    {
        $validated = $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'section_id'    => 'nullable|exists:sections,id',
            'subject_id'    => 'nullable|exists:subjects,id',
            'day_of_week'   => 'required|integer|min:1|max:7',
            'period_number' => 'nullable|integer|min:1|max:8',
        ]);

        $conflictService = app(\App\Services\TimetableConflictService::class);
        $classId = (int) $validated['class_id'];
        $sectionId = !empty($validated['section_id']) ? (int) $validated['section_id'] : null;
        $subjectId = !empty($validated['subject_id']) ? (int) $validated['subject_id'] : null;
        $dayOfWeek = (int) $validated['day_of_week'];

        if (!empty($validated['period_number'])) {
            $result = $conflictService->getEligibleAndAvailableStaff(
                $classId,
                $sectionId,
                $subjectId,
                $dayOfWeek,
                (int) $validated['period_number']
            );
            return response()->json(array_merge(['success' => true], $result));
        }

        $byPeriod = [];
        for ($p = 1; $p <= 8; $p++) {
            $byPeriod[$p] = $conflictService->getEligibleAndAvailableStaff(
                $classId,
                $sectionId,
                $subjectId,
                $dayOfWeek,
                $p
            );
        }

        return response()->json(['success' => true, 'periods' => $byPeriod]);
    }

    /**
     * API to resolve an existing timetable conflict record.
     */
    public function resolveConflict(Request $request)
    {
        if (!self::canManageTimetable(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'timetable_id'   => 'required|exists:timetables,id',
            'resolution'     => 'required|in:free_period,reassign',
            'new_teacher_id' => 'nullable|required_if:resolution,reassign|exists:employees,id',
        ]);

        $conflictService = app(\App\Services\TimetableConflictService::class);
        $result = $conflictService->resolveConflict(
            (int) $validated['timetable_id'],
            $validated['resolution'],
            !empty($validated['new_teacher_id']) ? (int) $validated['new_teacher_id'] : null
        );

        self::clearCache();

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Mark an entire day as holiday or restore regular schedule for a class section.
     * Holiday status is represented separately from timetable data so records are NOT deleted.
     */
    public function toggleHoliday(Request $request)
    {
        if (!self::canManageTimetable(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Principal and view-only roles do not have permission to manage holidays.'
            ], 403);
        }

        $validated = $request->validate([
            'class_id'    => 'required|exists:classes,id',
            'section_id'  => 'required|exists:sections,id',
            'day_of_week' => 'required|integer|min:1|max:6',
            'is_holiday'  => 'required|boolean',
        ]);

        $sectionId = (int) $validated['section_id'];
        $dayNum = (int) $validated['day_of_week'];
        $isHoliday = (bool) $validated['is_holiday'];

        // Save holiday status separately in SchoolSetting seals_meta
        $setting = SchoolSetting::first();
        if ($setting) {
            $meta = $setting->seals_meta ?? [];
            if (!isset($meta['section_day_holidays']) || !is_array($meta['section_day_holidays'])) {
                $meta['section_day_holidays'] = [];
            }
            $meta['section_day_holidays']["{$sectionId}_{$dayNum}"] = $isHoliday;
            $setting->update(['seals_meta' => $meta]);
        }
        Cache::forever("section_day_holiday_{$sectionId}_{$dayNum}", $isHoliday);

        self::clearCache();

        return response()->json([
            'success'    => true,
            'is_holiday' => $isHoliday,
            'message'    => $isHoliday ? 'Day marked as holiday successfully!' : 'Regular schedule restored for this day.',
        ]);
    }

    /**
     * Declare a school holiday for a specific date (e.g. Festival, Weather, Special Event)
     */
    public function declareHoliday(Request $request)
    {
        if (!self::canManageTimetable(auth()->user())) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Principal and view-only roles do not have permission to manage holidays.'], 403);
            }
            return back()->with('error', 'Unauthorized: Principal and view-only roles do not have permission to manage holidays.');
        }

        $request->validate([
            'date'        => 'required|date',
            'name'        => 'required|string|max:255',
            'type'        => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $currentYear = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::latest('id')->first();

        $holiday = Holiday::updateOrCreate(
            ['date' => $request->date],
            [
                'name'             => $request->name,
                'type'             => in_array($request->type, ['national', 'state', 'school', 'optional']) ? $request->type : 'school',
                'description'      => $request->description ?? 'Declared via Timetable Management',
                'academic_year_id' => $currentYear?->id ?? 1,
            ]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Holiday '{$holiday->name}' declared successfully for " . Carbon::parse($holiday->date)->format('D, M d, Y') . "!",
                'holiday' => [
                    'id'             => $holiday->id,
                    'name'           => $holiday->name,
                    'date'           => $holiday->date->format('Y-m-d'),
                    'formatted_date' => Carbon::parse($holiday->date)->format('D, M d, Y'),
                    'type'           => $holiday->type,
                    'description'    => $holiday->description,
                ],
            ]);
        }

        return back()->with('success', "Holiday '{$holiday->name}' declared for " . Carbon::parse($holiday->date)->format('D, M d, Y') . ".");
    }

    /**
     * Delete a declared holiday
     */
    public function deleteHoliday(int $id)
    {
        if (!self::canManageTimetable(auth()->user())) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Principal and view-only roles do not have permission to manage holidays.'], 403);
            }
            return back()->with('error', 'Unauthorized: Principal and view-only roles do not have permission to manage holidays.');
        }

        $holiday = Holiday::findOrFail($id);
        $name    = $holiday->name;
        $dateStr = Carbon::parse($holiday->date)->format('D, M d, Y');
        $holiday->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Declared holiday '{$name}' for {$dateStr} removed.",
            ]);
        }

        return back()->with('success', "Declared holiday '{$name}' for {$dateStr} removed.");
    }

    /**
     * List declared holidays (JSON API endpoint for UI modal)
     */
    public function getHolidaysList()
    {
        $holidays = Holiday::orderBy('date', 'desc')->take(30)->get()->map(function ($h) {
            return [
                'id'             => $h->id,
                'name'           => $h->name,
                'date'           => $h->date->format('Y-m-d'),
                'formatted_date' => $h->date->format('D, M d, Y'),
                'type'           => $h->type,
                'description'    => $h->description,
            ];
        });

        return response()->json([
            'success'  => true,
            'holidays' => $holidays,
        ]);
    }


    /**
     * Download high quality A4 landscape PDF of class & section timetable.
     */
    public function downloadPdf(Request $request, int $id)
    {
        $class = Classes::with('sections')->findOrFail($id);
        $sectionId = $request->get('section_id');
        $selectedSection = $sectionId ? Section::find($sectionId) : $class->sections->first();

        $query = Timetable::with(['subject', 'teacher', 'section'])
            ->where('class_id', $class->id);

        if ($selectedSection) {
            $query->where('section_id', $selectedSection->id);
        }

        $timetable = $query->orderBy('day_of_week')
            ->orderBy('period_number')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $school = SchoolSetting::first() ?? (object)[
            'school_name' => config('app.name', 'DASA EduERP'),
            'address'     => '123 Education Boulevard, Knowledge City',
            'phone'       => '+91 98765 43210',
            'email'       => 'info@dasaeduerp.edu',
        ];

        $currentYear = AcademicYear::where('is_current', true)->first();

        // Stream description for 11th & 12th
        $streamName = null;
        if ($class->numeric_value >= 11 && $selectedSection) {
            if ($selectedSection->name === 'A') $streamName = 'Group 1: Computer Science & Mathematics Stream';
            elseif ($selectedSection->name === 'B') $streamName = 'Group 2: Biology & Life Sciences Stream';
            elseif ($selectedSection->name === 'C') $streamName = 'Group 3: Commerce & Business Mathematics Stream';
            else $streamName = 'Group 3: Commerce & Computer Applications Stream';
        }

        $pdf = Pdf::loadView('pdf.class-timetable', compact(
            'class',
            'selectedSection',
            'timetable',
            'school',
            'currentYear',
            'streamName'
        ))->setPaper('a4', 'landscape');

        $filename = 'Timetable_' . str_replace(' ', '_', $class->display_name) . '_' . ($selectedSection ? 'Sec_' . $selectedSection->name : 'All') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export timetable to CSV.
     */
    public function exportCsv(Request $request, int $id)
    {
        $class = Classes::findOrFail($id);
        $sectionId = $request->get('section_id');
        $selectedSection = $sectionId ? Section::find($sectionId) : null;

        $query = Timetable::with(['subject', 'teacher', 'section'])
            ->where('class_id', $class->id);

        if ($selectedSection) {
            $query->where('section_id', $selectedSection->id);
        }

        $records = $query->orderBy('day_of_week')->orderBy('period_number')->orderBy('start_time')->get();

        $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

        $csvHeader = ['Day', 'Start Time', 'End Time', 'Period #', 'Section', 'Subject', 'Subject Code', 'Teacher', 'Room', 'Period Type'];
        $rows = [];
        $rows[] = implode(',', $csvHeader);

        foreach ($records as $r) {
            $rows[] = implode(',', [
                '"' . ($days[$r->day_of_week] ?? 'Day ' . $r->day_of_week) . '"',
                '"' . Carbon::parse($r->start_time)->format('h:i A') . '"',
                '"' . Carbon::parse($r->end_time)->format('h:i A') . '"',
                '"' . ($r->period_number ?? '—') . '"',
                '"' . ($r->section?->name ?? 'All') . '"',
                '"' . ($r->subject?->name ?? '—') . '"',
                '"' . ($r->subject?->code ?? '—') . '"',
                '"' . ($r->teacher ? ($r->teacher->first_name . ' ' . $r->teacher->last_name) : '—') . '"',
                '"' . ($r->room ?? '—') . '"',
                '"' . ucfirst($r->period_type ?? 'class') . '"',
            ]);
        }

        $content = implode("\n", $rows);
        $filename = 'Timetable_' . str_replace(' ', '_', $class->display_name) . '.csv';

        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Calculate period status from time.
     */
    public static function calculatePeriodStatus(string $timeStr, int $dayOfWeek = 1): array
    {
        $time = Carbon::parse($timeStr);

        $schedule = [
            ['type' => 'period',   'period' => 1, 'name' => 'Period 1',                    'start' => '09:10:00', 'end' => '09:50:00'],
            ['type' => 'period',   'period' => 2, 'name' => 'Period 2',                    'start' => '09:50:00', 'end' => '10:30:00'],
            ['type' => 'break',    'period' => 0, 'name' => 'Morning Interval Break',       'start' => '10:30:00', 'end' => '10:40:00'],
            ['type' => 'period',   'period' => 3, 'name' => 'Period 3',                    'start' => '10:40:00', 'end' => '11:20:00'],
            ['type' => 'period',   'period' => 4, 'name' => 'Period 4',                    'start' => '11:20:00', 'end' => '12:00:00'],
            ['type' => 'period',   'period' => 5, 'name' => 'Period 5',                    'start' => '12:00:00', 'end' => '12:40:00'],
            ['type' => 'lunch',    'period' => 0, 'name' => 'Lunch Break',                 'start' => '12:40:00', 'end' => '13:20:00'],
            ['type' => 'period',   'period' => 6, 'name' => 'Period 6',                    'start' => '13:20:00', 'end' => '14:00:00'],
            ['type' => 'break',    'period' => 0, 'name' => 'Afternoon Refreshment Break', 'start' => '14:00:00', 'end' => '14:10:00'],
            ['type' => 'period',   'period' => 7, 'name' => 'Period 7',                    'start' => '14:10:00', 'end' => '14:50:00'],
            ['type' => 'period',   'period' => 8, 'name' => 'Period 8 — Diary & Wrap-up',  'start' => '14:50:00', 'end' => '15:00:00'],
        ];

        if ($dayOfWeek === 7) {
            return [
                'status'        => 'holiday',
                'badge_color'   => 'red',
                'title'         => 'Sunday — Campus Holiday',
                'period_number' => 0,
                'is_live'       => false,
                'message'       => 'Classes will resume on Monday at 09:10 AM',
                'schedule'      => $schedule,
            ];
        }

        $schoolStart = Carbon::parse('09:10:00');
        $schoolEnd   = Carbon::parse('15:00:00');

        if ($time->lt($schoolStart)) {
            $minsUntil = $time->diffInMinutes($schoolStart);
            return [
                'status'        => 'before_school',
                'badge_color'   => 'amber',
                'title'         => 'School Starts at 09:10 AM',
                'period_number' => 0,
                'is_live'       => false,
                'message'       => "Period 1 begins in {$minsUntil} minutes (09:10 AM)",
                'schedule'      => $schedule,
            ];
        }

        if ($time->gte($schoolEnd)) {
            return [
                'status'        => 'after_school',
                'badge_color'   => 'slate',
                'title'         => 'School Dispersed for the Day',
                'period_number' => 0,
                'is_live'       => false,
                'message'       => 'Campus schedule complete. Next session tomorrow at 09:10 AM',
                'schedule'      => $schedule,
            ];
        }

        // Search through current period slots
        foreach ($schedule as $slot) {
            $slotStart = Carbon::parse($slot['start']);
            $slotEnd   = Carbon::parse($slot['end']);

            if ($time->gte($slotStart) && $time->lt($slotEnd)) {
                $totalMins = $slotStart->diffInMinutes($slotEnd);
                $elapsedMins = $slotStart->diffInMinutes($time);
                $remainingMins = $time->diffInMinutes($slotEnd);
                $progressPercent = $totalMins > 0 ? round(($elapsedMins / $totalMins) * 100) : 0;

                $dayName = $dayOfWeek === 6 ? 'Saturday (Extracurricular Day)' : 'Regular Academic Day';

                return [
                    'status'           => 'in_session',
                    'slot_type'        => $slot['type'],
                    'badge_color'      => ($slot['type'] === 'period' ? 'green' : ($slot['type'] === 'lunch' ? 'amber' : 'blue')),
                    'title'            => $slot['name'],
                    'period_number'    => $slot['period'],
                    'is_live'          => true,
                    'start_time'       => $slotStart->format('h:i A'),
                    'end_time'         => $slotEnd->format('h:i A'),
                    'elapsed_mins'     => $elapsedMins,
                    'remaining_mins'   => $remainingMins,
                    'progress_percent' => $progressPercent,
                    'day_type'         => $dayName,
                    'is_saturday'      => $dayOfWeek === 6,
                    'schedule'         => $schedule,
                ];
            }
        }

        return [
            'status'        => 'in_session',
            'badge_color'   => 'blue',
            'title'         => 'Campus In Session',
            'period_number' => 0,
            'is_live'       => true,
            'message'       => 'Academic schedule 09:15 AM - 04:30 PM',
            'schedule'      => $schedule,
        ];
    }
}
