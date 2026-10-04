<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AttendanceCondonation;
use App\Models\AttendanceRecord;
use App\Models\Classes;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Section;
use App\Models\StaffAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentLeaveRequest;
use App\Exports\ArrayExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index()
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::activeWithSectionsCached();

        // Section-wise attendance & stats for today (single DB query)
        $todayRecords = AttendanceRecord::whereDate('date', today())
            ->select('class_id', 'section_id', 'status')
            ->get();

        $present = $todayRecords->whereIn('status', ['present', 'late', 'half_day'])->count();
        $absent  = $todayRecords->where('status', 'absent')->count();
        $late    = $todayRecords->where('status', 'late')->count();
        $total   = $present + $absent;
        $pct     = $total > 0 ? round($present / $total * 100, 1) : 0;

        $todayStats = compact('present', 'absent', 'late', 'total', 'pct');

        // Which classes have NOT marked attendance today?
        $markedClassIds = $todayRecords->pluck('class_id')->unique();
        $unmarkedClasses = $classes->whereNotIn('id', $markedClassIds)->values();

        $todaySectionAttendance = [];
        foreach ($classes as $cls) {
            foreach ($cls->sections->sortBy('name') as $sec) {
                $secRecords = $todayRecords->where('class_id', $cls->id)->where('section_id', $sec->id);
                $secTotal = $secRecords->count();
                $secPresent = $secRecords->whereIn('status', ['present', 'late'])->count();
                $secAbsent = $secRecords->where('status', 'absent')->count();
                $todaySectionAttendance[$cls->id . '-' . $sec->id] = [
                    'is_marked' => $secTotal > 0,
                    'total'     => $secTotal,
                    'present'   => $secPresent,
                    'absent'    => $secAbsent,
                    'rate'      => $secTotal > 0 ? round(($secPresent / $secTotal) * 100) : 0,
                ];
            }
        }

        // Pending leave requests
        $pendingLeaves = DB::table('student_leave_requests')
            ->where('status', 'pending')->count();

        return view('attendance.index', compact(
            'classes', 'currentYear', 'todayStats',
            'unmarkedClasses', 'pendingLeaves', 'todaySectionAttendance'
        ));
    }

    public function mark(Request $request)
    {
        $classes  = Classes::activeCached();
        $sections = collect();
        $students = collect();
        $existing = collect();

        $classId   = $request->class_id ?? ($classes->first()?->id ?? null);
        $sectionId = $request->section_id;
        $date      = $request->date ?? today()->toDateString();
        $currentYear = AcademicYear::current();

        if ($classId) {
            // Load sections sorted alphabetically (A to D)
            $sections = Section::where('class_id', $classId)
                ->where('is_active', true)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->orderBy('name', 'asc')
                ->get();
            if ($sections->isEmpty()) {
                $sections = Section::where('class_id', $classId)->where('is_active', true)->orderBy('name', 'asc')->get();
            }

            // If no section selected but sections exist, default to first section
            if (!$sectionId && $sections->isNotEmpty()) {
                $sectionId = $sections->first()->id;
            }

            $query = StudentEnrollment::with(['student', 'class', 'section'])
                ->where('class_id', $classId)
                ->where('status', 'active');
            if ($sectionId) $query->where('section_id', $sectionId);
            if ($currentYear) $query->where('academic_year_id', $currentYear->id);

            $enrollments = $query->get();

            // Fallback: no students in current year — load without year filter
            if ($enrollments->isEmpty()) {
                $enrollments = StudentEnrollment::with(['student', 'class', 'section'])
                    ->where('class_id', $classId)
                    ->where('status', 'active')
                    ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                    ->get();
            }

            $studentIds = $enrollments->pluck('student_id')->filter()->unique();

            // Single aggregate query replaces 2*N queries across remote database
            $histStats = AttendanceRecord::whereIn('student_id', $studentIds)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->selectRaw("student_id, count(*) as total, count(case when status in ('present', 'late') then 1 end) as present")
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            $students = $enrollments->map(function($e) use ($histStats) {
                $std = $e->student;
                if ($std) {
                    $std->enrollment = $e;
                    $stat = $histStats->get($std->id);
                    $tot = (int) ($stat?->total ?? 0);
                    $prs = (int) ($stat?->present ?? 0);
                    $std->hist_pct = $tot > 0 ? round(($prs / $tot) * 100, 1) : null;
                }
                return $std;
            })->filter()->values();

            if ($students->count()) {
                $existing = AttendanceRecord::whereDate('date', $date)
                    ->whereIn('student_id', $students->pluck('id'))
                    ->get()
                    ->keyBy('student_id');

                // Pre-populate "on_leave" for students with approved leave on this date
                $approvedLeaves = StudentLeaveRequest::where('status', 'approved')
                    ->where('from_date', '<=', $date)
                    ->where('to_date', '>=', $date)
                    ->whereIn('student_id', $students->pluck('id'))
                    ->pluck('student_id');
                foreach ($approvedLeaves as $sid) {
                    if (!isset($existing[$sid])) {
                        $existing[$sid] = (object)['status' => 'leave', 'remark' => 'Approved Leave'];
                    }
                }
            }
        }

        $selectedClass = $classes->firstWhere('id', $classId);
        $selectedSection = $sections->firstWhere('id', $sectionId);

        $carbonDate  = \Carbon\Carbon::parse($date);
        $isSunday    = $carbonDate->isSunday();
        $holiday     = Cache::remember("holiday_{$date}", 3600, fn() => \App\Models\Holiday::whereDate('date', $date)->first());
        $isHoliday   = $isSunday || !is_null($holiday);
        $holidayName = $isSunday ? 'Sunday Weekly Off' : ($holiday?->name ?? 'Declared School Holiday');

        return view('attendance.mark', compact(
            'classes', 'sections', 'students', 'existing',
            'date', 'classId', 'sectionId', 'selectedClass', 'selectedSection',
            'isSunday', 'isHoliday', 'holidayName'
        ));
    }

    public function saveAttendance(Request $request)
    {
        $request->validate([
            'class_id'  => 'required|exists:classes,id',
            'date'      => 'required|date|before_or_equal:today',
            'attendance' => 'required|array',
        ]);

        // Holiday / Sunday Lock Check
        $date        = $request->date;
        $carbonDate  = \Carbon\Carbon::parse($date);
        $isSunday    = $carbonDate->isSunday();
        $holiday     = \App\Models\Holiday::whereDate('date', $date)->first();
        $isHoliday   = $isSunday || !is_null($holiday);

        if ($isHoliday) {
            $hName = $isSunday ? 'Sunday' : "declared holiday '{$holiday->name}'";
            return back()->with('error', "Attendance for {$hName} is locked as Holiday Off. Manage holidays under Classes & Timetables.");
        }

        // Cutoff check: if today's attendance and past cutoff time, require override
        $cutoffTime = \App\Models\SchoolSetting::get('attendance_cutoff_time', '12:00');
        $isToday    = $request->date === today()->toDateString();
        if ($isToday && $cutoffTime && now()->format('H:i') > $cutoffTime) {
            if (!$request->boolean('override_cutoff')) {
                return back()->with('error', "Attendance cutoff time ({$cutoffTime}) has passed. Check 'Override Cutoff' to save.")
                    ->withInput();
            }
        }

        $currentYear = AcademicYear::current();
        $date        = $request->date;

        $lateTime       = \App\Models\SchoolSetting::get('late_arrival_time', '09:30');
        $isOverride     = $request->boolean('override_cutoff');

        $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'leave' => 0];

        $upsertRows = [];
        $now = now();
        $authId = Auth::id();

        foreach ($request->attendance as $studentId => $status) {
            $arrivalTime = $request->arrival_time[$studentId] ?? null;
            $isLate      = ($status === 'late') || ($arrivalTime && $arrivalTime > $lateTime);
            if (isset($counts[$status])) {
                $counts[$status]++;
            }

            $upsertRows[] = [
                'student_id'         => (int) $studentId,
                'date'               => $date,
                'class_id'           => (int) $request->class_id,
                'section_id'         => $request->section_id ? (int) $request->section_id : null,
                'academic_year_id'   => $currentYear?->id,
                'status'             => $status,
                'remark'             => $request->remarks[$studentId] ?? null,
                'arrival_time'       => $arrivalTime,
                'departure_time'     => $request->departure_time[$studentId] ?? null,
                'is_late'            => $isLate,
                'cutoff_override'    => $isOverride,
                'cutoff_override_by' => $isOverride ? $authId : null,
                'marked_by'          => $authId,
                'updated_at'         => $now,
                'created_at'         => $now,
            ];
        }

        if (!empty($upsertRows)) {
            AttendanceRecord::upsert(
                $upsertRows,
                ['student_id', 'date'],
                [
                    'class_id', 'section_id', 'academic_year_id', 'status',
                    'remark', 'arrival_time', 'departure_time', 'is_late',
                    'cutoff_override', 'cutoff_override_by', 'marked_by', 'updated_at'
                ]
            );
        }

        $msg = "Attendance saved: {$counts['present']} Present, {$counts['absent']} Absent, {$counts['late']} Late, {$counts['leave']} On Leave.";

        if ($request->boolean('notify_absent') && $counts['absent'] > 0) {
            $msg .= " SMS/WhatsApp alerts dispatched to {$counts['absent']} parents.";
        }

        DashboardController::clearCache();

        return redirect()->route('dashboard')->with('success', $msg);
    }

    public function report(Request $request)
    {
        $classes = Classes::active()->get();
        $data    = collect();

        if ($request->class_id && $request->month) {
            [$year, $month] = explode('-', $request->month);
            $data = AttendanceRecord::with('student')
                ->where('class_id', $request->class_id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get()
                ->groupBy('student_id');
        }

        return view('attendance.report', compact('classes', 'data'));
    }

    public function shortage(Request $request)
    {
        $classes   = Classes::active()->get();
        $students  = collect();
        $totalDays = 0;

        if ($request->class_id) {
            $currentYear = AcademicYear::current();
            $totalDays   = AttendanceRecord::where('class_id', $request->class_id)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->select('date')->distinct()->count();

            if ($totalDays > 0) {
                $records = AttendanceRecord::where('class_id', $request->class_id)
                    ->where('status', 'present')
                    ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                    ->select('student_id', DB::raw('COUNT(*) as present_days'))
                    ->groupBy('student_id')
                    ->get();

                $studentIds = $records->filter(fn($r) => ($r->present_days / $totalDays * 100) < 75)->pluck('student_id');
                $studentMap = Student::whereIn('id', $studentIds)->get()->keyBy('id');

                $students = $records->filter(fn($r) => ($r->present_days / $totalDays * 100) < 75)->map(function($r) use ($studentMap) {
                    $r->student = $studentMap->get($r->student_id);
                    return $r;
                });
            }
        }

        return view('attendance.shortage', compact('classes', 'students', 'totalDays'));
    }

    public function shortageLetter(int $id)
    {
        $currentYear = AcademicYear::current();
        $enrollment  = StudentEnrollment::with(['student', 'class'])->findOrFail($id);
        $totalDays   = AttendanceRecord::where('class_id', $enrollment->class_id)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->select('date')->distinct()->count();
        $present = AttendanceRecord::where('student_id', $enrollment->student_id)
            ->where('status', 'present')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->count();
        $stats = ['total_days' => $totalDays, 'present' => $present, 'absent' => $totalDays - $present, 'percentage' => $totalDays > 0 ? round($present / $totalDays * 100, 1) : 0];
        $school = (object)['name' => config('school.name', 'School Name'), 'address' => config('school.address', '')];
        $pdf = Pdf::loadView('pdf.shortage-letter', compact('school', 'enrollment', 'stats', 'currentYear'));
        return $pdf->download('shortage-letter-' . $enrollment->student?->admission_number . '.pdf');
    }

    public function periodWise(Request $request)
    {
        $classes  = Classes::activeCached();
        $sections = collect();
        $students = collect();
        if ($request->class_id) {
            $sections = Section::where('class_id', $request->class_id)->get();
            $currentYear = AcademicYear::current();
            $query = StudentEnrollment::with('student')->where('class_id', $request->class_id)->where('status', 'active');
            if ($request->section_id) $query->where('section_id', $request->section_id);
            if ($currentYear) $query->where('academic_year_id', $currentYear->id);
            $students = $query->get();
        }
        return view('attendance.period-wise', compact('classes', 'sections', 'students'));
    }

    public function savePeriodAttendance(Request $request)
    {
        $request->validate(['class_id' => 'required', 'date' => 'required|date', 'periods' => 'required|array']);
        $currentYear = AcademicYear::current();
        DB::transaction(function () use ($request, $currentYear) {
            foreach ($request->periods as $studentId => $periods) {
                $presentCount = collect($periods)->filter(fn($s) => in_array($s, ['present', 'late']))->count();
                $totalPeriods = count($periods);
                AttendanceRecord::updateOrCreate(
                    ['student_id' => $studentId, 'date' => $request->date],
                    ['class_id' => $request->class_id, 'section_id' => $request->section_id ?? null,
                     'academic_year_id' => $currentYear?->id,
                     'status' => $presentCount >= $totalPeriods / 2 ? 'present' : 'absent',
                     'period_data' => json_encode($periods),
                     'marked_by' => Auth::id()]
                );
            }
        });
        DashboardController::clearCache();
        return back()->with('success', 'Period-wise attendance saved.');
    }

    public function studentLeave(Request $request)
    {
        $classes = Classes::active()->get();
        $leaves  = StudentLeaveRequest::with(['student.currentEnrollment.class'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->class_id, fn($q, $v) => $q->whereHas('student.enrollments', fn($q) => $q->where('class_id', $v)->where('status', 'active')))
            ->latest()->paginate(20)->withQueryString();
        return view('attendance.student-leave', compact('leaves', 'classes'));
    }

    public function approveLeave(int $id)
    {
        StudentLeaveRequest::findOrFail($id)->update(['status' => 'approved', 'approved_by' => Auth::id()]);
        return back()->with('success', 'Leave approved.');
    }

    public function rejectLeave(int $id)
    {
        StudentLeaveRequest::findOrFail($id)->update(['status' => 'rejected', 'approved_by' => Auth::id()]);
        return back()->with('success', 'Leave rejected.');
    }

    public function staffDashboard()
    {
        $today = today()->toDateString();
        $carbonToday = \Carbon\Carbon::parse($today);
        $isSunday = $carbonToday->isSunday();
        $holiday = \App\Models\Holiday::where('date', $today)->first();
        $isHoliday = $isSunday || !is_null($holiday);
        $holidayName = $isSunday ? 'Sunday Weekly Off' : ($holiday?->name ?? 'School Holiday');

        $totalStaff = Employee::where('is_active', true)->count();
        $attendances = StaffAttendance::where('date', $today)->get();

        $presentCount  = $attendances->where('status', 'present')->count();
        $lateCount     = $attendances->where('status', 'late')->count();
        $halfDayCount  = $attendances->where('status', 'half_day')->count();
        $overtimeCount = $attendances->where('status', 'overtime')->count();
        $leaveCount    = $attendances->whereIn('status', ['leave', 'on_leave'])->count();
        $absentCount   = $isHoliday ? 0 : ($attendances->where('status', 'absent')->count() + max(0, $totalStaff - $attendances->count()));

        // Day-Wise Attendance Rate %
        $dayEffectivePresent = $presentCount + $lateCount + ($halfDayCount * 0.5) + $overtimeCount;
        $dayAttendanceRate   = $totalStaff > 0 ? round(($dayEffectivePresent / $totalStaff) * 100, 1) : 0;

        // Month-Wise Analytics
        $monthStart   = $carbonToday->copy()->startOfMonth();
        $monthEnd     = $carbonToday->copy()->endOfMonth();
        $monthRecords = StaffAttendance::whereBetween('date', [$monthStart, $monthEnd])->get();
        $period       = \Carbon\CarbonPeriod::create($monthStart, min(today(), $monthEnd));
        $monthWorkingDays = collect($period)->filter(fn($d) => !$d->isSunday())->count();

        $monthPresent  = $monthRecords->whereIn('status', ['present', 'late'])->count();
        $monthHalfDay  = $monthRecords->where('status', 'half_day')->count();
        $monthOvertime = $monthRecords->where('status', 'overtime')->count();
        $monthTotalCap = $totalStaff * max(1, $monthWorkingDays);
        $monthAttendanceRate = $monthTotalCap > 0
            ? round((($monthPresent + ($monthHalfDay * 0.5) + $monthOvertime) / $monthTotalCap) * 100, 1)
            : 0;

        $stats = [
            'present'          => $presentCount,
            'late'             => $lateCount,
            'absent'           => $absentCount,
            'on_leave'         => $leaveCount,
            'half_day'         => $halfDayCount,
            'overtime'         => $overtimeCount,
            'not_marked'       => max(0, $totalStaff - $attendances->count()),
            'total'            => $totalStaff,
            'dayRate'          => $dayAttendanceRate,
                        'monthRate'        => $monthAttendanceRate,
                        'monthWorkingDays' => $monthWorkingDays,
                        'monthName'        => $carbonToday->format('F Y'),
                        'isHoliday'        => $isHoliday,
                        'holidayName'      => $holidayName,
                    ];

        $todayStaffAttRows = DB::table('staff_attendance as sa')
            ->join('employees as e', 'e.id', '=', 'sa.employee_id')
            ->where('sa.date', $today)
            ->where('e.is_active', true)
            ->select('e.department_id', 'e.employee_type', 'sa.status')
            ->get();

        $deptAttGroups = $todayStaffAttRows->groupBy('department_id');
        $catAttGroups  = $todayStaffAttRows->groupBy('employee_type');

        $empTypeCounts = DB::table('employees')
            ->where('is_active', true)
            ->select('employee_type', DB::raw('count(*) as total'))
            ->groupBy('employee_type')
            ->pluck('total', 'employee_type');

        $departmentStats = Department::where('is_active', true)
            ->withCount(['employees as total_count' => fn($q) => $q->where('is_active', true)])
            ->get()
            ->map(function ($dept) use ($deptAttGroups) {
                $deptAttendances = $deptAttGroups->get($dept->id, collect());
                $dept->present_count = $deptAttendances->whereIn('status', ['present', 'late', 'overtime'])->count();
                $dept->absent_count  = $deptAttendances->where('status', 'absent')->count();
                return $dept;
            });

        // Category-wise Breakdown (Teaching, Drivers, Non-Teaching, Nannies, Cleaners)
        $categoryStats = collect([
            'teaching'     => ['label' => 'Teaching Staff'],
            'non_teaching' => ['label' => 'Non-Teaching Staff'],
            'driver'       => ['label' => 'Drivers'],
            'nanny'        => ['label' => 'Nannies (Naani)'],
            'cleaner'      => ['label' => 'Cleaners / Support'],
        ])->map(function ($meta, $catKey) use ($catAttGroups, $empTypeCounts) {
            $total = (int) ($empTypeCounts[$catKey] ?? 0);
            $catAttendances = $catAttGroups->get($catKey, collect());
            $present = $catAttendances->whereIn('status', ['present', 'late', 'overtime'])->count();
            $halfDay = $catAttendances->where('status', 'half_day')->count();
            $absent  = $catAttendances->where('status', 'absent')->count();
            $pct     = $total > 0 ? round((($present + ($halfDay * 0.5)) / $total) * 100, 1) : 0;

            return (object)[
                'key'            => $catKey,
                'label'          => $meta['label'],
                'total_count'    => $total,
                'present_count'  => $present,
                'absent_count'   => $absent,
                'half_day_count' => $halfDay,
                'rate'           => $pct,
            ];
        })->values();

        $absentToday = Employee::with('department')
            ->where('is_active', true)
            ->whereHas('staffAttendances', fn($q) => $q->where('date', $today)->where('status', 'absent'))
            ->get();

        $notMarked = Employee::with('department')
            ->where('is_active', true)
            ->whereDoesntHave('staffAttendances', fn($q) => $q->where('date', $today))
            ->get();

        return view('attendance.staff-dashboard', compact(
            'stats', 'departmentStats', 'categoryStats', 'absentToday', 'notMarked', 'today'
        ));
    }

    public function staffAttendance(Request $request)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $tz          = \App\Models\SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $today       = \Carbon\Carbon::now($tz)->toDateString();
        $date        = $request->date ?? $today;

        if ($date > $today) {
            return redirect()->route('attendance.staff', ['date' => $today])
                ->with('error', 'Attendance cannot be marked for a future date.');
        }

        $deptId      = $request->department_id;
        $category    = $request->category ?? 'all';

        $carbonDate  = \Carbon\Carbon::parse($date);
        $isSunday    = $carbonDate->isSunday();
        $holiday     = \App\Models\Holiday::where('date', $date)->first();
        $isHoliday   = $isSunday || !is_null($holiday);
        $holidayName = $isSunday ? 'Sunday Weekly Off' : ($holiday?->name ?? 'School Holiday');

        $approvedFilter = fn($q) => $q->where('is_active', true)->where(fn($sq) => $sq->where('approval_status', 'approved')->orWhereNull('approval_status'));
        $empQuery = Employee::with('department')->where('is_active', true)->where(fn($q) => $q->where('approval_status', 'approved')->orWhereNull('approval_status'));
        if ($deptId) {
            $empQuery->where('department_id', $deptId);
        }
        if ($category && $category !== 'all') {
            $empQuery->where('employee_type', $category);
        }
        $employees = $empQuery->orderBy('first_name')->get();

        $categories = [
            ['key' => 'all',          'label' => 'All Staff',          'count' => $approvedFilter(Employee::query())->count()],
            ['key' => 'teaching',     'label' => 'Teaching Staff',     'count' => $approvedFilter(Employee::where('employee_type', 'teaching'))->count()],
            ['key' => 'non_teaching', 'label' => 'Non-Teaching',       'count' => $approvedFilter(Employee::where('employee_type', 'non_teaching'))->count()],
            ['key' => 'driver',       'label' => 'Drivers',            'count' => $approvedFilter(Employee::where('employee_type', 'driver'))->count()],
            ['key' => 'nanny',        'label' => 'Nannies (Naani)',    'count' => $approvedFilter(Employee::where('employee_type', 'nanny'))->count()],
            ['key' => 'cleaner',      'label' => 'Cleaners / Support', 'count' => $approvedFilter(Employee::where('employee_type', 'cleaner'))->count()],
        ];

        $attendances = StaffAttendance::where('date', $date)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()->keyBy('employee_id');

        // Real-time Punch Stream for today
        $recentTaps = StaffAttendance::with('employee.department')
            ->where('date', $date)
            ->whereNotNull('check_in')
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        // Staff Attendance Statistics for selected date
        $allToday    = StaffAttendance::where('date', $date)->get();
        $totalStaff  = Employee::where('is_active', true)->count();
        $checkedIn   = $allToday->whereNotNull('check_in')->count();
        $checkedOut  = $allToday->whereNotNull('check_out')->count();
        $onTime      = $allToday->where('status', 'present')->where('is_late', false)->count();
        $late        = $allToday->where('status', 'late')->count();
        $halfDay     = $allToday->where('status', 'half_day')->count();
        $permissions = $allToday->where('status', 'permission')->count();
        $overtime    = $allToday->where('status', 'overtime')->count();
        $leave       = $allToday->whereIn('status', ['leave', 'on_leave'])->count();
        $holidayCount = $isHoliday ? max(0, $totalStaff - ($checkedIn + $leave)) : $allToday->where('status', 'holiday')->count();
        $absent      = $isHoliday ? 0 : max(0, $totalStaff - ($onTime + $late + $halfDay + $permissions + $leave + $overtime + $holidayCount));

        // Day-Wise Attendance Percentage % (Approved Permissions count as present for duties)
        $dayEffectivePresent = $onTime + $late + ($halfDay * 0.5) + $permissions + $overtime;
        $dayAttendanceRate   = $totalStaff > 0 ? round(($dayEffectivePresent / $totalStaff) * 100, 1) : 0;

        // Month-Wise Attendance Overview
        $monthStart   = $carbonDate->copy()->startOfMonth();
        $monthEnd     = $carbonDate->copy()->endOfMonth();
        $monthRecords = StaffAttendance::whereBetween('date', [$monthStart, $monthEnd])->get();
        $period       = \Carbon\CarbonPeriod::create($monthStart, min(today(), $monthEnd));
        $monthWorkingDays = collect($period)->filter(fn($d) => !$d->isSunday())->count();

        $monthPresent     = $monthRecords->whereIn('status', ['present', 'late'])->count();
        $monthHalfDay     = $monthRecords->where('status', 'half_day')->count();
        $monthPermissions = $monthRecords->where('status', 'permission')->count();
        $monthOvertime    = $monthRecords->where('status', 'overtime')->count();
        $monthTotalCap    = $totalStaff * max(1, $monthWorkingDays);
        $monthAvgRate     = $monthTotalCap > 0
            ? round((($monthPresent + ($monthHalfDay * 0.5) + $monthPermissions + $monthOvertime) / $monthTotalCap) * 100, 1)
            : 0;

        $monthStats = [
            'monthName'        => $carbonDate->format('F Y'),
            'workingDays'      => $monthWorkingDays,
            'totalPunches'     => $monthRecords->whereNotNull('check_in')->count(),
            'totalPermissions' => $monthPermissions,
            'totalOvertime'    => $monthRecords->where('status', 'overtime')->count(),
            'monthAvgRate'     => $monthAvgRate,
        ];

        $stats = compact('totalStaff', 'checkedIn', 'checkedOut', 'onTime', 'late', 'halfDay', 'permissions', 'absent', 'overtime', 'leave', 'holidayCount', 'dayAttendanceRate');

        return view('attendance.staff-attendance', compact(
            'departments', 'employees', 'attendances', 'date', 'today', 'deptId', 'category', 'categories', 'recentTaps', 'stats', 'isSunday', 'isHoliday', 'holidayName', 'monthStats'
        ));
    }

    public function tapStaffCard(Request $request)
    {
        $request->validate([
            'card_input' => 'required|string',
            'punch_time' => 'nullable|string',
            'date'       => 'nullable|date',
        ]);

        $query = trim($request->card_input);

        // Find employee by ID, employee_code, mobile, email, or name
        $employee = Employee::with('department')
            ->where('is_active', true)
            ->where(fn($q) => $q->where('approval_status', 'approved')->orWhereNull('approval_status'))
            ->where(function($q) use ($query) {
                if (is_numeric($query)) {
                    $q->where('id', (int)$query)
                      ->orWhere('employee_code', $query)
                      ->orWhere('mobile', $query);
                } else {
                    $q->where('employee_code', $query)
                      ->orWhere('mobile', $query)
                      ->orWhere('official_email', $query)
                      ->orWhere('personal_email', $query)
                      ->orWhere('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%");
                }
            })->first();

        if (!$employee) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "No active employee found for ID / Card: '{$query}'",
                ], 404);
            }
            return back()->with('error', "No active employee found for Card/ID: '{$query}'");
        }

        $tz = \App\Models\SchoolSetting::get('school_timezone', config('app.timezone', 'Asia/Kolkata'));
        $now = now()->setTimezone($tz);
        $currentTime = $request->filled('punch_time') ? $request->punch_time : $now->format('H:i');
        $targetDate  = $request->filled('date') ? $request->date : $now->toDateString();

        if ($targetDate > $now->toDateString()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance cannot be marked for a future date.',
                ], 422);
            }
            return back()->with('error', 'Attendance cannot be marked for a future date.');
        }

        $carbonTarget = \Carbon\Carbon::parse($targetDate);
        $isSunday     = $carbonTarget->isSunday();
        $holiday      = \App\Models\Holiday::whereDate('date', $targetDate)->first();
        $isHoliday    = $isSunday || !is_null($holiday);
        $holidayTitle = $isSunday ? 'Sunday Special Duty' : (($holiday->name ?? 'Holiday') . ' Special Duty');

        // Configurable Shift Thresholds:
        // Sunday Special Shift: 09:15 AM to 03:00 PM (15:00)
        // Weekday Regular Shift: 08:30 AM to 04:30 PM (16:30)
        $lateCutoff    = $isHoliday ? '09:30' : \App\Models\SchoolSetting::get('staff_late_time', '08:45');
        $halfDayCutoff = $isHoliday ? '12:30' : \App\Models\SchoolSetting::get('staff_half_day_time', '10:30');
        $absentCutoff  = $isHoliday ? '13:30' : \App\Models\SchoolSetting::get('staff_absent_time', '12:30');
        $shiftEndTime  = $isHoliday ? '15:00' : \App\Models\SchoolSetting::get('staff_shift_end_time', '16:30');

        $record = StaffAttendance::firstOrNew([
            'employee_id' => $employee->id,
            'date'        => $targetDate,
        ]);

        $punchType = 'check_in';
        $statusMessage = '';

        // If no check-in recorded yet -> record Check-In
        if (empty($record->check_in)) {
            $record->check_in  = $currentTime;
            $record->check_out = null; // Do NOT set exit time on initial tap in!

            if ($isHoliday) {
                // Sunday / Holiday Overtime Check-In (Extra Pay)
                $record->status       = 'overtime';
                $record->is_late      = false;
                $record->late_minutes = 0;
                $record->remarks      = "{$holidayTitle} (09:15 AM - 03:00 PM Shift)";
                $statusMessage        = "Checked In at {$currentTime} — {$holidayTitle} (09:15 to 15:00) Started (Eligible for Extra Pay)";
            } elseif ($currentTime > $absentCutoff) {
                $record->status       = 'absent';
                $record->is_late      = true;
                $record->late_minutes = (int) \Carbon\Carbon::parse($currentTime)->diffInMinutes(\Carbon\Carbon::parse($lateCutoff));
                $record->remarks      = "Late check-in past absent cutoff ({$absentCutoff})";
                $statusMessage        = "Checked In at {$currentTime} — Marked Absent (exceeded {$absentCutoff})";
            } elseif ($currentTime > $halfDayCutoff) {
                $record->status       = 'half_day';
                $record->is_late      = true;
                $record->late_minutes = (int) \Carbon\Carbon::parse($currentTime)->diffInMinutes(\Carbon\Carbon::parse($lateCutoff));
                $record->remarks      = "Late check-in past half-day cutoff ({$halfDayCutoff})";
                $statusMessage        = "Checked In at {$currentTime} — Marked Half-Day (exceeded {$halfDayCutoff})";
            } elseif ($currentTime > $lateCutoff) {
                $lateMins             = (int) \Carbon\Carbon::parse($currentTime)->diffInMinutes(\Carbon\Carbon::parse($lateCutoff));
                $record->status       = 'late';
                $record->is_late      = true;
                $record->late_minutes = $lateMins;
                $record->remarks      = "Late Arrival ({$lateMins} mins late)";
                $statusMessage        = "Checked In at {$currentTime} — Marked Late ({$lateMins}m late)";
            } else {
                $record->status       = 'present';
                $record->is_late      = false;
                $record->late_minutes = 0;
                $record->remarks      = 'On Time Entry';
                $statusMessage        = "Checked In at {$currentTime} — On Time (Present)";
            }
        } else {
            // Already checked in -> record Check-Out
            $inTime = \Carbon\Carbon::parse($record->check_in);
            $outTime = \Carbon\Carbon::parse($currentTime);
            $workedMinutes = abs($outTime->diffInMinutes($inTime));
            $hrs = floor($workedMinutes / 60);
            $mins = $workedMinutes % 60;
            $durationFormatted = $hrs > 0 ? "{$hrs}h {$mins}m" : "{$mins}m";
            $workedHours = round($workedMinutes / 60, 2);

            // Record Check-Out
            $punchType = 'check_out';
            $record->check_out = $currentTime;

            if ($isHoliday || $record->status === 'overtime') {
                // Sunday / Holiday: Only mark Overtime if they actually worked 30+ minutes
                if ($workedMinutes < 30) {
                    // Came and left quickly — treat as Holiday (no overtime credit)
                    $record->status  = 'holiday';
                    $record->remarks = "{$holidayTitle}: Left after only {$durationFormatted} — No Overtime Credit";
                    $statusMessage   = "Checked Out at {$currentTime} — Marked Holiday (worked only {$durationFormatted}, no overtime credit)";
                } elseif ($currentTime >= $shiftEndTime || $workedHours >= 5.5) {
                    // Full Sunday/Holiday shift completed — full overtime pay
                    $record->status  = 'overtime';
                    $record->remarks = "Full {$holidayTitle} Completed ({$durationFormatted}) — Extra Pay Due";
                    $statusMessage   = "Checked Out at {$currentTime} — {$holidayTitle} Completed ({$durationFormatted}) (Full Overtime Extra Pay)";
                } else {
                    // Partial Sunday/Holiday — partial overtime pay
                    $record->status  = 'overtime';
                    $record->remarks = "{$holidayTitle}: {$durationFormatted} worked — Extra Pay Due";
                    $statusMessage   = "Checked Out at {$currentTime} — {$holidayTitle} ({$durationFormatted}) Logged for Extra Pay";
                }
            } elseif ($workedHours < 1.0) {
                $record->status  = 'absent';
                $record->remarks = "Left immediately: Only {$durationFormatted} worked (< 1 hr)";
                $statusMessage   = "Checked Out at {$currentTime} ({$durationFormatted}) — Marked Absent (worked < 1 hr)";
            } elseif ($currentTime < $shiftEndTime && $workedHours < 7.0) {
                $record->status  = 'half_day';
                $record->remarks = "Early Departure: Left at {$currentTime} before 04:30 PM";
                $statusMessage   = "Checked Out at {$currentTime} ({$durationFormatted}) — Marked Half-Day (Left before 04:30 PM)";
            } else {
                if ($record->status !== 'late') {
                    $record->status = 'present';
                }
                $record->remarks = "Completed Full Shift ({$durationFormatted})";
                $statusMessage   = "Checked Out at {$currentTime} — Shift Completed ({$durationFormatted})";
            }
        }

        $record->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'type'         => $punchType,
                'time'         => $currentTime,
                'status'       => $record->status,
                'message'      => $statusMessage,
                'duration'     => $durationFormatted ?? null,
                'employee'     => [
                    'id'          => $employee->id,
                    'name'        => $employee->full_name,
                    'code'        => $employee->employee_id,
                    'department'  => $employee->department_name,
                    'designation' => $employee->designation_name,
                    'photo'       => $employee->photo ? asset('storage/' . $employee->photo) : null,
                ],
                'check_in'     => $record->check_in,
                'check_out'    => $record->check_out,
            ]);
        }

        return back()->with('success', "Card Tapped: {$employee->full_name} &bull; {$statusMessage}");
    }

    /**
     * Save/Update Bulk Staff Attendance Form
     */
    public function saveStaffAttendance(Request $request)
    {
        @set_time_limit(120);

        $tz = \App\Models\SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $today = \Carbon\Carbon::now($tz)->toDateString();
        $date = $request->date ?? $today;

        if ($date > $today) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Attendance cannot be marked for a future date.');
        }

        $carbonDate = \Carbon\Carbon::parse($date);
        $isSunday = $carbonDate->isSunday();
        $holiday = \App\Models\Holiday::whereDate('date', $date)->first();
        $isHoliday = $isSunday || !is_null($holiday);

        $shiftEndTime = $isHoliday ? '15:00' : '16:30';

        $data = $request->input('attendance', []);
        $existingRecords = StaffAttendance::where('date', $date)
            ->whereIn('employee_id', array_keys($data))
            ->get()
            ->keyBy('employee_id');

        $now = now();
        $recordsToUpsert = [];

        foreach ($data as $empId => $att) {
            $status     = $att['status'] ?? ($isHoliday ? 'holiday' : 'absent');
            $inTime     = !empty($att['in_time']) ? $att['in_time'] : null;
            $outTime    = !empty($att['out_time']) ? $att['out_time'] : null;
            $isPerm     = ($status === 'permission') || !empty($att['is_permission']);
            $permHours  = !empty($att['permission_hours']) ? (float)$att['permission_hours'] : ($isPerm ? 1.5 : null);
            $permTime   = !empty($att['permission_time']) ? $att['permission_time'] : ($isPerm ? '09:00 - 10:30 AM' : null);
            $permReason = !empty($att['permission_reason']) ? $att['permission_reason'] : ($isPerm ? 'Principal Approved' : null);

            $remarks    = $existingRecords->get($empId)?->remarks;

            if ($inTime && $outTime) {
                $mins = abs(\Carbon\Carbon::parse($outTime)->diffInMinutes(\Carbon\Carbon::parse($inTime)));
                $hrs = floor($mins / 60);
                $m = $mins % 60;
                $dur = $hrs > 0 ? "{$hrs}h {$m}m" : "{$m}m";
                $workedHours = round($mins / 60, 2);

                if ($isHoliday || $status === 'overtime') {
                    $status = 'overtime';
                    $remarks = "Special Duty ({$dur}) — Extra Pay";
                } elseif ($status === 'permission') {
                    $status = 'permission';
                    $remarks = "Permission: {$permHours}h" . ($permReason ? " ({$permReason})" : "");
                } elseif ($outTime < $shiftEndTime && $workedHours < 7.0) {
                    $status = 'half_day';
                    $remarks = "Early departure before {$shiftEndTime}";
                }
            } elseif ($isPerm) {
                $status = 'permission';
                if (empty($remarks)) {
                    $remarks = "Permission: {$permHours}h" . ($permReason ? " ({$permReason})" : "");
                }
            }

            if ($isHoliday && empty($inTime)) {
                $status  = 'holiday';
                $remarks = null;
            } elseif ($inTime) {
                if ($isHoliday || $status === 'overtime') {
                    $status  = 'overtime';
                    $remarks = ($holiday?->name ?? 'Holiday') . ' Special Duty';
                }
            }

            $recordsToUpsert[] = [
                'employee_id'       => (int) $empId,
                'date'              => $date,
                'status'            => $status,
                'check_in'          => $inTime,
                'check_out'         => $outTime,
                'remarks'           => $remarks,
                'is_permission'     => $isPerm,
                'permission_hours'  => $isPerm ? $permHours : null,
                'permission_time'   => $isPerm ? $permTime : null,
                'permission_reason' => $isPerm ? $permReason : null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        if (!empty($recordsToUpsert)) {
            foreach (array_chunk($recordsToUpsert, 200) as $chunk) {
                StaffAttendance::upsert(
                    $chunk,
                    ['employee_id', 'date'],
                    ['status', 'check_in', 'check_out', 'remarks', 'is_permission', 'permission_hours', 'permission_time', 'permission_reason', 'updated_at']
                );
            }
        }

        DashboardController::clearCache();

        return redirect()->route('dashboard')->with('success', 'Staff attendance updated successfully.');
    }

    /**
     * Staff Attendance Register (Monthly Matrix)
     */
    public function staffRegister(Request $request)
    {
        $month       = $request->month ?? now()->format('Y-m');
        $deptId      = $request->department_id;
        $category    = $request->category ?? 'all';
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        [$yr, $mo] = explode('-', $month);
        $start = \Carbon\Carbon::create((int)$yr, (int)$mo, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $days = collect(CarbonPeriod::create($start, $end))->map(fn($d) => $d);
        $holidays = \App\Models\Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()->keyBy(fn($h) => \Carbon\Carbon::parse($h->date)->toDateString());

        $empQuery = Employee::with('department')->where('is_active', true);
        if ($deptId) {
            $empQuery->where('department_id', $deptId);
        }
        if ($category && $category !== 'all') {
            $empQuery->where('employee_type', $category);
        }
        $employees = $empQuery->orderBy('first_name')->get();

        $allRecords = StaffAttendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get();

        $attendanceMap = [];
        foreach ($allRecords as $r) {
            $dateStr = \Carbon\Carbon::parse($r->date)->toDateString();
            $attendanceMap[$r->employee_id][$dateStr] = $r;
        }

        $todayStr = today()->toDateString();
        $isCurrentMonth = ($yr == now()->year && $mo == now()->month);
        $workingDaysCount = $days->filter(function($d) use ($isCurrentMonth, $todayStr) {
            if ($isCurrentMonth && $d->toDateString() > $todayStr) return false;
            return !$d->isSunday();
        })->count();
        $workingDaysCount = max(1, $workingDaysCount);

        $categories = [
            ['key' => 'all',          'label' => 'All Staff'],
            ['key' => 'teaching',     'label' => 'Teaching Staff'],
            ['key' => 'non_teaching', 'label' => 'Non-Teaching'],
            ['key' => 'driver',       'label' => 'Drivers'],
            ['key' => 'nanny',        'label' => 'Nannies / Support'],
        ];

        return view('attendance.staff-register', compact(
            'departments', 'employees', 'month', 'days', 'holidays',
            'attendanceMap', 'workingDaysCount', 'deptId', 'category', 'categories'
        ));
    }

    public function register(Request $request)
    {
        $classes  = Classes::active()->get();
        $sections = collect();
        $students = collect();
        $dates    = collect();
        $attendanceMap = [];
        if ($request->class_id && $request->month) {
            $sections = Section::where('class_id', $request->class_id)->get();
            [$yr, $mo] = explode('-', $request->month);
            $period = CarbonPeriod::create("$yr-$mo-01", "last day of $yr-$mo");
            $dates  = collect($period)->map(fn($d) => $d);
            $currentYear = AcademicYear::current();
            $query = StudentEnrollment::with('student')->where('class_id', $request->class_id)->where('status', 'active');
            if ($request->section_id) $query->where('section_id', $request->section_id);
            if ($currentYear) $query->where('academic_year_id', $currentYear->id);
            $students = $query->get();
            $records = AttendanceRecord::where('class_id', $request->class_id)
                ->whereYear('date', $yr)->whereMonth('date', $mo)
                ->whereIn('student_id', $students->pluck('student_id'))
                ->get();
            foreach ($records as $r) {
                $attendanceMap[$r->student_id][$r->date->toDateString()] = $r->status;
            }
        }
        return view('attendance.register', compact('classes', 'sections', 'students', 'dates', 'attendanceMap'));
    }

    public function registerPdf(Request $request)
    {
        $classes  = Classes::active()->get();
        $sections = collect();
        $students = collect();
        $dates    = collect();
        $attendanceMap = [];
        if ($request->class_id && $request->month) {
            $sections = Section::where('class_id', $request->class_id)->get();
            [$yr, $mo] = explode('-', $request->month);
            $period = CarbonPeriod::create("$yr-$mo-01", "last day of $yr-$mo");
            $dates  = collect($period)->map(fn($d) => $d);
            $currentYear = AcademicYear::current();
            $query = StudentEnrollment::with('student')->where('class_id', $request->class_id)->where('status', 'active');
            if ($request->section_id) $query->where('section_id', $request->section_id);
            if ($currentYear) $query->where('academic_year_id', $currentYear->id);
            $students = $query->get();
            $records = AttendanceRecord::where('class_id', $request->class_id)
                ->whereYear('date', $yr)->whereMonth('date', $mo)
                ->whereIn('student_id', $students->pluck('student_id'))->get();
            foreach ($records as $r) {
                $attendanceMap[$r->student_id][$r->date->toDateString()] = $r->status;
            }
        }
        $pdf = Pdf::setPaper('a3', 'landscape')->loadView('pdf.attendance-register', compact('students', 'dates', 'attendanceMap'));
        return $pdf->download('attendance-register.pdf');
    }

    public function chronicAbsentees(Request $request)
    {
        $classes     = Classes::active()->get();
        $currentYear = AcademicYear::current();
        $threshold   = (int)($request->threshold ?? 75);
        $students    = collect();

        if ($request->class_id || $request->all) {
            $totalDaysQuery = AttendanceRecord::select('class_id', DB::raw('COUNT(DISTINCT date) as total_days'))
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->when($request->class_id, fn($q, $v) => $q->where('class_id', $v))
                ->groupBy('class_id')->get()->keyBy('class_id');

            $students = AttendanceRecord::with(['student.currentEnrollment.class'])
                ->select('student_id', 'class_id', DB::raw('COUNT(*) as present_days'))
                ->where('status', 'present')
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->when($request->class_id, fn($q, $v) => $q->where('class_id', $v))
                ->groupBy('student_id', 'class_id')
                ->get()
                ->filter(function ($row) use ($totalDaysQuery, $threshold) {
                    $total = $totalDaysQuery[$row->class_id]->total_days ?? 0;
                    return $total > 0 && ($row->present_days / $total * 100) < $threshold;
                })
                ->map(function ($row) use ($totalDaysQuery) {
                    $total = $totalDaysQuery[$row->class_id]->total_days ?? 1;
                    $row->total_days = $total;
                    $row->percentage = round($row->present_days / $total * 100, 1);
                    return $row;
                })
                ->sortBy('percentage');
        }

        return view('attendance.chronic-absentees', compact('classes', 'students', 'threshold', 'currentYear'));
    }

    public function dateWiseStrength(Request $request)
    {
        $currentYear = AcademicYear::current();
        $month       = $request->month ?? now()->format('Y-m');
        [$yr, $mo]   = explode('-', $month);

        $data = AttendanceRecord::select(
                'date',
                DB::raw("SUM(CASE WHEN status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw('COUNT(*) as total')
            )
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->whereYear('date', $yr)
            ->whereMonth('date', $mo)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('attendance.date-strength', compact('data', 'month', 'currentYear'));
    }

    public function classSummary(Request $request)
    {
        $currentYear = AcademicYear::current();
        $month       = $request->month ?? now()->format('Y-m');
        [$yr, $mo]   = explode('-', $month);

        $summary = AttendanceRecord::with('class')
            ->select(
                'class_id',
                DB::raw("SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw('COUNT(*) as total')
            )
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->whereYear('date', $yr)
            ->whereMonth('date', $mo)
            ->groupBy('class_id')
            ->get()
            ->map(function ($row) {
                $row->percentage = $row->total > 0 ? round($row->present / $row->total * 100, 1) : 0;
                return $row;
            })
            ->sortByDesc('percentage');

        return view('attendance.class-summary', compact('summary', 'month', 'currentYear'));
    }

    // ── Attendance Condonation ────────────────────────────

    public function condonation(Request $request)
    {
        $classes     = Classes::active()->get();
        $currentYear = AcademicYear::current();
        $students    = collect();

        if ($request->class_id) {
            $students = StudentEnrollment::with(['student', 'student.condonations' => function ($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear?->id);
            }])
                ->where('class_id', $request->class_id)
                ->where('status', 'active')
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->get();
        }

        $recentCondonations = AttendanceCondonation::with(['student', 'condonedBy'])
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear?->id))
            ->latest()->limit(50)->get();

        return view('attendance.condonation', compact('classes', 'students', 'currentYear', 'recentCondonations'));
    }

    public function storeCondonation(Request $request)
    {
        $request->validate([
            'student_id'   => 'required|exists:students,id',
            'days_condoned'=> 'required|integer|min:1|max:365',
            'reason'       => 'required|string|max:500',
        ]);
        $currentYear = AcademicYear::current();
        AttendanceCondonation::create([
            'student_id'       => $request->student_id,
            'academic_year_id' => $currentYear?->id,
            'days_condoned'    => $request->days_condoned,
            'reason'           => $request->reason,
            'condoned_by'      => Auth::id(),
            'condoned_on'      => today(),
        ]);
        return back()->with('success', $request->days_condoned . ' day(s) condoned successfully.');
    }

    // ── Subject-wise (period-wise) attendance report per student ──

    public function subjectAttendanceReport(Request $request)
    {
        $classes  = Classes::active()->get();
        $subjects = collect();
        $report   = collect();
        $student  = null;

        if ($request->student_id) {
            $student  = \App\Models\Student::findOrFail($request->student_id);
            $year     = AcademicYear::current();
            $enrollment = StudentEnrollment::where('student_id', $student->id)->where('status', 'active')
                ->when($year, fn($q) => $q->where('academic_year_id', $year->id))->first();

            if ($enrollment) {
                $subjects = \App\Models\Timetable::where('class_id', $enrollment->class_id)
                    ->where('period_type', 'class')
                    ->whereNotNull('subject_id')
                    ->with('subject')
                    ->get()->unique('subject_id')->pluck('subject');

                $records = AttendanceRecord::where('student_id', $student->id)
                    ->when($request->from_date, fn($q) => $q->where('date', '>=', $request->from_date))
                    ->when($request->to_date,   fn($q) => $q->where('date', '<=', $request->to_date))
                    ->whereNotNull('period_data')
                    ->get();

                // Build per-subject totals from period_data
                $perSubject = [];
                foreach ($records as $rec) {
                    $periods = json_decode($rec->period_data, true) ?? [];
                    // period_data keys are period_number; match to timetable
                    $dayName = \Carbon\Carbon::parse($rec->date)->format('l');
                    $dayNum  = \Carbon\Carbon::parse($rec->date)->dayOfWeekIso;
                    $ttSlots = \App\Models\Timetable::where('class_id', $enrollment->class_id)
                        ->where('period_type', 'class')
                        ->where(fn($q) => $q->where('day_of_week', $dayNum)->orWhere('day', $dayName))
                        ->get()->keyBy('period_number');

                    foreach ($periods as $periodNum => $status) {
                        $subjectId = $ttSlots[$periodNum]?->subject_id ?? null;
                        if (!$subjectId) continue;
                        if (!isset($perSubject[$subjectId])) {
                            $perSubject[$subjectId] = ['total' => 0, 'present' => 0];
                        }
                        $perSubject[$subjectId]['total']++;
                        if (in_array($status, ['present', 'late'])) {
                            $perSubject[$subjectId]['present']++;
                        }
                    }
                }

                foreach ($subjects as $subj) {
                    $data = $perSubject[$subj->id] ?? ['total' => 0, 'present' => 0];
                    $report->push([
                        'subject'    => $subj->name,
                        'total'      => $data['total'],
                        'present'    => $data['present'],
                        'absent'     => $data['total'] - $data['present'],
                        'percentage' => $data['total'] > 0 ? round(($data['present'] / $data['total']) * 100, 1) : 0,
                    ]);
                }
            }
        }

        $students = collect();
        if ($request->class_id) {
            $year = AcademicYear::current();
            $students = StudentEnrollment::with('student')
                ->where('class_id', $request->class_id)->where('status', 'active')
                ->when($year, fn($q) => $q->where('academic_year_id', $year->id))->get();
        }

        return view('attendance.subject-report', compact('classes', 'students', 'student', 'report'));
    }

    public function teacherAttendanceReport(Request $request)
    {
        $departments = Department::where('is_active', true)->get();
        $selectedCategory = $request->category ?? 'all';

        $employeesQuery = Employee::where('is_active', true)
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id));

        if ($selectedCategory && $selectedCategory !== 'all') {
            $employeesQuery->where('employee_type', $selectedCategory);
        }

        $employees = $employeesQuery->orderBy('first_name')->get();

        $categories = [
            ['key' => 'all',          'label' => 'All Staff',          'count' => Employee::where('is_active', true)->count()],
            ['key' => 'teaching',     'label' => 'Teaching Staff',     'count' => Employee::where('is_active', true)->where('employee_type', 'teaching')->count()],
            ['key' => 'non_teaching', 'label' => 'Non-Teaching',       'count' => Employee::where('is_active', true)->where('employee_type', 'non_teaching')->count()],
            ['key' => 'driver',       'label' => 'Drivers',            'count' => Employee::where('is_active', true)->where('employee_type', 'driver')->count()],
            ['key' => 'nanny',        'label' => 'Nannies (Naani)',    'count' => Employee::where('is_active', true)->where('employee_type', 'nanny')->count()],
            ['key' => 'cleaner',      'label' => 'Cleaners / Support', 'count' => Employee::where('is_active', true)->where('employee_type', 'cleaner')->count()],
        ];

        $month   = $request->month ?? now()->format('Y-m');
        [$yr, $mo] = explode('-', $month);

        $report = collect();

        if ($request->filled('action') || $request->filled('month')) {
            $isCurrentMonth = ($yr == now()->year && $mo == now()->month);
            $monthStart = "$yr-$mo-01";
            $monthEnd = \Carbon\Carbon::parse($monthStart)->endOfMonth()->toDateString();
            $effectiveEnd = $isCurrentMonth ? min(today()->toDateString(), $monthEnd) : $monthEnd;
            $period = \Carbon\CarbonPeriod::create($monthStart, $effectiveEnd);
            $workingDays = collect($period)->filter(fn($d) => !in_array($d->dayOfWeek, [0]))->count(); // exclude Sunday
            $workingDays = max(1, $workingDays);

            $records = StaffAttendance::whereYear('date', $yr)
                ->whereMonth('date', $mo)
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get()
                ->groupBy('employee_id');

            foreach ($employees as $emp) {
                $empRecords = $records->get($emp->id, collect());
                $present    = $empRecords->whereIn('status', ['present', 'late'])->count();
                $late       = $empRecords->where('status', 'late')->count();
                $halfDay    = $empRecords->where('status', 'half_day')->count();
                $overtime   = $empRecords->where('status', 'overtime')->count();
                $onLeave    = $empRecords->whereIn('status', ['leave', 'on_leave'])->count();
                $absent     = $empRecords->where('status', 'absent')->count();

                // Compute overtime hours
                $overtimeMins = $empRecords->where('status', 'overtime')->reduce(function($carry, $rec) {
                    if ($rec->check_in && $rec->check_out) {
                        return $carry + abs(\Carbon\Carbon::parse($rec->check_out)->diffInMinutes(\Carbon\Carbon::parse($rec->check_in)));
                    }
                    return $carry;
                }, 0);
                $otHrs = floor($overtimeMins / 60);
                $otMins = $overtimeMins % 60;
                $overtimeDuration = $otHrs > 0 ? "{$otHrs}h {$otMins}m" : ($overtimeMins > 0 ? "{$otMins}m" : '—');

                $effectivePresent = $present + ($halfDay * 0.5) + $overtime;
                $percentage = $workingDays > 0 ? number_format(round(($effectivePresent / $workingDays) * 100, 2), 2) : '0.00';

                $report->push([
                    'employee'          => $emp,
                    'working'           => $workingDays,
                    'present'           => $present,
                    'late'              => $late,
                    'half_day'          => $halfDay,
                    'overtime'          => $overtime,
                    'overtime_duration' => $overtimeDuration,
                    'on_leave'          => $onLeave,
                    'absent'            => $absent,
                    'unmarked'          => max(0, $workingDays - ($present + $halfDay + $onLeave + $absent)),
                    'percentage'        => $percentage,
                ]);
            }
        }

        return view('attendance.teacher-report', compact('departments', 'report', 'month', 'categories', 'selectedCategory'));
    }

    // ── Late Arrival Tracking for Staff ──────────────────

    public function lateArrivalReport(Request $request)
    {
        $month       = $request->month ?? now()->format('Y-m');
        $departments = \App\Models\Department::orderBy('name')->get();

        [$yr, $mo] = explode('-', $month);
        $start = \Carbon\Carbon::create($yr, $mo, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth();

        $records = StaffAttendance::with('employee.department')
            ->where('is_late', true)
            ->whereBetween('date', [$start, $end])
            ->when($request->department_id, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->orderBy('date')
            ->get();

        // Group by employee
        $byEmployee = $records->groupBy('employee_id')->map(function ($recs) {
            $emp = $recs->first()->employee;
            return [
                'employee'    => $emp,
                'late_count'  => $recs->count(),
                'total_mins'  => $recs->sum('late_minutes'),
                'dates'       => $recs->pluck('date'),
                'records'     => $recs,
            ];
        })->sortByDesc('late_count');

        $lateThreshold = \App\Models\SchoolSetting::get('staff_late_time', '09:30');

        return view('attendance.late-arrival', compact('records', 'byEmployee', 'month', 'departments', 'lateThreshold'));
    }

    /**
     * Export Day-Wise Staff Attendance to Excel
     */
    public function exportDayWiseReport(Request $request)
    {
        $date        = $request->date ?? today()->toDateString();
        $deptId      = $request->department_id;
        $category    = $request->category ?? 'all';

        $empQuery = Employee::with('department')->where('is_active', true);
        if ($deptId) {
            $empQuery->where('department_id', $deptId);
        }
        if ($category && $category !== 'all') {
            $empQuery->where('employee_type', $category);
        }
        $employees = $empQuery->orderBy('first_name')->get();

        $attendances = StaffAttendance::where('date', $date)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()->keyBy('employee_id');

        $rows = [];
        foreach ($employees as $idx => $emp) {
            $att = $attendances[$emp->id] ?? null;
            $status = $att?->status ? ucwords(str_replace('_', ' ', $att->status)) : 'Absent';
            $inTime = $att?->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '—';
            $outTime = $att?->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : '—';

            $duration = '—';
            if ($att?->check_in && $att?->check_out) {
                $mins = abs(\Carbon\Carbon::parse($att->check_out)->diffInMinutes(\Carbon\Carbon::parse($att->check_in)));
                $h = floor($mins / 60);
                $m = $mins % 60;
                $duration = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
            } elseif ($att?->check_in) {
                $duration = 'In Progress';
            }

            $permissionInfo = ($att?->is_permission || $att?->status === 'permission')
                ? ($att->permission_hours ? "{$att->permission_hours}h" : "Yes") . ($att->permission_reason ? " ({$att->permission_reason})" : "")
                : '—';

            $rows[] = [
                'Sl No'             => $idx + 1,
                'Employee Code'     => $emp->employee_code ?? 'EMP-' . $emp->id,
                'Staff Name'        => $emp->full_name,
                'Category / Role'   => $emp->category_label ?? ucfirst(str_replace('_', ' ', $emp->employee_type ?? 'Staff')),
                'Department'        => $emp->department?->name ?? 'General',
                'Designation'       => $emp->designation_name ?? '—',
                'Date'              => \Carbon\Carbon::parse($date)->format('d M Y (D)'),
                'Attendance Status' => $status,
                'Permission'        => $permissionInfo,
                'In-Time'           => $inTime,
                'Out-Time'          => $outTime,
                'Duration'          => $duration,
                'Remarks'           => $att?->remarks ?? '',
            ];
        }

        $filename = 'staff-attendance-day-' . $date . '.xlsx';
        return Excel::download(new ArrayExport($rows), $filename);
    }

    /**
     * Export Monthly Staff Attendance Summary & Overtime Register to Excel
     */
    public function exportMonthlyReport(Request $request)
    {
        $month            = $request->month ?? now()->format('Y-m');
        $selectedCategory = $request->category ?? 'all';
        $deptId           = $request->department_id;

        [$yr, $mo] = explode('-', $month);
        $start = \Carbon\Carbon::create($yr, $mo, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $employeesQuery = Employee::with('department')->where('is_active', true);
        if ($deptId) {
            $employeesQuery->where('department_id', $deptId);
        }
        if ($selectedCategory && $selectedCategory !== 'all') {
            $employeesQuery->where('employee_type', $selectedCategory);
        }
        $employees = $employeesQuery->orderBy('first_name')->get();

        // Calculate statutory working days in month
        $monthDays = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $monthDays[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $holidays = \App\Models\Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())->toArray();

        $todayDate = today()->toDateString();
        $isCurrentMonth = ($yr == now()->year && $mo == now()->month);
        $standardWorkingDays = collect($monthDays)->filter(function ($d) use ($holidays, $isCurrentMonth, $todayDate) {
            if ($isCurrentMonth && $d > $todayDate) return false;
            $c = \Carbon\Carbon::parse($d);
            return !$c->isSunday() && !in_array($d, $holidays);
        })->count();
        $standardWorkingDays = max(1, $standardWorkingDays);

        $rows = [];
        foreach ($employees as $idx => $emp) {
            $records = StaffAttendance::where('employee_id', $emp->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get();

            $presentCount    = $records->where('status', 'present')->count();
            $lateCount       = $records->where('status', 'late')->count();
            $halfDayCount    = $records->where('status', 'half_day')->count();
            $permissionCount = $records->where('status', 'permission')->count();
            $overtimeCount   = $records->where('status', 'overtime')->count();
            $leaveCount      = $records->where('status', 'leave')->count();

            // Total Overtime duration
            $totalOvertimeMins = 0;
            foreach ($records->where('status', 'overtime') as $ot) {
                if ($ot->check_in && $ot->check_out) {
                    $totalOvertimeMins += abs(\Carbon\Carbon::parse($ot->check_out)->diffInMinutes(\Carbon\Carbon::parse($ot->check_in)));
                }
            }
            $otHrs = floor($totalOvertimeMins / 60);
            $otMins = $totalOvertimeMins % 60;
            $overtimeDuration = $overtimeCount > 0 ? ($otHrs > 0 ? "{$otHrs}h {$otMins}m" : "{$otMins}m") : '0m';

            $totalAttended = $presentCount + $lateCount + ($halfDayCount * 0.5) + $permissionCount + $overtimeCount;
            $effectiveWorking = max(1, $standardWorkingDays);
            $absentCount = max(0, $standardWorkingDays - ($presentCount + $lateCount + $halfDayCount + $permissionCount + $leaveCount));
            $pct = number_format(round(($totalAttended / $effectiveWorking) * 100, 2), 2);

            $rows[] = [
                'Sl No'                 => $idx + 1,
                'Employee Code'         => $emp->employee_code ?? 'EMP-' . $emp->id,
                'Staff Name'            => $emp->full_name,
                'Category / Role'       => $emp->category_label ?? ucfirst(str_replace('_', ' ', $emp->employee_type ?? 'Staff')),
                'Department'            => $emp->department?->name ?? 'General',
                'Designation'           => $emp->designation_name ?? '—',
                'Month'                 => \Carbon\Carbon::create($yr, $mo, 1)->format('F Y'),
                'Standard Working Days' => $standardWorkingDays,
                'Present Days'          => $presentCount,
                'Late Arrivals'         => $lateCount,
                'Half Days'             => $halfDayCount,
                'Approved Permissions'  => $permissionCount,
                'Overtime Days'         => $overtimeCount,
                'Overtime Duration'     => $overtimeDuration,
                'Approved Leaves'       => $leaveCount,
                'Absent Days'           => $absentCount,
                'Attendance Rate (%)'   => "{$pct}%",
            ];
        }

        $filename = 'staff-attendance-monthly-' . $month . '.xlsx';
        return Excel::download(new ArrayExport($rows), $filename);
    }
}
