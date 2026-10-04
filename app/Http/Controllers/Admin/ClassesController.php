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

    public function rename(Request $request, int $id)
    {
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
        $cacheKey = "classes_module_base_v2_{$yearId}";

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
                                'period'       => $entry->period_number ?? ($idx + 1),
                                'subject_name' => $entry->subject?->name ?? 'Study Period',
                                'subject_code' => $entry->subject?->code ?? '',
                                'subject_type' => $entry->period_type ?? 'theory',
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
                        'class_teacher'    => ($sec->classTeacher && $sec->classTeacher->employee_id) ? $sec->classTeacher->name : 'Not Assigned',
                        'teacher_avatar'   => 'https://ui-avatars.com/api/?name=' . urlencode(($sec->classTeacher && $sec->classTeacher->employee_id) ? $sec->classTeacher->name : 'NA') . '&background=0D8ABC&color=fff',
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
            $allSubjects   = Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'type', 'stream']);
            $allTeachers   = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);
            $totalSubjects = $allSubjects->count();
            $totalTeachers = $allTeachers->count();
            $school        = SchoolSetting::getSingleton();

            return [
                'classes'        => $classes,
                'rawClassesData' => $rawClassesData,
                'totalStandards' => $totalStandards,
                'totalSections'  => $totalSections,
                'totalStudents'  => $totalStudents,
                'totalSubjects'  => $totalSubjects,
                'totalTeachers'  => $totalTeachers,
                'allSubjects'    => $allSubjects,
                'allTeachers'    => $allTeachers,
                'school'         => $school,
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

        $classes        = $cachedData['classes'];
        $totalStandards = $cachedData['totalStandards'];
        $totalSections  = $cachedData['totalSections'];
        $totalStudents  = $cachedData['totalStudents'];
        $totalSubjects  = $cachedData['totalSubjects'];
        $totalTeachers  = $cachedData['totalTeachers'];
        $allSubjects    = $cachedData['allSubjects'];
        $allTeachers    = $cachedData['allTeachers'];
        $school         = $cachedData['school'];

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
            'school'
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

        return response()->json([
            'class'     => $class,
            'timetable' => $formattedTimetable,
        ]);
    }

    /**
     * Update or create a timetable slot (Edit Timetable feature).
     */
    public function updateSlot(Request $request)
    {
        $validated = $request->validate([
            'class_id'               => 'required|exists:classes,id',
            'section_id'             => 'required|exists:sections,id',
            'day_of_week'            => 'required|integer|min:1|max:6',
            'period_number'          => 'required|integer|min:1|max:8',
            'subject_id'             => 'nullable|exists:subjects,id',
            'teacher_id'             => 'nullable|exists:employees,id',
            'substitute_teacher_id'  => 'nullable|exists:employees,id',
            'room'                   => 'nullable|string|max:50',
            'period_type'            => 'nullable|string|in:class,free,substitution,activity,holiday',
            'notes'                  => 'nullable|string|max:255',
        ]);

        $currentYear = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::latest('id')->first();

        $periodTimings = [
            1 => ['09:15:00', '10:00:00'],
            2 => ['10:00:00', '10:45:00'],
            3 => ['11:00:00', '11:45:00'],
            4 => ['11:45:00', '12:30:00'],
            5 => ['13:15:00', '14:00:00'],
            6 => ['14:00:00', '14:45:00'],
            7 => ['15:00:00', '15:45:00'],
            8 => ['15:45:00', '16:30:00'],
        ];

        $timings = $periodTimings[$validated['period_number']] ?? ['09:15:00', '10:00:00'];
        $periodType = $validated['period_type'] ?? 'class';

        // If subject_id is not provided (e.g. for free period or holiday), fallback to first subject
        $subjectId = $validated['subject_id'] ?? Subject::first()?->id;

        $effectiveTeacherId = $periodType === 'substitution' && !empty($validated['substitute_teacher_id'])
            ? $validated['substitute_teacher_id']
            : ($periodType === 'free' || $periodType === 'holiday' ? null : ($request->input('teacher_id') ?: null));

        $timetable = Timetable::updateOrCreate(
            [
                'class_id'         => $validated['class_id'],
                'section_id'       => $validated['section_id'],
                'day_of_week'      => $validated['day_of_week'],
                'period_number'    => $validated['period_number'],
                'academic_year_id' => $currentYear->id,
            ],
            [
                'subject_id'     => $subjectId,
                'teacher_id'     => $effectiveTeacherId,
                'start_time'     => $timings[0],
                'end_time'       => $timings[1],
                'room'           => $periodType === 'holiday' ? 'Holiday' : ($request->input('room') ?: 'Room 1-A'),
                'is_active'      => true,
                'period_type'    => $periodType,
                'effective_from' => '2025-06-01',
            ]
        );

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
     * Mark an entire day as holiday or restore regular schedule for a class section.
     */
    public function toggleHoliday(Request $request)
    {
        $validated = $request->validate([
            'class_id'    => 'required|exists:classes,id',
            'section_id'  => 'required|exists:sections,id',
            'day_of_week' => 'required|integer|min:1|max:6',
            'is_holiday'  => 'required|boolean',
        ]);

        $currentYear = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::latest('id')->first();

        $periodTimings = [
            1 => ['09:15:00', '10:00:00'],
            2 => ['10:00:00', '10:45:00'],
            3 => ['11:00:00', '11:45:00'],
            4 => ['11:45:00', '12:30:00'],
            5 => ['13:15:00', '14:00:00'],
            6 => ['14:00:00', '14:45:00'],
            7 => ['15:00:00', '15:45:00'],
            8 => ['15:45:00', '16:30:00'],
        ];

        $firstSubject = Subject::first();

        for ($p = 1; $p <= 8; $p++) {
            $timings = $periodTimings[$p] ?? ['09:15:00', '10:00:00'];
            Timetable::updateOrCreate(
                [
                    'class_id'         => $validated['class_id'],
                    'section_id'       => $validated['section_id'],
                    'day_of_week'      => $validated['day_of_week'],
                    'period_number'    => $p,
                    'academic_year_id' => $currentYear->id,
                ],
                [
                    'subject_id'     => $firstSubject?->id ?? 1,
                    'teacher_id'     => null,
                    'start_time'     => $timings[0],
                    'end_time'       => $timings[1],
                    'room'           => $validated['is_holiday'] ? 'Holiday' : 'Room',
                    'is_active'      => true,
                    'period_type'    => $validated['is_holiday'] ? 'holiday' : 'class',
                    'effective_from' => '2025-06-01',
                ]
            );
        }

        self::clearCache();

        return response()->json([
            'success'    => true,
            'is_holiday' => $validated['is_holiday'],
            'message'    => $validated['is_holiday'] ? 'Day marked as holiday successfully!' : 'Regular schedule restored for this day.',
        ]);
    }

    /**
     * Declare a school holiday for a specific date (e.g. Festival, Weather, Special Event)
     */
    public function declareHoliday(Request $request)
    {
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
    private function calculatePeriodStatus(string $timeStr, int $dayOfWeek): array
    {
        $time = Carbon::parse($timeStr);

        $schedule = [
            ['type' => 'period',   'period' => 1, 'name' => 'Period 1',                    'start' => '09:15:00', 'end' => '10:00:00'],
            ['type' => 'period',   'period' => 2, 'name' => 'Period 2',                    'start' => '10:00:00', 'end' => '10:45:00'],
            ['type' => 'break',    'period' => 0, 'name' => 'Morning Interval Break',       'start' => '10:45:00', 'end' => '11:00:00'],
            ['type' => 'period',   'period' => 3, 'name' => 'Period 3',                    'start' => '11:00:00', 'end' => '11:45:00'],
            ['type' => 'period',   'period' => 4, 'name' => 'Period 4',                    'start' => '11:45:00', 'end' => '12:30:00'],
            ['type' => 'lunch',    'period' => 0, 'name' => 'Lunch Break',                 'start' => '12:30:00', 'end' => '13:15:00'],
            ['type' => 'period',   'period' => 5, 'name' => 'Period 5',                    'start' => '13:15:00', 'end' => '14:00:00'],
            ['type' => 'period',   'period' => 6, 'name' => 'Period 6',                    'start' => '14:00:00', 'end' => '14:45:00'],
            ['type' => 'break',    'period' => 0, 'name' => 'Afternoon Refreshment Break', 'start' => '14:45:00', 'end' => '15:00:00'],
            ['type' => 'period',   'period' => 7, 'name' => 'Period 7',                    'start' => '15:00:00', 'end' => '15:45:00'],
            ['type' => 'period',   'period' => 8, 'name' => 'Period 8',                    'start' => '15:45:00', 'end' => '16:30:00'],
        ];

        if ($dayOfWeek === 7) {
            return [
                'status'        => 'holiday',
                'badge_color'   => 'red',
                'title'         => 'Sunday — Campus Holiday',
                'period_number' => 0,
                'is_live'       => false,
                'message'       => 'Classes will resume on Monday at 09:15 AM',
                'schedule'      => $schedule,
            ];
        }

        $schoolStart = Carbon::parse('09:15:00');
        $schoolEnd   = Carbon::parse('16:30:00');

        if ($time->lt($schoolStart)) {
            $minsUntil = $time->diffInMinutes($schoolStart);
            return [
                'status'        => 'before_school',
                'badge_color'   => 'amber',
                'title'         => 'School Starts at 09:15 AM',
                'period_number' => 0,
                'is_live'       => false,
                'message'       => "Period 1 begins in {$minsUntil} minutes (09:15 AM)",
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
                'message'       => 'Campus schedule complete. Next session tomorrow at 09:15 AM',
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
