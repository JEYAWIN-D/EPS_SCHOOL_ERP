<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Notice;
use App\Models\NoticeRead;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Substitution;
use App\Models\TeacherSubjectAllocation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AcademicController extends Controller
{
    public function index()
    {
        $currentYear  = AcademicYear::current();
        $classes = Classes::active()->withCount(['sections' => function ($q) {
            $q->where('is_active', true);
        }])->get();
        $subjects     = Subject::where('is_active', true)->count();
        $teachers     = Employee::where('is_active', true)
            ->where('employee_type', 'teaching')->count()
            ?: Employee::where('is_active', true)->count();
        $pendingHomework   = DB::table('homework')->where('due_date', '>=', today()->toDateString())->count();
        $activeNotices    = DB::table('notices')->where('is_published', true)
            ->where(fn($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', today()))
            ->count();
        return view('academics.index', compact(
            'currentYear', 'classes', 'subjects', 'teachers',
            'pendingHomework', 'activeNotices'
        ));
    }

    public function timetable(Request $request)
    {
        $user = auth()->user();
        $authEmployeeId = \App\Http\Controllers\Admin\PeriodReportController::resolveAuthEmployeeId();
        $canManage = \App\Http\Controllers\Admin\ClassesController::canManageTimetable($user);

        // Find classes where this teacher is assigned periods
        $teacherClassIds = collect();
        if ($authEmployeeId) {
            $teacherClassIds = \App\Models\Timetable::where('teacher_id', $authEmployeeId)
                ->where('is_active', true)
                ->pluck('class_id')
                ->unique()
                ->filter();
        }

        // If no class is explicitly requested, auto-select the teacher's primary assigned class
        if (!$request->class_id && $authEmployeeId && $teacherClassIds->isNotEmpty()) {
            $firstClassId = $teacherClassIds->first();
            $firstSlot = \App\Models\Timetable::where('teacher_id', $authEmployeeId)
                ->where('class_id', $firstClassId)
                ->where('is_active', true)
                ->first();
            $request->merge([
                'class_id'   => $firstClassId,
                'section_id' => $firstSlot?->section_id,
            ]);
        }

        $classes  = Classes::active()->orderBy('sort_order')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $allTeachers = Employee::where('is_active', true)->orderBy('first_name')->get();
        $teachers = $allTeachers->filter(fn($t) => ($t->employee_type ?? 'teaching') === 'teaching');
        if ($teachers->isEmpty()) {
            $teachers = $allTeachers;
        }

        // Partition for conditional selection boxes
        $theorySubjects = $subjects->filter(fn($s) => in_array($s->type, ['theory', 'language']));
        $activitySubjects = $subjects->filter(fn($s) => $s->type === 'activity' || $s->is_coscholastic);
        $practicalSubjects = $subjects->filter(fn($s) => $s->type === 'practical' || stripos($s->name, 'computer') !== false || stripos($s->name, 'lab') !== false);

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

        $sections = collect();
        $timetable = [];

        if ($request->class_id) {
            $sections = Section::where('class_id', $request->class_id)->orderBy('name')->get();

            if (!$request->section_id && $sections->isNotEmpty()) {
                $teacherSecId = $authEmployeeId ? \App\Models\Timetable::where('teacher_id', $authEmployeeId)
                    ->where('class_id', $request->class_id)
                    ->value('section_id') : null;
                $request->merge(['section_id' => $teacherSecId ?: $sections->first()->id]);
            }

            $timetable = \App\Models\Timetable::with(['subject', 'teacher', 'class', 'section'])
                ->where('class_id', $request->class_id)
                ->when($request->section_id, fn($q, $v) => $q->where('section_id', $v))
                ->orderBy('day_of_week')->orderBy('start_time')
                ->get()->groupBy('day_of_week');
        }

        $previousReports = collect();
        if ($authEmployeeId) {
            $previousReports = \App\Models\PeriodReport::with(['subject', 'class', 'section'])
                ->where('teacher_id', $authEmployeeId)
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->take(50)
                ->get();
        }

        $formattedPreviousReports = $previousReports->map(function ($r) {
            return [
                'id'             => $r->id,
                'timetable_id'   => $r->timetable_id,
                'date'           => $r->date ? $r->date->format('Y-m-d') : '',
                'formatted_date' => $r->date ? $r->date->format('d M Y') : '',
                'day'            => $r->day ?: ($r->date ? $r->date->format('l') : ''),
                'class_id'       => $r->class_id,
                'class_name'     => $r->class?->name ?? '',
                'section_id'     => $r->section_id,
                'section_name'   => $r->section?->name ?? '',
                'subject_id'     => $r->subject_id,
                'subject_name'   => $r->subject?->name ?? 'Subject',
                'period_number'  => $r->period_number,
                'present_count'  => $r->student_present_count,
                'absent_count'   => $r->student_absent_count,
                'description'    => $r->description,
            ];
        })->values();

        return view('academics.timetable', compact(
            'classes', 'sections', 'timetable', 'subjects', 'teachers',
            'theorySubjects', 'activitySubjects', 'practicalSubjects',
            'activityTeachers', 'labTeachers',
            'authEmployeeId', 'teacherClassIds', 'previousReports', 'formattedPreviousReports'
        ));
    }

    public function saveTimetable(Request $request)
    {
        if (!\App\Http\Controllers\Admin\ClassesController::canManageTimetable(auth()->user())) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Principal and view-only roles do not have permission to modify timetable entries.'
                ], 403);
            }
            abort(403, 'Unauthorized: Principal and view-only roles do not have permission to modify timetable entries.');
        }

        $request->validate([
            'entries'                 => 'required|array',
            'entries.*.class_id'      => 'required|exists:classes,id',
            'entries.*.day_of_week'   => 'required',
            'entries.*.period_number' => 'required|integer',
        ]);

        $dayMap = [
            'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
            'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7,
        ];

        $currentYear = \App\Models\AcademicYear::current() ?? \App\Models\AcademicYear::first();

        // Validate consecutive assignments across entries
        foreach ($request->entries as $entry) {
            $dayOfWeek = $entry['day_of_week'];
            if (isset($dayMap[$dayOfWeek])) {
                $dayOfWeek = $dayMap[$dayOfWeek];
            } else {
                $dayOfWeek = (int) $dayOfWeek;
            }

            $teacherId = $entry['teacher_id'] ?? ($entry['employee_id'] ?? null);
            $periodType = $entry['period_type'] ?? 'class';

            if ($teacherId && !in_array($periodType, ['free', 'holiday'])) {
                $consecutiveConflict = \App\Http\Controllers\Admin\ClassesController::checkConsecutiveTeacherConflict(
                    (int) $entry['class_id'],
                    (int) ($entry['section_id'] ?? 0),
                    (int) $dayOfWeek,
                    (int) $currentYear->id,
                    [[
                        'period_number' => (int) $entry['period_number'],
                        'teacher_id'    => (int) $teacherId,
                        'period_type'   => $periodType,
                    ]]
                );

                if ($consecutiveConflict) {
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json([
                            'success'              => false,
                            'consecutive_conflict' => true,
                            'message'              => $consecutiveConflict,
                        ], 422);
                    }
                    return back()->with('error', $consecutiveConflict);
                }

                // Check cross-class double booking
                if (!$request->boolean('force_conflict')) {
                    $conflict = \App\Models\Timetable::where('academic_year_id', $currentYear->id)
                        ->where('day_of_week', $dayOfWeek)
                        ->where('period_number', (int) $entry['period_number'])
                        ->where('teacher_id', (int) $teacherId)
                        ->where('is_active', true)
                        ->whereNotIn('period_type', ['holiday', 'free'])
                        ->where(function ($q) use ($entry) {
                            $q->where('class_id', '!=', $entry['class_id']);
                            if (!empty($entry['section_id'])) {
                                $q->orWhere('section_id', '!=', $entry['section_id']);
                            }
                        })
                        ->with(['class', 'section', 'subject', 'teacher'])
                        ->first();

                    if ($conflict) {
                        $teacherName = $conflict->teacher ? ($conflict->teacher->first_name . ' ' . $conflict->teacher->last_name) : 'Faculty member';
                        $conflictClass = ($conflict->class?->name ?? 'Class') . ' - Section ' . ($conflict->section?->name ?? 'A');
                        $conflictSubject = $conflict->subject?->name ?? 'another session';
                        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
                        $dayName = $dayNames[$dayOfWeek] ?? 'this day';

                        $msg = "Teacher Conflict: {$teacherName} is already assigned to {$conflictClass} ({$conflictSubject}) during Period {$entry['period_number']} on {$dayName}.";
                        if ($request->wantsJson() || $request->ajax()) {
                            return response()->json([
                                'success'  => false,
                                'conflict' => true,
                                'message'  => $msg,
                            ], 422);
                        }
                        return back()->with('error', $msg);
                    }
                }
            }
        }

        foreach ($request->entries as $entry) {
            $dayOfWeek = $entry['day_of_week'];
            if (isset($dayMap[$dayOfWeek])) {
                $dayOfWeek = $dayMap[$dayOfWeek];
            } else {
                $dayOfWeek = (int) $dayOfWeek;
            }

            $teacherId = $entry['teacher_id'] ?? ($entry['employee_id'] ?? null);
            $subjectId = $entry['subject_id'] ?? null;
            $periodType = $entry['period_type'] ?? 'class';

            \App\Models\Timetable::updateOrCreate(
                [
                    'class_id'      => $entry['class_id'],
                    'section_id'    => $entry['section_id'] ?? null,
                    'day_of_week'   => $dayOfWeek,
                    'period_number' => $entry['period_number'],
                ],
                [
                    'period_type'   => $periodType,
                    'subject_id'    => $periodType === 'class' ? $subjectId : null,
                    'teacher_id'    => $periodType === 'class' ? $teacherId : null,
                    'start_time'    => $entry['start_time'] ?? null,
                    'end_time'      => $entry['end_time'] ?? null,
                    'is_active'     => true,
                ]
            );

            // Also ensure TeacherSubjectAllocation exists if both subject and teacher are set
            if ($subjectId && $teacherId && $periodType === 'class') {
                $currentYear = \App\Models\AcademicYear::current() ?? \App\Models\AcademicYear::first();
                \App\Models\TeacherSubjectAllocation::firstOrCreate([
                    'subject_id'       => $subjectId,
                    'employee_id'      => $teacherId,
                    'class_id'         => $entry['class_id'],
                ], [
                    'academic_year_id' => $currentYear?->id,
                ]);
            }
        }

        return back()->with('success', 'Timetable period updated successfully.');
    }

    public function subjects(Request $request)
    {
        $subjects = Subject::with(['class', 'allocations.employee'])
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->orderBy('name')->paginate(25)->withQueryString();

        $teachers = Employee::where('is_active', true)->where('employee_type', 'teaching')->orderBy('first_name')->get();
        if ($teachers->isEmpty()) {
            $teachers = Employee::where('is_active', true)->orderBy('first_name')->get();
        }
        $classes = Classes::active()->orderBy('name')->get();

        return view('academics.subjects', compact('subjects', 'teachers', 'classes'));
    }

    public function syllabus(Request $request)
    {
        $currentYear = \App\Models\AcademicYear::current() ?? \App\Models\AcademicYear::first();
        
        // Fetch all active classes with their overall syllabus completion progress
        $classes = Classes::active()->get()->map(function ($c) use ($currentYear) {
            $base = \App\Models\Syllabus::where('class_id', $c->id);
            $total = (clone $base)->count();
            $completed = (clone $base)->where('status', 'completed')->count();
            $inProgress = (clone $base)->where('status', 'in_progress')->count();

            $c->total_chapters = $total;
            $c->completed_chapters = $completed;
            $c->in_progress_chapters = $inProgress;
            $c->progress_pct = $total > 0 ? round(($completed / $total) * 100) : 0;
            return $c;
        });

        // Determine selected class (default to request class_id, or first class in list)
        $selectedClass = null;
        if ($request->filled('class_id')) {
            $selectedClass = $classes->firstWhere('id', (int)$request->class_id);
        }
        if (!$selectedClass && $classes->isNotEmpty()) {
            $selectedClass = $classes->first();
        }

        $allSubjects = Subject::where('is_active', true)->orderBy('name')->get();
        $classSubjects = collect();
        $syllabus = collect();
        $termStats = [
            'Term 1' => ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'pct' => 0],
            'Term 2' => ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'pct' => 0],
        ];
        $classOverallStats = [
            'total' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'pct' => 0,
        ];

        if ($selectedClass) {
            // Get ONLY the subjects that exist for this standard
            $classSubjectIds = \App\Models\Syllabus::where('class_id', $selectedClass->id)
                ->pluck('subject_id')
                ->unique();

            $classSubjects = Subject::whereIn('id', $classSubjectIds)
                ->orderBy('name')
                ->get()
                ->map(function ($s) use ($selectedClass) {
                    $sBase = \App\Models\Syllabus::where('class_id', $selectedClass->id)->where('subject_id', $s->id);
                    $sTotal = (clone $sBase)->count();
                    $sCompleted = (clone $sBase)->where('status', 'completed')->count();
                    $sInProgress = (clone $sBase)->where('status', 'in_progress')->count();

                    $s->total_chapters = $sTotal;
                    $s->completed_chapters = $sCompleted;
                    $s->in_progress_chapters = $sInProgress;
                    $s->progress_pct = $sTotal > 0 ? round(($sCompleted / $sTotal) * 100) : 0;
                    return $s;
                });

            // Calculate strictly Term 1 and Term 2 statistics for this class
            foreach (['Term 1', 'Term 2'] as $t) {
                $tBase = \App\Models\Syllabus::where('class_id', $selectedClass->id)->where('term', $t);
                $tTotal = (clone $tBase)->count();
                $tCompleted = (clone $tBase)->where('status', 'completed')->count();
                $tInProgress = (clone $tBase)->where('status', 'in_progress')->count();

                $termStats[$t] = [
                    'total'       => $tTotal,
                    'completed'   => $tCompleted,
                    'in_progress' => $tInProgress,
                    'pending'     => max(0, $tTotal - $tCompleted - $tInProgress),
                    'pct'         => $tTotal > 0 ? round(($tCompleted / $tTotal) * 100) : 0,
                ];
            }

            // Overall class stats
            $cTotal = \App\Models\Syllabus::where('class_id', $selectedClass->id)->count();
            $cCompleted = \App\Models\Syllabus::where('class_id', $selectedClass->id)->where('status', 'completed')->count();
            $cInProgress = \App\Models\Syllabus::where('class_id', $selectedClass->id)->where('status', 'in_progress')->count();

            $classOverallStats = [
                'total'       => $cTotal,
                'completed'   => $cCompleted,
                'in_progress' => $cInProgress,
                'pending'     => max(0, $cTotal - $cCompleted - $cInProgress),
                'pct'         => $cTotal > 0 ? round(($cCompleted / $cTotal) * 100) : 0,
            ];

            // Fetch the filtered syllabus list
            $syllabus = \App\Models\Syllabus::with(['subject', 'class'])
                ->where('class_id', $selectedClass->id)
                ->when($request->filled('subject_id'), fn($q) => $q->where('subject_id', $request->subject_id))
                ->when($request->filled('term') && in_array($request->term, ['Term 1', 'Term 2']), fn($q) => $q->where('term', $request->term))
                ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
                ->orderBy('term')
                ->orderBy('sort_order')
                ->orderBy('chapter_number')
                ->get();
        }

        return view('academics.syllabus', compact(
            'classes',
            'selectedClass',
            'classSubjects',
            'allSubjects',
            'syllabus',
            'termStats',
            'classOverallStats'
        ));
    }

    public function batchStoreSyllabus(Request $request)
    {
        $request->validate([
            'class_id'    => 'required|exists:classes,id',
            'subject_id'  => 'required|exists:subjects,id',
            'term'        => 'required|string|in:Term 1,Term 2,Term 3',
            'topics_list' => 'required|string|min:3',
        ]);

        $currentYear = \App\Models\AcademicYear::current() ?? \App\Models\AcademicYear::first();
        $lines = preg_split('/\r\n|\r|\n/', trim($request->topics_list));
        $lines = array_filter(array_map('trim', $lines));

        $maxOrder = \App\Models\Syllabus::where('class_id', $request->class_id)
            ->where('subject_id', $request->subject_id)
            ->max('sort_order') ?? 0;

        $created = 0;
        foreach ($lines as $line) {
            if (empty($line)) continue;
            $maxOrder++;
            
            // Try extracting chapter number if written like "1. Title" or "Chapter 1: Title"
            $chapterNum = (string)$maxOrder;
            $title = $line;
            if (preg_match('/^(?:Chapter\s*)?([0-9A-Za-z]+)[\.\:\-]\s*(.+)$/i', $line, $m)) {
                $chapterNum = $m[1];
                $title = trim($m[2]);
            }

            \App\Models\Syllabus::create([
                'class_id'         => $request->class_id,
                'subject_id'       => $request->subject_id,
                'academic_year_id' => $currentYear?->id ?? 1,
                'chapter_number'   => $chapterNum,
                'chapter_title'    => $title,
                'term'             => $request->term,
                'status'           => 'pending',
                'sort_order'       => $maxOrder,
            ]);
            $created++;
        }

        return back()->with('success', "{$created} chapters added to {$request->term}.");
    }

    public function printSyllabus(Request $request)
    {
        $request->validate(['class_id' => 'required|exists:classes,id']);
        $class = Classes::findOrFail($request->class_id);
        $school = \App\Models\SchoolSetting::first();
        $currentYear = \App\Models\AcademicYear::current() ?? \App\Models\AcademicYear::first();

        $syllabus = \App\Models\Syllabus::with(['subject'])
            ->where('class_id', $class->id)
            ->when($request->filled('term'), fn($q) => $q->where('term', $request->term))
            ->when($request->filled('subject_id'), fn($q) => $q->where('subject_id', $request->subject_id))
            ->orderBy('term')
            ->orderBy('subject_id')
            ->orderBy('sort_order')
            ->get()
            ->groupBy(['term', 'subject.name']);

        return view('academics.syllabus-print', compact('class', 'school', 'currentYear', 'syllabus'));
    }

    public function saveSyllabus(Request $request)
    {
        $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'subject_id'    => 'required|exists:subjects,id',
            'chapter_title' => 'required|string|max:200',
            'chapter_number'=> 'nullable|string|max:10',
            'topics'        => 'nullable|string',
            'description'   => 'nullable|string',
            'status'        => 'nullable|in:pending,in_progress,completed',
            'planned_date'  => 'nullable|date',
            'term'          => 'nullable|string|max:50',
        ]);

        $currentYear = \App\Models\AcademicYear::current() ?? \App\Models\AcademicYear::first();

        // Determine sort order (append to end)
        $maxOrder = \App\Models\Syllabus::where('class_id', $request->class_id)
            ->where('subject_id', $request->subject_id)
            ->max('sort_order') ?? 0;

        \App\Models\Syllabus::create(array_merge(
            $request->only(['class_id', 'subject_id', 'chapter_number', 'chapter_title', 'topics', 'description', 'status', 'planned_date', 'term']),
            [
                'academic_year_id' => $currentYear?->id ?? 1,
                'sort_order'       => $maxOrder + 1,
                'status'           => $request->status ?? 'pending',
                'term'             => $request->term ?: 'Term 1',
            ]
        ));

        return back()->with('success', 'Chapter added to syllabus.');
    }

    public function updateSyllabus(Request $request, int $id)
    {
        $request->validate([
            'chapter_title' => 'required|string|max:200',
            'chapter_number'=> 'nullable|string|max:10',
            'topics'        => 'nullable|string',
            'description'   => 'nullable|string',
            'status'        => 'nullable|in:pending,in_progress,completed',
            'planned_date'  => 'nullable|date',
            'completed_date'=> 'nullable|date',
            'term'          => 'nullable|string|max:50',
        ]);

        $syllabus = \App\Models\Syllabus::findOrFail($id);
        $syllabus->update($request->only([
            'chapter_number', 'chapter_title', 'topics', 'description',
            'status', 'planned_date', 'completed_date', 'term',
        ]));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Chapter updated.']);
        }
        return back()->with('success', 'Chapter updated.');
    }

    public function deleteSyllabus(int $id)
    {
        \App\Models\Syllabus::findOrFail($id)->delete();
        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Chapter removed.');
    }

    public function updateSyllabusStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:pending,in_progress,completed']);
        $syllabus = \App\Models\Syllabus::findOrFail($id);
        $updates  = ['status' => $request->status];
        if ($request->status === 'completed' && !$syllabus->completed_date) {
            $updates['completed_date'] = now()->toDateString();
        }
        $syllabus->update($updates);
        return response()->json(['success' => true, 'status' => $request->status]);
    }

    public function reorderSyllabus(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*.id' => 'required|integer', 'order.*.sort_order' => 'required|integer']);
        foreach ($request->order as $item) {
            \App\Models\Syllabus::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }
        return response()->json(['success' => true]);
    }

    public function uploadSyllabusDocument(Request $request, int $id)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:10240',
            'term'     => 'nullable|string|max:50',
        ]);
        $syllabus = \App\Models\Syllabus::findOrFail($id);
        $path = $request->file('document')->store('syllabus-docs', 'public');
        $syllabus->update([
            'document_path' => $path,
            'term'          => $request->term,
        ]);
        return back()->with('success', 'Syllabus document uploaded.');
    }


    public function homework(Request $request)
    {
        $classes  = Classes::active()->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $homework = \App\Models\Homework::with(['class', 'section', 'subject'])
            ->when($request->class_id, fn($q, $v) => $q->where('class_id', $v))
            ->when($request->subject_id, fn($q, $v) => $q->where('subject_id', $v))
            ->when($request->date, fn($q, $v) => $q->whereDate('due_date', $v))
            ->orderByDesc('created_at')
            ->paginate(18)
            ->withQueryString();

        return view('academics.homework', compact('classes', 'subjects', 'homework'));
    }

    public function saveHomework(Request $request)
    {
        $request->validate([
            'class_id'   => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'title'      => 'nullable|string|max:200',
            'description'=> 'required|string|min:5',
            'due_date'   => 'required|date',
            'max_score'  => 'nullable|integer|min:0',
            'attachment' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('homework/attachments', 'public');
        }
        \App\Models\Homework::create(array_merge($request->only(['class_id', 'section_id', 'subject_id', 'title', 'description', 'due_date', 'max_score']), [
            'assigned_date' => $request->assigned_date ?: now()->toDateString(),
            'attachment'    => $attachmentPath,
            'assigned_by'   => Auth::id(),
            'is_active'     => true,
        ]));
        return back()->with('success', 'Homework assigned.');
    }

    public function holidays()
    {
        $currentYear   = AcademicYear::current();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $holidays = Holiday::when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->orderBy('date')->paginate(30);
        return view('academics.holidays', compact('holidays', 'currentYear', 'academicYears'));
    }

    public function addHoliday(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'date'             => 'required|date',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);
        Holiday::firstOrCreate(
            ['date' => $request->date, 'academic_year_id' => $request->academic_year_id],
            $request->only(['name', 'description', 'type'])
        );
        return back()->with('success', 'Holiday added.');
    }

    public function deleteHoliday(int $id)
    {
        Holiday::findOrFail($id)->delete();
        return back()->with('success', 'Holiday removed.');
    }

    public function subjectsManage(Request $request)
    {
        $subjects = Subject::withCount(['allocations'])->orderBy('name')->paginate(20)->withQueryString();
        return view('academics.subjects-manage', compact('subjects'));
    }

    public function storeSubject(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => 'nullable|string|max:20',
            'type'         => 'nullable|in:theory,practical,activity,language',
            'credit_hours' => 'nullable|integer|min:0|max:40',
            'class_id'     => 'nullable|exists:classes,id',
            'teacher_id'   => 'nullable|exists:employees,id',
            'term'         => 'nullable|string|in:Term 1,Term 2',
            'first_topic'  => 'nullable|string|max:200',
        ]);

        $subject = Subject::firstOrCreate(
            ['name' => $request->name],
            array_merge(
                $request->only(['code', 'class_id', 'medium', 'language_type', 'board_curriculum', 'credit_hours', 'stream']),
                [
                    'type'            => $request->type ?: 'theory',
                    'code'            => $request->code ?: strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $request->name), 0, 4)),
                    'is_active'       => true,
                    'is_elective'     => $request->boolean('is_elective'),
                    'is_coscholastic' => $request->boolean('is_coscholastic'),
                ]
            )
        );

        $currentYear = \App\Models\AcademicYear::current() ?? \App\Models\AcademicYear::first();

        // Assign handling teacher if selected
        if ($request->filled('teacher_id')) {
            \App\Models\TeacherSubjectAllocation::updateOrCreate(
                [
                    'subject_id' => $subject->id,
                    'class_id'   => $request->class_id ?: 1,
                ],
                [
                    'employee_id'      => $request->teacher_id,
                    'academic_year_id' => $currentYear?->id,
                ]
            );
        }

        if ($request->class_id) {
            $term = $request->term ?: 'Term 1';
            $title = $request->first_topic ?: ($request->name . ' - Introduction & Basics');

            \App\Models\Syllabus::firstOrCreate(
                [
                    'class_id'   => $request->class_id,
                    'subject_id' => $subject->id,
                    'term'       => $term,
                ],
                [
                    'academic_year_id' => $currentYear?->id ?? 1,
                    'chapter_number'   => '1',
                    'chapter_title'    => $title,
                    'status'           => 'pending',
                    'sort_order'       => 1,
                ]
            );

            return redirect()->route('academics.syllabus', [
                'class_id'   => $request->class_id,
                'subject_id' => $subject->id,
                'term'       => $term,
            ])->with('success', "Subject '{$subject->name}' added to this standard.");
        }

        return back()->with('success', 'Subject added successfully.');
    }

    public function updateSubject(Request $request, int $id)
    {
        $subject = Subject::findOrFail($id);
        $subject->update(array_merge(
            $request->only(['name', 'code', 'type', 'class_id', 'medium', 'language_type', 'board_curriculum', 'credit_hours', 'stream']),
            [
                'is_elective'     => $request->boolean('is_elective'),
                'is_coscholastic' => $request->boolean('is_coscholastic'),
            ]
        ));

        // Sync or assign handling teacher if provided
        if ($request->filled('teacher_id')) {
            $currentYear = \App\Models\AcademicYear::current() ?? \App\Models\AcademicYear::first();
            \App\Models\TeacherSubjectAllocation::updateOrCreate(
                [
                    'subject_id' => $subject->id,
                    'class_id'   => $subject->class_id ?: 1,
                ],
                [
                    'employee_id'      => $request->teacher_id,
                    'academic_year_id' => $currentYear?->id,
                ]
            );
        }

        return back()->with('success', 'Subject updated successfully.');
    }

    public function toggleSubject(int $id)
    {
        $s = Subject::findOrFail($id);
        $s->update(['is_active' => !$s->is_active]);
        return back()->with('success', 'Subject ' . ($s->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function teacherAllocation(Request $request)
    {
        $teachers       = Employee::where('employee_type', 'teaching')->where('is_active', true)->orderBy('first_name')->get();
        $subjects       = Subject::where('is_active', true)->orderBy('name')->get();
        $classes        = Classes::active()->get();
        $sections       = Section::orderBy('name')->get();
        $academicYears  = AcademicYear::orderByDesc('start_date')->get();
        $currentYear    = AcademicYear::current();
        $filterYearId   = $request->academic_year_id ?? $currentYear?->id;

        $allocations = TeacherSubjectAllocation::with(['employee', 'subject', 'class', 'section', 'academicYear'])
            ->when($filterYearId, fn($q) => $q->where('academic_year_id', $filterYearId))
            ->when($request->teacher_id, fn($q, $v) => $q->where('employee_id', $v))
            ->when($request->class_id,   fn($q, $v) => $q->where('class_id', $v))
            ->latest()->paginate(25)->withQueryString();

        return view('academics.teacher-allocation', compact(
            'teachers', 'subjects', 'classes', 'sections',
            'allocations', 'academicYears', 'filterYearId'
        ));
    }

    public function allocationHistory(Request $request)
    {
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $teachers      = Employee::where('employee_type', 'teaching')->orderBy('first_name')->get();
        $subjects      = Subject::orderBy('name')->get();
        $classes       = Classes::orderBy('name')->get();

        $history = TeacherSubjectAllocation::with(['employee', 'subject', 'class', 'section', 'academicYear'])
            ->when($request->academic_year_id, fn($q, $v) => $q->where('academic_year_id', $v))
            ->when($request->teacher_id,       fn($q, $v) => $q->where('employee_id', $v))
            ->when($request->subject_id,       fn($q, $v) => $q->where('subject_id', $v))
            ->when($request->class_id,         fn($q, $v) => $q->where('class_id', $v))
            ->orderByDesc('academic_year_id')
            ->paginate(30)->withQueryString();

        return view('academics.allocation-history', compact('history', 'academicYears', 'teachers', 'subjects', 'classes'));
    }

    public function saveAllocation(Request $request)
    {
        $request->validate(['employee_id' => 'required|exists:employees,id', 'subject_id' => 'required|exists:subjects,id', 'class_id' => 'required|exists:classes,id']);
        $currentYear = AcademicYear::current();
        TeacherSubjectAllocation::firstOrCreate([
            'employee_id' => $request->employee_id,
            'subject_id'  => $request->subject_id,
            'class_id'    => $request->class_id,
            'section_id'  => $request->section_id ?? null,
            'academic_year_id' => $currentYear?->id,
        ]);
        return back()->with('success', 'Teacher assigned.');
    }

    public function deleteAllocation(int $id)
    {
        TeacherSubjectAllocation::findOrFail($id)->delete();
        return back()->with('success', 'Allocation removed.');
    }

    public function academicYear()
    {
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        return view('academics.academic-year', compact('academicYears'));
    }

    public function saveAcademicYear(Request $request)
    {
        $request->validate(['name' => 'required|string|max:20', 'start_date' => 'required|date', 'end_date' => 'required|date|after:start_date']);
        if ($request->is_current) AcademicYear::query()->update(['is_current' => false]);
        AcademicYear::create(array_merge($request->only(['name', 'start_date', 'end_date']), ['is_current' => (bool)$request->is_current]));
        return back()->with('success', 'Academic year created.');
    }

    public function setCurrentYear(int $id)
    {
        AcademicYear::query()->update(['is_current' => false]);
        AcademicYear::findOrFail($id)->update(['is_current' => true]);
        return back()->with('success', 'Current academic year updated.');
    }

    public function yearArchive(Request $request)
    {
        $academicYears   = AcademicYear::orderByDesc('start_date')->get();
        $selectedYear    = $request->year_id
            ? AcademicYear::findOrFail($request->year_id)
            : $academicYears->where('is_current', false)->first();

        $enrollments = collect();
        $classSummary = collect();
        if ($selectedYear) {
            $classes = \App\Models\Classes::withCount([
                'enrollments as student_count' => fn($q) => $q->where('academic_year_id', $selectedYear->id),
            ])->get()->filter(fn($c) => $c->student_count > 0);

            foreach ($classes as $class) {
                $classSummary->push([
                    'class'         => $class,
                    'total'         => $class->student_count,
                    'promoted'      => \App\Models\StudentEnrollment::where('class_id', $class->id)
                                        ->where('academic_year_id', $selectedYear->id)
                                        ->where('promoted', true)->count(),
                    'detained'      => \App\Models\StudentEnrollment::where('class_id', $class->id)
                                        ->where('academic_year_id', $selectedYear->id)
                                        ->where('promoted', false)->count(),
                ]);
            }
        }

        return view('academics.year-archive', compact('academicYears', 'selectedYear', 'classSummary'));
    }

    public function yearArchiveStudents(Request $request, int $yearId)
    {
        $year        = AcademicYear::findOrFail($yearId);
        $classes     = \App\Models\Classes::active()->get();
        $enrollments = \App\Models\StudentEnrollment::with(['student', 'class', 'section'])
            ->where('academic_year_id', $yearId)
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->orderBy('class_id')->orderBy('roll_number')
            ->paginate(50);

        return view('academics.year-archive-students', compact('year', 'classes', 'enrollments'));
    }

    // ── Notice Board ──────────────────────────────────────────

    public function notices(Request $request)
    {
        $notices = Notice::with(['createdBy', 'targetClass', 'reads'])
            ->when($request->type, fn($q, $v) => $q->where('notice_type', $v))
            ->when($request->audience, fn($q, $v) => $q->where('target_audience', $v))
            ->latest('publish_date')->paginate(20)->withQueryString();
        $classes = Classes::active()->get();
        return view('academics.notices', compact('notices', 'classes'));
    }

    public function createNotice()
    {
        $classes = Classes::active()->get();
        return view('academics.notice-form', compact('classes'));
    }

    public function storeNotice(Request $request)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:200',
            'content'          => 'required|string',
            'notice_type'      => 'required|in:general,circular,academic,exam,fee,event',
            'target_audience'  => 'required|in:all,students,staff,parents,class_specific',
            'publish_date'     => 'required|date',
            'expiry_date'      => 'nullable|date|after:publish_date',
            'target_class_id'  => 'nullable|exists:classes,id',
        ]);

        Notice::create(array_merge($data, [
            'created_by'   => Auth::id(),
            'is_published' => $request->boolean('is_published'),
        ]));

        return redirect()->route('academics.notices')->with('success', 'Notice created.');
    }

    public function editNotice(int $id)
    {
        $notice  = Notice::findOrFail($id);
        $classes = Classes::active()->get();
        return view('academics.notice-edit', compact('notice', 'classes'));
    }

    public function updateNotice(Request $request, int $id)
    {
        $notice = Notice::findOrFail($id);
        $notice->update(array_merge($request->only(['title', 'content', 'notice_type', 'target_audience', 'publish_date', 'expiry_date', 'target_class_id']), [
            'is_published' => $request->boolean('is_published'),
        ]));
        return back()->with('success', 'Notice updated.');
    }

    public function deleteNotice(int $id)
    {
        Notice::findOrFail($id)->delete();
        return back()->with('success', 'Notice deleted.');
    }

    public function markNoticeRead(int $id)
    {
        NoticeRead::firstOrCreate(['notice_id' => $id, 'user_id' => Auth::id()], ['read_at' => now()]);
        return back();
    }

    public function noticeReadReceipts(int $id)
    {
        $notice = Notice::with('createdBy')->findOrFail($id);
        $reads  = NoticeRead::with('user')
            ->where('notice_id', $id)
            ->orderBy('read_at')
            ->paginate(50);

        // Audience total (approximation for %age display)
        $totalUsers = \App\Models\User::where('is_active', true)->count();

        return view('academics.notice-read-receipts', compact('notice', 'reads', 'totalUsers'));
    }

    // ── Substitutions ─────────────────────────────────────────

    public function substitutions(Request $request)
    {
        $classes   = Classes::active()->get();
        $teachers  = Employee::where('employee_type', 'teaching')->where('is_active', true)->orderBy('first_name')->get();
        $subjects  = Subject::where('is_active', true)->orderBy('name')->get();
        $date      = $request->date ?? today()->toDateString();

        $substitutions = Substitution::with(['absentTeacher', 'substituteTeacher', 'class', 'section', 'subject'])
            ->when($request->date, fn($q, $v) => $q->where('date', $v))
            ->when(!$request->date, fn($q) => $q->where('date', today()))
            ->orderBy('period_number')->get();

        return view('academics.substitutions', compact('classes', 'teachers', 'subjects', 'substitutions', 'date'));
    }

    public function storeSubstitution(Request $request)
    {
        $request->validate([
            'date'                    => 'required|date',
            'absent_teacher_id'       => 'required|exists:employees,id',
            'substitute_teacher_id'   => 'required|exists:employees,id|different:absent_teacher_id',
            'class_id'                => 'required|exists:classes,id',
            'period_number'           => 'required|integer|min:1|max:12',
        ]);

        Substitution::create(array_merge($request->only(['date', 'absent_teacher_id', 'substitute_teacher_id', 'class_id', 'section_id', 'subject_id', 'period_number', 'start_time', 'end_time', 'remarks']), [
            'arranged_by' => Auth::id(),
        ]));

        return back()->with('success', 'Substitution recorded.');
    }

    public function deleteSubstitution(int $id)
    {
        Substitution::findOrFail($id)->delete();
        return back()->with('success', 'Substitution removed.');
    }

    // ── PDFs ──────────────────────────────────────────────────

    public function timetablePdf(Request $request)
    {
        $request->validate(['class_id' => 'required|exists:classes,id']);
        $class    = Classes::findOrFail($request->class_id);
        $sections = Section::where('class_id', $class->id)->get();
        $timetable = \App\Models\Timetable::with(['subject', 'teacher'])
            ->where('class_id', $class->id)
            ->when($request->section_id, fn($q, $v) => $q->where('section_id', $v))
            ->orderBy('day_of_week')->orderBy('start_time')
            ->get()->groupBy('day_of_week');
        $school = \App\Models\SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.timetable', compact('timetable', 'class', 'sections', 'school'))->setPaper('a4', 'landscape');
        return $pdf->download('timetable-' . $class->name . '.pdf');
    }

    public function calendarPdf()
    {
        $currentYear = AcademicYear::current();
        $holidays    = Holiday::orderBy('date')->get();
        $school      = SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.academic-calendar', compact('currentYear', 'holidays', 'school'));
        return $pdf->download('academic-calendar.pdf');
    }

    // ── Terms / Semesters ────────────────────────────────────

    public function terms(Request $request)
    {
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $selectedYear  = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::current();
        $terms = AcademicTerm::where('academic_year_id', $selectedYear?->id)
            ->orderBy('order_position')->get();
        return view('academics.terms', compact('academicYears', 'selectedYear', 'terms'));
    }

    public function storeTerm(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name'             => 'required|string|max:50',
            'type'             => 'required|in:term,semester,quarter',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after:start_date',
            'order_position'   => 'nullable|integer|min:1',
        ]);
        AcademicTerm::create($request->only(['academic_year_id', 'name', 'type', 'start_date', 'end_date', 'order_position']));
        return back()->with('success', 'Term "' . $request->name . '" added.');
    }

    public function updateTerm(Request $request, int $id)
    {
        $term = AcademicTerm::findOrFail($id);
        $request->validate([
            'name'           => 'required|string|max:50',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'order_position' => 'nullable|integer|min:1',
        ]);
        $term->update($request->only(['name', 'type', 'start_date', 'end_date', 'order_position']));
        return back()->with('success', 'Term updated.');
    }

    public function deleteTerm(int $id)
    {
        AcademicTerm::findOrFail($id)->delete();
        return back()->with('success', 'Term deleted.');
    }

    // ── Copy holidays from a previous year ─────────────────

    public function copyHolidays(Request $request)
    {
        $request->validate([
            'from_year_id' => 'required|exists:academic_years,id',
            'to_year_id'   => 'required|exists:academic_years,id|different:from_year_id',
        ]);
        $fromYearStart = AcademicYear::find($request->from_year_id)->start_date;
        $toYear        = AcademicYear::find($request->to_year_id);
        $fromHolidays  = Holiday::where('academic_year_id', $request->from_year_id)->get();
        $copied = 0;
        foreach ($fromHolidays as $h) {
            $offset = $h->date->diffInDays($fromYearStart, false);
            $newDate = $toYear->start_date->addDays($offset);
            Holiday::firstOrCreate(
                ['date' => $newDate, 'academic_year_id' => $toYear->id],
                ['name' => $h->name, 'type' => $h->type, 'description' => $h->description]
            );
            $copied++;
        }
        return back()->with('success', "{$copied} holidays copied to {$toYear->name}.");
    }

    // ── Working days configuration ────────────────────────

    public function workingDays()
    {
        $setting = SchoolSetting::first();
        $config  = json_decode($setting?->working_days_config ?? '{}', true);
        return view('academics.working-days', compact('config'));
    }

    public function saveWorkingDays(Request $request)
    {
        $days   = $request->input('days', []);
        $config = json_encode($days);
        $setting = SchoolSetting::firstOrCreate([]);
        if (!\Illuminate\Support\Facades\Schema::hasColumn('school_settings', 'working_days_config')) {
            return back()->with('error', 'Working days config column not in DB yet.');
        }
        $setting->update(['working_days_config' => $config]);
        return back()->with('success', 'Working days configuration saved.');
    }

    public function teacherTimetable(Request $request)
    {
        $user = auth()->user();
        $authEmployeeId = \App\Http\Controllers\Admin\PeriodReportController::resolveAuthEmployeeId();
        $isElevated = $user && $user->hasAnyRole(['super_admin', 'admin', 'principal']);
        $isTeacherUser = !$isElevated && $user && ($user->hasRole('teacher') || $authEmployeeId);

        $teachers  = collect();
        $timetable = collect();
        $teacher   = null;

        if ($isTeacherUser) {
            // Logged-in teacher: strictly scoped to their own account mapping
            if ($authEmployeeId) {
                $teacher = Employee::find($authEmployeeId);
                if ($teacher) {
                    $timetable = \App\Models\Timetable::with(['subject', 'class', 'section'])
                        ->where('teacher_id', $teacher->id)
                        ->whereNotIn('period_type', ['free', 'holiday'])
                        ->orderBy('day_of_week')->orderBy('period_number')
                        ->get()->groupBy('day_of_week');
                }
            }
        } else {
            // Admins / Principal: can view any teacher's timetable
            $teachers = Employee::where('is_active', true)
                ->where(fn($q) => $q->where('employee_type', 'teaching')->orWhereNull('employee_type'))
                ->orderBy('first_name')->get();

            $targetTeacherId = $request->teacher_id ?: ($teachers->first()?->id);
            if ($targetTeacherId) {
                $teacher = Employee::find($targetTeacherId);
                if ($teacher) {
                    $timetable = \App\Models\Timetable::with(['subject', 'class', 'section'])
                        ->where('teacher_id', $teacher->id)
                        ->whereNotIn('period_type', ['free', 'holiday'])
                        ->orderBy('day_of_week')->orderBy('period_number')
                        ->get()->groupBy('day_of_week');
                }
            }
        }

        // Previously saved period reports for this teacher
        $previousReports = collect();
        if ($teacher) {
            $previousReports = \App\Models\PeriodReport::with(['subject', 'class', 'section'])
                ->where('teacher_id', $teacher->id)
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->take(50)
                ->get();
        }

        return view('academics.teacher-timetable', compact(
            'teachers', 'timetable', 'teacher', 'isTeacherUser', 'previousReports'
        ));
    }

    // ── Teacher Workload Report ────────────────────────────

    public function teacherWorkload(Request $request)
    {
        $maxPerWeek = (int) \App\Models\SchoolSetting::get('teacher_max_periods_per_week', 30);
        $teachers   = Employee::where('is_active', true)
            ->where(fn($q) => $q->where('employee_type', 'teaching')->orWhereNull('employee_type'))
            ->orderBy('first_name')->get();

        $workload = $teachers->map(function ($teacher) use ($maxPerWeek) {
            $periods = \App\Models\Timetable::where('teacher_id', $teacher->id)
                ->whereNotIn('period_type', ['free', 'holiday'])->count();
            return [
                'teacher'    => $teacher,
                'periods'    => $periods,
                'max'        => $maxPerWeek,
                'pct'        => $maxPerWeek > 0 ? round(($periods / $maxPerWeek) * 100) : 0,
                'over'       => $periods > $maxPerWeek,
            ];
        });

        return view('academics.teacher-workload', compact('workload', 'maxPerWeek'));
    }

    public function timetableConflicts(Request $request)
    {
        // Teacher double-booked: same employee, same day, same period
        $teacherConflicts = \App\Models\Timetable::with(['class', 'section', 'subject', 'teacher'])
            ->whereNotNull('teacher_id')
            ->get()
            ->groupBy(fn($t) => $t->teacher_id . '_' . $t->day_of_week . '_' . $t->period_number)
            ->filter(fn($group) => $group->count() > 1)
            ->map(fn($group) => [
                'type'    => 'Teacher Double-Booked',
                'teacher' => $group->first()->teacher?->full_name ?? 'Unknown',
                'day'     => $group->first()->day_of_week,
                'period'  => $group->first()->period_number,
                'classes' => $group->map(fn($t) => ($t->class?->name ?? '?') . ($t->section ? ' ' . $t->section->name : ''))->implode(', '),
                'entries' => $group,
            ]);

        // Subject taught in same class by multiple teachers same period (shouldn't happen normally)
        $classConflicts = \App\Models\Timetable::with(['class', 'section', 'subject', 'teacher'])
            ->get()
            ->groupBy(fn($t) => $t->class_id . '_' . ($t->section_id ?? 0) . '_' . $t->day_of_week . '_' . $t->period_number)
            ->filter(fn($group) => $group->count() > 1)
            ->map(fn($group) => [
                'type'    => 'Class Double-Booked',
                'teacher' => $group->map(fn($t) => $t->teacher?->full_name)->filter()->implode(', '),
                'day'     => $group->first()->day_of_week,
                'period'  => $group->first()->period_number,
                'classes' => ($group->first()->class?->name ?? '?') . ($group->first()->section ? ' ' . $group->first()->section->name : ''),
                'entries' => $group,
            ]);

        $conflicts = $teacherConflicts->merge($classConflicts)->values();
        $totalConflicts = $conflicts->count();

        return view('academics.timetable-conflicts', compact('conflicts', 'totalConflicts'));
    }

    public function homeworkSubmissions(Request $request, int $id)
    {
        $homework    = \App\Models\Homework::with(['class', 'section', 'subject'])->findOrFail($id);
        $currentYear = AcademicYear::current();

        $enrollments = \App\Models\StudentEnrollment::with('student')
            ->where('class_id', $homework->class_id)
            ->where('status', 'active')
            ->when($homework->section_id, fn($q) => $q->where('section_id', $homework->section_id))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->get();

        $submissions = \App\Models\HomeworkSubmission::where('homework_id', $id)
            ->get()->keyBy('student_id');

        return view('academics.homework-submissions', compact('homework', 'enrollments', 'submissions'));
    }

    public function saveSubmissionStatus(Request $request, int $id)
    {
        $request->validate(['submissions' => 'required|array']);
        $homework = \App\Models\Homework::findOrFail($id);

        DB::transaction(function () use ($request, $homework) {
            foreach ($request->submissions as $studentId => $data) {
                \App\Models\HomeworkSubmission::updateOrCreate(
                    ['homework_id' => $homework->id, 'student_id' => $studentId],
                    array_merge(
                        array_intersect_key($data, array_flip(['status','submitted_at','score','teacher_feedback'])),
                        ['evaluated_by' => Auth::id(), 'evaluated_at' => now()]
                    )
                );
            }
        });
        return back()->with('success', 'Submission statuses saved.');
    }

    public function homeworkCompletionReport(Request $request)
    {
        $classes  = Classes::active()->get();
        $subjects = Subject::where('is_active', true)->get();
        $rows     = collect();

        if ($request->class_id) {
            $homeworks = \App\Models\Homework::with(['subject', 'submissions'])
                ->where('class_id', $request->class_id)
                ->when($request->subject_id, fn($q) => $q->where('subject_id', $request->subject_id))
                ->latest('due_date')->paginate(20)->withQueryString();

            $currentYear = AcademicYear::current();
            $totalStudents = \App\Models\StudentEnrollment::where('class_id', $request->class_id)
                ->where('status', 'active')
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->count();

            return view('academics.homework-completion-report', compact('homeworks', 'classes', 'subjects', 'totalStudents'));
        }

        return view('academics.homework-completion-report', compact('classes', 'subjects'));
    }

    // ── Lesson Plans ─────────────────────────────────────────

    public function lessonPlans(Request $request)
    {
        $classes  = Classes::active()->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $currentYear = AcademicYear::current();

        $query = \App\Models\LessonPlan::with(['class', 'subject', 'createdBy'])
            ->when($request->class_id,   fn($q, $v) => $q->where('class_id', $v))
            ->when($request->subject_id, fn($q, $v) => $q->where('subject_id', $v))
            ->when($request->status,     fn($q, $v) => $q->where('status', $v))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->latest('plan_date');

        $plans = $query->paginate(25)->withQueryString();

        // Syllabus topics for linking
        $syllabusTopics = collect();
        if ($request->class_id && $request->subject_id) {
            $syllabusTopics = \App\Models\Syllabus::where('class_id', $request->class_id)
                ->where('subject_id', $request->subject_id)
                ->orderBy('chapter_number')->get();
        }

        return view('academics.lesson-plans', compact('plans', 'classes', 'subjects', 'syllabusTopics'));
    }

    public function storeLessonPlan(Request $request)
    {
        $request->validate([
            'class_id'            => 'required|exists:classes,id',
            'subject_id'          => 'required|exists:subjects,id',
            'topic'               => 'required|string|max:255',
            'plan_date'           => 'required|date',
            'learning_objectives' => 'nullable|string',
            'teaching_method'     => 'nullable|string',
            'resources_required'  => 'nullable|string',
            'period_number'       => 'nullable|string',
            'duration_minutes'    => 'nullable|integer|min:1',
        ]);
        $currentYear = AcademicYear::current();
        \App\Models\LessonPlan::create(array_merge($request->only([
            'class_id', 'subject_id', 'syllabus_id', 'topic',
            'learning_objectives', 'teaching_method', 'resources_required',
            'period_number', 'plan_date', 'duration_minutes', 'teacher_notes',
        ]), [
            'academic_year_id' => $currentYear?->id,
            'status'           => 'submitted',
            'created_by'       => auth()->id(),
        ]));
        return back()->with('success', 'Lesson plan submitted for HOD review.');
    }

    public function reviewLessonPlan(Request $request, int $id)
    {
        $request->validate(['decision' => 'required|in:approved,rejected', 'hod_remarks' => 'nullable|string']);
        \App\Models\LessonPlan::findOrFail($id)->update([
            'status'           => $request->decision === 'approved' ? 'approved' : 'rejected',
            'hod_decision'     => $request->decision,
            'hod_remarks'      => $request->hod_remarks,
            'hod_reviewed_by'  => auth()->id(),
            'hod_reviewed_at'  => now(),
        ]);
        return back()->with('success', 'Lesson plan ' . $request->decision . '.');
    }

    public function markLessonComplete(int $id)
    {
        $plan = \App\Models\LessonPlan::findOrFail($id);
        $plan->update(['is_completed' => true, 'completed_at' => now()]);
        // Auto-mark syllabus topic as done if linked
        if ($plan->syllabus_id) {
            \App\Models\Syllabus::where('id', $plan->syllabus_id)->update(['status' => 'completed']);
        }
        return back()->with('success', 'Lesson marked complete.');
    }

    public function lessonPlanPdf(int $id)
    {
        $plan   = \App\Models\LessonPlan::with(['class', 'subject', 'createdBy', 'hodReviewedBy', 'syllabus'])->findOrFail($id);
        $school = \App\Models\SchoolSetting::first();
        $pdf    = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.lesson-plan', compact('plan', 'school'));
        return $pdf->download('lesson-plan-' . $plan->id . '.pdf');
    }

    // ── Syllabus Coverage Report ──────────────────────────────

    public function syllabusCoverage(Request $request)
    {
        $classes = Classes::active()->get()->map(function ($c) {
            $base = \App\Models\Syllabus::where('class_id', $c->id);
            $total = (clone $base)->count();
            $completed = (clone $base)->where('status', 'completed')->count();
            $inProgress = (clone $base)->where('status', 'in_progress')->count();

            $c->total_chapters = $total;
            $c->completed_chapters = $completed;
            $c->in_progress_chapters = $inProgress;
            $c->progress_pct = $total > 0 ? round(($completed / $total) * 100) : 0;
            return $c;
        });

        // Determine selected class (default to request class_id, or first class)
        $selectedClass = null;
        if ($request->filled('class_id')) {
            $selectedClass = $classes->firstWhere('id', (int)$request->class_id);
        }
        if (!$selectedClass && $classes->isNotEmpty()) {
            $selectedClass = $classes->first();
        }

        $coverage = collect();
        $classSubjects = collect();
        $termCoverage = [
            'Term 1' => ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'pct' => 0],
            'Term 2' => ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'pct' => 0],
            'Term 3' => ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'pct' => 0],
        ];

        if ($selectedClass) {
            $subjectIds = \App\Models\Syllabus::where('class_id', $selectedClass->id)
                ->pluck('subject_id')
                ->unique();

            $classSubjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();

            if ($request->filled('subject_id')) {
                $subjectIds = [$request->subject_id];
            }

            foreach ($subjectIds as $sid) {
                $query = \App\Models\Syllabus::where('class_id', $selectedClass->id)
                    ->where('subject_id', $sid)
                    ->when($request->filled('term'), fn($q) => $q->where('term', $request->term));

                $total      = (clone $query)->count();
                $completed  = (clone $query)->where('status', 'completed')->count();
                $inProgress = (clone $query)->where('status', 'in_progress')->count();
                $pending    = max(0, $total - $completed - $inProgress);

                if ($total > 0) {
                    $coverage->push([
                        'subject'     => Subject::find($sid),
                        'total'       => $total,
                        'completed'   => $completed,
                        'in_progress' => $inProgress,
                        'pending'     => $pending,
                        'percentage'  => round(($completed / $total) * 100, 1),
                    ]);
                }
            }

            // Calculate Term 1, Term 2, Term 3 statistics
            foreach (['Term 1', 'Term 2', 'Term 3'] as $t) {
                $tQuery = \App\Models\Syllabus::where('class_id', $selectedClass->id)->where('term', $t);
                $tTotal = (clone $tQuery)->count();
                $tCompleted = (clone $tQuery)->where('status', 'completed')->count();
                $tInProgress = (clone $tQuery)->where('status', 'in_progress')->count();

                $termCoverage[$t] = [
                    'total'       => $tTotal,
                    'completed'   => $tCompleted,
                    'in_progress' => $tInProgress,
                    'pending'     => max(0, $tTotal - $tCompleted - $tInProgress),
                    'pct'         => $tTotal > 0 ? round(($tCompleted / $tTotal) * 100) : 0,
                ];
            }
        }

        return view('academics.syllabus-coverage', compact('classes', 'selectedClass', 'classSubjects', 'coverage', 'termCoverage'));
    }


    /* ------------------------------------------------------------------ */
    /*  Section Management                                                  */
    /* ------------------------------------------------------------------ */

    public function sectionsManage(Request $request)
    {
        $currentYear = AcademicYear::current();
        $classes = Classes::active()->with(['sections' => function ($q) use ($currentYear) {
            if ($currentYear) $q->where('academic_year_id', $currentYear->id);
            $q->with(['classTeacher', 'coClassTeacher']);
        }])->get();
        $teachers = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $selectedClass = $request->class_id ? Classes::find($request->class_id) : null;
        $sections = collect();
        if ($selectedClass) {
            $sections = Section::where('class_id', $selectedClass->id)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->with(['classTeacher', 'coClassTeacher'])
                ->withCount(['enrollments as active_students_count' => fn($q) => $q->where('status', 'active')])
                ->get();
        }
        return view('academics.sections-manage', compact('classes', 'teachers', 'selectedClass', 'sections', 'currentYear'));
    }

    public function updateSection(Request $request, int $id)
    {
        $section = Section::findOrFail($id);
        $validated = $request->validate([
            'name'                => 'required|string|max:20',
            'capacity'            => 'required|integer|min:1|max:200',
            'class_teacher_id'    => 'nullable|exists:users,id',
            'co_class_teacher_id' => 'nullable|exists:users,id',
            'is_active'           => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $section->update($validated);
        return back()->with('success', 'Section "' . $section->name . '" updated.');
    }

    public function deactivateSectionIfEmpty(int $id)
    {
        $section = Section::withCount(['enrollments as active_count' => fn($q) => $q->where('status', 'active')])->findOrFail($id);
        if ($section->active_count > 0) {
            return back()->with('error', 'Cannot deactivate: section has ' . $section->active_count . ' active students.');
        }
        $section->update(['is_active' => false]);
        return back()->with('success', 'Section "' . $section->name . '" deactivated.');
    }

    public function createSection(Request $request)
    {
        $currentYear = AcademicYear::current();
        if (!$currentYear) {
            return back()->with('error', 'No active academic year found. Please set a current academic year before creating sections.');
        }
        $validated = $request->validate([
            'class_id'            => 'required|exists:classes,id',
            'name'                => 'required|string|max:20',
            'capacity'            => 'required|integer|min:1|max:200',
            'class_teacher_id'    => 'nullable|exists:users,id',
            'co_class_teacher_id' => 'nullable|exists:users,id',
        ]);
        $validated['academic_year_id'] = $currentYear->id;
        $validated['is_active'] = true;
        Section::create($validated);
        return back()->with('success', 'Section "' . $validated['name'] . '" created.');
    }

    public function renameClass(Request $request, int $id)
    {
        $class = Classes::findOrFail($id);
        $request->validate(['name' => 'required|string|max:100']);
        $old = $class->name;
        $class->update(['name' => $request->name]);
        return back()->with('success', "Class renamed from \"{$old}\" to \"{$request->name}\".");
    }

    public function mergeSection(Request $request, int $id)
    {
        $request->validate(['target_section_id' => 'required|exists:sections,id|different:id']);
        $source = Section::withCount(['enrollments as active_count' => fn($q) => $q->where('status', 'active')])->findOrFail($id);
        $target = Section::findOrFail($request->target_section_id);

        if ($source->id === $target->id) {
            return back()->with('error', 'Source and target sections must be different.');
        }

        \DB::transaction(function () use ($source, $target) {
            StudentEnrollment::where('section_id', $source->id)
                ->where('status', 'active')
                ->update(['section_id' => $target->id]);
            $source->update(['is_active' => false]);
        });

        return back()->with('success', "Merged section \"{$source->name}\" into \"{$target->name}\". {$source->active_count} students moved.");
    }

    public function splitSection(Request $request, int $id)
    {
        $request->validate([
            'new_section_name' => 'required|string|max:20',
            'move_count'       => 'required|integer|min:1',
        ]);
        $source = Section::findOrFail($id);
        $currentYear = AcademicYear::current();

        $studentsToMove = StudentEnrollment::where('section_id', $source->id)
            ->where('status', 'active')
            ->orderBy('roll_number')
            ->limit($request->move_count)
            ->get();

        if ($studentsToMove->isEmpty()) {
            return back()->with('error', 'No active students found in this section to split.');
        }

        \DB::transaction(function () use ($source, $request, $studentsToMove, $currentYear) {
            $newSection = Section::create([
                'class_id'         => $source->class_id,
                'academic_year_id' => $currentYear?->id,
                'name'             => $request->new_section_name,
                'capacity'         => $source->capacity,
                'is_active'        => true,
            ]);
            StudentEnrollment::whereIn('id', $studentsToMove->pluck('id'))
                ->update(['section_id' => $newSection->id]);
        });

        return back()->with('success', "Split: {$studentsToMove->count()} students moved from \"{$source->name}\" to new section \"{$request->new_section_name}\".");
    }

    // ── Homework Calendar ─────────────────────────────────

    public function homeworkCalendar(Request $request)
    {
        $classes  = Classes::active()->orderBy('name')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();

        $month   = (int)($request->month ?? now()->month);
        $year    = (int)($request->year ?? now()->year);
        $classId = $request->class_id;

        $start = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $homework = \App\Models\Homework::with(['class', 'subject', 'createdBy'])
            ->whereBetween('due_date', [$start, $end])
            ->when($classId, fn($q, $v) => $q->where('class_id', $v))
            ->where('is_active', true)
            ->get()
            ->groupBy(fn($h) => $h->due_date->format('Y-m-d'));

        $calendar = [];
        $current  = $start->copy();
        while ($current->lte($end)) {
            $calendar[$current->format('Y-m-d')] = [
                'date'     => $current->copy(),
                'homework' => $homework[$current->format('Y-m-d')] ?? collect(),
            ];
            $current->addDay();
        }

        return view('academics.homework-calendar', compact(
            'classes', 'subjects', 'calendar', 'month', 'year', 'classId', 'start'
        ));
    }
}
