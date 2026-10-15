<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Models\EmployeeDocument;
use App\Models\EmployeeCertification;
use App\Models\EmployeeFamilyMember;
use App\Models\EmployeeCareerHistory;
use App\Models\PayrollRecord;
use App\Exports\SalaryRegisterExport;
use Spatie\Permission\Models\Role;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\EmployeeQualification;
use App\Models\EmployeeExperience;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\StaffAttendance;
use App\Models\StaffPermissionSession;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\StaffEvent;
use App\Models\AcademicYear;
use App\Models\AcademicTerm;
use App\Models\Holiday;
use App\Models\SchoolSetting;
use App\Models\Classes;
use App\Models\StaffClassTeacherAssignment;
use App\Models\TeachingLevel;
use App\Models\TeacherLevelEligibility;
use App\Models\TeacherSubjectAllocation;
use App\Models\Subject;
use App\Models\Section;
use App\Models\User;
use App\Modules\Rbac\Services\RbacAuditService;

class HrController extends Controller
{
    public function index(Request $request)
    {
        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $today = Carbon::now($tz)->toDateString();

        // 1. Total active staff
        $activeEmployees = Employee::with(['department', 'designation'])->where('is_active', true)->get();
        $totalStaff = $activeEmployees->count();

        // 2. Today's attendance records from staff_attendance table
        $todayAttendances = DB::table('staff_attendance')
            ->whereDate('date', $today)
            ->select('id', 'employee_id', 'status', 'check_in')
            ->get()
            ->keyBy('employee_id');

        // Approved leaves for today
        $approvedLeavesToday = DB::table('leave_requests')
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->pluck('employee_id')
            ->flip();

        $presentToday    = 0;
        $absentToday     = 0;
        $onLeaveToday    = 0;
        $onDutyToday     = 0;
        $paidOffToday    = 0;
        $permissionToday = 0;
        $notMarkedToday  = 0;

        foreach ($activeEmployees as $emp) {
            $att = $todayAttendances->get($emp->id);
            if ($att) {
                if ($att->status === 'on_duty') {
                    $onDutyToday++;
                } elseif ($att->status === 'paid_off') {
                    $paidOffToday++;
                } elseif ($att->status === 'permission') {
                    $permissionToday++;
                } elseif (in_array($att->status, ['present', 'late', 'half_day', 'overtime']) || (!empty($att->check_in) && !in_array($att->status, ['on_duty', 'paid_off', 'permission', 'absent']))) {
                    $presentToday++;
                } elseif ($att->status === 'absent') {
                    $absentToday++;
                } elseif (in_array($att->status, ['leave', 'on_leave']) || $approvedLeavesToday->has($emp->id)) {
                    $onLeaveToday++;
                } else {
                    $notMarkedToday++;
                }
            } else {
                if ($approvedLeavesToday->has($emp->id)) {
                    $onLeaveToday++;
                } else {
                    $notMarkedToday++;
                }
            }
        }

        // Pending approvals
        $pendingStaffApprovals = Employee::where(function($q) {
            $q->where('approval_status', 'pending')
              ->orWhere('status', 'pending');
        })->count();

        $pendingLeaves = DB::table('leave_requests')->where('status', 'pending')->count();

        // Today's attendance grouped by staff category
        $categoriesAttendance = [];
        $grouped = $activeEmployees->groupBy(fn($e) => $e->category_label);
        foreach ($grouped as $label => $emps) {
            $cTotal = $emps->count();
            $cPresent = 0;
            $cAbsent = 0;
            $cLeave = 0;
            $cNotMarked = 0;

            foreach ($emps as $emp) {
                $att = $todayAttendances->get($emp->id);
                if ($att) {
                    if ($att->status === 'on_duty' || $att->status === 'paid_off' || $att->status === 'permission') {
                        // Counted in total category staff
                    } elseif (in_array($att->status, ['present', 'late', 'half_day', 'overtime']) || !empty($att->check_in)) {
                        $cPresent++;
                    } elseif ($att->status === 'absent') {
                        $cAbsent++;
                    } elseif (in_array($att->status, ['leave', 'on_leave']) || $approvedLeavesToday->has($emp->id)) {
                        $cLeave++;
                    } else {
                        $cNotMarked++;
                    }
                } else {
                    if ($approvedLeavesToday->has($emp->id)) {
                        $cLeave++;
                    } else {
                        $cNotMarked++;
                    }
                }
            }

            $categoriesAttendance[] = [
                'category'   => $label,
                'total'      => $cTotal,
                'present'    => $cPresent,
                'absent'     => $cAbsent,
                'on_leave'   => $cLeave,
                'not_marked' => $cNotMarked,
                'pct'        => $cTotal > 0 ? round(($cPresent / $cTotal) * 100) : 0,
            ];
        }

        // Upcoming staff events
        $upcomingEvents = array_slice($this->getUpcomingStaffCelebrations(), 0, 8);

        // Recent hires
        $recentHires = Employee::where('joining_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('joining_date')
            ->limit(5)
            ->get();

        return view('hr.index', compact(
            'today',
            'totalStaff',
            'presentToday',
            'absentToday',
            'onLeaveToday',
            'onDutyToday',
            'paidOffToday',
            'permissionToday',
            'notMarkedToday',
            'pendingStaffApprovals',
            'pendingLeaves',
            'categoriesAttendance',
            'upcomingEvents',
            'recentHires'
        ));
    }

    public function staffApprovals(Request $request)
    {
        $status = $request->get('status', 'pending');
        $query = Employee::with(['department', 'designation']);

        if ($status === 'pending') {
            $query->where(function($q) {
                $q->where('approval_status', 'pending')
                  ->orWhere('status', 'pending');
            });
        } elseif ($status === 'approved') {
            $query->where('approval_status', 'approved');
        } elseif ($status === 'rejected') {
            $query->where(function($q) {
                $q->where('approval_status', 'rejected')
                  ->orWhere('status', 'rejected');
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('employee_type', $request->category);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('employee_code', 'ilike', "%{$search}%");
            });
        }

        $employees = $query->latest('id')->paginate(15)->withQueryString();

        $counts = [
            'pending'  => Employee::where(fn($q) => $q->where('approval_status', 'pending')->orWhere('status', 'pending'))->count(),
            'approved' => Employee::where('approval_status', 'approved')->count(),
            'rejected' => Employee::where(fn($q) => $q->where('approval_status', 'rejected')->orWhere('status', 'rejected'))->count(),
            'all'      => Employee::count(),
        ];

        return view('hr.staff-approvals', compact('employees', 'status', 'counts'));
    }

    /**
     * Staff Approval API: List pending/filtered staff approvals (Permanent Principal API contract)
     */
    public function apiStaffApprovals(Request $request)
    {
        $status = $request->get('status', 'pending');
        $query  = Employee::with(['department', 'designation']);

        if ($status === 'pending') {
            $query->where(function ($q) {
                $q->where('approval_status', 'pending')
                  ->orWhere('status', 'pending');
            });
        } elseif ($status === 'approved') {
            $query->where('approval_status', 'approved');
        } elseif ($status === 'rejected') {
            $query->where(function ($q) {
                $q->where('approval_status', 'rejected')
                  ->orWhere('status', 'rejected');
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('employee_type', $request->category);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('employee_code', 'ilike', "%{$search}%");
            });
        }

        $perPage   = min(max((int)$request->get('per_page', 15), 1), 100);
        $employees = $query->latest('id')->paginate($perPage);

        $counts = [
            'pending'  => Employee::where(fn($q) => $q->where('approval_status', 'pending')->orWhere('status', 'pending'))->count(),
            'approved' => Employee::where('approval_status', 'approved')->count(),
            'rejected' => Employee::where(fn($q) => $q->where('approval_status', 'rejected')->orWhere('status', 'rejected'))->count(),
            'all'      => Employee::count(),
        ];

        return response()->json([
            'success'    => true,
            'filter'     => $status,
            'counts'     => $counts,
            'data'       => $employees->items(),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page'    => $employees->lastPage(),
                'per_page'     => $employees->perPage(),
                'total'        => $employees->total(),
            ],
        ], 200);
    }

    /**
     * Staff Approval API: Retrieve single staff approval record details (Permanent Principal API contract)
     */
    public function apiStaffApprovalDetail(int $id)
    {
        $employee = Employee::with(['department', 'designation', 'manager', 'qualifications', 'experiences'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $employee,
        ], 200);
    }

    public function approveStaff(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);
        $employee->update([
            'approval_status' => 'approved',
            'status'          => 'active',
            'is_active'       => true,
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'rejection_reason'=> null,
        ]);

        AuditLog::record('employee_approved', $employee, [], [
            'employee_code' => $employee->employee_code,
            'name'          => $employee->full_name,
            'approved_by'   => auth()->id(),
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Staff member {$employee->full_name} has been approved and activated.",
                'data'    => $employee,
            ], 200);
        }

        return back()->with('success', "Staff member {$employee->full_name} has been approved and activated.");
    }

    public function rejectStaff(Request $request, int $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $reason = $request->input('rejection_reason') ?: 'Rejected by Administrator';
        $employee = Employee::findOrFail($id);
        $employee->update([
            'approval_status' => 'rejected',
            'status'          => 'rejected',
            'is_active'       => false,
            'rejection_reason'=> $reason,
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
        ]);

        AuditLog::record('employee_rejected', $employee, [], [
            'employee_code'    => $employee->employee_code,
            'name'             => $employee->full_name,
            'rejection_reason' => $request->rejection_reason,
            'rejected_by'      => auth()->id(),
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Staff member {$employee->full_name} has been rejected.",
                'data'    => $employee,
            ], 200);
        }

        return back()->with('success', "Staff member {$employee->full_name} has been rejected.");
    }

    public function leaveApprovals(Request $request)
    {
        $status = $request->get('status', 'pending');
        $query = LeaveRequest::with(['employee.department', 'leaveType', 'appliedBy']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->whereHas('employee', fn($q) => $q->where('employee_type', $request->category));
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('employee_code', 'ilike', "%{$search}%");
            });
        }

        $leaves = $query->latest('id')->paginate(15)->withQueryString();

        $counts = [
            'pending'  => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
            'all'      => LeaveRequest::count(),
        ];

        return view('hr.leave-approvals', compact('leaves', 'status', 'counts'));
    }

    public function markAttendance(Request $request)
    {
        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $today = Carbon::now($tz)->toDateString();
        $date = $request->get('date', $today);
        $category = $request->get('category', 'all');

        // Prevent selecting or accessing future dates for marking attendance
        if ($date > $today) {
            return redirect()->route('hr.attendance.mark', ['date' => $today, 'category' => $category])
                ->with('error', 'Attendance cannot be marked for a future date.');
        }

        // Auto-fill permission in-time if school dispersal reached
        \App\Console\Commands\AutoFillPermissionInTime::executeAutoFill($date);

        $empQuery = Employee::with(['department', 'designation'])
            ->where('is_active', true)
            ->where(function($q) {
                $q->where('approval_status', 'approved')
                  ->orWhereNull('approval_status');
            });
        if ($category && $category !== 'all') {
            $empQuery->where('employee_type', $category);
        }
        $employees = $empQuery->orderBy('first_name')->get();

        $attendances = StaffAttendance::with('permissionSessions')
            ->whereDate('date', $date)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        // Check approved leaves for that date
        $approvedLeaves = LeaveRequest::with('leaveType')
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $activeApproved = fn($q) => $q->where('is_active', true)->where(fn($sq) => $sq->where('approval_status', 'approved')->orWhereNull('approval_status'));

        $categories = [
            ['key' => 'all',          'label' => 'All Staff',          'count' => $activeApproved(Employee::query())->count()],
            ['key' => 'teaching',     'label' => 'Teaching Staff',     'count' => $activeApproved(Employee::where('employee_type', 'teaching'))->count()],
            ['key' => 'non_teaching', 'label' => 'Non-Teaching',       'count' => $activeApproved(Employee::where('employee_type', 'non_teaching'))->count()],
            ['key' => 'driver',       'label' => 'Drivers',            'count' => $activeApproved(Employee::where('employee_type', 'driver'))->count()],
            ['key' => 'cleaner',      'label' => 'Cleaners / Support', 'count' => $activeApproved(Employee::where('employee_type', 'cleaner'))->count()],
            ['key' => 'nanny',        'label' => 'Nannies (Naani)',    'count' => $activeApproved(Employee::whereIn('employee_type', ['nanny', 'naani']))->count()],
        ];

        return view('hr.attendance-mark', compact('employees', 'attendances', 'approvedLeaves', 'date', 'today', 'category', 'categories'));
    }

    public function saveAttendanceMark(Request $request)
    {
        @set_time_limit(120);

        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $today = Carbon::now($tz)->toDateString();

        $validated = $request->validate([
            'date'                  => "required|date|before_or_equal:{$today}",
            'category'              => 'nullable|string',
            'attendance'            => 'required|array',
            'attendance.*.status'   => 'required|in:present,absent,half_day,on_duty,paid_off,permission',
            'attendance.*.out_time' => 'nullable|string',
            'attendance.*.in_time'  => 'nullable|string',
            'attendance.*.sessions' => 'nullable|array',
        ], [
            'date.before_or_equal' => 'Attendance cannot be marked for a future date.',
        ]);

        $date = $validated['date'];
        if ($date > $today) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date' => 'Attendance cannot be marked for a future date.'])
                ->with('error', 'Attendance cannot be marked for a future date.');
        }

        $attendanceData = $validated['attendance'];

        // Enforce Permission validation rules:
        // Out Time is compulsory, In Time >= Out Time, and no overlapping sessions on same day
        $parsedPermissions = [];

        foreach ($attendanceData as $empId => $att) {
            $status = $att['status'] ?? '';
            if ($status === 'permission') {
                $sessionsList = [];

                if (!empty($att['sessions']) && is_array($att['sessions'])) {
                    foreach ($att['sessions'] as $s) {
                        $sOut = trim($s['out_time'] ?? '');
                        $sIn  = trim($s['in_time'] ?? '');
                        if ($sOut !== '' || $sIn !== '') {
                            $sessionsList[] = ['out_time' => $sOut, 'in_time' => $sIn];
                        }
                    }
                }

                // Fallback to top-level out_time / in_time if sessions array was empty
                if (empty($sessionsList)) {
                    $rawOut = trim($att['out_time'] ?? '');
                    $rawIn  = trim($att['in_time'] ?? '');
                    if ($rawOut !== '') {
                        $sessionsList[] = ['out_time' => $rawOut, 'in_time' => $rawIn];
                    }
                }

                if (empty($sessionsList)) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['attendance' => 'Out time is required for Permission attendance.']);
                }

                // Validate each session and format
                $formattedSessions = [];
                foreach ($sessionsList as $idx => $s) {
                    $sOut = trim($s['out_time']);
                    $sIn  = trim($s['in_time']);

                    if ($sOut === '') {
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['attendance' => 'Out time is required for all permission sessions.']);
                    }

                    try {
                        $outObj = Carbon::parse($sOut);
                        $inObj  = $sIn !== '' ? Carbon::parse($sIn) : null;

                        if ($inObj && $inObj->lt($outObj)) {
                            return redirect()->back()
                                ->withInput()
                                ->withErrors(['attendance' => 'In time cannot be earlier than Out time for Permission attendance.']);
                        }

                        $formattedSessions[] = [
                            'out_time' => $outObj->format('H:i:s'),
                            'in_time'  => $inObj ? $inObj->format('H:i:s') : null,
                        ];
                    } catch (\Exception $e) {
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['attendance' => 'Invalid time format entered for Permission attendance.']);
                    }
                }

                // Sort sessions chronologically by out_time
                usort($formattedSessions, fn($a, $b) => strcmp($a['out_time'], $b['out_time']));

                // Check for overlapping session intervals
                $count = count($formattedSessions);
                for ($i = 1; $i < $count; $i++) {
                    $prev = $formattedSessions[$i - 1];
                    $curr = $formattedSessions[$i];
                    if ($prev['in_time'] && $curr['out_time'] < $prev['in_time']) {
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['attendance' => "Permission sessions cannot overlap in time ({$prev['out_time']} to {$prev['in_time']} overlaps with {$curr['out_time']})."]);
                    }
                }

                $parsedPermissions[$empId] = $formattedSessions;
            }
        }

        DB::transaction(function () use ($attendanceData, $parsedPermissions, $date) {
            $now = now();
            foreach ($attendanceData as $empId => $att) {
                $status = $att['status'];
                $isPermission = ($status === 'permission');

                $outTime = null;
                $inTime = null;
                $isAutoFilled = false;

                if ($isPermission && isset($parsedPermissions[$empId])) {
                    $sessions = $parsedPermissions[$empId];
                    $outTime = $sessions[0]['out_time'] ?? null;
                    $lastSess = end($sessions);
                    $inTime = $lastSess['in_time'] ?? null;
                }

                // Single daily attendance record per employee per date
                $attendance = StaffAttendance::updateOrCreate(
                    [
                        'employee_id' => (int) $empId,
                        'date'        => $date,
                    ],
                    [
                        'status'              => $status,
                        'check_out'           => $outTime,
                        'check_in'            => $inTime,
                        'is_permission'       => $isPermission,
                        'in_time_auto_filled' => $isAutoFilled,
                        'updated_at'          => $now,
                    ]
                );

                if ($isPermission && isset($parsedPermissions[$empId])) {
                    // Sync child permission sessions
                    StaffPermissionSession::where('staff_attendance_id', $attendance->id)->delete();
                    foreach ($parsedPermissions[$empId] as $idx => $sess) {
                        StaffPermissionSession::create([
                            'staff_attendance_id' => $attendance->id,
                            'session_order'       => $idx + 1,
                            'out_time'            => $sess['out_time'],
                            'in_time'             => $sess['in_time'],
                            'in_time_auto_filled' => false,
                        ]);
                    }
                } elseif (!$isPermission) {
                    // If changed from permission to another status, clear child sessions
                    StaffPermissionSession::where('staff_attendance_id', $attendance->id)->delete();
                }
            }
        });

        // Run auto-fill in case this record is for today after dispersal or a past date
        \App\Console\Commands\AutoFillPermissionInTime::executeAutoFill($date);

        DashboardController::clearCache();

        return redirect()->route('hr.index')
            ->with('success', 'Attendance marked successfully.');
    }

    public function viewAttendance(Request $request)
    {
        $category = $request->get('category', 'all');
        $periodType = $request->get('period_type', 'monthly');
        $date = $request->get('date', today()->toDateString());
        $month = $request->get('month', now()->format('Y-m'));
        $termId = $request->get('term_id');
        $yearId = $request->get('year_id');
        $search = $request->get('search');
        $statusFilter = $request->get('status');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $currentYear = AcademicYear::current() ?? $academicYears->first();
        $academicTerms = $currentYear ? AcademicTerm::where('academic_year_id', $currentYear->id)->orderBy('order_position')->get() : collect();

        // Calculate date range based on periodType
        $startDate = today()->startOfMonth()->toDateString();
        $endDate = today()->endOfMonth()->toDateString();
        $periodLabel = now()->format('F Y');

        if ($periodType === 'daily') {
            $startDate = $date;
            $endDate = $date;
            $periodLabel = \Carbon\Carbon::parse($date)->format('d M Y');
        } elseif ($periodType === 'weekly') {
            $carbonDate = \Carbon\Carbon::parse($date);
            $startDate = $carbonDate->copy()->startOfWeek()->toDateString();
            $endDate = $carbonDate->copy()->endOfWeek()->toDateString();
            $periodLabel = \Carbon\Carbon::parse($startDate)->format('d M Y') . ' to ' . \Carbon\Carbon::parse($endDate)->format('d M Y');
        } elseif ($periodType === 'monthly') {
            [$yr, $mo] = explode('-', $month);
            $cMonth = \Carbon\Carbon::create((int)$yr, (int)$mo, 1);
            $startDate = $cMonth->startOfMonth()->toDateString();
            $endDate = $cMonth->endOfMonth()->toDateString();
            $periodLabel = $cMonth->format('F Y');
        } elseif ($periodType === 'term') {
            $term = AcademicTerm::find($termId) ?? $academicTerms->first();
            if ($term) {
                $termId = $term->id;
                $startDate = $term->start_date->toDateString();
                $endDate = $term->end_date->toDateString();
                $periodLabel = $term->name . ' (' . $term->start_date->format('d M Y') . ' - ' . $term->end_date->format('d M Y') . ')';
            }
        } elseif ($periodType === 'yearly') {
            $year = AcademicYear::find($yearId) ?? $currentYear;
            if ($year) {
                $yearId = $year->id;
                $startDate = $year->start_date->toDateString();
                $endDate = $year->end_date->toDateString();
                $periodLabel = $year->name . ' (' . $year->start_date->format('d M Y') . ' - ' . $year->end_date->format('d M Y') . ')';
            }
        }

        // Active staff matching category
        $empQuery = Employee::with(['department', 'designation'])->where('is_active', true);
        if ($category && $category !== 'all') {
            $empQuery->where('employee_type', $category);
        }
        $employees = $empQuery->orderBy('first_name')->get();

        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $todayDate = Carbon::now($tz)->toDateString();
        $periodDates = collect(CarbonPeriod::create($startDate, min($todayDate, $endDate)))->filter(fn($d) => !$d->isSunday());
        $totalWorkingDays = max(1, $periodDates->count());

        // Fetch attendance records for this period
        $attendanceRecords = StaffAttendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        $staffSummary = [];
        $totalPresentAll = 0;
        $totalAbsentAll = 0;
        $totalLeaveAll = 0;

        foreach ($employees as $emp) {
            $records = $attendanceRecords->get($emp->id, collect());
            $presentCount    = $records->whereIn('status', ['present', 'late'])->count();
            $absentCount     = $records->where('status', 'absent')->count();
            $leaveCount      = $records->whereIn('status', ['leave', 'on_leave'])->count();
            $halfDayCount    = $records->where('status', 'half_day')->count();
            $onDutyCount     = $records->where('status', 'on_duty')->count();
            $paidOffCount    = $records->where('status', 'paid_off')->count();
            $permissionCount = $records->where('status', 'permission')->count();

            $effectivePresent = $presentCount + ($halfDayCount * 0.5);
            $empWorkingDays = $totalWorkingDays;
            $rate = $empWorkingDays > 0 ? number_format(round(($effectivePresent / $empWorkingDays) * 100, 2), 2) : '0.00';

            $totalPresentAll += $presentCount;
            $totalAbsentAll += $absentCount;
            $totalLeaveAll += $leaveCount;

            $staffSummary[] = [
                'employee'     => $emp,
                'present'      => $presentCount,
                'absent'       => $absentCount,
                'leave'        => $leaveCount,
                'half_day'     => $halfDayCount,
                'on_duty'      => $onDutyCount,
                'paid_off'     => $paidOffCount,
                'permission'   => $permissionCount,
                'working_days' => $empWorkingDays,
                'rate'         => $rate,
            ];
        }

        if ($statusFilter && in_array($statusFilter, ['on_duty', 'paid_off', 'permission', 'absent', 'present'])) {
            $staffSummary = array_values(array_filter($staffSummary, function($row) use ($statusFilter) {
                return ($row[$statusFilter] ?? 0) > 0;
            }));
        }

        $categories = [
            ['key' => 'all',          'label' => 'All Staff'],
            ['key' => 'teaching',     'label' => 'Teaching Staff'],
            ['key' => 'non_teaching', 'label' => 'Non-Teaching Staff'],
            ['key' => 'driver',       'label' => 'Drivers'],
            ['key' => 'cleaner',      'label' => 'Cleaners / Support'],
            ['key' => 'nanny',        'label' => 'Nannies (Naani)'],
        ];

        return view('hr.attendance-view', compact(
            'staffSummary', 'category', 'categories', 'periodType',
            'periodLabel', 'date', 'month', 'termId', 'yearId',
            'academicYears', 'academicTerms', 'totalWorkingDays',
            'totalPresentAll', 'totalAbsentAll', 'totalLeaveAll',
            'search', 'statusFilter'
        ));
    }

    public function viewAbsentAttendance(Request $request)
    {
        return $this->viewStatusAttendance($request, 'absent');
    }

    public function viewOnDutyAttendance(Request $request)
    {
        return $this->viewStatusAttendance($request, 'on_duty');
    }

    public function viewPaidOffAttendance(Request $request)
    {
        return $this->viewStatusAttendance($request, 'paid_off');
    }

    public function viewPermissionAttendance(Request $request)
    {
        return $this->viewStatusAttendance($request, 'permission');
    }

    public function viewStatusAttendance(Request $request, string $status)
    {
        if (!in_array($status, ['absent', 'on_duty', 'paid_off', 'permission'])) {
            abort(404);
        }

        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $date = $request->get('date', Carbon::now($tz)->toDateString());
        $category = $request->get('category', 'all');
        $search = trim($request->get('search', ''));

        $attendanceQuery = StaffAttendance::with(['employee.department', 'employee.designation', 'permissionSessions'])
            ->whereDate('date', $date)
            ->where('status', $status)
            ->whereHas('employee', function($q) use ($category, $search) {
                $q->where('is_active', true);
                if ($category && $category !== 'all') {
                    $q->where('employee_type', $category);
                }
                if (!empty($search)) {
                    $q->where(function($sq) use ($search) {
                        $sq->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%")
                           ->orWhere('employee_code', 'like', "%{$search}%");
                    });
                }
            });

        $records = $attendanceQuery->get()->sortBy(function($rec) {
            return $rec->employee->full_name ?? '';
        })->values();

        $categories = [
            ['key' => 'all',          'label' => 'All Staff'],
            ['key' => 'teaching',     'label' => 'Teaching Staff'],
            ['key' => 'non_teaching', 'label' => 'Non-Teaching Staff'],
            ['key' => 'driver',       'label' => 'Drivers'],
            ['key' => 'cleaner',      'label' => 'Cleaners / Support'],
            ['key' => 'nanny',        'label' => 'Nannies (Naani)'],
        ];

        $statusTitles = [
            'absent'     => 'Absent Staff',
            'on_duty'    => 'On Duty Staff',
            'paid_off'   => 'Paid Off Staff',
            'permission' => 'Permission Staff',
        ];

        $statusEmptyMessages = [
            'absent'     => 'No staff members are marked absent today.',
            'on_duty'    => 'No staff members are currently marked On Duty.',
            'paid_off'   => 'No staff members are marked Paid Off today.',
            'permission' => 'No staff members are currently on Permission.',
        ];

        $title = $statusTitles[$status] ?? ucfirst(str_replace('_', ' ', $status)) . ' Staff';
        $emptyMessage = $statusEmptyMessages[$status] ?? 'No staff members found.';

        return view('hr.attendance-status', compact(
            'records', 'status', 'title', 'emptyMessage', 'date', 'category', 'categories', 'search'
        ));
    }

    public function updatePermissionInTime(Request $request)
    {
        $validated = $request->validate([
            'session_id'    => 'nullable|integer|exists:staff_permission_sessions,id',
            'attendance_id' => 'nullable|integer|exists:staff_attendance,id',
            'in_time'       => 'required|string',
        ], [
            'in_time.required' => 'Return In Time is required.',
        ]);

        $session = null;
        if (!empty($validated['session_id'])) {
            $session = StaffPermissionSession::with('staffAttendance.employee')->findOrFail($validated['session_id']);
            $record  = $session->staffAttendance;
        } else {
            $record = StaffAttendance::with(['employee', 'permissionSessions'])->findOrFail($validated['attendance_id']);
            $session = $record->permissionSessions->whereNull('in_time')->first() ?? $record->permissionSessions->last();
        }

        if ($record->status !== 'permission') {
            return back()->with('error', 'Only Permission attendance records can have return In Time updated.');
        }

        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $today = Carbon::now($tz)->toDateString();
        $attDate = Carbon::parse($record->date)->toDateString();
        if ($attDate > $today) {
            return back()->with('error', 'Attendance cannot be marked for a future date.');
        }

        $refOut = $session ? $session->out_time : $record->check_out;
        if (empty($refOut)) {
            return back()->with('error', 'Cannot enter In Time because this permission record has no Out Time.');
        }

        $rawIn = trim($validated['in_time']);
        if ($rawIn === '') {
            return back()->with('error', 'In Time cannot be blank.');
        }

        try {
            $inTimeCarbon = Carbon::parse($rawIn);
            $outTimeCarbon = Carbon::parse($refOut);

            // In time cannot be earlier than out time
            if ($inTimeCarbon->lt($outTimeCarbon)) {
                return back()->with('error', 'In time cannot be earlier than out time.');
            }

            $inTimeFormatted = $inTimeCarbon->format('H:i:s');
        } catch (\Exception $e) {
            return back()->with('error', 'Invalid In Time format.');
        }

        // Update EXISTING session only - duplicate prevention
        if ($session) {
            $session->update([
                'in_time'             => $inTimeFormatted,
                'in_time_auto_filled' => false,
            ]);
        } else {
            $session = StaffPermissionSession::create([
                'staff_attendance_id' => $record->id,
                'session_order'       => 1,
                'out_time'            => $refOut,
                'in_time'             => $inTimeFormatted,
                'in_time_auto_filled' => false,
            ]);
        }

        // Keep parent record in sync
        $record->update([
            'check_in'            => $inTimeFormatted,
            'in_time_auto_filled' => false,
            'is_permission'       => true,
        ]);

        DashboardController::clearCache();

        $empName = $record->employee?->full_name ?? 'Staff member';
        return back()->with('success', "Return In-Time recorded successfully for {$empName}.");
    }

    public function addPermissionSession(Request $request)
    {
        $validated = $request->validate([
            'attendance_id' => 'required|integer|exists:staff_attendance,id',
            'out_time'      => 'required|string',
            'in_time'       => 'nullable|string',
        ], [
            'out_time.required' => 'Out Time is required for a new Permission session.',
        ]);

        $record = StaffAttendance::with(['employee', 'permissionSessions'])->findOrFail($validated['attendance_id']);

        if ($record->status !== 'permission') {
            return back()->with('error', 'Only Permission attendance records can have permission sessions added.');
        }

        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $today = Carbon::now($tz)->toDateString();
        $attDate = Carbon::parse($record->date)->toDateString();
        if ($attDate > $today) {
            return back()->with('error', 'Attendance cannot be marked for a future date.');
        }

        $rawOut = trim($validated['out_time']);
        $rawIn  = trim($validated['in_time'] ?? '');

        try {
            $outCarbon = Carbon::parse($rawOut);
            $inCarbon  = $rawIn !== '' ? Carbon::parse($rawIn) : null;

            if ($inCarbon && $inCarbon->lt($outCarbon)) {
                return back()->with('error', 'In time cannot be earlier than Out time.');
            }

            $outFormatted = $outCarbon->format('H:i:s');
            $inFormatted  = $inCarbon ? $inCarbon->format('H:i:s') : null;

            // Check for overlapping session times
            foreach ($record->permissionSessions as $existingSess) {
                $eOut = $existingSess->out_time;
                $eIn  = $existingSess->in_time;
                if ($eIn) {
                    if ($outFormatted < $eIn && ($inFormatted === null || $inFormatted > $eOut)) {
                        return back()->with('error', "New session overlaps with an existing session ({$existingSess->timing_display}).");
                    }
                }
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Invalid time format entered.');
        }

        $order = ($record->permissionSessions()->max('session_order') ?? 0) + 1;

        StaffPermissionSession::create([
            'staff_attendance_id' => $record->id,
            'session_order'       => $order,
            'out_time'            => $outFormatted,
            'in_time'             => $inFormatted,
            'in_time_auto_filled' => false,
        ]);

        // Keep parent record in sync
        $record->update([
            'check_out'     => $record->permissionSessions()->min('out_time') ?? $outFormatted,
            'check_in'      => $inFormatted ?? $record->check_in,
            'is_permission' => true,
        ]);

        DashboardController::clearCache();

        $empName = $record->employee?->full_name ?? 'Staff member';
        return back()->with('success', "New permission session added for {$empName}.");
    }

    public function viewStaffAttendanceDetail(Request $request, int $id)
    {
        $employee = Employee::with(['department', 'designation'])->findOrFail($id);

        $periodType = $request->get('period_type', 'monthly');
        $date = $request->get('date', today()->toDateString());
        $month = $request->get('month', now()->format('Y-m'));
        $termId = $request->get('term_id');
        $yearId = $request->get('year_id');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $currentYear = AcademicYear::current() ?? $academicYears->first();
        $academicTerms = $currentYear ? AcademicTerm::where('academic_year_id', $currentYear->id)->orderBy('order_position')->get() : collect();

        $startDate = today()->startOfMonth()->toDateString();
        $endDate = today()->endOfMonth()->toDateString();
        $periodLabel = now()->format('F Y');

        if ($periodType === 'daily') {
            $startDate = $date;
            $endDate = $date;
            $periodLabel = \Carbon\Carbon::parse($date)->format('d M Y');
        } elseif ($periodType === 'weekly') {
            $carbonDate = \Carbon\Carbon::parse($date);
            $startDate = $carbonDate->copy()->startOfWeek()->toDateString();
            $endDate = $carbonDate->copy()->endOfWeek()->toDateString();
            $periodLabel = \Carbon\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d M Y');
        } elseif ($periodType === 'monthly') {
            [$yr, $mo] = explode('-', $month);
            $cMonth = \Carbon\Carbon::create((int)$yr, (int)$mo, 1);
            $startDate = $cMonth->startOfMonth()->toDateString();
            $endDate = $cMonth->endOfMonth()->toDateString();
            $periodLabel = $cMonth->format('F Y');
        } elseif ($periodType === 'term') {
            $term = AcademicTerm::find($termId) ?? $academicTerms->first();
            if ($term) {
                $termId = $term->id;
                $startDate = $term->start_date->toDateString();
                $endDate = $term->end_date->toDateString();
                $periodLabel = $term->name;
            }
        } elseif ($periodType === 'yearly') {
            $year = AcademicYear::find($yearId) ?? $currentYear;
            if ($year) {
                $yearId = $year->id;
                $startDate = $year->start_date->toDateString();
                $endDate = $year->end_date->toDateString();
                $periodLabel = $year->name;
            }
        }

        // Attendance records for this employee in period
        $history = StaffAttendance::with('permissionSessions')
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $todayDate = Carbon::now($tz)->toDateString();
        $workingDays = collect(CarbonPeriod::create($startDate, min($todayDate, $endDate)))->filter(fn($d) => !$d->isSunday())->count();
        $workingDays = max(1, $workingDays);

        $presentCount    = $history->whereIn('status', ['present', 'late'])->count();
        $absentCount     = $history->where('status', 'absent')->count();
        $leaveCount      = $history->whereIn('status', ['leave', 'on_leave'])->count();
        $halfDayCount    = $history->where('status', 'half_day')->count();
        $onDutyCount     = $history->where('status', 'on_duty')->count();
        $paidOffCount    = $history->where('status', 'paid_off')->count();

        $permissionRecords = $history->filter(fn($r) => $r->status === 'permission' || $r->is_permission || $r->permissionSessions->isNotEmpty());
        $permissionDaysCount = $permissionRecords->count();
        $permissionSessionsCount = $permissionRecords->sum(fn($r) => $r->permissionSessions->count() > 0 ? $r->permissionSessions->count() : 1);
        $permissionCount = $permissionDaysCount;

        $allPermissionSessions = collect();
        foreach ($permissionRecords as $rec) {
            if ($rec->permissionSessions->isNotEmpty()) {
                foreach ($rec->permissionSessions as $sess) {
                    $allPermissionSessions->push((object)[
                        'date'                => $rec->date,
                        'session_order'       => $sess->session_order,
                        'out_time'            => $sess->out_time,
                        'in_time'             => $sess->in_time,
                        'in_time_auto_filled' => $sess->in_time_auto_filled,
                        'formatted_out_time'  => $sess->formatted_out_time,
                        'formatted_in_time'   => $sess->formatted_in_time,
                        'timing_display'      => $sess->timing_display,
                    ]);
                }
            } elseif (!empty($rec->check_out)) {
                $allPermissionSessions->push((object)[
                    'date'                => $rec->date,
                    'session_order'       => 1,
                    'out_time'            => $rec->check_out,
                    'in_time'             => $rec->check_in,
                    'in_time_auto_filled' => (bool)$rec->in_time_auto_filled,
                    'formatted_out_time'  => \Carbon\Carbon::parse($rec->check_out)->format('h:i A'),
                    'formatted_in_time'   => $rec->check_in ? \Carbon\Carbon::parse($rec->check_in)->format('h:i A') : null,
                    'timing_display'      => (\Carbon\Carbon::parse($rec->check_out)->format('h:i A')) . ' - ' . ($rec->check_in ? \Carbon\Carbon::parse($rec->check_in)->format('h:i A') : 'Not Entered'),
                ]);
            }
        }
        $allPermissionSessions = $allPermissionSessions->sortBy([
            ['date', 'desc'],
            ['out_time', 'asc'],
        ])->values();

        $effectivePresent = $presentCount + ($halfDayCount * 0.5);
        $attendancePercentage = $workingDays > 0 ? number_format(round(($effectivePresent / $workingDays) * 100, 2), 2) : '0.00';

        // Leave summary from LeaveType and approved LeaveRequest
        $totalAllowedLeave = (int) LeaveType::sum('days_allowed');
        $approvedLeaveTaken = (float) LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('from_date', now()->year)
            ->sum('total_days');
        $leaveRemaining = max(0, $totalAllowedLeave - $approvedLeaveTaken);

        return view('hr.attendance-staff-detail', compact(
            'employee', 'history', 'periodType', 'periodLabel', 'date', 'month',
            'termId', 'yearId', 'academicYears', 'academicTerms', 'workingDays',
            'presentCount', 'absentCount', 'leaveCount', 'halfDayCount',
            'onDutyCount', 'paidOffCount', 'permissionCount', 'permissionDaysCount', 'permissionSessionsCount',
            'allPermissionSessions',
            'attendancePercentage', 'totalAllowedLeave', 'approvedLeaveTaken', 'leaveRemaining'
        ));
    }

    public function reports(Request $request)
    {
        // Auto-fill permission in-time if school dispersal reached
        \App\Console\Commands\AutoFillPermissionInTime::executeAutoFill();

        $reportMode = $request->get('mode', 'overall'); // overall | individual
        $periodType = $request->get('period_type', 'monthly'); // daily, weekly, monthly, term, yearly
        $category = $request->get('category', 'all');
        $employeeId = $request->get('employee_id');
        $date = $request->get('date', today()->toDateString());
        $month = $request->get('month', now()->format('Y-m'));
        $termId = $request->get('term_id');
        $yearId = $request->get('year_id');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $currentYear = AcademicYear::current() ?? $academicYears->first();
        $academicTerms = $currentYear ? AcademicTerm::where('academic_year_id', $currentYear->id)->orderBy('order_position')->get() : collect();

        $startDate = today()->startOfMonth()->toDateString();
        $endDate = today()->endOfMonth()->toDateString();
        $periodLabel = now()->format('F Y');

        if ($periodType === 'daily') {
            $startDate = $date;
            $endDate = $date;
            $periodLabel = \Carbon\Carbon::parse($date)->format('d M Y');
        } elseif ($periodType === 'weekly') {
            $carbonDate = \Carbon\Carbon::parse($date);
            $startDate = $carbonDate->copy()->startOfWeek()->toDateString();
            $endDate = $carbonDate->copy()->endOfWeek()->toDateString();
            $periodLabel = \Carbon\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d M Y');
        } elseif ($periodType === 'monthly') {
            [$yr, $mo] = explode('-', $month);
            $cMonth = \Carbon\Carbon::create((int)$yr, (int)$mo, 1);
            $startDate = $cMonth->startOfMonth()->toDateString();
            $endDate = $cMonth->endOfMonth()->toDateString();
            $periodLabel = $cMonth->format('F Y');
        } elseif ($periodType === 'term') {
            $term = AcademicTerm::find($termId) ?? $academicTerms->first();
            if ($term) {
                $termId = $term->id;
                $startDate = $term->start_date->toDateString();
                $endDate = $term->end_date->toDateString();
                $periodLabel = $term->name;
            }
        } elseif ($periodType === 'yearly') {
            $year = AcademicYear::find($yearId) ?? $currentYear;
            if ($year) {
                $yearId = $year->id;
                $startDate = $year->start_date->toDateString();
                $endDate = $year->end_date->toDateString();
                $periodLabel = $year->name;
            }
        }

        $allActiveStaff = Employee::where('is_active', true)->orderBy('first_name')->get();
        $selectedStaff = $employeeId ? Employee::with(['department', 'designation'])->find($employeeId) : $allActiveStaff->first();

        // Data for Overall Report
        $empQuery = Employee::with(['department', 'designation'])->where('is_active', true);
        if ($category && $category !== 'all') {
            $empQuery->where('employee_type', $category);
        }
        $employees = $empQuery->orderBy('first_name')->get();

        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $todayDate = Carbon::now($tz)->toDateString();
        $workingDays = collect(CarbonPeriod::create($startDate, min($todayDate, $endDate)))->filter(fn($d) => !$d->isSunday())->count();
        $workingDays = max(1, $workingDays);

        $attendanceRecords = StaffAttendance::with('permissionSessions')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        $overallRows = [];
        $totalPresentSum = 0;
        $totalAbsentSum = 0;
        $totalLeaveSum = 0;
        $totalHalfDaySum = 0;
        $totalOnDutySum = 0;
        $totalPaidOffSum = 0;
        $totalPermissionDaysSum = 0;
        $totalPermissionSessionsSum = 0;

        $presentStaffCount = 0;
        $absentStaffCount  = 0;
        $leaveStaffCount   = 0;

        $empIds = $employees->pluck('id');
        $approvedLeaveEmpIds = $empIds->isNotEmpty()
            ? DB::table('leave_requests')
                ->where('status', 'approved')
                ->whereIn('employee_id', $empIds)
                ->whereDate('from_date', '<=', $endDate)
                ->whereDate('to_date', '>=', $startDate)
                ->pluck('employee_id')
                ->flip()
            : collect();

        foreach ($employees as $emp) {
            $recs = $attendanceRecords->get($emp->id, collect());
            $p  = $recs->whereIn('status', ['present', 'late'])->count();
            $a  = $recs->where('status', 'absent')->count();
            $h  = $recs->where('status', 'half_day')->count();
            $od = $recs->where('status', 'on_duty')->count();
            $po = $recs->where('status', 'paid_off')->count();
            $l  = $recs->whereIn('status', ['leave', 'on_leave'])->count();

            $permRecs = $recs->filter(fn($r) => $r->status === 'permission' || $r->is_permission || $r->permissionSessions->isNotEmpty());
            $permDays = $permRecs->count();
            $permSessions = $permRecs->sum(fn($r) => $r->permissionSessions->count() > 0 ? $r->permissionSessions->count() : 1);

            if ($p > 0) {
                $presentStaffCount++;
            }
            if ($a > 0) {
                $absentStaffCount++;
            }
            if ($l > 0 || $approvedLeaveEmpIds->has($emp->id)) {
                $leaveStaffCount++;
            }

            $eff = $p + ($h * 0.5);
            $pct = $workingDays > 0 ? round(($eff / $workingDays) * 100, 2) : 0;

            $totalPresentSum += $p;
            $totalAbsentSum  += $a;
            $totalLeaveSum   += $l;
            $totalHalfDaySum += $h;
            $totalOnDutySum  += $od;
            $totalPaidOffSum += $po;
            $totalPermissionDaysSum += $permDays;
            $totalPermissionSessionsSum += $permSessions;

            $overallRows[] = [
                'employee'            => $emp,
                'category'            => $emp->category_label,
                'present'             => $p,
                'absent'              => $a,
                'half_day'            => $h,
                'on_duty'             => $od,
                'paid_off'            => $po,
                'permission'          => $permDays,
                'permission_sessions' => $permSessions,
                'leave'               => $l,
                'percentage'          => number_format($pct, 2),
                'raw_percentage'      => $pct,
            ];
        }

        $avgPercentage = count($overallRows) > 0 ? number_format(collect($overallRows)->avg('raw_percentage'), 2) : '0.00';

        // Data for Individual Report
        $individualHistory = collect();
        $individualSummary = null;
        $leaveSummaryBreakdown = [];
        $allPermissionSessions = collect();

        if ($selectedStaff) {
            $individualHistory = StaffAttendance::with('permissionSessions')
                ->where('employee_id', $selectedStaff->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'asc')
                ->get();

            $p = $individualHistory->whereIn('status', ['present', 'late'])->count();
            $a = $individualHistory->where('status', 'absent')->count();
            $l = $individualHistory->whereIn('status', ['leave', 'on_leave'])->count();
            $h = $individualHistory->where('status', 'half_day')->count();
            $eff = $p + ($h * 0.5);
            $pct = $workingDays > 0 ? round(($eff / $workingDays) * 100, 2) : 0;

            // Dual Permission calculations:
            // 1. Permission Days = distinct dates containing Permission sessions
            // 2. Permission Sessions = total Permission sessions
            $permissionRecords = $individualHistory->filter(fn($r) => $r->status === 'permission' || $r->is_permission || $r->permissionSessions->isNotEmpty());
            $permissionDaysCount = $permissionRecords->count();
            $permissionSessionsCount = $permissionRecords->sum(fn($r) => $r->permissionSessions->count() > 0 ? $r->permissionSessions->count() : 1);

            foreach ($permissionRecords as $rec) {
                if ($rec->permissionSessions->isNotEmpty()) {
                    foreach ($rec->permissionSessions as $sess) {
                        $allPermissionSessions->push((object)[
                            'date'                => $rec->date,
                            'session_order'       => $sess->session_order,
                            'out_time'            => $sess->out_time,
                            'in_time'             => $sess->in_time,
                            'in_time_auto_filled' => $sess->in_time_auto_filled,
                            'formatted_out_time'  => $sess->formatted_out_time,
                            'formatted_in_time'   => $sess->formatted_in_time,
                            'timing_display'      => $sess->timing_display,
                        ]);
                    }
                } elseif (!empty($rec->check_out)) {
                    $allPermissionSessions->push((object)[
                        'date'                => $rec->date,
                        'session_order'       => 1,
                        'out_time'            => $rec->check_out,
                        'in_time'             => $rec->check_in,
                        'in_time_auto_filled' => (bool)$rec->in_time_auto_filled,
                        'formatted_out_time'  => \Carbon\Carbon::parse($rec->check_out)->format('h:i A'),
                        'formatted_in_time'   => $rec->check_in ? \Carbon\Carbon::parse($rec->check_in)->format('h:i A') : null,
                        'timing_display'      => (\Carbon\Carbon::parse($rec->check_out)->format('h:i A')) . ' - ' . ($rec->check_in ? \Carbon\Carbon::parse($rec->check_in)->format('h:i A') : 'Not Entered'),
                    ]);
                }
            }
            $allPermissionSessions = $allPermissionSessions->sortBy([
                ['date', 'asc'],
                ['out_time', 'asc'],
            ])->values();

            // Leave breakdown per leave type
            $activeLeaveTypes = LeaveType::orderBy('name')->get();

            $approvedLeaveRequests = LeaveRequest::where('employee_id', $selectedStaff->id)
                ->where('status', 'approved')
                ->whereYear('from_date', now()->year)
                ->get();

            $totalAllocatedLeave = 0;
            $totalTakenLeave = 0;

            foreach ($activeLeaveTypes as $lt) {
                $allocated = (int) $lt->days_allowed;
                $taken = (float) $approvedLeaveRequests->where('leave_type_id', $lt->id)->sum('total_days');
                $remaining = max(0, $allocated - $taken);

                $totalAllocatedLeave += $allocated;
                $totalTakenLeave += $taken;

                $leaveSummaryBreakdown[] = [
                    'type'      => $lt->name,
                    'allocated' => $allocated,
                    'taken'     => $taken,
                    'remaining' => $remaining,
                ];
            }

            $individualSummary = [
                'working_days'        => $workingDays,
                'present'             => $p,
                'absent'              => $a,
                'leave'               => $l,
                'half_day'            => $h,
                'on_duty'             => $individualHistory->where('status', 'on_duty')->count(),
                'paid_off'            => $individualHistory->where('status', 'paid_off')->count(),
                'permission'          => $permissionDaysCount,
                'permission_days'     => $permissionDaysCount,
                'permission_sessions' => $permissionSessionsCount,
                'percentage'          => number_format($pct, 2),
                'raw_percentage'      => $pct,
                'effective_present'   => $eff,
                'leave_taken'         => $totalTakenLeave,
                'total_allowed'       => $totalAllocatedLeave,
            ];
        }

        $categories = [
            ['key' => 'all',          'label' => 'All Staff'],
            ['key' => 'teaching',     'label' => 'Teaching Staff'],
            ['key' => 'non_teaching', 'label' => 'Non-Teaching Staff'],
            ['key' => 'driver',       'label' => 'Drivers'],
            ['key' => 'cleaner',      'label' => 'Cleaners / Support'],
            ['key' => 'nanny',        'label' => 'Nannies (Naani)'],
        ];

        $selectedCategoryLabel = collect($categories)->firstWhere('key', $category)['label'] ?? 'All Staff';
        $school = SchoolSetting::first();
        $academicYearName = $currentYear?->name ?? (AcademicYear::find($yearId)?->name ?? (now()->year . '-' . (now()->year + 1)));

        return view('hr.reports', compact(
            'reportMode', 'periodType', 'periodLabel', 'category', 'categories',
            'selectedCategoryLabel', 'allActiveStaff', 'selectedStaff', 'overallRows', 'avgPercentage',
            'presentStaffCount', 'absentStaffCount', 'leaveStaffCount',
            'totalPresentSum', 'totalAbsentSum', 'totalLeaveSum', 'workingDays',
            'individualHistory', 'individualSummary', 'leaveSummaryBreakdown', 'allPermissionSessions',
            'school', 'academicYearName', 'date', 'month', 'termId',
            'yearId', 'academicYears', 'academicTerms'
        ));
    }

    public function exportReportsExcel(Request $request)
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="staff-attendance-report-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($request) {
            $handle = fopen('php://output', 'w');
            $mode = $request->get('mode', 'overall');
            $employeeId = $request->get('employee_id');

            $empQuery = Employee::with(['department', 'designation'])->where('is_active', true);
            if ($mode === 'individual' && $employeeId) {
                $empQuery->where('id', $employeeId);
            } elseif ($request->filled('category') && $request->category !== 'all') {
                $empQuery->where('employee_type', $request->category);
            }
            $employees = $empQuery->orderBy('first_name')->get();

            // Support period calculation in CSV if provided
            $periodType = $request->get('period_type', 'monthly');
            $date = $request->get('date', today()->toDateString());
            $month = $request->get('month', now()->format('Y-m'));

            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();

            if ($periodType === 'daily') {
                $startDate = $date;
                $endDate = $date;
            } elseif ($periodType === 'weekly') {
                $cDate = \Carbon\Carbon::parse($date);
                $startDate = $cDate->copy()->startOfWeek()->toDateString();
                $endDate = $cDate->copy()->endOfWeek()->toDateString();
            } elseif ($periodType === 'monthly') {
                [$yr, $mo] = explode('-', $month);
                $cMonth = \Carbon\Carbon::create((int)$yr, (int)$mo, 1);
                $startDate = $cMonth->startOfMonth()->toDateString();
                $endDate = $cMonth->endOfMonth()->toDateString();
            } elseif ($periodType === 'term' && $request->filled('term_id')) {
                $term = AcademicTerm::find($request->get('term_id'));
                if ($term) {
                    $startDate = $term->start_date->toDateString();
                    $endDate = $term->end_date->toDateString();
                }
            } elseif ($periodType === 'yearly' && $request->filled('year_id')) {
                $year = AcademicYear::find($request->get('year_id'));
                if ($year) {
                    $startDate = $year->start_date->toDateString();
                    $endDate = $year->end_date->toDateString();
                }
            }

            fputcsv($handle, ['Staff ID', 'Staff Name', 'Category', 'Department', 'Designation', 'Present', 'Absent', 'Half Day', 'On Duty', 'Paid Off', 'Permission', 'Leave', 'Attendance %']);

            $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
            $todayDate = Carbon::now($tz)->toDateString();
            $workingDays = max(1, collect(CarbonPeriod::create($startDate, min($todayDate, $endDate)))->filter(fn($d) => !$d->isSunday())->count());

            $attendances = StaffAttendance::with('permissionSessions')
                ->whereBetween('date', [$startDate, $endDate])
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get()
                ->groupBy('employee_id');

            foreach ($employees as $emp) {
                $recs = $attendances->get($emp->id, collect());
                $p  = $recs->whereIn('status', ['present', 'late'])->count();
                $a  = $recs->where('status', 'absent')->count();
                $h  = $recs->where('status', 'half_day')->count();
                $od = $recs->where('status', 'on_duty')->count();
                $po = $recs->where('status', 'paid_off')->count();
                $l  = $recs->whereIn('status', ['leave', 'on_leave'])->count();

                $permRecs = $recs->filter(fn($r) => $r->status === 'permission' || $r->is_permission || $r->permissionSessions->isNotEmpty());
                $permDays = $permRecs->count();

                $pct = round((($p + ($h * 0.5)) / $workingDays) * 100, 2);

                fputcsv($handle, [
                    $emp->employee_code,
                    $emp->full_name,
                    $emp->category_label,
                    $emp->department_name,
                    $emp->designation_name,
                    $p,
                    $a,
                    $h,
                    $od,
                    $po,
                    $permDays,
                    $l,
                    number_format($pct, 2) . '%',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function events(Request $request)
    {
        $tab = $request->get('tab', 'upcoming'); // upcoming | past | all
        $type = $request->get('type', 'all');

        $query = StaffEvent::with(['employee.department']);
        if ($tab === 'upcoming') {
            $query->where('event_date', '>=', today()->toDateString())->orderBy('event_date', 'asc');
        } elseif ($tab === 'past') {
            $query->where('event_date', '<', today()->toDateString())->orderBy('event_date', 'desc');
        } else {
            $query->orderBy('event_date', 'desc');
        }

        if ($type !== 'all') {
            $query->where('event_type', $type);
        }

        $events = $query->paginate(20)->withQueryString();
        $employees = Employee::where('is_active', true)->orderBy('first_name')->get();

        $staffCelebrations = $this->getUpcomingStaffCelebrations();

        return view('hr.events', compact('events', 'employees', 'tab', 'type', 'staffCelebrations'));
    }

    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'event_type'  => 'required|in:birthday,wedding,wedding_anniversary,joining_anniversary,retirement,other',
            'title'       => 'required|string|max:255',
            'event_date'  => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        StaffEvent::create($validated);

        return back()->with('success', 'Staff event added successfully.');
    }

    public function deleteEvent(int $id)
    {
        StaffEvent::findOrFail($id)->delete();
        return back()->with('success', 'Staff event deleted successfully.');
    }

    private function getUpcomingStaffCelebrations(): array
    {
        $todayStr = today()->toDateString();
        return \Illuminate\Support\Facades\Cache::remember("hr_staff_celebrations_v1_{$todayStr}", 3600, function () {
            $celebrations = [];
            $today = today();
            $limitDate = today()->addDays(30);

            $employees = Employee::where('is_active', true)
                ->where(function($q) {
                    $q->whereNotNull('dob')->orWhereNotNull('joining_date');
                })
                ->get();

            foreach ($employees as $emp) {
                // Check birthday
                if ($emp->dob) {
                    $bdayThisYear = \Carbon\Carbon::create($today->year, $emp->dob->month, $emp->dob->day);
                    if ($bdayThisYear->lt($today)) {
                        $bdayThisYear->addYear();
                    }
                    if ($bdayThisYear->betweenIncluded($today, $limitDate)) {
                        $celebrations[] = [
                            'type'        => 'birthday',
                            'label'       => 'Birthday',
                            'badge_class' => 'bg-pink-100 text-pink-700 border-pink-200',
                            'staff_name'  => $emp->full_name,
                            'date'        => $bdayThisYear,
                            'date_label'  => $bdayThisYear->format('d M'),
                            'days_left'   => $today->diffInDays($bdayThisYear),
                            'title'       => $emp->full_name . "'s Birthday",
                        ];
                    }
                }

                // Check joining anniversary
                if ($emp->joining_date && $emp->joining_date->lt($today)) {
                    $joinThisYear = \Carbon\Carbon::create($today->year, $emp->joining_date->month, $emp->joining_date->day);
                    if ($joinThisYear->lt($today)) {
                        $joinThisYear->addYear();
                    }
                    if ($joinThisYear->betweenIncluded($today, $limitDate)) {
                        $years = $joinThisYear->year - $emp->joining_date->year;
                        if ($years > 0) {
                            $celebrations[] = [
                                'type'        => 'joining_anniversary',
                                'label'       => 'Joining Anniversary',
                                'badge_class' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                'staff_name'  => $emp->full_name,
                                'date'        => $joinThisYear,
                                'date_label'  => $joinThisYear->format('d M'),
                                'days_left'   => $today->diffInDays($joinThisYear),
                                'title'       => $emp->full_name . " ({$years} " . ($years == 1 ? 'Year' : 'Years') . ' at School)',
                            ];
                        }
                    }
                }
            }

            // Add explicit staff_events in next 30 days
            $manualEvents = StaffEvent::with('employee')
                ->whereBetween('event_date', [$today->toDateString(), $limitDate->toDateString()])
                ->get();

            foreach ($manualEvents as $ev) {
                $eventDate = \Carbon\Carbon::parse($ev->event_date);
                $celebrations[] = [
                    'type'        => $ev->event_type,
                    'label'       => $ev->event_label,
                    'badge_class' => $ev->event_badge_color,
                    'staff_name'  => $ev->employee?->full_name ?? 'Staff Member',
                    'date'        => $eventDate,
                    'date_label'  => $eventDate->format('d M'),
                    'days_left'   => $today->diffInDays($eventDate),
                    'title'       => $ev->title,
                    'description' => $ev->description,
                ];
            }

            // Sort by date
            usort($celebrations, fn($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

            return $celebrations;
        });
    }

    public function employees(Request $request)
    {
        $categoryCounts = \Illuminate\Support\Facades\Cache::remember('hr_employee_category_counts', 30, function() {
            $catRow = DB::table('employees')
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN employee_type = 'teaching' THEN 1 END) as teaching,
                    COUNT(CASE WHEN employee_type = 'non_teaching' THEN 1 END) as non_teaching,
                    COUNT(CASE WHEN employee_type = 'driver' THEN 1 END) as driver,
                    COUNT(CASE WHEN employee_type = 'cleaner' THEN 1 END) as cleaner,
                    COUNT(CASE WHEN employee_type IN ('nanny', 'naani') THEN 1 END) as nanny
                ")
                ->first();

            return [
                'total'        => (int) ($catRow->total ?? 0),
                'teaching'     => (int) ($catRow->teaching ?? 0),
                'non_teaching' => (int) ($catRow->non_teaching ?? 0),
                'driver'       => (int) ($catRow->driver ?? 0),
                'cleaner'      => (int) ($catRow->cleaner ?? 0),
                'nanny'        => (int) ($catRow->nanny ?? 0),
            ];
        });

        $typeFilter = $request->get('type');

        $employees = Employee::select([
                'id', 'employee_code', 'first_name', 'last_name', 'designation',
                'department', 'employee_type', 'mobile', 'official_email', 'photo',
                'is_active', 'joining_date'
            ])
            ->when($request->search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%$v%")
                  ->orWhere('last_name', 'like', "%$v%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$v%"])
                  ->orWhere('employee_code', 'like', "%$v%")
                  ->orWhere('mobile', 'like', "%$v%")
                  ->orWhere('designation', 'like', "%$v%")
                  ->orWhere('department', 'like', "%$v%");
            }))
            ->when($request->department, fn($q, $v) => $q->where('department', $v))
            ->when($typeFilter && $typeFilter !== 'all', function ($q) use ($typeFilter) {
                if (in_array($typeFilter, ['nanny', 'naani'])) {
                    $q->whereIn('employee_type', ['nanny', 'naani']);
                } else {
                    $q->where('employee_type', $typeFilter);
                }
            })
            ->when(!$request->show_all, fn($q) => $q->where('is_active', true))
            ->orderBy('id')->paginate(36)->withQueryString();

        $departments = \Illuminate\Support\Facades\Cache::remember('hr_departments_list', 120, function() {
            return Employee::select('department')->distinct()->pluck('department')->filter()->values();
        });

        return view('hr.employees', compact('employees', 'departments', 'categoryCounts', 'typeFilter'));
    }

    public function createEmployee()
    {
        $departments = Department::orderBy('name')->get();
        $designations = Designation::orderBy('name')->get();
        $generatedEmployeeCode = $this->generateEmployeeNumber();
        $teachingLevels = TeachingLevel::active()->get();
        $classes = Classes::active()->with(['teachingLevel', 'sections' => fn($q) => $q->where('is_active', true)->orderBy('name')])->orderBy('sort_order')->orderBy('numeric_value')->get();
        $sections = Section::where('is_active', true)->orderBy('class_id')->orderBy('name')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $managers = Employee::where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'designation', 'employee_code']);
        $vehicles = \App\Models\Vehicle::where('is_active', true)->orderBy('vehicle_number')->get(['id', 'vehicle_number', 'vehicle_type', 'model']);
        return view('hr.employees-create', compact(
            'departments', 'designations', 'generatedEmployeeCode',
            'teachingLevels', 'classes', 'sections', 'subjects', 'managers', 'vehicles'
        ));
    }

    public function storeEmployee(Request $request)
    {
        // Handle NAME (Excel heading: NAME)
        if ($request->filled('name')) {
            $parts = preg_split('/\s+/', trim((string) $request->input('name')), 2);
            $request->merge([
                'first_name' => $parts[0] ?? '',
                'last_name'  => $parts[1] ?? '',
            ]);
        } elseif ($request->filled('first_name')) {
            $request->merge([
                'name' => trim($request->input('first_name') . ' ' . ($request->input('last_name') ?? '')),
            ]);
        }

        if ($request->filled('date_of_birth') && !$request->filled('dob')) {
            $request->merge(['dob' => $request->date_of_birth]);
        }
        if ($request->filled('dob') && !$request->filled('date_of_birth')) {
            $request->merge(['date_of_birth' => $request->dob]);
        }
        if ($request->filled('designation_id') && !$request->filled('designation')) {
            $desig = Designation::find($request->designation_id);
            if ($desig) {
                $request->merge(['designation' => $desig->name]);
            }
        }
        if ($request->filled('department_id') && !$request->filled('department')) {
            $dept = Department::find($request->department_id);
            if ($dept) {
                $request->merge(['department' => $dept->name]);
            }
        }
        if ($request->filled('teaching_experience') && !$request->filled('experience_years')) {
            $request->merge(['experience_years' => (int) $request->teaching_experience]);
        }

        // Sanitize digits and uppercase before validation
        if ($request->has('aadhaar_no') && $request->aadhaar_no !== null) {
            $request->merge(['aadhaar_no' => preg_replace('/[\s\-]+/', '', (string) $request->aadhaar_no)]);
        }
        if ($request->has('pan_no') && $request->pan_no !== null) {
            $request->merge(['pan_no' => strtoupper(trim((string) $request->pan_no))]);
        }
        if ($request->has('mobile') && $request->mobile !== null) {
            $request->merge(['mobile' => preg_replace('/[\s\-]+/', '', (string) $request->mobile)]);
        }
        if ($request->has('contact_no') && $request->contact_no !== null) {
            $request->merge(['contact_no' => preg_replace('/[\s\-]+/', '', (string) $request->contact_no)]);
        }
        if ($request->has('emergency_contact_mobile') && $request->emergency_contact_mobile !== null) {
            $request->merge(['emergency_contact_mobile' => preg_replace('/[\s\-]+/', '', (string) $request->emergency_contact_mobile)]);
        }
        if ($request->has('alternate_phone') && $request->alternate_phone !== null) {
            $request->merge(['alternate_phone' => preg_replace('/[\s\-]+/', '', (string) $request->alternate_phone)]);
        }
        if ($request->has('pincode') && $request->pincode !== null) {
            $request->merge(['pincode' => trim((string) $request->pincode)]);
        }
        if ($request->has('official_email') && $request->official_email !== null) {
            $request->merge(['official_email' => strtolower(trim((string) $request->official_email))]);
        }
        // Section J: default to 'None / Not Applicable' if not provided so staff creation is never blocked
        if (!$request->filled('additional_qual_after_recruitment')) {
            $request->merge(['additional_qual_after_recruitment' => 'None / Not Applicable']);
        }
        if (!$request->filled('training_attended_before_recruitment')) {
            $request->merge(['training_attended_before_recruitment' => 'None / Not Applicable']);
        }

        $employeeType = $request->input('employee_type', 'teaching');
        $isRbacEligible = in_array($employeeType, ['teaching', 'non_teaching'], true);

        if ($isRbacEligible) {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                // Section A: Personal Details
                'name'                   => 'required|string|max:150',
                'first_name'             => 'required|string|max:60',
                'last_name'              => 'nullable|string|max:60',
                'dob'                    => 'required|date|before:today',
                'gender'                 => 'required|string|in:male,female,other',
                'mother_tongue'          => 'required|string|max:100',
                'nationality'            => 'required|string|max:100',
                'religion'               => 'required|string|max:100',
                'caste'                  => 'required|string|max:100',
                'marital_status'         => 'required|string|in:Single,Married,single,married',
                'number_of_children'     => strcasecmp($request->marital_status ?? '', 'Married') === 0 ? 'required|integer|min:0' : 'nullable|integer|min:0',
                'children_male'          => strcasecmp($request->marital_status ?? '', 'Married') === 0 ? 'required|integer|min:0' : 'nullable|integer|min:0',
                'children_female'        => strcasecmp($request->marital_status ?? '', 'Married') === 0 ? 'required|integer|min:0' : 'nullable|integer|min:0',
                'address'                => 'required|string|max:500',

                // Section B: Employment Details
                'employee_code'          => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]{3,50}$/', 'unique:employees,employee_code'],
                'designation'            => 'required|string|max:100',
                'joining_date'           => 'required|date',
                'first_appointment_date' => $employeeType === 'teaching' ? 'required|date' : 'nullable|date',
                'current_employment_status' => 'required|string|in:Permanent,Contractual,Probation,Temporary,Part-Time,Other,permanent,contractual,probation,temporary,part-time,other',
                'probation_start_date'      => 'nullable|date',
                'probation_end_date'        => 'nullable|date',
                'employee_type'          => 'required|string|in:teaching,non_teaching',
                'department'             => 'nullable|string|max:100',
                'manager_id'             => 'nullable|exists:employees,id',

                // Teaching Level & Academic Allocations (Section 14)
                'teaching_levels'        => 'nullable|array',
                'teaching_levels.*'      => 'exists:teaching_levels,id',
                'experience_years'       => 'nullable|integer|min:0',
                'experience_months'      => 'nullable|integer|min:0|max:11',
                'kg_experience_years'    => 'nullable|integer|min:0',
                'qualified_subjects'     => 'nullable|array',
                'secondary_subject_expertise' => 'nullable|string|max:500',
                'higher_sec_subject_expertise'=> 'nullable|string|max:500',
                'max_weekly_periods'     => 'nullable|integer|min:1|max:60',
                'assigned_class_id'      => 'nullable|exists:classes,id',
                'assigned_section_id'    => 'nullable|exists:sections,id',
                'assigned_subject_id'    => 'nullable|exists:subjects,id',
                'is_class_teacher'       => 'nullable|boolean',

                // Section C: Identity / Government Details
                'aadhaar_no'             => 'required|string|regex:/^[0-9]{12}$/|unique:employees,aadhaar_no',
                'pan_no'                 => 'required|string|regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}$/|unique:employees,pan_no',
                'epf_no'                 => 'required|string|max:50',

                // Section D: Contact Details
                'official_email'         => 'required|email|max:100|unique:employees,email|unique:users,email',
                'mobile'                 => 'required|string|regex:/^[0-9]{10}$/|unique:employees,mobile|unique:users,mobile',
                'name_of_contact_person' => 'required|string|max:150',
                'contact_relationship'   => 'required|string|max:100',
                'contact_address'        => 'required|string|max:500',
                'contact_no'             => 'required|string|regex:/^[0-9]{10}$/',

                // Section E: Previous Employment Details
                'previous_designation'   => 'required|string|max:100',
                'previous_school'        => 'required|string|max:250',
                'previous_from_date'     => 'required|date',
                'previous_to_date'       => 'required|date|after_or_equal:previous_from_date',

                // Section F: Academic Qualification
                'academic_qualification' => 'required|string|max:150',
                'academic_university'    => 'required|string|max:150',
                'academic_medium'        => 'required|string|max:100',
                'academic_institution'   => 'required|string|max:200',
                'academic_percentage'    => 'required|numeric|min:0|max:100',
                'academic_year_of_passing' => 'required|integer|min:1950|max:' . (date('Y') + 1),

                // Section G: Professional Qualification
                'professional_qualification' => 'required|string|max:150',
                'professional_university'    => 'required|string|max:150',
                'professional_medium'        => 'required|string|max:100',
                'professional_institution'   => 'required|string|max:200',
                'professional_percentage'    => 'required|numeric|min:0|max:100',
                'professional_year_of_passing' => 'required|integer|min:1950|max:' . (date('Y') + 1),

                // Section H: Other Qualification 1 (conditional if provided)
                'other_qual_1_name'      => 'nullable|string|max:150',
                'other_qual_1_university'=> 'required_with:other_qual_1_name|nullable|string|max:150',
                'other_qual_1_medium'    => 'required_with:other_qual_1_name|nullable|string|max:100',
                'other_qual_1_institution'=> 'required_with:other_qual_1_name|nullable|string|max:200',
                'other_qual_1_percentage'=> 'required_with:other_qual_1_name|nullable|numeric|min:0|max:100',
                'other_qual_1_year_of_passing' => 'required_with:other_qual_1_name|nullable|integer|min:1950|max:' . (date('Y') + 1),

                // Section I: Other Qualification 2 (conditional if provided)
                'other_qual_2_name'      => 'nullable|string|max:150',
                'other_qual_2_university'=> 'required_with:other_qual_2_name|nullable|string|max:150',
                'other_qual_2_medium'    => 'required_with:other_qual_2_name|nullable|string|max:100',
                'other_qual_2_institution'=> 'required_with:other_qual_2_name|nullable|string|max:200',
                'other_qual_2_percentage'=> 'required_with:other_qual_2_name|nullable|numeric|min:0|max:100',
                'other_qual_2_year_of_passing' => 'required_with:other_qual_2_name|nullable|integer|min:1950|max:' . (date('Y') + 1),

                // Section J: Additional Qualification / Training
                'additional_qual_after_recruitment' => 'nullable|string|max:1000',
                'training_attended_before_recruitment' => 'nullable|string|max:1000',

                // Optional category specific / existing fields
                'photo'                  => 'nullable|image|max:2048',
                'basic_salary'           => 'nullable|numeric|min:0',
                'hra'                    => 'nullable|numeric|min:0',
                'da'                     => 'nullable|numeric|min:0',
                'ta'                     => 'nullable|numeric|min:0',
                'medical_allowance'      => 'nullable|numeric|min:0',
                'other_allowances'       => 'nullable|numeric|min:0',
                'tds'                    => 'nullable|numeric|min:0',

                // 13 Staff Data Collection Headings additions
                'pincode'                => 'required|string|regex:/^[1-9][0-9]{5}$/',
                'alternate_phone'        => 'nullable|string|regex:/^[0-9]{10}$/|different:mobile',
                'permanent_address'      => 'nullable|string|max:500',
                'pay_scale'              => 'nullable|string|max:100',
                'last_promotion_date'    => 'nullable|date',
                'contract_end_date'      => 'nullable|date',
                'confirmation_date'      => 'nullable|date',
                'major_subject'          => 'nullable|string|max:150',
                'minor_subject'          => 'nullable|string|max:150',
                'special_skills'         => 'nullable|string|max:500',
                'preferred_teaching_level' => 'nullable|string|max:100',
                'lab_subjects'           => 'nullable|string|max:250',
                'uan_no'                 => 'nullable|string|max:50',
                'professional_tax_no'    => 'nullable|string|max:50',
                'passport_no'            => 'nullable|string|max:50',
                'annual_increment'       => 'nullable|numeric|min:0',
                'increment_effective_date' => 'nullable|date',
                'nominee_name'           => 'nullable|string|max:150',
                'nominee_relationship'   => 'nullable|string|max:100',
                'nominee_share'          => 'nullable|numeric|min:0|max:100',
            ], [
                'name.required'                  => 'NAME is required.',
                'first_name.required'            => 'NAME is required.',
                'dob.required'                   => 'DOB is required.',
                'dob.before'                     => 'DOB must be a date before today.',
                'gender.required'                => 'M/F is required.',
                'mother_tongue.required'         => 'MOTHER TONGUE is required.',
                'nationality.required'           => 'NATIONALITY is required.',
                'religion.required'              => 'RELIGION is required.',
                'caste.required'                 => 'CASTE is required.',
                'marital_status.required'        => 'MARITAL STATUS is required.',
                'marital_status.in'              => 'MARITAL STATUS must be Single or Married.',
                'number_of_children.required'    => 'NUMBER OF CHILDREN is required for married staff.',
                'children_male.required'         => 'MALE children count is required for married staff.',
                'children_female.required'       => 'FEMALE children count is required for married staff.',
                'address.required'               => 'ADDRESS is required.',
                'employee_code.regex'            => 'EMPLOYEE ID must follow format EPSS0001.',
                'employee_code.unique'           => 'This EMPLOYEE ID is already taken. Please enter a unique ID or leave empty to auto-generate.',
                'designation.required'           => 'DESIGNATION is required.',
                'joining_date.required'          => 'DOJ is required.',
                'first_appointment_date.required'=> 'DATE OF FIRST APPOINTMENT AS TEACHER is required.',
                'current_employment_status.required' => 'CURRENT EMPLOYMENT STATUS is required.',
                'current_employment_status.in'   => 'CURRENT EMPLOYMENT STATUS must be Permanent, Probation, Contractual, Temporary, or Other.',
                'employee_type.in'               => 'Category must be Teaching Staff or Non-Teaching Staff.',
                'aadhaar_no.required'            => 'AADHAR NO is required.',
                'aadhaar_no.regex'               => 'AADHAR NO must be exactly 12 numeric digits.',
                'aadhaar_no.unique'              => 'This 12-digit Aadhaar Number is already associated with an existing staff member in the system.',
                'pan_no.required'                => 'PAN NO. is required.',
                'pan_no.regex'                   => 'PAN NO. must be 10 characters in valid format (e.g. ABCDE1234F).',
                'pan_no.unique'                  => 'This PAN Number is already associated with an existing staff member in the system.',
                'pincode.required'               => 'POSTAL PIN CODE is required.',
                'pincode.regex'                  => 'POSTAL PIN CODE must be a valid 6-digit Indian postal code (e.g. 638001).',
                'alternate_phone.regex'          => 'Alternate telephone must be exactly 10 digits.',
                'alternate_phone.different'      => 'Alternate telephone number cannot be identical to the primary contact number.',
                'epf_no.required'                => 'EPF NO. is required.',
                'official_email.required'        => 'EMAIL ID is required.',
                'official_email.email'           => 'EMAIL ID must be a valid email address.',
                'official_email.unique'          => 'This Official Email ID is already registered to another staff member or system user.',
                'mobile.required'                => 'CONTACT NO. is required.',
                'mobile.regex'                   => 'CONTACT NO. must be exactly 10 digits.',
                'mobile.unique'                  => 'This Contact Mobile number is already registered to another staff member or system user.',
                'name_of_contact_person.required'=> 'NAME OF THE CONTACT PERSON is required.',
                'contact_relationship.required'  => 'RELATIONSHIP is required.',
                'contact_address.required'       => 'CONTACT ADDRESS is required.',
                'contact_no.required'            => 'CONTACT NO is required.',
                'contact_no.regex'               => 'CONTACT NO must be exactly 10 digits.',
                'previous_designation.required'  => 'PREVIOUS POST HELD is required.',
                'previous_school.required'       => 'NAME & ADDRESS OF THE INSTITUTION is required.',
                'previous_from_date.required'    => 'FROM date is required.',
                'previous_to_date.required'      => 'TO date is required.',
                'previous_to_date.after_or_equal'=> 'Previous Employment "TO" date cannot be earlier than "FROM" date.',
                'academic_qualification.required'=> 'ACADEMIC QUALIFICATION is required.',
                'academic_university.required'   => 'Academic UNIVERSITY is required.',
                'academic_medium.required'       => 'Academic MEDIUM is required.',
                'academic_institution.required'  => 'Academic NAME OF THE INSTITUTION is required.',
                'academic_percentage.required'   => 'Academic %MARKS is required.',
                'academic_percentage.min'        => 'Academic %MARKS cannot be negative.',
                'academic_percentage.max'        => 'Academic %MARKS cannot exceed 100.',
                'academic_year_of_passing.required' => 'Academic YEAR OF PASSING is required.',
                'professional_qualification.required' => 'PROFESSIONAL QUALIFICATION is required.',
                'professional_university.required'=> 'Professional UNIVERSITY is required.',
                'professional_medium.required'   => 'Professional MEDIUM is required.',
                'professional_institution.required' => 'Professional NAME OF INSTITUTION is required.',
                'professional_percentage.required' => 'Professional %MARKS is required.',
                'professional_percentage.min'    => 'Professional %MARKS cannot be negative.',
                'professional_percentage.max'    => 'Professional %MARKS cannot exceed 100.',
                'professional_year_of_passing.required' => 'Professional YEAR OF PASSING is required.',
                'other_qual_1_percentage.min'    => 'Other Qualification 1 %MARKS cannot be negative.',
                'other_qual_1_percentage.max'    => 'Other Qualification 1 %MARKS cannot exceed 100.',
                'other_qual_2_percentage.min'    => 'Other Qualification 2 %MARKS cannot exceed 100.',
            ]);

            $validator->after(function ($validator) use ($request) {
                if (strcasecmp($request->marital_status ?? '', 'Married') === 0) {
                    if ($request->filled('number_of_children') && $request->filled('children_male') && $request->filled('children_female')) {
                        $total = (int) $request->number_of_children;
                        $m = (int) $request->children_male;
                        $f = (int) $request->children_female;
                        if (($m + $f) !== $total) {
                            $validator->errors()->add('number_of_children', "The sum of Male ($m) and Female ($f) children must equal the Number of Children ($total).");
                        }
                    }
                }
                if ($request->boolean('is_class_teacher')) {
                    if (!$request->filled('assigned_class_id') || !$request->filled('assigned_section_id')) {
                        $validator->errors()->add('is_class_teacher', 'To assign staff as Class Teacher, please select both a Class and a Section.');
                    }
                }
            });
        } else {
            // Validation for Operational Support Personnel (Drivers, Cleaners, Nannies)
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'name'                   => 'required|string|max:150',
                'first_name'             => 'required|string|max:60',
                'last_name'              => 'nullable|string|max:60',
                'dob'                    => 'nullable|date|before:today',
                'gender'                 => 'required|string|in:male,female,other',
                'mobile'                 => 'required|string|regex:/^[0-9]{10}$/|unique:employees,mobile|unique:users,mobile',
                'designation'            => 'required|string|max:100',
                'joining_date'           => 'required|date',
                'employee_code'          => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]{3,50}$/', 'unique:employees,employee_code'],
                'employee_type'          => 'required|string|in:driver,cleaner,nanny,naani',
                'current_employment_status' => 'nullable|string|in:Permanent,Contractual,Probation,Temporary,Part-Time,Other,permanent,contractual,probation,temporary,part-time,other',
                'probation_start_date'      => 'nullable|date',
                'probation_end_date'        => 'nullable|date',
                'department'             => 'nullable|string|max:100',
                'manager_id'             => 'nullable|exists:employees,id',
                'basic_salary'           => 'nullable|numeric|min:0',
                'address'                => 'nullable|string|max:500',
                'pincode'                => 'nullable|string|regex:/^[1-9][0-9]{5}$/',
                'aadhaar_no'             => 'nullable|string|regex:/^[0-9]{12}$/|unique:employees,aadhaar_no',
                'pan_no'                 => 'nullable|string|unique:employees,pan_no',
                'epf_no'                 => 'nullable|string|max:50',
                'official_email'         => 'nullable|email|max:100|unique:employees,email|unique:users,email',
                'contact_no'             => 'nullable|string|regex:/^[0-9]{10}$/',
                'name_of_contact_person' => 'nullable|string|max:150',
                'contact_relationship'   => 'nullable|string|max:100',
                'contact_address'        => 'nullable|string|max:500',
                'mother_tongue'          => 'nullable|string|max:100',
                'nationality'            => 'nullable|string|max:100',
                'religion'               => 'nullable|string|max:100',
                'caste'                  => 'nullable|string|max:100',
                'marital_status'         => 'nullable|string',
                'number_of_children'     => 'nullable|integer|min:0',
                'children_male'          => 'nullable|integer|min:0',
                'children_female'        => 'nullable|integer|min:0',

                // Operational / Transport fields
                'license_number'         => 'nullable|string|max:100',
                'license_expiry'         => 'nullable|date',
                'assigned_vehicle'       => 'nullable|string|max:250',
                'assigned_block'         => 'nullable|string|max:250',
                'shift_timing'           => 'nullable|string|max:250',
                'qualification'          => 'nullable|string|max:250',

                'photo'                  => 'nullable|image|max:2048',
                'hra'                    => 'nullable|numeric|min:0',
                'da'                     => 'nullable|numeric|min:0',
                'ta'                     => 'nullable|numeric|min:0',
                'medical_allowance'      => 'nullable|numeric|min:0',
                'other_allowances'       => 'nullable|numeric|min:0',
                'tds'                    => 'nullable|numeric|min:0',

                // 13 Staff Data Collection Headings additions
                'alternate_phone'        => 'nullable|string|regex:/^[0-9]{10}$/',
                'permanent_address'      => 'nullable|string|max:500',
                'pay_scale'              => 'nullable|string|max:100',
                'uan_no'                 => 'nullable|string|max:50',
                'professional_tax_no'    => 'nullable|string|max:50',
                'passport_no'            => 'nullable|string|max:50',
                'nominee_name'           => 'nullable|string|max:150',
                'nominee_relationship'   => 'nullable|string|max:100',
                'nominee_share'          => 'nullable|numeric|min:0|max:100',
            ], [
                'name.required'        => 'NAME is required.',
                'first_name.required'  => 'NAME is required.',
                'gender.required'      => 'M/F (Gender) is required.',
                'mobile.required'      => 'CONTACT NO. is required.',
                'mobile.regex'         => 'CONTACT NO. must be exactly 10 digits.',
                'pincode.regex'        => 'PIN Code must be a valid 6-digit Indian postal code (e.g. 638001).',
                'designation.required' => 'DESIGNATION is required.',
                'joining_date.required'=> 'DOJ is required.',
                'employee_type.in'     => 'Invalid employee category selected.',
            ]);
        }

        $validated = $validator->validate();

        $salaryFields = ['hra', 'da', 'ta', 'medical_allowance', 'other_allowances', 'tds'];
        $salaryData = array_filter(
            array_intersect_key($validated, array_flip($salaryFields)),
            fn($v) => $v !== null
        );

        $data = array_diff_key($validated, array_flip($salaryFields));
        unset($data['photo'], $data['teaching_levels'], $data['assigned_class_id'], $data['assigned_section_id'], $data['assigned_subject_id'], $data['is_class_teacher']);

        try {
            return DB::transaction(function () use ($request, $data, $salaryData, $isRbacEligible) {
                if ($request->hasFile('photo')) {
                    $data['photo'] = $request->file('photo')->store('employee-photos', 'public');
                }

                // Fill normalized fields and aliases
                $data['marital_status'] = ucfirst(strtolower($data['marital_status'] ?? 'Single'));
                $data['current_employment_status'] = ucfirst(strtolower($data['current_employment_status'] ?? 'Permanent'));
                $data['employment_type'] = strtolower($data['current_employment_status']);
                $data['qualification'] = $data['qualification'] ?? ($data['academic_qualification'] ?? null);
                $data['contact_person'] = $data['name_of_contact_person'] ?? ($data['name'] ?? null);
                $data['email'] = $data['official_email'] ?? null;
                $data['personal_email'] = $data['personal_email'] ?? ($data['official_email'] ?? null);
                $data['residential_address'] = $data['address'] ?? ($data['residential_address'] ?? null);
                $data['emergency_contact_name'] = $data['name_of_contact_person'] ?? null;
                $data['emergency_contact_mobile'] = $data['contact_no'] ?? null;
                $data['emergency_contact_relation'] = $data['contact_relationship'] ?? null;
                $data['pf_account_no'] = $data['epf_no'] ?? null;
                $data['employee_code'] = !empty($data['employee_code']) ? strtoupper($data['employee_code']) : $this->generateEmployeeNumber();

                $data['marital_status'] = ucfirst(strtolower($data['marital_status'] ?? 'Single'));
                if (strcasecmp($data['marital_status'], 'Married') !== 0) {
                    $data['number_of_children'] = 0;
                    $data['children_male'] = 0;
                    $data['children_female'] = 0;
                } else {
                    $data['number_of_children'] = (int) ($data['number_of_children'] ?? 0);
                    $data['children_male'] = (int) ($data['children_male'] ?? 0);
                    $data['children_female'] = (int) ($data['children_female'] ?? 0);
                }

                if (!$isRbacEligible) {
                    $data['nationality'] = $data['nationality'] ?? 'Indian';
                    $data['category'] = $data['category'] ?? 'general';
                    $data['mother_tongue'] = $data['mother_tongue'] ?? 'Tamil';
                    $data['address'] = $data['address'] ?? 'Campus / Staff Quarters';
                    $data['first_appointment_date'] = $data['first_appointment_date'] ?? $data['joining_date'];
                    $data['experience_months'] = (int) ($data['experience_months'] ?? 0);
                    $data['kg_experience_years'] = (int) ($data['kg_experience_years'] ?? 0);
                    $data['max_weekly_periods'] = (int) ($data['max_weekly_periods'] ?? 0);
                    $data['employment_category'] = 'experienced';
                    $data['user_id'] = null;
                }

                // Set department_id if found by name
                if (!empty($data['department'])) {
                    $deptRecord = Department::firstOrCreate(['name' => $data['department']], ['code' => strtoupper(substr($data['department'], 0, 4))]);
                    $data['department_id'] = $deptRecord->id;
                }

                // Set designation_id if found by name
                if (!empty($data['designation'])) {
                    $desigRecord = Designation::firstOrCreate(['name' => $data['designation']]);
                    $data['designation_id'] = $desigRecord->id;
                }

                // Experience calculations
                if (isset($data['previous_from_date']) && isset($data['previous_to_date'])) {
                    $prevStart = \Carbon\Carbon::parse($data['previous_from_date']);
                    $prevEnd = \Carbon\Carbon::parse($data['previous_to_date']);
                    $prevMonths = $prevStart->diffInMonths($prevEnd);
                    if (empty($data['experience_years']) && empty($data['experience_months'])) {
                        $data['experience_years'] = (int) floor($prevMonths / 12);
                        $data['experience_months'] = (int) ($prevMonths % 12);
                    }
                }

                $employee = Employee::create(array_merge($data, [
                    'approval_status' => 'approved',
                    'status'          => 'active',
                    'is_active'       => true,
                ]));

                AuditLog::record('employee_created', $employee, [], ['employee_code' => $employee->employee_code, 'name' => $employee->full_name]);

                // Sync to relational EmployeeQualification records if present
                if (!empty($employee->academic_qualification)) {
                    EmployeeQualification::updateOrCreate(
                        ['employee_id' => $employee->id, 'degree' => $employee->academic_qualification],
                        [
                            'education_level'     => 'graduate',
                            'university'          => $employee->academic_university,
                            'subject'             => $employee->academic_medium,
                            'institution'         => $employee->academic_institution,
                            'grade_or_percentage' => $employee->academic_percentage . '%',
                            'year_of_passing'     => $employee->academic_year_of_passing,
                        ]
                    );
                }

                if (!empty($employee->professional_qualification)) {
                    EmployeeQualification::updateOrCreate(
                        ['employee_id' => $employee->id, 'degree' => $employee->professional_qualification],
                        [
                            'education_level'     => 'diploma',
                            'university'          => $employee->professional_university,
                            'subject'             => $employee->professional_medium,
                            'institution'         => $employee->professional_institution,
                            'grade_or_percentage' => $employee->professional_percentage . '%',
                            'year_of_passing'     => $employee->professional_year_of_passing,
                        ]
                    );
                }

                if (!empty($employee->other_qual_1_name)) {
                    EmployeeQualification::updateOrCreate(
                        ['employee_id' => $employee->id, 'degree' => $employee->other_qual_1_name],
                        [
                            'education_level'     => 'other',
                            'university'          => $employee->other_qual_1_university,
                            'subject'             => $employee->other_qual_1_medium,
                            'institution'         => $employee->other_qual_1_institution,
                            'grade_or_percentage' => $employee->other_qual_1_percentage !== null ? ($employee->other_qual_1_percentage . '%') : null,
                            'year_of_passing'     => $employee->other_qual_1_year_of_passing,
                        ]
                    );
                }

                if (!empty($employee->other_qual_2_name)) {
                    EmployeeQualification::updateOrCreate(
                        ['employee_id' => $employee->id, 'degree' => $employee->other_qual_2_name],
                        [
                            'education_level'     => 'other',
                            'university'          => $employee->other_qual_2_university,
                            'subject'             => $employee->other_qual_2_medium,
                            'institution'         => $employee->other_qual_2_institution,
                            'grade_or_percentage' => $employee->other_qual_2_percentage !== null ? ($employee->other_qual_2_percentage . '%') : null,
                            'year_of_passing'     => $employee->other_qual_2_year_of_passing,
                        ]
                    );
                }

                // Sync to relational EmployeeExperience records if present
                if (!empty($employee->previous_school) || !empty($employee->previous_designation)) {
                    EmployeeExperience::updateOrCreate(
                        ['employee_id' => $employee->id, 'is_current' => false],
                        [
                            'organisation' => $employee->previous_school ?? 'Previous Institution',
                            'role'         => $employee->previous_designation ?? 'Staff',
                            'from_date'    => $employee->previous_from_date,
                            'to_date'      => $employee->previous_to_date,
                        ]
                    );
                }

                // Create salary structure if any salary data was provided
                if (($employee->basic_salary ?? 0) > 0 || count($salaryData) > 0) {
                    DB::table('salary_structures')->insert(array_merge([
                        'employee_id'    => $employee->id,
                        'basic_salary'   => $employee->basic_salary ?? 0,
                        'effective_from' => $employee->joining_date ?? today(),
                        'is_active'      => true,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ], $salaryData));
                }

                // Teaching Level Eligibility sync (Section 14) - Teaching staff only
                if ($employee->employee_type === 'teaching' && $request->filled('teaching_levels')) {
                    foreach ($request->input('teaching_levels') as $levelId) {
                        TeacherLevelEligibility::updateOrCreate(
                            ['employee_id' => $employee->id, 'teaching_level_id' => (int) $levelId],
                            ['is_approved' => true, 'approved_by' => Auth::id()]
                        );
                    }
                }

                // Initial Subject Allocation if specified - Teaching staff only
                if ($employee->employee_type === 'teaching' && $request->filled('assigned_subject_id') && $request->filled('assigned_class_id')) {
                    $currentYear = AcademicYear::current() ?? AcademicYear::first();
                    TeacherSubjectAllocation::firstOrCreate([
                        'employee_id'      => $employee->id,
                        'subject_id'       => (int) $request->assigned_subject_id,
                        'class_id'         => (int) $request->assigned_class_id,
                        'section_id'       => $request->assigned_section_id ? (int) $request->assigned_section_id : null,
                        'academic_year_id' => $currentYear?->id,
                    ]);
                }

                // Initial Class Teacher Responsibility if specified - Teaching staff only
                if ($employee->employee_type === 'teaching' && $request->boolean('is_class_teacher') && $request->filled('assigned_class_id')) {
                    $currentYear = AcademicYear::current() ?? AcademicYear::first();
                    StaffClassTeacherAssignment::where('class_id', $request->assigned_class_id)
                        ->when($request->assigned_section_id, fn($q, $v) => $q->where('section_id', $v))
                        ->update(['is_current' => false, 'ended_at' => today()]);

                    StaffClassTeacherAssignment::create([
                        'employee_id'      => $employee->id,
                        'class_id'         => (int) $request->assigned_class_id,
                        'section_id'       => $request->assigned_section_id ? (int) $request->assigned_section_id : null,
                        'academic_year_id' => $currentYear?->id,
                        'assigned_at'      => today(),
                        'is_current'       => true,
                        'approval_status'  => 'approved',
                        'assigned_by'      => Auth::id(),
                    ]);
                }

                // =========================================================================
                // AUTOMATIC USER ACCOUNT PROVISIONING & RBAC INTEGRATION (Section 3)
                // Strictly ONLY for Teaching Staff and Non-Teaching Staff.
                // Support personnel (Drivers, Cleaners, Nannies) are NOT provisioned.
                // =========================================================================
                if ($isRbacEligible) {
                    $existingUser = User::where('employee_id', $employee->id)
                        ->orWhere('username', $employee->employee_code)
                        ->first();

                    if (!$existingUser) {
                        // Initial temporary password derived from Date of Birth: DDMMYYYY
                        $dobPassword = \Carbon\Carbon::parse($employee->dob)->format('dmY');

                        // Determine safe default role
                        $defaultRole = 'staff';
                        $desigLower = strtolower($employee->designation ?? '');
                        if ($employee->employee_type === 'teaching') {
                            $defaultRole = 'teacher';
                            if (str_contains($desigLower, 'vice principal') || str_contains($desigLower, 'vice-principal')) {
                                $defaultRole = 'vice_principal';
                            } elseif (str_contains($desigLower, 'principal')) {
                                $defaultRole = 'principal';
                            }
                        } else {
                            if (str_contains($desigLower, 'accountant') || str_contains($desigLower, 'billing')) {
                                $defaultRole = 'accountant';
                            } elseif (str_contains($desigLower, 'librarian')) {
                                $defaultRole = 'librarian';
                            } elseif (str_contains($desigLower, 'receptionist')) {
                                $defaultRole = 'receptionist';
                            }
                        }

                        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $defaultRole, 'guard_name' => 'web']);

                        // Ensure unique email fallback if needed
                        $userEmail = $employee->official_email;
                        if (User::where('email', $userEmail)->exists()) {
                            $userEmail = strtolower($employee->employee_code) . '@schoolerp.in';
                        }

                        // Ensure unique mobile fallback if needed (mobile is nullable in users table)
                        $userMobile = $employee->mobile;
                        if ($userMobile && User::where('mobile', $userMobile)->exists()) {
                            $userMobile = null;
                        }

                        $user = User::create([
                            'name'                 => $employee->full_name,
                            'username'             => $employee->employee_code,
                            'email'                => $userEmail,
                            'mobile'               => $userMobile,
                            'password'             => \Illuminate\Support\Facades\Hash::make($dobPassword),
                            'employee_id'          => $employee->id,
                            'is_active'            => true,
                            'must_change_password' => true,
                        ]);

                        $user->assignRole($defaultRole);

                        // Update employee user_id link
                        $employee->user_id = $user->id;
                        $employee->saveQuietly();

                        // Safe initial data scope
                        \App\Modules\Rbac\Models\UserDataScope::create([
                            'user_id'     => $user->id,
                            'scope_type'  => $employee->employee_type === 'teaching' ? 'assigned_students_only' : 'department',
                            'scope_value' => $employee->employee_type === 'teaching'
                                ? ($request->assigned_class_id ? json_encode([(string)$request->assigned_class_id]) : null)
                                : ($employee->department_id ? json_encode([(string)$employee->department_id]) : null),
                            'is_active'   => true,
                            'notes'       => 'Auto-provisioned initial scope from HR staff onboarding',
                            'created_by'  => Auth::id(),
                        ]);

                        // RBAC Audit Log
                        RbacAuditService::log(
                            action: 'USER_CREATED_FROM_HR_ONBOARDING',
                            modelType: 'User',
                            modelId: $user->id,
                            newValues: [
                                'username' => $user->username,
                                'role'     => $defaultRole,
                                'employee_code' => $employee->employee_code,
                                'temporary_password_policy' => 'DOB_DDMMYYYY',
                            ],
                            actor: Auth::user()
                        );

                        // In-app Notification for System Administrators
                        DB::table('notifications')->insert([
                            'id'              => (string) \Illuminate\Support\Str::uuid(),
                            'type'            => 'App\\Notifications\\StaffOnboardedNotification',
                            'notifiable_type' => 'App\\Models\\User',
                            'notifiable_id'   => Auth::id() ?? $user->id,
                            'data'            => json_encode([
                                'title'       => 'Staff User Account Created: ' . $employee->full_name,
                                'message'     => "User {$employee->employee_code} provisioned with role '{$defaultRole}'. Synchronized with RBAC User Management.",
                                'employee_id' => $employee->id,
                                'user_id'     => $user->id,
                                'action_url'  => route('rbac.users.show', $user->id),
                            ]),
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);

                        $linkedUserId = $user->id;
                    } else {
                        // Idempotent safeguard: sync links
                        if (!$employee->user_id) {
                            $employee->user_id = $existingUser->id;
                            $employee->saveQuietly();
                        }
                        if (!$existingUser->employee_id) {
                            $existingUser->employee_id = $employee->id;
                            $existingUser->save();
                        }
                        $linkedUserId = $existingUser->id;
                    }

                    return redirect()->route('hr.employees.show', $employee->id)
                        ->with('success', "Staff record for {$employee->full_name} ({$employee->employee_code}) saved successfully! User login provisioned with temporary DOB credentials and synchronized with RBAC.");
                }

                // Return redirect for operational support staff (no RBAC account)
                $catLabel = match($employee->employee_type) {
                    'driver'         => 'Driver',
                    'cleaner'        => 'Cleaner',
                    'nanny', 'naani' => 'Nanny (Naani)',
                    default          => 'Support staff'
                };

                return redirect()->route('hr.employees.show', $employee->id)
                    ->with('success', "{$catLabel} '{$employee->full_name}' ({$employee->employee_code}) onboarded successfully into staff directory.");
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Staff onboarding failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Unable to save staff record: ' . $e->getMessage() . '. Your entered form data has been preserved. Please verify your network connection and click Save again.');
        }
    }

    public function showEmployee(int $id)
    {
        $employee        = Employee::with('department')->findOrFail($id);
        $linkedUser      = $employee->user_id ? \App\Models\User::find($employee->user_id) : null;
        $staffRoles      = Role::pluck('name');
        $payroll         = PayrollRecord::where('employee_id', $id)->latest('month')->take(6)->get();
        $qualifications  = \App\Models\EmployeeQualification::where('employee_id', $id)->orderByDesc('year_of_passing')->get();
        $experiences     = \App\Models\EmployeeExperience::where('employee_id', $id)->orderByDesc('from_date')->get();
        $empDocuments    = EmployeeDocument::where('employee_id', $id)->orderBy('document_type')->get();
        $certifications  = EmployeeCertification::where('employee_id', $id)->orderByDesc('issue_date')->get();

        $classAssignments = StaffClassTeacherAssignment::with(['class', 'assignedBy'])
            ->where('employee_id', $id)
            ->orderByDesc('is_current')
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();
        $currentClassAssignment = $classAssignments->firstWhere('is_current', true);
        $availableClasses = Classes::where('is_active', true)->orderBy('sort_order')->get();

        // ── Staff Attendance Statistics ─────────────────────────────
        $attQuery = \App\Models\StaffAttendance::where('employee_id', $id);
        $allAttRecords = (clone $attQuery)->orderByDesc('date')->get();

        $attPresent   = $allAttRecords->where('status', 'present')->count();
        $attLate      = $allAttRecords->where('status', 'late')->count();
        $attHalfDay   = $allAttRecords->where('status', 'half_day')->count();
        $attOvertime  = $allAttRecords->where('status', 'overtime')->count();
        $attAbsent    = $allAttRecords->where('status', 'absent')->count();
        $attLeave     = $allAttRecords->whereIn('status', ['leave', 'on_leave'])->count();
        $attPermission= $allAttRecords->filter(fn($r) => $r->status === 'permission' || $r->is_permission)->count();
        $attTotalDays = $allAttRecords->count();

        // Calculate total overtime hours worked on Sundays / Holidays
        $totalOtMins = $allAttRecords->where('status', 'overtime')->reduce(function($carry, $rec) {
            if ($rec->check_in && $rec->check_out) {
                return $carry + abs(\Carbon\Carbon::parse($rec->check_out)->diffInMinutes(\Carbon\Carbon::parse($rec->check_in)));
            }
            return $carry;
        }, 0);
        $otHrs = floor($totalOtMins / 60);
        $otMins = $totalOtMins % 60;
        $attOvertimeDuration = $otHrs > 0 ? "{$otHrs}h {$otMins}m" : ($totalOtMins > 0 ? "{$otMins}m" : '0m');

        $effectivePresent = $attPresent + $attLate + ($attHalfDay * 0.5) + $attOvertime;

        $tz = SchoolSetting::first()?->timezone ?: config('app.timezone', 'Asia/Kolkata');
        $today = Carbon::now($tz)->toDateString();
        $cMonthStart = Carbon::now($tz)->startOfMonth()->toDateString();
        $cMonthEnd = Carbon::now($tz)->endOfMonth()->toDateString();
        $cPeriod = collect(CarbonPeriod::create($cMonthStart, min($today, $cMonthEnd)))->filter(fn($d) => !$d->isSunday());
        $currentMonthWorkingDays = max(1, $cPeriod->count());

        $currentMonthRecords = $allAttRecords->whereBetween('date', [$cMonthStart, $cMonthEnd]);
        $cmPresent = $currentMonthRecords->whereIn('status', ['present', 'late'])->count();
        $cmHalfDay = $currentMonthRecords->where('status', 'half_day')->count();
        $cmEffectivePresent = $cmPresent + ($cmHalfDay * 0.5);
        $attendancePercentage = $currentMonthWorkingDays > 0 ? number_format(round(($cmEffectivePresent / $currentMonthWorkingDays) * 100, 2), 2) : '0.00';
        $recentAttendance = $allAttRecords->take(30);

        // Monthly Attendance Breakdown (Collection grouped)
        $monthlyAttendance = $allAttRecords
            ->groupBy(fn($r) => \Carbon\Carbon::parse($r->date)->format('Y-m'))
            ->take(6)
            ->map(function ($records, $monthKey) use ($today) {
                [$yr, $mo] = explode('-', $monthKey);
                $mStart = "$yr-$mo-01";
                $mEnd = Carbon::parse($mStart)->endOfMonth()->toDateString();
                $effectiveEnd = min($today, $mEnd);
                $period = CarbonPeriod::create($mStart, $effectiveEnd);
                $workingDays = collect($period)->filter(fn($d) => !$d->isSunday())->count();
                $workingDays = max(1, $workingDays);

                $presentCount  = $records->whereIn('status', ['present', 'late'])->count();
                $halfDayCount  = $records->where('status', 'half_day')->count();
                $overtimeCount = $records->where('status', 'overtime')->count();
                $absentCount   = $records->where('status', 'absent')->count();
                $leaveCount    = $records->whereIn('status', ['leave', 'on_leave'])->count();

                $otMins = $records->where('status', 'overtime')->reduce(function($carry, $rec) {
                    if ($rec->check_in && $rec->check_out) {
                        return $carry + abs(\Carbon\Carbon::parse($rec->check_out)->diffInMinutes(\Carbon\Carbon::parse($rec->check_in)));
                    }
                    return $carry;
                }, 0);
                $h = floor($otMins / 60);
                $m = $otMins % 60;
                $otDuration = $h > 0 ? "{$h}h {$m}m" : ($otMins > 0 ? "{$m}m" : '—');

                $monthName = \Carbon\Carbon::parse($records->first()->date)->format('M Y');
                $eff = $presentCount + ($halfDayCount * 0.5);
                $pct = $workingDays > 0 ? number_format(round(($eff / $workingDays) * 100, 2), 2) : '0.00';

                return (object)[
                    'month_key'         => $monthKey,
                    'month_name'        => $monthName,
                    'working_days'      => $workingDays,
                    'present_count'     => $presentCount,
                    'half_day_count'    => $halfDayCount,
                    'overtime_count'    => $overtimeCount,
                    'overtime_duration' => $otDuration,
                    'absent_count'      => $absentCount,
                    'leave_count'       => $leaveCount,
                    'percentage'        => $pct,
                ];
            })->values();

        // ── Staff Leave History & Summary ───────────────────────────
        $leaveRequests = \App\Models\LeaveRequest::with(['leaveType', 'approvedBy'])
            ->where('employee_id', $id)
            ->orderByDesc('from_date')
            ->get();

        $approvedLeaveDays  = $leaveRequests->where('status', 'approved')->sum(fn($l) => (float)($l->total_days ?? $l->days ?? 1));
        $pendingLeaveCount  = $leaveRequests->where('status', 'pending')->count();
        $rejectedLeaveCount = $leaveRequests->where('status', 'rejected')->count();

        // ── 13 Staff Data Headings Integrations ───────────────────────
        // Heading 11: Family Details
        $familyMembers = EmployeeFamilyMember::where('employee_id', $id)->orderBy('id')->get();

        // Heading 10: Career & Annual Performance
        $careerHistories = EmployeeCareerHistory::where('employee_id', $id)->orderByDesc('effective_date')->get();
        $appraisals = DB::table('employee_appraisals')->where('employee_id', $id)->orderByDesc('appraisal_year')->get();

        // Heading 5: Live Timetable & Academic Workload
        $timetableRows = DB::table('timetables as t')
            ->leftJoin('classes as c', 'c.id', '=', 't.class_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 't.section_id')
            ->leftJoin('subjects as s', 's.id', '=', 't.subject_id')
            ->where('t.teacher_id', $id)
            ->where('t.is_active', true)
            ->select('t.*', 'c.name as class_name', 'sec.name as section_name', 's.name as subject_name', 's.code as subject_code')
            ->orderBy('t.day_of_week')
            ->orderBy('t.period_number')
            ->orderBy('t.start_time')
            ->get();

        $weeklyTeachingWorkload = $timetableRows->count();
        $teacherTimetable = [];
        $daysMap = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
        foreach ($daysMap as $dNum => $dName) {
            $teacherTimetable[$dNum] = [
                'day_name' => $dName,
                'periods'  => $timetableRows->where('day_of_week', $dNum)->values(),
            ];
        }
        $handledSubjects = $timetableRows->pluck('subject_name')->unique()->filter()->values()->all();
        $handledClasses = $timetableRows->map(fn($r) => trim(($r->class_name ?? '') . ' ' . ($r->section_name ?? '')))->unique()->filter()->values()->all();

        // Heading 4: Teaching Level Eligibilities
        $teacherEligibilities = DB::table('teacher_level_eligibilities as tle')
            ->leftJoin('teaching_levels as tl', 'tl.id', '=', 'tle.teaching_level_id')
            ->where('tle.employee_id', $id)
            ->select('tl.name', 'tl.code', 'tle.is_approved')
            ->get();

        // Heading 7: Salary & Statutory Structure
        $salaryStructure = DB::table('salary_structures')->where('employee_id', $id)->where('is_active', true)->first();

        return view('hr.employees-show', compact(
            'employee', 'linkedUser', 'staffRoles', 'payroll', 'qualifications',
            'experiences', 'empDocuments', 'certifications',
            'classAssignments', 'currentClassAssignment', 'availableClasses',
            'attPresent', 'attLate', 'attHalfDay', 'attAbsent', 'attLeave', 'attPermission', 'attOvertime', 'attOvertimeDuration', 'attTotalDays',
            'effectivePresent', 'cmEffectivePresent', 'currentMonthWorkingDays', 'attendancePercentage', 'recentAttendance', 'monthlyAttendance',
            'leaveRequests', 'approvedLeaveDays', 'pendingLeaveCount', 'rejectedLeaveCount',
            'familyMembers', 'careerHistories', 'appraisals', 'teacherTimetable', 'weeklyTeachingWorkload',
            'handledSubjects', 'handledClasses', 'teacherEligibilities', 'salaryStructure'
        ));
    }

    public function getStaffCategoryMetadata($employee): array
    {
        $type = strtolower($employee->employee_type ?? 'teaching');
        $desig = strtolower($employee->designation ?? '');

        if ($type !== 'teaching') {
            return [
                'category_key'   => 'non_teaching',
                'category_name'  => 'Non-Teaching Staff',
                'theme_label'    => 'NON-TEACHING',
                'theme_badge'    => 'NON-TEACHING THEME',
                'badge_class'    => 'bg-slate-100 text-slate-800 border-slate-300',
                'header_bg'      => 'linear-gradient(135deg, #1e293b 0%, #334155 100%)',
                'header_color'   => '#1e293b',
                'accent_color'   => '#475569',
                'text_accent'    => 'text-slate-700',
                'bg_accent'      => 'bg-slate-600',
                'border_color'   => '#475569',
            ];
        }

        if (str_contains($desig, 'hod') || str_contains($desig, 'head of department')) {
            return [
                'category_key'   => 'hod',
                'category_name'  => 'Head of Department (HOD)',
                'theme_label'    => 'HOD',
                'theme_badge'    => 'HOD THEME',
                'badge_class'    => 'bg-amber-100 text-amber-900 border-amber-300 font-bold',
                'header_bg'      => 'linear-gradient(135deg, #1e1b4b 0%, #312e81 100%)',
                'header_color'   => '#1e1b4b',
                'accent_color'   => '#4338ca',
                'text_accent'    => 'text-indigo-600',
                'bg_accent'      => 'bg-indigo-700',
                'border_color'   => '#312e81',
            ];
        }

        if (str_contains($desig, 'senior') || str_contains($desig, 'sr.') || str_contains($desig, 'pgt') || ($employee->experience_years ?? 0) >= 8) {
            return [
                'category_key'   => 'senior_teacher',
                'category_name'  => 'Senior Teacher',
                'theme_label'    => 'SENIOR',
                'theme_badge'    => 'SENIOR THEME',
                'badge_class'    => 'bg-emerald-100 text-emerald-900 border-emerald-300 font-bold',
                'header_bg'      => 'linear-gradient(135deg, #064e3b 0%, #065f46 100%)',
                'header_color'   => '#064e3b',
                'accent_color'   => '#059669',
                'text_accent'    => 'text-emerald-700',
                'bg_accent'      => 'bg-emerald-700',
                'border_color'   => '#065f46',
            ];
        }

        return [
            'category_key'   => 'teacher',
            'category_name'  => 'Teacher / Faculty',
            'theme_label'    => 'TEACHER',
            'theme_badge'    => 'TEACHER THEME',
            'badge_class'    => 'bg-blue-100 text-blue-900 border-blue-300 font-bold',
            'header_bg'      => 'linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%)',
            'header_color'   => '#1d4ed8',
            'accent_color'   => '#2563eb',
            'text_accent'    => 'text-blue-600',
            'bg_accent'      => 'bg-blue-600',
            'border_color'   => '#1d4ed8',
        ];
    }

    public function employeeIdCardStudio(Request $request, $id = null)
    {
        $school = \App\Models\SchoolSetting::first();
        $departments = Department::orderBy('name')->get();
        $categoryFilter = $request->get('category', 'all');
        $deptFilter = $request->get('department_id');
        $search = $request->get('search');

        $empQuery = Employee::with('department')->where('is_active', true);

        if ($deptFilter) {
            $empQuery->where('department_id', $deptFilter);
        }
        if ($search) {
            $empQuery->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('employee_code', 'like', "%$search%")
                  ->orWhere('designation', 'like', "%$search%");
            });
        }

        $allEmployees = $empQuery->orderBy('first_name')->get();

        // Map category metadata
        $allEmployees->each(function($emp) {
            $emp->theme_meta = $this->getStaffCategoryMetadata($emp);
        });

        // Filter by category
        $filteredEmployees = $allEmployees;
        if ($categoryFilter && $categoryFilter !== 'all') {
            $filteredEmployees = $allEmployees->filter(fn($e) => $e->theme_meta['category_key'] === $categoryFilter)->values();
        }

        // Determine currently selected employee
        $selectedId = $id ?? $request->get('employee_id');
        $employee = null;
        if ($selectedId) {
            $employee = Employee::with(['department', 'qualifications'])->find($selectedId);
        }
        if (!$employee) {
            $employee = $filteredEmployees->first() ?? $allEmployees->first() ?? Employee::first();
        }

        if ($employee) {
            $employee->theme_meta = $this->getStaffCategoryMetadata($employee);
        }

        // Category counts
        $counts = [
            'all'            => $allEmployees->count(),
            'hod'            => $allEmployees->filter(fn($e) => $e->theme_meta['category_key'] === 'hod')->count(),
            'senior_teacher' => $allEmployees->filter(fn($e) => $e->theme_meta['category_key'] === 'senior_teacher')->count(),
            'teacher'        => $allEmployees->filter(fn($e) => $e->theme_meta['category_key'] === 'teacher')->count(),
            'non_teaching'   => $allEmployees->filter(fn($e) => $e->theme_meta['category_key'] === 'non_teaching')->count(),
        ];

        return view('hr.employee-id-card-studio', compact('employee', 'filteredEmployees', 'allEmployees', 'school', 'departments', 'categoryFilter', 'deptFilter', 'counts'));
    }

    public function singleEmployeeIdCard(int $id)
    {
        return $this->employeeIdCardStudio(request(), $id);
    }

    public function generateStaffIdCards(Request $request)
    {
        $categoryFilter = $request->get('category', 'all');
        $deptFilter     = $request->get('department_id');
        $ids            = $request->get('ids');

        $query = Employee::with('department')->where('is_active', true);

        if ($ids) {
            $idArray = is_array($ids) ? $ids : explode(',', $ids);
            $query->whereIn('id', $idArray);
        }
        if ($deptFilter) {
            $query->where('department_id', $deptFilter);
        }

        $employees = $query->orderBy('first_name')->get();
        $employees->each(function($emp) {
            $emp->theme_meta = $this->getStaffCategoryMetadata($emp);
        });

        if ($categoryFilter && $categoryFilter !== 'all') {
            $employees = $employees->filter(fn($e) => $e->theme_meta['category_key'] === $categoryFilter)->values();
        }

        $school = \App\Models\SchoolSetting::first();
        $validUntil = now()->addYear()->format('M Y');

        foreach ($employees as $emp) {
            $qrData = implode(' | ', array_filter([
                'ID: ' . ($emp->employee_code ?? 'EMP-' . $emp->id),
                'Name: ' . $emp->full_name,
                'Dept: ' . ($emp->department?->name ?? $emp->department ?? ''),
                'Role: ' . ($emp->designation ?? ''),
                'Blood: ' . ($emp->blood_group ?? ''),
            ]));
            try {
                $emp->_qrCode = base64_encode(
                    QrCode::format('png')->size(80)->generate($qrData)
                );
            } catch (\Exception $e) {
                $emp->_qrCode = null;
            }
        }

        $pdf = Pdf::loadView('pdf.staff-id-cards', compact('employees', 'school', 'validUntil', 'categoryFilter'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('staff-id-cards-' . ($categoryFilter ?: 'all') . '.pdf');
    }

    public function syncEmployeeQualification($employee): void
    {
        if (is_numeric($employee)) {
            $employee = Employee::find($employee);
        }
        if (!$employee) return;

        $qualifications = $employee->qualifications()
            ->orderBy('year_of_passing', 'desc')
            ->pluck('degree')
            ->filter()
            ->toArray();

        $employee->qualification = !empty($qualifications) ? implode(', ', $qualifications) : null;
        $employee->saveQuietly();
    }

    public function storeQualification(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);
        $validated = $request->validate([
            'degree'              => 'required|string|max:100',
            'subject'             => 'nullable|string|max:100',
            'institution'         => 'required|string|max:150',
            'university'          => 'nullable|string|max:150',
            'year_of_passing'     => 'nullable|integer|min:1950|max:' . date('Y'),
            'grade_or_percentage' => 'nullable|string|max:50',
            'education_level'     => 'required|in:secondary,higher_secondary,diploma,graduate,post_graduate,doctorate,other',
        ]);

        $employee->qualifications()->create($validated);
        $this->syncEmployeeQualification($employee);

        return back()->with('success', 'Qualification added and synced successfully.');
    }

    public function updateQualification(Request $request, int $id, int $qualId)
    {
        $employee = Employee::findOrFail($id);
        $qual = EmployeeQualification::where('employee_id', $id)->findOrFail($qualId);
        $validated = $request->validate([
            'degree'              => 'required|string|max:100',
            'subject'             => 'nullable|string|max:100',
            'institution'         => 'required|string|max:150',
            'university'          => 'nullable|string|max:150',
            'year_of_passing'     => 'nullable|integer|min:1950|max:' . date('Y'),
            'grade_or_percentage' => 'nullable|string|max:50',
            'education_level'     => 'required|in:secondary,higher_secondary,diploma,graduate,post_graduate,doctorate,other',
        ]);

        $qual->update($validated);
        $this->syncEmployeeQualification($employee);

        return back()->with('success', 'Qualification updated and synced successfully.');
    }

    public function deleteQualification(int $id, int $qualId)
    {
        $employee = Employee::findOrFail($id);
        $qual = EmployeeQualification::where('employee_id', $id)->findOrFail($qualId);
        $qual->delete();
        $this->syncEmployeeQualification($employee);

        return back()->with('success', 'Qualification removed and synced successfully.');
    }

    public function storeExperience(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);
        $validated = $request->validate([
            'organisation'        => 'required|string|max:150',
            'role'                => 'required|string|max:100',
            'from_date'           => 'required|date',
            'to_date'             => 'nullable|date|after_or_equal:from_date',
            'is_current'          => 'boolean',
            'reason_for_leaving'  => 'nullable|string|max:255',
            'responsibilities'    => 'nullable|string',
            'reference_contact'   => 'nullable|string|max:100',
        ]);

        $employee->experiences()->create($validated);
        return back()->with('success', 'Experience record added.');
    }

    public function deleteExperience(int $id, int $expId)
    {
        $exp = EmployeeExperience::where('employee_id', $id)->findOrFail($expId);
        $exp->delete();
        return back()->with('success', 'Experience record removed.');
    }

    public function updateEmployeePhoto(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }
            $photoPath = $request->file('photo')->store('employee-photos', 'public');
            $employee->update(['photo' => $photoPath]);
        }

        return redirect()->back()->with('success', 'Employee photo updated successfully on ID card!');
    }

    public function uploadEmployeeDocument(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);
        $request->validate([
            'document_type' => 'required|string',
            'document_name' => 'required|string|max:150',
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);
        $path = $request->file('file')->store('employee-documents/' . $id, 'local');
        EmployeeDocument::create([
            'employee_id'       => $id,
            'document_type'     => $request->document_type,
            'document_name'     => $request->document_name,
            'file_path'         => $path,
            'file_original_name'=> $request->file('file')->getClientOriginalName(),
            'notes'             => $request->notes,
        ]);
        return back()->with('success', 'Document uploaded.');
    }

    public function downloadEmployeeDocument(int $employeeId, int $docId)
    {
        $doc = EmployeeDocument::where('employee_id', $employeeId)->findOrFail($docId);
        abort_unless(Storage::disk('local')->exists($doc->file_path), 404);
        return Storage::disk('local')->download($doc->file_path, $doc->file_original_name ?? basename($doc->file_path));
    }

    public function previewEmployeeDocument(int $employeeId, int $docId)
    {
        $doc = EmployeeDocument::where('employee_id', $employeeId)->findOrFail($docId);
        abort_unless(Storage::disk('local')->exists($doc->file_path), 404);
        return response()->file(Storage::disk('local')->path($doc->file_path));
    }

    public function deleteEmployeeDocument(int $employeeId, int $docId)
    {
        $doc = EmployeeDocument::where('employee_id', $employeeId)->findOrFail($docId);
        Storage::disk('local')->delete($doc->file_path);
        $doc->delete();
        return back()->with('success', 'Document deleted.');
    }

    public function verifyEmployeeDocument(Request $request, int $employeeId, int $docId)
    {
        $doc = EmployeeDocument::where('employee_id', $employeeId)->findOrFail($docId);
        $doc->update([
            'verification_status' => $request->status,
            'verified_by'         => Auth::id(),
            'verified_at'         => now(),
            'notes'               => $request->notes ?? $doc->notes,
        ]);
        return back()->with('success', 'Document status updated.');
    }

    public function storeCertification(Request $request, int $id)
    {
        Employee::findOrFail($id);
        $request->validate([
            'certification_name' => 'required|string|max:200',
            'issuing_authority'  => 'nullable|string|max:200',
            'issue_date'         => 'nullable|date',
            'certificate_number' => 'nullable|string|max:100',
            'course_name'        => 'nullable|string|max:200',
            'duration_days'      => 'nullable|integer|min:1',
            'remarks'            => 'nullable|string|max:500',
            'file'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('employee-certifications/' . $id, 'local');
        }
        EmployeeCertification::create([
            'employee_id'        => $id,
            'certification_name' => $request->certification_name,
            'issuing_authority'  => $request->issuing_authority,
            'issue_date'         => $request->issue_date,
            'certificate_number' => $request->certificate_number,
            'course_name'        => $request->course_name,
            'duration_days'      => $request->duration_days,
            'remarks'            => $request->remarks,
            'file_path'          => $filePath,
        ]);
        return back()->with('success', 'Certification / Training record added successfully.');
    }

    public function downloadCertification(int $employeeId, int $certId)
    {
        $cert = EmployeeCertification::where('employee_id', $employeeId)->findOrFail($certId);
        abort_unless($cert->file_path && Storage::disk('local')->exists($cert->file_path), 404);
        return Storage::disk('local')->download($cert->file_path, basename($cert->file_path));
    }

    public function previewCertification(int $employeeId, int $certId)
    {
        $cert = EmployeeCertification::where('employee_id', $employeeId)->findOrFail($certId);
        abort_unless($cert->file_path && Storage::disk('local')->exists($cert->file_path), 404);
        return response()->file(Storage::disk('local')->path($cert->file_path));
    }

    public function deleteCertification(int $employeeId, int $certId)
    {
        $cert = EmployeeCertification::where('employee_id', $employeeId)->findOrFail($certId);
        if ($cert->file_path) Storage::disk('local')->delete($cert->file_path);
        $cert->delete();
        return back()->with('success', 'Certification deleted.');
    }

    public function storeFamilyMember(Request $request, int $id)
    {
        Employee::findOrFail($id);

        if ($request->filled('name') && !$request->filled('member_name')) {
            $request->merge(['member_name' => $request->name]);
        }

        $validated = $request->validate([
            'member_name'       => 'required|string|max:150',
            'relationship'      => 'required|string|max:100',
            'dob'               => 'nullable|date',
            'contact_number'    => 'nullable|string|max:20',
            'permanent_address' => 'nullable|string|max:500',
            'aadhaar_address'   => 'nullable|string|max:500',
            'is_verified'       => 'nullable|boolean',
        ]);

        EmployeeFamilyMember::create(array_merge($validated, [
            'employee_id' => $id,
            'is_verified' => $request->boolean('is_verified', false),
        ]));

        return back()->with('success', 'Family member details recorded successfully.');
    }

    public function deleteFamilyMember(int $id, int $familyId)
    {
        $member = EmployeeFamilyMember::where('employee_id', $id)->findOrFail($familyId);
        $member->delete();
        return back()->with('success', 'Family member record removed successfully.');
    }

    public function assignClassTeacher(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);

        if (auth()->check() && !auth()->user()->can('edit employees') && !auth()->user()->hasRole('Super Admin') && !auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized action. Only authorized Admin/HR users can assign Class Teachers.');
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ], [
            'class_id.required' => 'Please select a class to assign.',
            'class_id.exists'   => 'The selected class is invalid.',
        ]);

        $class = Classes::findOrFail($validated['class_id']);

        // Check if staff member is already assigned to this exact class as current
        $currentAssignment = StaffClassTeacherAssignment::where('employee_id', $employee->id)
            ->where('is_current', true)
            ->first();

        if ($currentAssignment && (int)$currentAssignment->class_id === (int)$class->id) {
            return back()->with('info', "This staff member is already assigned as {$class->class_teacher_title} Teacher.");
        }

        // Conflict check: Check whether another staff member is already the current Class Teacher for this class
        $conflictAssignment = StaffClassTeacherAssignment::with('employee')
            ->where('class_id', $class->id)
            ->where('is_current', true)
            ->where('employee_id', '!=', $employee->id)
            ->first();

        if ($conflictAssignment) {
            $otherTeacherName = $conflictAssignment->employee ? $conflictAssignment->employee->full_name : 'another staff member';
            return back()
                ->withErrors(['class_id' => "This class is already assigned to another Class Teacher ({$otherTeacherName})."])
                ->with('error', "This class is already assigned to another Class Teacher ({$otherTeacherName}).");
        }

        // Atomically transition assignments within a database transaction
        DB::transaction(function () use ($employee, $class) {
            $today = Carbon::today();

            // End/close any existing current assignment for this employee
            StaffClassTeacherAssignment::where('employee_id', $employee->id)
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'ended_at'   => $today,
                ]);

            // Create new current assignment
            StaffClassTeacherAssignment::create([
                'employee_id' => $employee->id,
                'class_id'    => $class->id,
                'assigned_at' => $today,
                'ended_at'    => null,
                'is_current'  => true,
                'assigned_by' => auth()->id(),
            ]);

            // Keep assigned_class on employees table in sync
            $employee->update([
                'assigned_class' => $class->class_teacher_title,
            ]);
        });

        // Clear class & timetable cache
        Classes::clearClassCache();
        ClassesController::clearCache();

        return back()->with('success', "Class Teacher assignment for {$class->class_teacher_title} saved successfully.");
    }

    public function editEmployee(int $id)
    {
        $employee = Employee::findOrFail($id);
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->orderBy('name')->get();
        $managers = Employee::where('is_active', true)->where('id', '!=', $id)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'designation', 'employee_code']);
        return view('hr.employees-edit', compact('employee', 'departments', 'designations', 'managers'));
    }

    public function updateEmployee(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);

        // Handle NAME (Excel heading: NAME)
        if ($request->filled('name')) {
            $parts = preg_split('/\s+/', trim((string) $request->input('name')), 2);
            $request->merge([
                'first_name' => $parts[0] ?? '',
                'last_name'  => $parts[1] ?? '',
            ]);
        } elseif ($request->filled('first_name')) {
            $request->merge([
                'name' => trim($request->input('first_name') . ' ' . ($request->input('last_name') ?? '')),
            ]);
        }

        // Sanitize digits and uppercase before validation
        if ($request->has('aadhaar_no') && $request->aadhaar_no !== null) {
            $request->merge(['aadhaar_no' => preg_replace('/[\s\-]+/', '', (string) $request->aadhaar_no)]);
        }
        if ($request->has('pan_no') && $request->pan_no !== null) {
            $request->merge(['pan_no' => strtoupper(trim((string) $request->pan_no))]);
        }
        if ($request->has('mobile') && $request->mobile !== null) {
            $request->merge(['mobile' => preg_replace('/[\s\-]+/', '', (string) $request->mobile)]);
        }
        if ($request->has('contact_no') && $request->contact_no !== null) {
            $request->merge(['contact_no' => preg_replace('/[\s\-]+/', '', (string) $request->contact_no)]);
        }
        if ($request->has('emergency_contact_mobile') && $request->emergency_contact_mobile !== null) {
            $request->merge(['emergency_contact_mobile' => preg_replace('/[\s\-]+/', '', (string) $request->emergency_contact_mobile)]);
        }
        if ($request->has('alternate_phone') && $request->alternate_phone !== null) {
            $request->merge(['alternate_phone' => preg_replace('/[\s\-]+/', '', (string) $request->alternate_phone)]);
        }
        if ($request->has('pincode') && $request->pincode !== null) {
            $request->merge(['pincode' => trim((string) $request->pincode)]);
        }
        if ($request->has('official_email') && $request->official_email !== null) {
            $request->merge(['official_email' => strtolower(trim((string) $request->official_email))]);
        }
        // Section J: default to 'None / Not Applicable' if not provided
        if (!$request->filled('additional_qual_after_recruitment')) {
            $request->merge(['additional_qual_after_recruitment' => 'None / Not Applicable']);
        }
        if (!$request->filled('training_attended_before_recruitment')) {
            $request->merge(['training_attended_before_recruitment' => 'None / Not Applicable']);
        }

        $empType = $request->input('employee_type', $employee->employee_type);
        $isRbacEligible = in_array($empType, ['teaching', 'non_teaching'], true);

        if ($isRbacEligible) {
            $rules = [
                // Section A: Personal Details
                'name'                   => 'required|string|max:150',
                'first_name'             => 'required|string|max:60',
                'last_name'              => 'nullable|string|max:60',
                'dob'                    => 'required|date|before:today',
                'gender'                 => 'required|string|in:male,female,other',
                'mother_tongue'          => 'required|string|max:100',
                'nationality'            => 'required|string|max:100',
                'religion'               => 'required|string|max:100',
                'caste'                  => 'required|string|max:100',
                'marital_status'         => 'required|string|in:Single,Married,single,married',
                'number_of_children'     => strcasecmp($request->marital_status ?? '', 'Married') === 0 ? 'required|integer|min:0' : 'nullable|integer|min:0',
                'children_male'          => strcasecmp($request->marital_status ?? '', 'Married') === 0 ? 'required|integer|min:0' : 'nullable|integer|min:0',
                'children_female'        => strcasecmp($request->marital_status ?? '', 'Married') === 0 ? 'required|integer|min:0' : 'nullable|integer|min:0',
                'address'                => 'required|string|max:500',

                // Section B: Employment Details
                'employee_code'          => 'required|string|max:50|unique:employees,employee_code,' . $id,
                'designation'            => 'required|string|max:100',
                'joining_date'           => 'required|date',
                'first_appointment_date' => $empType === 'teaching' ? 'required|date' : 'nullable|date',
                'current_employment_status' => 'required|string|in:Permanent,Contractual,Probation,Temporary,Part-Time,Other,permanent,contractual,probation,temporary,part-time,other',
                'probation_start_date'      => 'nullable|date',
                'probation_end_date'        => 'nullable|date',
                'employee_type'          => 'required|string|in:teaching,non_teaching,driver,cleaner,nanny,naani,contract,part_time,admin,support',
                'department'             => 'nullable|string|max:100',
                'is_active'              => 'nullable|boolean',

                // Section C: Identity / Government Details
                'aadhaar_no'             => 'required|string|regex:/^[0-9]{12}$/',
                'pan_no'                 => 'required|string|regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}$/',
                'epf_no'                 => 'required|string|max:50',

                // Section D: Contact Details
                'official_email'         => 'required|email|max:100',
                'mobile'                 => 'required|string|regex:/^[0-9]{10}$/',
                'name_of_contact_person' => 'required|string|max:150',
                'contact_relationship'   => 'required|string|max:100',
                'contact_address'        => 'required|string|max:500',
                'contact_no'             => 'required|string|regex:/^[0-9]{10}$/',

                // Section E: Previous Employment Details
                'previous_designation'   => 'required|string|max:100',
                'previous_school'        => 'required|string|max:250',
                'previous_from_date'     => 'required|date',
                'previous_to_date'       => 'required|date|after_or_equal:previous_from_date',

                // Section F: Academic Qualification
                'academic_qualification' => 'required|string|max:150',
                'academic_university'    => 'required|string|max:150',
                'academic_medium'        => 'required|string|max:100',
                'academic_institution'   => 'required|string|max:200',
                'academic_percentage'    => 'required|numeric|min:0|max:100',
                'academic_year_of_passing' => 'required|integer|min:1950|max:' . (date('Y') + 1),

                // Section G: Professional Qualification
                'professional_qualification' => 'required|string|max:150',
                'professional_university'    => 'required|string|max:150',
                'professional_medium'        => 'required|string|max:100',
                'professional_institution'   => 'required|string|max:200',
                'professional_percentage'    => 'required|numeric|min:0|max:100',
                'professional_year_of_passing' => 'required|integer|min:1950|max:' . (date('Y') + 1),

                // Section H: Other Qualification 1 (conditional if provided)
                'other_qual_1_name'      => 'nullable|string|max:150',
                'other_qual_1_university'=> 'required_with:other_qual_1_name|nullable|string|max:150',
                'other_qual_1_medium'    => 'required_with:other_qual_1_name|nullable|string|max:100',
                'other_qual_1_institution'=> 'required_with:other_qual_1_name|nullable|string|max:200',
                'other_qual_1_percentage'=> 'required_with:other_qual_1_name|nullable|numeric|min:0|max:100',
                'other_qual_1_year_of_passing' => 'required_with:other_qual_1_name|nullable|integer|min:1950|max:' . (date('Y') + 1),

                // Section I: Other Qualification 2 (conditional if provided)
                'other_qual_2_name'      => 'nullable|string|max:150',
                'other_qual_2_university'=> 'required_with:other_qual_2_name|nullable|string|max:150',
                'other_qual_2_medium'    => 'required_with:other_qual_2_name|nullable|string|max:100',
                'other_qual_2_institution'=> 'required_with:other_qual_2_name|nullable|string|max:200',
                'other_qual_2_percentage'=> 'required_with:other_qual_2_name|nullable|numeric|min:0|max:100',
                'other_qual_2_year_of_passing' => 'required_with:other_qual_2_name|nullable|integer|min:1950|max:' . (date('Y') + 1),

                // Section J: Additional Qualification / Training
                'additional_qual_after_recruitment' => 'nullable|string|max:1000',
                'training_attended_before_recruitment' => 'nullable|string|max:1000',

                // Optional category specific / existing fields
                'photo'                  => 'nullable|image|max:2048',
                'basic_salary'           => 'nullable|numeric|min:0',
                'pf_account_no'          => 'nullable|string|max:50',
                'esi_no'                 => 'nullable|string|max:50',
                'license_number'         => 'nullable|string|max:100',
                'license_expiry'         => 'nullable|date',
                'assigned_vehicle'       => 'nullable|string|max:100',
                'assigned_block'         => 'nullable|string|max:100',
                'shift_timing'           => 'nullable|string|max:100',

                // 13 Staff Data Collection Headings additions
                'pincode'                => 'nullable|string|regex:/^[1-9][0-9]{5}$/',
                'alternate_phone'        => 'nullable|string|regex:/^[0-9]{10}$/',
                'permanent_address'      => 'nullable|string|max:500',
                'pay_scale'              => 'nullable|string|max:100',
                'last_promotion_date'    => 'nullable|date',
                'contract_end_date'      => 'nullable|date',
                'confirmation_date'      => 'nullable|date',
                'major_subject'          => 'nullable|string|max:150',
                'minor_subject'          => 'nullable|string|max:150',
                'special_skills'         => 'nullable|string|max:500',
                'preferred_teaching_level' => 'nullable|string|max:100',
                'lab_subjects'           => 'nullable|string|max:250',
                'uan_no'                 => 'nullable|string|max:50',
                'professional_tax_no'    => 'nullable|string|max:50',
                'passport_no'            => 'nullable|string|max:50',
                'annual_increment'       => 'nullable|numeric|min:0',
                'increment_effective_date' => 'nullable|date',
                'nominee_name'           => 'nullable|string|max:150',
                'nominee_relationship'   => 'nullable|string|max:100',
                'nominee_share'          => 'nullable|numeric|min:0|max:100',
            ];
        } else {
            $rules = [
                'name'                   => 'required|string|max:150',
                'first_name'             => 'required|string|max:60',
                'last_name'              => 'nullable|string|max:60',
                'dob'                    => 'nullable|date|before:today',
                'gender'                 => 'required|string|in:male,female,other',
                'mother_tongue'          => 'nullable|string|max:100',
                'nationality'            => 'nullable|string|max:100',
                'religion'               => 'nullable|string|max:100',
                'caste'                  => 'nullable|string|max:100',
                'marital_status'         => 'nullable|string',
                'number_of_children'     => 'nullable|integer|min:0',
                'children_male'          => 'nullable|integer|min:0',
                'children_female'        => 'nullable|integer|min:0',
                'address'                => 'nullable|string|max:500',
                'pincode'                => 'nullable|string|regex:/^[1-9][0-9]{5}$/',
                'employee_code'          => 'required|string|max:50|unique:employees,employee_code,' . $id,
                'designation'            => 'required|string|max:100',
                'joining_date'           => 'required|date',
                'first_appointment_date' => 'nullable|date',
                'current_employment_status' => 'nullable|string|in:Permanent,Contractual,Probation,Temporary,Part-Time,Other,permanent,contractual,probation,temporary,part-time,other',
                'probation_start_date'      => 'nullable|date',
                'probation_end_date'        => 'nullable|date',
                'employee_type'          => 'required|string',
                'department'             => 'nullable|string|max:100',
                'is_active'              => 'nullable|boolean',
                'aadhaar_no'             => 'nullable|string|regex:/^[0-9]{12}$/',
                'pan_no'                 => 'nullable|string',
                'epf_no'                 => 'nullable|string|max:50',
                'official_email'         => 'nullable|email|max:100',
                'mobile'                 => 'required|string|regex:/^[0-9]{10}$/',
                'name_of_contact_person' => 'nullable|string|max:150',
                'contact_relationship'   => 'nullable|string|max:100',
                'contact_address'        => 'nullable|string|max:500',
                'contact_no'             => 'nullable|string|regex:/^[0-9]{10}$/',
                'previous_designation'   => 'nullable|string|max:100',
                'previous_school'        => 'nullable|string|max:250',
                'previous_from_date'     => 'nullable|date',
                'previous_to_date'       => 'nullable|date',
                'academic_qualification' => 'nullable|string|max:150',
                'academic_university'    => 'nullable|string|max:150',
                'academic_medium'        => 'nullable|string|max:100',
                'academic_institution'   => 'nullable|string|max:200',
                'academic_percentage'    => 'nullable|numeric|min:0|max:100',
                'academic_year_of_passing' => 'nullable|integer',
                'professional_qualification' => 'nullable|string|max:150',
                'professional_university'    => 'nullable|string|max:150',
                'professional_medium'        => 'nullable|string|max:100',
                'professional_institution'   => 'nullable|string|max:200',
                'professional_percentage'    => 'nullable|numeric|min:0|max:100',
                'professional_year_of_passing' => 'nullable|integer',
                'additional_qual_after_recruitment' => 'nullable|string|max:1000',
                'training_attended_before_recruitment' => 'nullable|string|max:1000',
                'photo'                  => 'nullable|image|max:2048',
                'basic_salary'           => 'nullable|numeric|min:0',
                'pf_account_no'          => 'nullable|string|max:50',
                'esi_no'                 => 'nullable|string|max:50',
                'license_number'         => 'nullable|string|max:100',
                'license_expiry'         => 'nullable|date',
                'assigned_vehicle'       => 'nullable|string|max:100',
                'assigned_block'         => 'nullable|string|max:100',
                'shift_timing'           => 'nullable|string|max:100',

                // 13 Staff Data Collection Headings additions
                'alternate_phone'        => 'nullable|string|regex:/^[0-9]{10}$/',
                'permanent_address'      => 'nullable|string|max:500',
                'pay_scale'              => 'nullable|string|max:100',
                'uan_no'                 => 'nullable|string|max:50',
                'professional_tax_no'    => 'nullable|string|max:50',
                'passport_no'            => 'nullable|string|max:50',
                'nominee_name'           => 'nullable|string|max:150',
                'nominee_relationship'   => 'nullable|string|max:100',
                'nominee_share'          => 'nullable|numeric|min:0|max:100',
            ];
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, [
            'name.required'                  => 'NAME is required.',
            'first_name.required'            => 'NAME is required.',
            'dob.required'                   => 'DOB is required.',
            'dob.before'                     => 'DOB must be a date before today.',
            'gender.required'                => 'M/F (Gender) is required.',
            'mother_tongue.required'         => 'MOTHER TONGUE is required.',
            'nationality.required'           => 'NATIONALITY is required.',
            'religion.required'              => 'RELIGION is required.',
            'caste.required'                 => 'CASTE is required.',
            'marital_status.required'        => 'MARITAL STATUS is required.',
            'marital_status.in'              => 'MARITAL STATUS must be Single or Married.',
            'number_of_children.required'    => 'NUMBER OF CHILDREN is required for married staff.',
            'children_male.required'         => 'MALE children count is required for married staff.',
            'children_female.required'       => 'FEMALE children count is required for married staff.',
            'address.required'               => 'ADDRESS is required.',
            'employee_code.required'         => 'EMPLOYEE ID is required.',
            'designation.required'           => 'DESIGNATION is required.',
            'joining_date.required'          => 'DOJ (Date of Joining) is required.',
            'first_appointment_date.required'=> 'DATE OF FIRST APPOINTMENT AS TEACHER is required.',
            'current_employment_status.required' => 'CURRENT EMPLOYMENT STATUS is required.',
            'current_employment_status.in'   => 'CURRENT EMPLOYMENT STATUS must be Permanent, Probation, Contractual, Temporary, or Other.',
            'aadhaar_no.required'            => 'AADHAR NO is required.',
            'aadhaar_no.regex'               => 'AADHAR NO must be exactly 12 digits.',
            'pan_no.required'                => 'PAN NO. is required.',
            'pan_no.regex'                   => 'PAN NO. must be 10 characters in valid format (e.g. ABCDE1234F).',
            'epf_no.required'                => 'EPF NO. is required.',
            'official_email.required'        => 'EMAIL ID is required.',
            'official_email.email'           => 'EMAIL ID must be a valid email address.',
            'mobile.required'                => 'CONTACT NO. is required.',
            'mobile.regex'                   => 'CONTACT NO. must be exactly 10 digits.',
            'name_of_contact_person.required'=> 'NAME OF THE CONTACT PERSON is required.',
            'contact_relationship.required'  => 'RELATIONSHIP is required.',
            'contact_address.required'       => 'CONTACT ADDRESS is required.',
            'contact_no.required'            => 'CONTACT NO (Emergency Contact) is required.',
            'contact_no.regex'               => 'CONTACT NO must be exactly 10 digits.',
            'previous_designation.required'  => 'PREVIOUS POST HELD is required.',
            'previous_school.required'       => 'NAME & ADDRESS OF THE INSTITUTION is required.',
            'previous_from_date.required'    => 'FROM date is required.',
            'previous_to_date.required'      => 'TO date is required.',
            'previous_to_date.after_or_equal'=> 'Previous Employment "TO" date cannot be earlier than "FROM" date.',
            'academic_qualification.required'=> 'ACADEMIC QUALIFICATION is required.',
            'academic_university.required'   => 'Academic UNIVERSITY is required.',
            'academic_medium.required'       => 'Academic MEDIUM is required.',
            'academic_institution.required'  => 'Academic NAME OF THE INSTITUTION is required.',
            'academic_percentage.required'   => 'Academic %MARKS is required.',
            'academic_percentage.min'        => 'Academic %MARKS cannot be negative.',
            'academic_percentage.max'        => 'Academic %MARKS cannot exceed 100.',
            'academic_year_of_passing.required' => 'Academic YEAR OF PASSING is required.',
            'professional_qualification.required' => 'PROFESSIONAL QUALIFICATION is required.',
            'professional_university.required'=> 'Professional UNIVERSITY is required.',
            'professional_medium.required'   => 'Professional MEDIUM is required.',
            'professional_institution.required' => 'Professional NAME OF INSTITUTION is required.',
            'professional_percentage.required' => 'Professional %MARKS is required.',
            'professional_percentage.min'    => 'Professional %MARKS cannot be negative.',
            'professional_percentage.max'    => 'Professional %MARKS cannot exceed 100.',
            'professional_year_of_passing.required' => 'Professional YEAR OF PASSING is required.',
            'other_qual_1_percentage.min'    => 'Other Qualification 1 %MARKS cannot be negative.',
            'other_qual_1_percentage.max'    => 'Other Qualification 1 %MARKS cannot exceed 100.',
            'other_qual_2_percentage.min'    => 'Other Qualification 2 %MARKS cannot exceed 100.',
            'additional_qual_after_recruitment.required' => 'ADDITIONAL QUALIFICATION OBTAINED AFTER RECRUITMENT is required.',
            'training_attended_before_recruitment.required' => 'TRAINING PROGRAMME ATTENDED BEFORE RECRUITMENT is required.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (strcasecmp($request->marital_status ?? '', 'Married') === 0) {
                if ($request->filled('number_of_children') && $request->filled('children_male') && $request->filled('children_female')) {
                    $total = (int) $request->number_of_children;
                    $m = (int) $request->children_male;
                    $f = (int) $request->children_female;
                    if (($m + $f) !== $total) {
                        $validator->errors()->add('number_of_children', "The sum of Male ($m) and Female ($f) children must equal the Number of Children ($total).");
                    }
                }
            }
        });

        $validated = $validator->validate();

        $data = $validated;
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->photo);
            }
            $data['photo'] = $request->file('photo')->store('employee-photos', 'public');
        }

        // Fill normalized fields and aliases
        $data['marital_status'] = ucfirst(strtolower($data['marital_status']));
        if (strcasecmp($data['marital_status'], 'Married') !== 0) {
            $data['number_of_children'] = 0;
            $data['children_male'] = 0;
            $data['children_female'] = 0;
        } else {
            $data['number_of_children'] = (int) ($data['number_of_children'] ?? 0);
            $data['children_male'] = (int) ($data['children_male'] ?? 0);
            $data['children_female'] = (int) ($data['children_female'] ?? 0);
        }
        $data['current_employment_status'] = ucfirst(strtolower($data['current_employment_status'] ?? 'Permanent'));
        $data['employment_type'] = strtolower($data['current_employment_status']);
        if (!empty($data['designation'])) {
            $desigRecord = Designation::firstOrCreate(['name' => $data['designation']]);
            $data['designation_id'] = $desigRecord->id;
        }
        if (!empty($data['department'])) {
            $deptRecord = Department::firstOrCreate(['name' => $data['department']], ['code' => strtoupper(substr($data['department'], 0, 4))]);
            $data['department_id'] = $deptRecord->id;
        }
        $data['qualification'] = $data['academic_qualification'];
        $data['contact_person'] = $data['name_of_contact_person'];
        $data['email'] = $data['official_email'];
        $data['personal_email'] = $data['personal_email'] ?? $data['official_email'];
        $data['residential_address'] = $data['address'];
        $data['emergency_contact_name'] = $data['name_of_contact_person'];
        $data['emergency_contact_mobile'] = $data['contact_no'];
        $data['pf_account_no'] = $data['epf_no'];
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $employee->is_active;

        $employee->update($data);

        // Sync to relational EmployeeQualification records
        if (!empty($employee->academic_qualification)) {
            EmployeeQualification::updateOrCreate(
                ['employee_id' => $employee->id, 'degree' => $employee->academic_qualification],
                [
                    'education_level'     => 'graduate',
                    'university'          => $employee->academic_university,
                    'subject'             => $employee->academic_medium,
                    'institution'         => $employee->academic_institution,
                    'grade_or_percentage' => $employee->academic_percentage . '%',
                    'year_of_passing'     => $employee->academic_year_of_passing,
                ]
            );
        }

        if (!empty($employee->professional_qualification)) {
            EmployeeQualification::updateOrCreate(
                ['employee_id' => $employee->id, 'degree' => $employee->professional_qualification],
                [
                    'education_level'     => 'diploma',
                    'university'          => $employee->professional_university,
                    'subject'             => $employee->professional_medium,
                    'institution'         => $employee->professional_institution,
                    'grade_or_percentage' => $employee->professional_percentage . '%',
                    'year_of_passing'     => $employee->professional_year_of_passing,
                ]
            );
        }

        if (!empty($employee->other_qual_1_name)) {
            EmployeeQualification::updateOrCreate(
                ['employee_id' => $employee->id, 'degree' => $employee->other_qual_1_name],
                [
                    'education_level'     => 'other',
                    'university'          => $employee->other_qual_1_university,
                    'subject'             => $employee->other_qual_1_medium,
                    'institution'         => $employee->other_qual_1_institution,
                    'grade_or_percentage' => $employee->other_qual_1_percentage !== null ? ($employee->other_qual_1_percentage . '%') : null,
                    'year_of_passing'     => $employee->other_qual_1_year_of_passing,
                ]
            );
        }

        if (!empty($employee->other_qual_2_name)) {
            EmployeeQualification::updateOrCreate(
                ['employee_id' => $employee->id, 'degree' => $employee->other_qual_2_name],
                [
                    'education_level'     => 'other',
                    'university'          => $employee->other_qual_2_university,
                    'subject'             => $employee->other_qual_2_medium,
                    'institution'         => $employee->other_qual_2_institution,
                    'grade_or_percentage' => $employee->other_qual_2_percentage !== null ? ($employee->other_qual_2_percentage . '%') : null,
                    'year_of_passing'     => $employee->other_qual_2_year_of_passing,
                ]
            );
        }

        // Sync to relational EmployeeExperience records
        if (!empty($employee->previous_school) || !empty($employee->previous_designation)) {
            EmployeeExperience::updateOrCreate(
                ['employee_id' => $employee->id, 'is_current' => false],
                [
                    'organisation' => $employee->previous_school ?? 'Previous Institution',
                    'role'         => $employee->previous_designation ?? 'Staff',
                    'from_date'    => $employee->previous_from_date,
                    'to_date'      => $employee->previous_to_date,
                ]
            );
        }

        AuditLog::record('employee_updated', $employee, [], ['employee_code' => $employee->employee_code, 'is_active' => $employee->is_active]);
        return redirect()->route('hr.employees.show', $employee->id)
            ->with('success', 'Employee updated successfully.');
    }

    public function payroll(Request $request)
    {
        $month     = $request->month ?? now()->format('Y-m');
        $records   = PayrollRecord::with('employee')
            ->where('month', $month)->get();
        $employees = Employee::where('is_active', true)->orderBy('first_name')->get();
        return view('hr.payroll', compact('records', 'employees', 'month'));
    }

    public function processPayroll(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        // Cannot reprocess if any record for this month is locked
        $lockedCount = PayrollRecord::where('month', $request->month)->where('is_locked', true)->count();
        if ($lockedCount > 0) {
            return back()->with('error', "Payroll for {$request->month} is locked. Unlock records first.");
        }

        $employees   = Employee::where('is_active', true)->get();
        [$year, $mon] = explode('-', $request->month);
        $workingDays = (int) \App\Models\SchoolSetting::get('payroll_working_days', 26);

        DB::transaction(function () use ($request, $employees, $year, $mon, $workingDays) {
            foreach ($employees as $emp) {
                // Fetch gross salary from salary_structures
                $salaryStruct = DB::table('salary_structures')
                    ->where('employee_id', $emp->id)
                    ->where('is_active', true)
                    ->orderByDesc('effective_from')
                    ->first();
                $grossSalary = $salaryStruct
                    ? (float)($salaryStruct->basic_salary + $salaryStruct->hra + $salaryStruct->da +
                       $salaryStruct->ta + $salaryStruct->medical_allowance + $salaryStruct->other_allowances)
                    : 0;

                // Count LOP (Loss of Pay) days: StaffAttendance absent + no approved leave
                $lopDays = \App\Models\StaffAttendance::where('employee_id', $emp->id)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $mon)
                    ->where('status', 'absent')
                    ->count();

                // Subtract approved leave days from LOP
                $leaveDays = \App\Models\LeaveRequest::where('employee_id', $emp->id)
                    ->where('status', 'approved')
                    ->where('leave_type_id', function ($q) {
                        $q->select('id')->from('leave_types')->where('is_paid', true)->limit(1);
                    })
                    ->whereMonth('from_date', $mon)->whereYear('from_date', $year)
                    ->count();
                $lopDays = max(0, $lopDays - $leaveDays);

                $dailyRate = $workingDays > 0 ? ($grossSalary / $workingDays) : 0;
                $lopAmount = round($dailyRate * $lopDays, 2);

                // Statutory deductions from salary structure
                $pfEmployee  = $salaryStruct ? (float) $salaryStruct->pf_employee  : 0;
                $esiEmployee = $salaryStruct ? (float) $salaryStruct->esi_employee : 0;
                $tdsMonthly  = $salaryStruct ? (float) $salaryStruct->tds          : 0;

                // Active loan EMI deduction
                $loanEmi = \App\Models\EmployeeLoan::where('employee_id', $emp->id)
                    ->where('status', 'active')
                    ->where('emi_start_month', '<=', $request->month . '-01')
                    ->sum('emi_amount');

                // Overtime earnings
                $overtimeAmount = DB::table('overtime_entries')
                    ->where('employee_id', $emp->id)
                    ->where('month', $request->month)
                    ->sum('amount');

                // Arrears / bonus / other additions
                $arrearsAmount = DB::table('arrears_bonus_entries')
                    ->where('employee_id', $emp->id)
                    ->where('month', $request->month)
                    ->where('type', 'arrears')
                    ->sum('amount');
                $bonusAmount = DB::table('arrears_bonus_entries')
                    ->where('employee_id', $emp->id)
                    ->where('month', $request->month)
                    ->whereIn('type', ['bonus', 'other_addition'])
                    ->sum('amount');

                $totalDeductions = round($lopAmount + $loanEmi + $pfEmployee + $esiEmployee + $tdsMonthly, 2);
                $netSalary = max(0, $grossSalary - $totalDeductions + $overtimeAmount + $arrearsAmount + $bonusAmount);

                PayrollRecord::updateOrCreate(
                    ['employee_id' => $emp->id, 'month' => $request->month],
                    [
                        'working_days'    => $workingDays,
                        'present_days'    => max(0, $workingDays - $lopDays),
                        'lop_days'        => $lopDays,
                        'lop_amount'      => $lopAmount,
                        'gross_salary'    => $grossSalary,
                        'deductions'      => $totalDeductions,
                        'overtime_amount' => $overtimeAmount,
                        'arrears_amount'  => $arrearsAmount,
                        'bonus_amount'    => $bonusAmount,
                        'net_salary'      => $netSalary,
                        'loan_emi'        => $loanEmi,
                        'status'          => 'draft',
                        'generated_by'    => auth()->id(),
                    ]
                );
            }
        });

        return redirect()->route('hr.payroll', ['month' => $request->month])
            ->with('success', 'Payroll generated for ' . $employees->count() . ' employees.');
    }

    public function approvePayroll(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $count = PayrollRecord::where('month', $request->month)
            ->update(['status' => 'approved', 'is_locked' => true, 'locked_at' => now(), 'approved_by' => auth()->id()]);
        return back()->with('success', "{$count} payroll record(s) approved and locked for {$request->month}.");
    }

    public function unlockPayroll(Request $request, int $id)
    {
        PayrollRecord::findOrFail($id)->update(['is_locked' => false, 'locked_at' => null]);
        return back()->with('success', 'Payroll record unlocked.');
    }

    public function leaves(Request $request)
    {
        return $this->leaveApprovals($request);
    }

    public function generateEmployeeNumber(): string
    {
        $prefix = 'EPSS';

        // Get max numeric suffix for codes starting with EPSS
        $existingCodes = Employee::withTrashed()
            ->where('employee_code', 'like', $prefix . '%')
            ->pluck('employee_code');

        $maxSeq = 0;
        foreach ($existingCodes as $code) {
            if (preg_match('/^EPSS(\d+)$/i', $code, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            }
        }

        $nextNum = $maxSeq + 1;
        do {
            $code = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            $nextNum++;
        } while (Employee::withTrashed()->where('employee_code', $code)->exists());

        return $code;
    }

    public function applyLeaveForm()
    {
        $employees  = Employee::where('is_active', true)->orderBy('first_name')->get();
        $leaveTypes = \App\Models\LeaveType::orderBy('name')->get();
        return view('hr.leave-apply', compact('employees', 'leaveTypes'));
    }

    /**
     * Web handler: Submit leave application on behalf of staff member
     */
    public function storeLeave(Request $request)
    {
        return $this->processLeaveApplicationOnBehalf($request, isApi: false);
    }

    /**
     * Permanent Backend API: Admin applies leave on behalf of staff member (for future Principal workflow)
     */
    public function apiApplyLeaveOnBehalf(Request $request)
    {
        return $this->processLeaveApplicationOnBehalf($request, isApi: true);
    }

    /**
     * Core handler for Leave Application on Behalf
     */
    protected function processLeaveApplicationOnBehalf(Request $request, bool $isApi = false)
    {
        $validated = $request->validate([
            'employee_id'      => 'required|exists:employees,id',
            'leave_type_id'    => 'required|exists:leave_types,id',
            'from_date'        => 'required|date',
            'to_date'          => 'required|date|after_or_equal:from_date',
            'reason'           => 'required|string|max:500',
            'is_half_day'      => 'nullable|boolean',
            'half_day_session' => 'nullable|in:morning,afternoon',
            'attachment'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        // Validation: Employee must be active
        if (!$employee->is_active && $employee->status !== 'active') {
            if ($isApi || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot apply leave for an inactive or unapproved staff member.',
                ], 422);
            }
            return back()->withInput()->withErrors(['employee_id' => 'Cannot apply leave for an inactive or unapproved staff member.']);
        }

        $isHalfDay = $request->boolean('is_half_day');
        $fromDate  = \Carbon\Carbon::parse($request->from_date)->toDateString();
        $toDate    = $isHalfDay ? $fromDate : \Carbon\Carbon::parse($request->to_date)->toDateString();

        // Duplicate / Overlap protection: Avoid duplicate identical requests or conflicting date ranges
        $hasOverlap = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('from_date', [$fromDate, $toDate])
                  ->orWhereBetween('to_date', [$fromDate, $toDate])
                  ->orWhere(function ($sub) use ($fromDate, $toDate) {
                      $sub->where('from_date', '<=', $fromDate)
                          ->where('to_date', '>=', $toDate);
                  });
            })
            ->exists();

        if ($hasOverlap) {
            if ($isApi || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A pending or approved leave request already exists for this staff member during the selected dates.',
                ], 422);
            }
            return back()->withInput()->withErrors(['from_date' => 'A pending or approved leave request already exists for this staff member during the selected dates.']);
        }

        // Calculate days accurately (0.5 for half day, otherwise inclusive calendar range)
        $totalDays = $isHalfDay ? 0.5 : (\Carbon\Carbon::parse($fromDate)->diffInDays(\Carbon\Carbon::parse($toDate)) + 1);

        \DB::beginTransaction();
        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('leave-attachments', 'public');
            }

            // Create leave request in PENDING state. Never auto-approved, never marks attendance as Leave.
            $leave = LeaveRequest::create([
                'employee_id'       => $employee->id,
                'leave_type_id'     => $request->leave_type_id,
                'from_date'         => $fromDate,
                'to_date'           => $toDate,
                'total_days'        => $totalDays,
                'reason'            => $request->reason,
                'status'            => 'pending', // Strictly Pending awaiting future Principal review
                'is_half_day'       => $isHalfDay,
                'half_day_session'  => $request->half_day_session,
                'attachment'        => $attachmentPath,
                'applied_by'        => auth()->id(),
                'applied_on_behalf' => true,
            ]);

            AuditLog::record('leave_applied_on_behalf', $leave, [], [
                'employee_id'       => $employee->id,
                'employee_name'     => $employee->full_name,
                'applied_by'        => auth()->id(),
                'total_days'        => $totalDays,
                'from_date'         => $fromDate,
                'to_date'           => $toDate,
                'applied_on_behalf' => true,
            ]);

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            if ($isApi || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to apply leave: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withInput()->withErrors(['general' => 'Failed to apply leave: ' . $e->getMessage()]);
        }

        if ($isApi || $request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Leave application submitted successfully on behalf of {$employee->full_name}.",
                'data'    => $leave->load(['employee', 'leaveType', 'appliedBy']),
            ], 201);
        }

        return redirect()->route('hr.leaves')->with('success', "Leave application submitted on behalf of {$employee->full_name}. Awaiting Principal approval.");
    }

    // NOTE: Leave Approval API (approveLeave, rejectLeave) is STRICTLY DEFERRED AND NOT CREATED NOW per design scope.
    // Leave approval workflow and role-based permissions will be implemented in a future Principal workflow phase.

    public function downloadPayslip(int $id)
    {
        $payroll  = PayrollRecord::with('employee')->findOrFail($id);
        $employee = $payroll->employee;
        [$year, $month] = explode('-', $payroll->month . '-01') + [null, null, null];
        $school   = (object)['name' => config('school.name', 'School Name'), 'address' => config('school.address', ''), 'logo' => null];
        $pdf = Pdf::loadView('pdf.payslip', compact('school', 'employee', 'payroll', 'month', 'year'));
        return $pdf->download('payslip-' . $employee->employee_code . '-' . $payroll->month . '.pdf');
    }

    public function emailPayslip(int $id)
    {
        $payroll  = PayrollRecord::with('employee')->findOrFail($id);
        $employee = $payroll->employee;
        $email    = $employee->official_email ?? $employee->personal_email ?? null;
        if (!$email) {
            return back()->with('error', 'No email address found for ' . $employee->full_name . '.');
        }
        [$year, $month] = explode('-', $payroll->month . '-01') + [null, null, null];
        $school   = (object)['name' => config('school.name', 'School Name'), 'address' => config('school.address', ''), 'logo' => null];
        $pdf      = Pdf::loadView('pdf.payslip', compact('school', 'employee', 'payroll', 'month', 'year'));
        $fileName = 'payslip-' . $employee->employee_code . '-' . $payroll->month . '.pdf';
        $pdfPath  = storage_path('app/temp/' . $fileName);
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        $pdf->save($pdfPath);
        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\PayslipMail($employee, $payroll, $pdfPath, $fileName));
            @unlink($pdfPath);
            return back()->with('success', "Payslip emailed to {$email}.");
        } catch (\Exception $e) {
            @unlink($pdfPath);
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function downloadPayslipProtected(int $id)
    {
        $payroll  = PayrollRecord::with('employee')->findOrFail($id);
        $employee = $payroll->employee;
        [$year, $month] = explode('-', $payroll->month . '-01') + [null, null, null];
        $school   = (object)['name' => config('school.name', 'School Name'), 'address' => config('school.address', ''), 'logo' => null];
        $pdf      = Pdf::loadView('pdf.payslip', compact('school', 'employee', 'payroll', 'month', 'year'));
        $fileName = 'payslip-' . $employee->employee_code . '-' . $payroll->month . '.pdf';
        $pdfPath  = storage_path('app/temp/' . $fileName);
        $zipName  = str_replace('.pdf', '.zip', $fileName);
        $zipPath  = storage_path('app/temp/' . $zipName);
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        $pdf->save($pdfPath);
        // Password = employee DOB in DDMMYYYY, fallback to last 4 of PAN or '1234'
        $dob      = $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('dmY') : null;
        $pan      = $employee->pan_number ? substr($employee->pan_number, -4) : null;
        $password = $dob ?? $pan ?? '1234';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->setPassword($password);
        $zip->addFile($pdfPath, $fileName);
        if (defined('ZipArchive::EM_AES_256')) {
            $zip->setEncryptionName($fileName, \ZipArchive::EM_AES_256);
        }
        $zip->close();
        @unlink($pdfPath);
        return response()->download($zipPath, $zipName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function bankTransfer(Request $request)
    {
        $month  = $request->month ?? now()->month;
        $year   = $request->year ?? now()->year;
        $format = $request->format ?? 'neft';

        $query = PayrollRecord::with('employee')
            ->where('month', "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT))
            ->where('status', 'approved');

        if ($request->bank) {
            $query->whereHas('employee', fn($q) => $q->where('bank_name', $request->bank));
        }

        $records = $query->get();
        $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT))->format('M_Y');

        return match($format) {
            'sbi'   => $this->generateSbiFormat($records, $monthLabel),
            'hdfc'  => $this->generateHdfcFormat($records, $monthLabel),
            'icici' => $this->generateIciciFormat($records, $monthLabel),
            'axis'  => $this->generateAxisFormat($records, $monthLabel),
            default => $this->generateNeftCsv($records, $monthLabel),
        };
    }

    private function generateNeftCsv($records, string $monthLabel)
    {
        $rows = ["Account_No,Name,IFSC,Amount,Remarks"];
        foreach ($records as $p) {
            $emp = $p->employee;
            $rows[] = implode(',', [
                '"' . ($emp?->bank_account_no ?? '') . '"',
                '"' . ($emp?->first_name . ' ' . $emp?->last_name) . '"',
                '"' . ($emp?->bank_ifsc ?? '') . '"',
                number_format($p->net_salary, 2, '.', ''),
                '"Salary ' . $monthLabel . '"',
            ]);
        }
        return response(implode("\n", $rows))
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="neft-transfer-' . $monthLabel . '.csv"');
    }

    private function generateSbiFormat($records, string $monthLabel)
    {
        // SBI bulk upload format: TXN_TYPE|BENE_ACNO|BENE_NAME|BENE_IFSC|AMT|REM_INFO
        $rows = ["TXN_TYPE|BENE_ACNO|BENE_NAME|BENE_IFSC|AMT|REM_INFO"];
        foreach ($records as $p) {
            $emp = $p->employee;
            $rows[] = implode('|', [
                'NEFT',
                $emp?->bank_account_no ?? '',
                $emp?->first_name . ' ' . $emp?->last_name,
                $emp?->bank_ifsc ?? '',
                number_format($p->net_salary, 2, '.', ''),
                'SALARY ' . $monthLabel,
            ]);
        }
        return response(implode("\n", $rows))
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="sbi-transfer-' . $monthLabel . '.txt"');
    }

    private function generateHdfcFormat($records, string $monthLabel)
    {
        // HDFC NetBanking bulk salary upload CSV
        $rows = ["SR No,Beneficiary Account No,Beneficiary Name,IFSC Code,Amount,Payment Type,Remarks"];
        $i = 1;
        foreach ($records as $p) {
            $emp = $p->employee;
            $rows[] = implode(',', [
                $i++,
                '"' . ($emp?->bank_account_no ?? '') . '"',
                '"' . ($emp?->first_name . ' ' . $emp?->last_name) . '"',
                '"' . ($emp?->bank_ifsc ?? '') . '"',
                number_format($p->net_salary, 2, '.', ''),
                'NEFT',
                '"Salary ' . $monthLabel . '"',
            ]);
        }
        return response(implode("\n", $rows))
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="hdfc-transfer-' . $monthLabel . '.csv"');
    }

    private function generateIciciFormat($records, string $monthLabel)
    {
        // ICICI Corporate Internet Banking bulk payment
        $rows = ["RecordType,Payee Name,Account No,IFSC,Amount,Payment Mode,Narration"];
        foreach ($records as $p) {
            $emp = $p->employee;
            $rows[] = implode(',', [
                'SALARY',
                '"' . ($emp?->first_name . ' ' . $emp?->last_name) . '"',
                '"' . ($emp?->bank_account_no ?? '') . '"',
                '"' . ($emp?->bank_ifsc ?? '') . '"',
                number_format($p->net_salary, 2, '.', ''),
                'NEFT',
                '"SALARY ' . $monthLabel . '"',
            ]);
        }
        return response(implode("\n", $rows))
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="icici-transfer-' . $monthLabel . '.csv"');
    }

    private function generateAxisFormat($records, string $monthLabel)
    {
        // Axis Bank bulk payment format
        $rows = ["Debit Acct No,Credit Acct No,Beneficiary Name,IFSC Code,Amount,Payment Type,Remarks"];
        $schoolAccount = \App\Models\SchoolSetting::get('bank_account_number', 'XXXXXXXXXX');
        foreach ($records as $p) {
            $emp = $p->employee;
            $rows[] = implode(',', [
                '"' . $schoolAccount . '"',
                '"' . ($emp?->bank_account_no ?? '') . '"',
                '"' . ($emp?->first_name . ' ' . $emp?->last_name) . '"',
                '"' . ($emp?->bank_ifsc ?? '') . '"',
                number_format($p->net_salary, 2, '.', ''),
                'NEFT',
                '"SALARY ' . $monthLabel . '"',
            ]);
        }
        return response(implode("\n", $rows))
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="axis-transfer-' . $monthLabel . '.csv"');
    }

    public function bankTransferView(Request $request)
    {
        $month  = $request->month ?? now()->month;
        $year   = $request->year ?? now()->year;
        $payrollSummary = PayrollRecord::with('employee')
            ->where('month', "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT))->get();
        $banks = $payrollSummary->map(fn($p) => $p->employee?->bank_name)->unique()->filter()->values();
        $payrollMonth = $month;
        $payrollYear  = $year;
        return view('hr.bank-transfer', compact('payrollSummary', 'banks', 'payrollMonth', 'payrollYear'));
    }

    public function salaryRegister(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year  = $request->year ?? now()->year;
        return Excel::download(new SalaryRegisterExport($month, $year), 'salary-register.xlsx');
    }

    public function challanGeneration(Request $request)
    {
        $month  = $request->month ?? now()->format('Y-m');
        $type   = $request->type ?? 'pf'; // pf | esi | pt

        $records = PayrollRecord::with('employee')
            ->where('month', $month)
            ->where('status', '!=', 'draft')
            ->get();

        $school = \App\Models\SchoolSetting::first();

        // PT slabs (Tamil Nadu)
        $ptSlabs = [
            ['min' => 0,     'max' => 3500,  'pt' => 0],
            ['min' => 3501,  'max' => 5000,  'pt' => 45],
            ['min' => 5001,  'max' => 7500,  'pt' => 90],
            ['min' => 7501,  'max' => 10000, 'pt' => 135],
            ['min' => 10001, 'max' => 12500, 'pt' => 180],
            ['min' => 12501, 'max' => PHP_INT_MAX, 'pt' => 208],
        ];
        $getPT = function(float $gross) use ($ptSlabs) {
            foreach ($ptSlabs as $slab) {
                if ($gross >= $slab['min'] && $gross <= $slab['max']) return $slab['pt'];
            }
            return 208;
        };

        $challanData = $records->map(function ($r) use ($getPT) {
            $pfEmployee = $r->pf_deduction ?? 0;
            $pfEmployer = round(($r->basic_salary ?? 0) * 0.12, 2);
            $esiEmployee = $r->esi_deduction ?? 0;
            $esiEmployer = round(($r->gross_salary ?? 0) * 0.0325, 2);
            $pt = $getPT($r->gross_salary ?? 0);
            return [
                'record'        => $r,
                'employee'      => $r->employee,
                'pf_employee'   => $pfEmployee,
                'pf_employer'   => $pfEmployer,
                'pf_total'      => round($pfEmployee + $pfEmployer, 2),
                'esi_employee'  => $esiEmployee,
                'esi_employer'  => $esiEmployer,
                'esi_total'     => round($esiEmployee + $esiEmployer, 2),
                'pt'            => $pt,
            ];
        });

        $totals = [
            'pf_employee'  => $challanData->sum('pf_employee'),
            'pf_employer'  => $challanData->sum('pf_employer'),
            'pf_total'     => $challanData->sum('pf_total'),
            'esi_employee' => $challanData->sum('esi_employee'),
            'esi_employer' => $challanData->sum('esi_employer'),
            'esi_total'    => $challanData->sum('esi_total'),
            'pt_total'     => $challanData->sum('pt'),
        ];

        if ($request->format === 'pdf') {
            $pdf = Pdf::loadView('pdf.statutory-challan', compact('challanData', 'totals', 'type', 'month', 'school'));
            return $pdf->download("challan-{$type}-{$month}.pdf");
        }

        return view('hr.challan', compact('challanData', 'totals', 'type', 'month', 'school'));
    }

    public function statutoryReport(Request $request)
    {
        $financialYear = $request->financial_year ?? date('Y') . '-' . (date('Y') + 1);
        [$fyStart, $fyEnd] = $this->parseFY($financialYear);

        $employees = Employee::where('is_active', true)
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->orderBy('first_name')->get();
        $departments = Department::where('is_active', true)->get();

        $ptSlabs = [
            ['min' => 0,    'max' => 3500,  'pt' => 0],
            ['min' => 3501, 'max' => 5000,  'pt' => 45],
            ['min' => 5001, 'max' => 7500,  'pt' => 90],
            ['min' => 7501, 'max' => 10000, 'pt' => 135],
            ['min' => 10001,'max' => 12500, 'pt' => 180],
            ['min' => 12501,'max' => PHP_INT_MAX,'pt' => 208],
        ];
        $getPT = function(float $gross) use ($ptSlabs) {
            foreach ($ptSlabs as $s) { if ($gross >= $s['min'] && $gross <= $s['max']) return $s['pt']; }
            return 208;
        };

        $report = collect();
        if ($request->filled('action') || $request->filled('financial_year')) {
            $payrolls = PayrollRecord::with('employee')
                ->whereBetween('month', [$fyStart, $fyEnd])
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get()->groupBy('employee_id');

            foreach ($employees as $emp) {
                $empPayrolls = $payrolls->get($emp->id, collect());
                $annualGross = $empPayrolls->sum('gross_salary');
                $annualPfEmp = $empPayrolls->sum('pf_deduction');
                $annualPfEr  = round($annualPfEmp * 1.1, 2); // employer typically ~1.1x
                $annualEsiEmp= $empPayrolls->sum('esi_deduction');
                $annualEsiEr = round($annualEsiEmp * (3.25/1.75), 2);
                $annualPT    = $empPayrolls->reduce(fn($carry, $p) => $carry + $getPT($p->gross_salary ?? 0), 0);

                $tds = $this->computeAnnualTax($emp, $annualGross);

                $report->push([
                    'employee'    => $emp,
                    'annual_gross'=> $annualGross,
                    'pf_employee' => $annualPfEmp,
                    'pf_employer' => $annualPfEr,
                    'esi_employee'=> $annualEsiEmp,
                    'esi_employer'=> $annualEsiEr,
                    'pt_annual'   => $annualPT,
                    'tds_annual'  => $tds['annual_tax'],
                    'months'      => $empPayrolls->count(),
                ]);
            }
        }

        if ($request->format === 'excel') {
            $headers = ['Employee', 'PAN', 'PF No', 'ESI No', 'Gross (Annual)', 'PF (Employee)', 'PF (Employer)', 'ESI (Employee)', 'ESI (Employer)', 'PT (Annual)', 'TDS (Annual)'];
            $rows = $report->map(fn($r) => [
                $r['employee']->first_name . ' ' . $r['employee']->last_name,
                $r['employee']->pan_no ?? '',
                $r['employee']->pf_account_no ?? '',
                $r['employee']->esi_no ?? '',
                $r['annual_gross'], $r['pf_employee'], $r['pf_employer'],
                $r['esi_employee'], $r['esi_employer'], $r['pt_annual'], $r['tds_annual'],
            ])->prepend($headers);

            return Excel::download(new \App\Exports\CollectionExport($rows, 'Statutory Report'), 'statutory-report-' . $financialYear . '.xlsx');
        }

        return view('hr.statutory-report', compact('departments', 'report', 'financialYear'));
    }

    public function departments()
    {
        $departments  = Department::withCount(['designations', 'employees'])->orderBy('name')->get();
        $designations = Designation::with('department')->orderBy('name')->get();
        return view('hr.departments', compact('departments', 'designations'));
    }

    public function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        Department::create($request->only(['name', 'code', 'description']) + ['is_active' => true]);
        return back()->with('success', 'Department added.');
    }

    public function updateDepartment(Request $request, int $id)
    {
        Department::findOrFail($id)->update($request->only(['name', 'code', 'description', 'is_active']));
        return back()->with('success', 'Department updated.');
    }

    public function storeDesignation(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100', 'department_id' => 'required|exists:departments,id']);
        $designation = Designation::create(
            $request->only(['name', 'department_id', 'grade', 'pay_band_min', 'pay_band_max', 'pay_scale', 'description', 'spatie_role'])
            + ['is_active' => true]
        );
        // Ensure the Spatie role exists if specified
        if ($designation->spatie_role) {
            Role::findOrCreate($designation->spatie_role, 'web');
        }
        return back()->with('success', 'Designation added.');
    }

    public function updateDesignation(Request $request, int $id)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $designation = Designation::findOrFail($id);
        $oldRole = $designation->spatie_role;
        $designation->update(array_merge(
            $request->only(['name', 'department_id', 'grade', 'pay_band_min', 'pay_band_max', 'pay_scale', 'description', 'spatie_role']),
            ['is_active' => $request->boolean('is_active')]
        ));
        $newRole = $designation->fresh()->spatie_role;
        // Ensure new role exists
        if ($newRole) {
            Role::findOrCreate($newRole, 'web');
        }
        // Re-sync all employees with this designation if role changed
        if ($oldRole !== $newRole) {
            $this->syncDesignationRoles($designation->id, $oldRole, $newRole);
        }
        return back()->with('success', 'Designation updated.');
    }

    protected function syncDesignationRoles(int $designationId, ?string $oldRole, ?string $newRole): void
    {
        $employees = Employee::where('designation_id', $designationId)
            ->whereNotNull('user_id')->with('user')->get();
        foreach ($employees as $emp) {
            if (!$emp->user) continue;
            if ($oldRole) $emp->user->removeRole($oldRole);
            if ($newRole) $emp->user->assignRole($newRole);
        }
    }

    public function appointmentLetter(int $id)
    {
        $employee  = Employee::findOrFail($id);
        $school    = \App\Models\SchoolSetting::first() ?? (object)[
            'school_name' => config('app.name', 'DASA EduERP'),
            'address'     => 'School Main Campus, Knowledge Park',
            'phone'       => '+91 9876543210',
            'email'       => 'admin@dasaeduerp.com',
            'logo'        => null,
        ];
        $refNumber = 'APT-' . date('Y') . '-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT);
        $pdf       = Pdf::loadView('pdf.appointment-letter', compact('school', 'employee', 'refNumber'));
        return $pdf->download('appointment-letter-' . ($employee->employee_code ?? $employee->id) . '.pdf');
    }

    public function experienceCertificate(int $id)
    {
        $employee = Employee::findOrFail($id);
        $school   = \App\Models\SchoolSetting::first() ?? (object)[
            'school_name' => config('app.name', 'DASA EduERP'),
            'address'     => 'School Main Campus, Knowledge Park',
            'phone'       => '+91 9876543210',
            'email'       => 'admin@dasaeduerp.com',
            'logo'        => null,
        ];
        $pdf      = Pdf::loadView('pdf.experience-certificate', compact('school', 'employee'));
        return $pdf->download('experience-certificate-' . ($employee->employee_code ?? $employee->id) . '.pdf');
    }

    public function leaveBalance(Request $request)
    {
        $leaveTypes = \App\Models\LeaveType::where('is_active', true)->get();
        $year       = $request->year ?? now()->year;
        $employees  = Employee::where('is_active', true)
            ->when($request->department, fn($q, $v) => $q->where('department', $v))
            ->orderBy('first_name')->get();
        $usedMap = \App\Models\LeaveRequest::whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'approved')
            ->whereYear('from_date', $year)
            ->selectRaw('employee_id, leave_type_id, SUM(days) as total_days')
            ->groupBy('employee_id', 'leave_type_id')
            ->get()->groupBy('employee_id')->map(fn($g) => $g->keyBy('leave_type_id'));
        $departments = Employee::select('department')->distinct()->pluck('department')->filter();
        return view('hr.leave-balance', compact('employees', 'leaveTypes', 'usedMap', 'year', 'departments'));
    }

    public function cancelLeave(int $id)
    {
        $leave = \App\Models\LeaveRequest::findOrFail($id);
        if (in_array($leave->status, ['pending', 'approved'])) {
            $leave->update(['status' => 'cancelled', 'approved_by' => auth()->id()]);
        }
        return back()->with('success', 'Leave cancelled.');
    }

    public function carryForwardLeaves(Request $request)
    {
        $request->validate(['from_year' => 'required|integer', 'to_year' => 'required|integer|gt:from_year']);
        $leaveTypes = \App\Models\LeaveType::where('is_active', true)->get();
        $employees  = Employee::where('is_active', true)->get();
        $carried    = 0;
        foreach ($employees as $emp) {
            foreach ($leaveTypes as $lt) {
                $used = \App\Models\LeaveRequest::where('employee_id', $emp->id)
                    ->where('leave_type_id', $lt->id)->where('status', 'approved')
                    ->whereYear('from_date', $request->from_year)->sum('days');
                $remaining = max(0, $lt->days_allowed - $used);
                if ($remaining > 0) {
                    \App\Models\LeaveRequest::create([
                        'employee_id'   => $emp->id,
                        'leave_type_id' => $lt->id,
                        'from_date'     => $request->to_year . '-01-01',
                        'to_date'       => $request->to_year . '-01-01',
                        'days'          => $remaining,
                        'reason'        => 'Carry-forward from ' . $request->from_year,
                        'status'        => 'approved',
                        'approved_by'   => auth()->id(),
                    ]);
                    $carried++;
                }
            }
        }
        return back()->with('success', "Carry-forward done. $carried entries created.");
    }

    public function bulkPayslipPdf(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $records = PayrollRecord::with('employee')->where('month', $request->month)->get();
        if ($records->isEmpty()) return back()->with('error', 'No payroll records found for this month.');
        [$year, $mo] = explode('-', $request->month);
        $month = $mo;
        $school = (object)['name' => config('school.name', 'School Name'), 'address' => config('school.address', ''), 'logo' => null];
        $pdf = Pdf::loadView('pdf.bulk-payslips', compact('records', 'school', 'month', 'year'));
        return $pdf->download('payslips-' . $request->month . '.pdf');
    }

    public function relievingLetter(int $id)
    {
        $employee  = Employee::findOrFail($id);
        $school    = \App\Models\SchoolSetting::first() ?? (object)['school_name' => config('school.name', 'School Name'), 'address' => config('school.address', '')];
        $refNumber = 'REL-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
        $pdf = Pdf::loadView('pdf.relieving-letter', compact('school', 'employee', 'refNumber'));
        return $pdf->download('relieving-letter-' . $employee->employee_code . '.pdf');
    }

    public function nocLetter(int $id)
    {
        $employee  = Employee::findOrFail($id);
        $school    = \App\Models\SchoolSetting::first() ?? (object)['school_name' => config('school.name', 'School Name'), 'address' => config('school.address', '')];
        $refNumber = 'NOC-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
        $pdf = Pdf::loadView('pdf.noc-letter', compact('school', 'employee', 'refNumber'));
        return $pdf->download('noc-' . $employee->employee_code . '.pdf');
    }

    public function incrementLetter(int $id)
    {
        $employee  = Employee::findOrFail($id);
        $school    = \App\Models\SchoolSetting::first() ?? (object)['school_name' => config('school.name', 'School Name'), 'address' => config('school.address', '')];
        $refNumber = 'INC-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
        $newSalary = ($employee->basic_salary ?? 0) * 1.05;
        $pdf = Pdf::loadView('pdf.increment-letter', compact('school', 'employee', 'refNumber', 'newSalary'));
        return $pdf->download('increment-letter-' . $employee->employee_code . '.pdf');
    }

    public function holdSalary(int $id)
    {
        Employee::findOrFail($id)->update(['salary_on_hold' => true]);
        return back()->with('success', 'Salary held for this employee.');
    }

    public function releaseSalary(int $id)
    {
        Employee::findOrFail($id)->update(['salary_on_hold' => false]);
        return back()->with('success', 'Salary released.');
    }

    public function leaveCalendar(Request $request)
    {
        $departments = Department::where('is_active', true)->get();
        $month       = $request->month ?? now()->format('Y-m');
        [$yr, $mo]   = explode('-', $month);
        $period      = CarbonPeriod::create("$yr-$mo-01", "last day of $yr-$mo");
        $dates        = collect($period)->map(fn($d) => $d);
        $employees    = Employee::with('department')->where('is_active', true)
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->orderBy('first_name')->get();
        $leaveMap = [];
        $leaves   = \App\Models\LeaveRequest::where('status', 'approved')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where(fn($q) => $q->whereMonth('from_date', $mo)->orWhereMonth('to_date', $mo))
            ->get();
        foreach ($leaves as $l) {
            $lp = CarbonPeriod::create($l->from_date, $l->to_date);
            foreach ($lp as $d) {
                if ($d->month == $mo) {
                    $leaveMap[$l->employee_id][$d->toDateString()] = 'approved';
                }
            }
        }
        return view('hr.leave-calendar', compact('departments', 'employees', 'dates', 'leaveMap', 'month'));
    }

    public function departmentSalary(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');
        [$yr, $mo] = explode('-', $month);
        $departments = Department::where('is_active', true)->get();
        $data = [];
        foreach ($departments as $dept) {
            $records = PayrollRecord::where('month', $month)
                ->whereHas('employee', fn($q) => $q->where('department_id', $dept->id))
                ->with('employee')
                ->get();
            $data[] = [
                'department'  => $dept,
                'count'       => $records->count(),
                'gross'       => $records->sum('gross_salary'),
                'deductions'  => $records->sum('total_deductions'),
                'net'         => $records->sum('net_salary'),
            ];
        }
        $grandTotal = [
            'count'      => array_sum(array_column($data, 'count')),
            'gross'      => array_sum(array_column($data, 'gross')),
            'deductions' => array_sum(array_column($data, 'deductions')),
            'net'        => array_sum(array_column($data, 'net')),
        ];
        return view('hr.department-salary', compact('data', 'grandTotal', 'month'));
    }

    public function leaveHistory(int $id)
    {
        $employee = Employee::findOrFail($id);
        $leaves   = \App\Models\LeaveRequest::where('employee_id', $id)
            ->with('leaveType')
            ->latest()
            ->paginate(20);
        return view('hr.leave-history', compact('employee', 'leaves'));
    }

    public function leaveEncashment(Request $request)
    {
        $employees  = Employee::where('is_active', true)->orderBy('first_name')->get();
        $elTypes    = \App\Models\LeaveType::where('is_active', true)->where('code', 'EL')->get();
        if ($elTypes->isEmpty()) {
            $elTypes = \App\Models\LeaveType::where('is_active', true)->get();
        }
        $history = \App\Models\LeaveEncashment::with(['employee', 'leaveType'])
            ->latest()->paginate(20);

        $calculation = null;
        if ($request->employee_id && $request->leave_type_id && $request->days) {
            $emp      = Employee::findOrFail($request->employee_id);
            $lt       = \App\Models\LeaveType::findOrFail($request->leave_type_id);
            $year     = $request->year ?? date('Y');
            $used     = \App\Models\LeaveRequest::where('employee_id', $emp->id)
                ->where('leave_type_id', $lt->id)->where('status', 'approved')
                ->whereYear('from_date', $year)->sum('total_days');
            $balance  = max(0, ($lt->days_allowed ?? 0) - $used);
            $days     = min((int)$request->days, $balance);
            $basicPerDay = ($emp->basic_salary ?? 0) / 26;
            $amount   = round($basicPerDay * $days, 2);
            $calculation = compact('emp', 'lt', 'year', 'balance', 'days', 'basicPerDay', 'amount');
        }

        return view('hr.leave-encashment', compact('employees', 'elTypes', 'history', 'calculation'));
    }

    public function processLeaveEncashment(Request $request)
    {
        $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'days_encashed' => 'required|integer|min:1|max:365',
            'year'          => 'required|digits:4',
            'encashment_date' => 'required|date',
            'remarks'       => 'nullable|string|max:500',
        ]);

        $emp    = Employee::findOrFail($request->employee_id);
        $basicPerDay = ($emp->basic_salary ?? 0) / 26;
        $amount = round($basicPerDay * $request->days_encashed, 2);

        \App\Models\LeaveEncashment::create([
            'employee_id'    => $request->employee_id,
            'leave_type_id'  => $request->leave_type_id,
            'days_encashed'  => $request->days_encashed,
            'basic_per_day'  => $basicPerDay,
            'amount'         => $amount,
            'year'           => $request->year,
            'remarks'        => $request->remarks,
            'processed_by'   => auth()->id(),
            'encashment_date'=> $request->encashment_date,
        ]);

        return back()->with('success', "Leave encashment of ₹" . number_format($amount, 2) . " processed for {$emp->full_name}.");
    }

    // ── Advance Salary ─────────────────────────────────────

    public function salaryAdvances(Request $request)
    {
        $employees = Employee::active()->orderBy('first_name')->get();
        $advances  = \App\Models\SalaryAdvance::with('employee', 'approvedBy')
            ->when($request->employee_id, fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(25);
        return view('hr.salary-advances', compact('employees', 'advances'));
    }

    public function storeSalaryAdvance(Request $request)
    {
        $validated = $request->validate([
            'employee_id'      => 'required|exists:employees,id',
            'amount'           => 'required|numeric|min:1',
            'advance_date'     => 'required|date',
            'reason'           => 'nullable|string|max:500',
            'recovery_months'  => 'required|integer|min:1|max:24',
            'remarks'          => 'nullable|string|max:500',
        ]);
        $validated['monthly_deduction'] = round($validated['amount'] / $validated['recovery_months'], 2);
        \App\Models\SalaryAdvance::create($validated);
        return back()->with('success', 'Salary advance recorded.');
    }

    public function approveSalaryAdvance(int $id)
    {
        $advance = \App\Models\SalaryAdvance::findOrFail($id);
        $advance->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_date' => today()]);
        return back()->with('success', 'Advance approved.');
    }

    public function rejectSalaryAdvance(int $id)
    {
        \App\Models\SalaryAdvance::findOrFail($id)->update(['status' => 'rejected']);
        return back()->with('success', 'Advance rejected.');
    }

    public function recordAdvanceRecovery(Request $request, int $id)
    {
        $request->validate(['recovery_amount' => 'required|numeric|min:0.01']);
        $advance = \App\Models\SalaryAdvance::findOrFail($id);
        $newRecovered = $advance->recovered_amount + $request->recovery_amount;
        $status = $newRecovered >= $advance->amount ? 'recovered' : 'approved';
        $advance->update(['recovered_amount' => $newRecovered, 'status' => $status]);
        return back()->with('success', '₹' . number_format($request->recovery_amount, 2) . ' recovery recorded.');
    }

    public function appraisalForm(Request $request)
    {
        $employees = Employee::where('is_active', true)->orderBy('first_name')->get();

        $kpis = \App\Models\SchoolSetting::get('appraisal_kpis',
            'Punctuality,Subject Knowledge,Student Feedback,Lesson Plan Adherence,Teamwork,Communication,Professional Development');
        $kpiList = array_map('trim', explode(',', $kpis));

        $appraisals = collect();
        if ($request->year) {
            $appraisals = \App\Models\EmployeeAppraisal::with('employee')
                ->where('appraisal_year', $request->year)
                ->get()->keyBy('employee_id');
        }

        return view('hr.appraisal', compact('employees', 'kpiList', 'appraisals'));
    }

    public function saveAppraisal(Request $request)
    {
        $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'appraisal_year' => 'required|integer|min:2000|max:2100',
            'ratings'        => 'required|array',
        ]);

        $overallScore = collect($request->ratings)->avg();
        $rating = match(true) {
            $overallScore >= 4.5 => 'Outstanding',
            $overallScore >= 3.5 => 'Very Good',
            $overallScore >= 2.5 => 'Good',
            $overallScore >= 1.5 => 'Average',
            default              => 'Below Average',
        };

        \App\Models\EmployeeAppraisal::updateOrCreate(
            ['employee_id' => $request->employee_id, 'appraisal_year' => $request->appraisal_year],
            [
                'ratings'           => json_encode($request->ratings),
                'overall_score'     => round($overallScore, 2),
                'rating_label'      => $rating,
                'hod_remarks'       => $request->hod_remarks,
                'principal_remarks' => $request->principal_remarks,
                'appraised_by'      => auth()->id(),
                'appraised_at'      => now(),
            ]
        );
        return back()->with('success', 'Appraisal saved. Rating: ' . $rating);
    }

    public function selfAppraisalForm(Request $request)
    {
        $employees = Employee::where('is_active', true)->orderBy('first_name')->get();
        $kpis      = \App\Models\SchoolSetting::get('appraisal_kpis',
            'Punctuality,Subject Knowledge,Student Feedback,Lesson Plan Adherence,Teamwork,Communication,Professional Development');
        $kpiList   = array_map('trim', explode(',', $kpis));

        $appraisal = null;
        if ($request->employee_id && $request->year) {
            $appraisal = \App\Models\EmployeeAppraisal::where('employee_id', $request->employee_id)
                ->where('appraisal_year', $request->year)->first();
        }
        return view('hr.self-appraisal', compact('employees', 'kpiList', 'appraisal'));
    }

    public function saveSelfAppraisal(Request $request)
    {
        $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'appraisal_year' => 'required|integer|min:2000|max:2100',
            'self_ratings'   => 'required|array',
            'self_remarks'   => 'nullable|string|max:1000',
        ]);
        $selfScore = round(collect($request->self_ratings)->avg(), 2);

        \App\Models\EmployeeAppraisal::updateOrCreate(
            ['employee_id' => $request->employee_id, 'appraisal_year' => $request->appraisal_year],
            [
                'self_ratings'      => $request->self_ratings,
                'self_score'        => $selfScore,
                'self_remarks'      => $request->self_remarks,
                'self_submitted_at' => now(),
            ]
        );
        return back()->with('success', 'Self-appraisal saved. Score: ' . $selfScore . '/5');
    }

    public function processIncrement(Request $request, int $appraisalId)
    {
        $request->validate([
            'increment_type'           => 'required|in:percent,amount',
            'increment_value'          => 'required|numeric|min:0',
            'increment_effective_date' => 'required|date',
        ]);

        $appraisal = \App\Models\EmployeeAppraisal::with('employee')->findOrFail($appraisalId);
        $employee  = $appraisal->employee;
        $current   = $employee->gross_salary ?? 0;

        if ($request->increment_type === 'percent') {
            $amount  = round($current * $request->increment_value / 100, 2);
            $percent = $request->increment_value;
        } else {
            $amount  = $request->increment_value;
            $percent = $current > 0 ? round($amount / $current * 100, 2) : 0;
        }

        $appraisal->update([
            'increment_amount'         => $amount,
            'increment_percent'        => $percent,
            'increment_effective_date' => $request->increment_effective_date,
            'increment_approved_by'    => Auth::id(),
            'increment_approved_at'    => now(),
        ]);

        // Apply increment to employee salary
        $newGross = $current + $amount;
        $employee->update(['gross_salary' => $newGross]);

        return back()->with('success', "Increment of ₹{$amount} ({$percent}%) applied. New gross: ₹{$newGross}.");
    }

    // ── Loan Management ──────────────────────────────────────

    public function loans(Request $request)
    {
        $employees = Employee::where('is_active', true)->orderBy('first_name')->get();
        $query = \App\Models\EmployeeLoan::with('employee.department');
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $loans = $query->latest()->paginate(20);
        return view('hr.loans', compact('loans', 'employees'));
    }

    public function storeLoan(Request $request)
    {
        $request->validate([
            'employee_id'       => 'required|exists:employees,id',
            'loan_type'         => 'required|string',
            'principal_amount'  => 'required|numeric|min:1',
            'interest_rate'     => 'nullable|numeric|min:0|max:100',
            'emi_months'        => 'required|integer|min:1',
            'disbursement_date' => 'required|date',
            'emi_start_month'   => 'required|date',
            'purpose'           => 'nullable|string',
        ]);

        $principal = $request->principal_amount;
        $rate      = $request->interest_rate ?? 0;
        $months    = $request->emi_months;

        if ($rate > 0) {
            $monthlyRate = $rate / 100 / 12;
            $emi = round($principal * $monthlyRate * pow(1 + $monthlyRate, $months) / (pow(1 + $monthlyRate, $months) - 1), 2);
        } else {
            $emi = round($principal / $months, 2);
        }

        \App\Models\EmployeeLoan::create([
            'employee_id'         => $request->employee_id,
            'loan_type'           => $request->loan_type,
            'principal_amount'    => $principal,
            'interest_rate'       => $rate,
            'emi_months'          => $months,
            'emi_amount'          => $emi,
            'total_paid'          => 0,
            'outstanding_balance' => $principal,
            'disbursement_date'   => $request->disbursement_date,
            'emi_start_month'     => $request->emi_start_month,
            'status'              => 'active',
            'purpose'             => $request->purpose,
            'approved_by'         => auth()->id(),
            'approved_at'         => now(),
        ]);
        return back()->with('success', 'Loan created. EMI: ₹' . number_format($emi, 2) . '/month.');
    }

    public function closeLoan(int $id)
    {
        \App\Models\EmployeeLoan::findOrFail($id)->update(['status' => 'closed', 'outstanding_balance' => 0]);
        return back()->with('success', 'Loan marked as closed.');
    }

    public function recordLoanPayment(Request $request, int $id)
    {
        $loan = \App\Models\EmployeeLoan::findOrFail($id);
        $amount = min($request->amount, $loan->outstanding_balance);
        $loan->update([
            'total_paid'          => $loan->total_paid + $amount,
            'outstanding_balance' => max(0, $loan->outstanding_balance - $amount),
            'status'              => ($loan->outstanding_balance - $amount) <= 0 ? 'closed' : $loan->status,
        ]);
        return back()->with('success', 'Payment of ₹' . number_format($amount, 2) . ' recorded.');
    }

    // ── Staff Attendance vs Leave Reconciliation ─────────────

    public function attendanceLeaveReconciliation(Request $request)
    {
        $month = $request->month ?? date('Y-m');
        [$year, $mon] = explode('-', $month);

        $employees = Employee::where('is_active', true)
            ->with('department')
            ->orderBy('first_name')->get();

        $reconciliation = $employees->map(function ($emp) use ($year, $mon) {
            // Total working days attendance records
            $totalDays = \App\Models\StaffAttendance::where('employee_id', $emp->id)
                ->whereYear('date', $year)->whereMonth('date', $mon)->count();

            // Present/half-day count
            $presentDays = \App\Models\StaffAttendance::where('employee_id', $emp->id)
                ->whereYear('date', $year)->whereMonth('date', $mon)
                ->whereIn('status', ['present', 'half_day'])->count();

            // Absent count
            $absentDays = \App\Models\StaffAttendance::where('employee_id', $emp->id)
                ->whereYear('date', $year)->whereMonth('date', $mon)
                ->where('status', 'absent')->count();

            // Approved leave days
            $approvedLeaves = \App\Models\LeaveRequest::where('employee_id', $emp->id)
                ->where('status', 'approved')
                ->whereMonth('from_date', $mon)->whereYear('from_date', $year)
                ->sum('total_days');

            // LOP = absences not covered by approved leave
            $lopDays = max(0, $absentDays - $approvedLeaves);

            return [
                'employee'       => $emp,
                'total_days'     => $totalDays,
                'present_days'   => $presentDays,
                'absent_days'    => $absentDays,
                'approved_leave' => $approvedLeaves,
                'lop_days'       => $lopDays,
            ];
        });

        return view('hr.attendance-leave-reconciliation', compact('reconciliation', 'month'));
    }

    public function tdsComputation(Request $request)
    {
        $employees = Employee::where('is_active', true)
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->orderBy('first_name')->get();

        $financialYear = $request->financial_year ?? date('Y') . '-' . (date('Y') + 1);
        [$fyStart, $fyEnd] = $this->parseFY($financialYear);
        $departments = Department::where('is_active', true)->get();

        $tdsReport = collect();
        if ($request->filled('action') || $request->filled('financial_year')) {
            foreach ($employees as $emp) {
                $annualGross = PayrollRecord::where('employee_id', $emp->id)
                    ->whereBetween('month', [$fyStart, $fyEnd])
                    ->sum('gross_salary');

                $tds = $this->computeAnnualTax($emp, $annualGross);

                $totalTdsPaid = PayrollRecord::where('employee_id', $emp->id)
                    ->whereBetween('month', [$fyStart, $fyEnd])
                    ->sum('tds_amount');

                $tdsReport->push([
                    'employee'    => $emp,
                    'gross'       => $annualGross,
                    'std_ded'     => $tds['std_deduction'],
                    'invest_80c'  => $tds['invest_80c'],
                    'invest_80d'  => $tds['invest_80d'],
                    'hra_exempt'  => $tds['hra_exemption'],
                    'taxable'     => $tds['taxable_income'],
                    'annual_tax'  => $tds['annual_tax'],
                    'monthly_tds' => $tds['monthly_tds'],
                    'tds_paid'    => $totalTdsPaid,
                    'balance'     => max(0, $tds['annual_tax'] - $totalTdsPaid),
                    'regime'      => $emp->tax_regime ?? 'new',
                ]);
            }
        }

        return view('hr.tds-computation', compact('departments', 'tdsReport', 'financialYear'));
    }

    public function saveTdsDeclaration(Request $request, int $id)
    {
        $request->validate([
            'investment_80c' => 'nullable|numeric|min:0|max:150000',
            'investment_80d' => 'nullable|numeric|min:0|max:50000',
            'hra_exemption'  => 'nullable|numeric|min:0',
            'tax_regime'     => 'required|in:old,new',
        ]);
        Employee::findOrFail($id)->update($request->only(['investment_80c', 'investment_80d', 'hra_exemption', 'tax_regime']));
        return back()->with('success', 'TDS declaration saved.');
    }

    public function form16Pdf(int $id, Request $request)
    {
        $financialYear = $request->financial_year ?? date('Y') . '-' . (date('Y') + 1);
        [$fyStart, $fyEnd] = $this->parseFY($financialYear);

        $employee = Employee::findOrFail($id);
        $payrolls = PayrollRecord::where('employee_id', $id)
            ->whereBetween('month', [$fyStart, $fyEnd])
            ->orderBy('month')
            ->get();

        $annualGross = $payrolls->sum('gross_salary');
        $totalDeductions = $payrolls->sum('deductions');
        $netPaid = $payrolls->sum('net_salary');
        $totalTds = $payrolls->sum('tds_amount');

        $tds = $this->computeAnnualTax($employee, $annualGross);
        $school = \App\Models\SchoolSetting::first();

        $pdf = Pdf::loadView('pdf.form-16', compact(
            'employee', 'payrolls', 'financialYear', 'annualGross',
            'totalDeductions', 'netPaid', 'totalTds', 'tds', 'school'
        ))->setPaper('a4');

        return $pdf->download('form-16-' . ($employee->employee_code ?? $id) . '-' . str_replace('/', '-', $financialYear) . '.pdf');
    }

    private function parseFY(string $fy): array
    {
        [$startY, $endY] = explode('-', $fy);
        return ["{$startY}-04", "{$endY}-03"];
    }

    private function computeAnnualTax(Employee $emp, float $annualGross): array
    {
        $regime = $emp->tax_regime ?? 'new';
        $stdDeduction  = 50000;
        $invest80c     = min((float)($emp->investment_80c ?? 0), 150000);
        $invest80d     = min((float)($emp->investment_80d ?? 0), 50000);
        $hraExemption  = (float)($emp->hra_exemption ?? 0);

        if ($regime === 'old') {
            $taxable = max(0, $annualGross - $stdDeduction - $invest80c - $invest80d - $hraExemption);
            $tax = $this->calculateOldRegimeTax($taxable);
        } else {
            $taxable = max(0, $annualGross - $stdDeduction);
            $tax = $this->calculateNewRegimeTax($taxable);
        }

        $cess = $tax * 0.04;
        $annualTax = round($tax + $cess, 2);

        return [
            'std_deduction' => $stdDeduction,
            'invest_80c'    => $invest80c,
            'invest_80d'    => $invest80d,
            'hra_exemption' => $hraExemption,
            'taxable_income'=> round($taxable, 2),
            'base_tax'      => round($tax, 2),
            'cess'          => round($cess, 2),
            'annual_tax'    => $annualTax,
            'monthly_tds'   => round($annualTax / 12, 2),
        ];
    }

    private function calculateNewRegimeTax(float $income): float
    {
        if ($income <= 300000)  return 0;
        if ($income <= 600000)  return ($income - 300000) * 0.05;
        if ($income <= 900000)  return 15000 + ($income - 600000) * 0.10;
        if ($income <= 1200000) return 45000 + ($income - 900000) * 0.15;
        if ($income <= 1500000) return 90000 + ($income - 1200000) * 0.20;
        return 150000 + ($income - 1500000) * 0.30;
    }

    private function calculateOldRegimeTax(float $income): float
    {
        if ($income <= 250000)  return 0;
        if ($income <= 500000)  return ($income - 250000) * 0.05;
        if ($income <= 1000000) return 12500 + ($income - 500000) * 0.20;
        return 112500 + ($income - 1000000) * 0.30;
    }

    // ── Staff ID Cards ────────────────────────────────────

    public function staffIdCards(Request $request)
    {
        $departments = \App\Models\Department::orderBy('name')->get();
        $employees   = \App\Models\Employee::with('department')
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return view('hr.staff-id-cards', compact('employees', 'departments'));
    }

    public function downloadStaffIdCards(Request $request)
    {
        $employees = \App\Models\Employee::with('department')
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->when($request->ids, fn($q, $v) => $q->whereIn('id', explode(',', $v)))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $school = \App\Models\SchoolSetting::first();
        $validUntil = now()->addYear()->format('M Y');

        foreach ($employees as $emp) {
            $qrData = implode(' | ', array_filter([
                'ID: ' . ($emp->employee_id ?? $emp->id),
                'Name: ' . $emp->name,
                'Dept: ' . ($emp->department?->name ?? ''),
                'Blood: ' . ($emp->blood_group ?? ''),
            ]));
            try {
                $emp->_qrCode = base64_encode(
                    \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(80)->generate($qrData)
                );
            } catch (\Exception $e) {
                $emp->_qrCode = null;
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.staff-id-cards', compact('employees', 'school', 'validUntil'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('staff-id-cards.pdf');
    }

    // ── Overtime Entry ────────────────────────────────────

    public function overtime(Request $request)
    {
        $month       = $request->month ?? now()->format('Y-m');
        $departments = \App\Models\Department::orderBy('name')->get();
        $employees   = \App\Models\Employee::with('department')
            ->where('is_active', true)
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->orderBy('name')->get();

        $entries = \App\Models\OvertimeEntry::with('employee.department')
            ->where('month', $month)
            ->orderBy('entry_date')
            ->get();

        return view('hr.overtime', compact('employees', 'departments', 'month', 'entries'));
    }

    public function storeOvertime(Request $request)
    {
        $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'entry_date'    => 'required|date',
            'hours'         => 'required|numeric|min:0.5|max:24',
            'rate_per_hour' => 'required|numeric|min:0',
            'remarks'       => 'nullable|string|max:200',
        ]);

        $amount = round($request->hours * $request->rate_per_hour, 2);
        $month  = \Carbon\Carbon::parse($request->entry_date)->format('Y-m');

        \App\Models\OvertimeEntry::create([
            'employee_id'   => $request->employee_id,
            'entry_date'    => $request->entry_date,
            'hours'         => $request->hours,
            'rate_per_hour' => $request->rate_per_hour,
            'amount'        => $amount,
            'month'         => $month,
            'remarks'       => $request->remarks,
            'created_by'    => auth()->id(),
        ]);

        return back()->with('success', 'Overtime entry saved.');
    }

    // ── Arrears & Bonus ───────────────────────────────────

    public function arrearsBonus(Request $request)
    {
        $month       = $request->month ?? now()->format('Y-m');
        $departments = \App\Models\Department::orderBy('name')->get();
        $employees   = \App\Models\Employee::with('department')
            ->where('is_active', true)
            ->orderBy('name')->get();

        $entries = \App\Models\ArrearsBonusEntry::with('employee.department')
            ->where('month', $month)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalMonth = $entries->sum('amount');

        return view('hr.arrears-bonus', compact('employees', 'departments', 'month', 'entries', 'totalMonth'));
    }

    public function storeArrearsBonus(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type'        => 'required|in:arrears,bonus,other_addition',
            'amount'      => 'required|numeric|min:0.01',
            'month'       => 'required|string|size:7',
            'description' => 'nullable|string|max:300',
        ]);

        \App\Models\ArrearsBonusEntry::create([
            'employee_id' => $request->employee_id,
            'type'        => $request->type,
            'amount'      => $request->amount,
            'month'       => $request->month,
            'description' => $request->description,
            'payment_mode'=> 'payroll',
            'created_by'  => auth()->id(),
        ]);

        return back()->with('success', ucfirst($request->type) . ' entry added.');
    }

    // ── Increment History Report ──────────────────────────

    public function incrementHistory(Request $request)
    {
        $fyStart = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfYear();
        $fyEnd   = $request->to   ? \Carbon\Carbon::parse($request->to)   : now();

        $increments = \App\Models\EmployeeAppraisal::with('employee.department')
            ->whereNotNull('increment_amount')
            ->where('increment_amount', '>', 0)
            ->where(fn($q) => $q->whereBetween('increment_effective_date', [$fyStart, $fyEnd])
                               ->orWhereBetween('appraised_at', [$fyStart, $fyEnd]))
            ->orderByDesc('increment_effective_date')
            ->get();

        $totalIncrement = $increments->sum('increment_amount');

        return view('hr.increment-history', compact('increments', 'fyStart', 'fyEnd', 'totalIncrement'));
    }

    // ── Leave Encashment Report ───────────────────────────

    public function leaveEncashmentReport(Request $request)
    {
        $fyStart = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfYear();
        $fyEnd   = $request->to   ? \Carbon\Carbon::parse($request->to)   : now();

        $encashments = \App\Models\LeaveEncashment::with('employee.department')
            ->whereBetween('encashment_date', [$fyStart, $fyEnd])
            ->orderByDesc('encashment_date')
            ->get();

        $totalPaid = $encashments->sum('amount');

        return view('hr.leave-encashment-report', compact('encashments', 'fyStart', 'fyEnd', 'totalPaid'));
    }

    // ── HR Report Exports ─────────────────────────────────

    public function incrementHistoryPdf(Request $request)
    {
        $fyStart = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfYear();
        $fyEnd   = $request->to   ? \Carbon\Carbon::parse($request->to)   : now();
        $increments     = \App\Models\EmployeeAppraisal::with('employee.department')
            ->whereNotNull('increment_amount')->where('increment_amount', '>', 0)
            ->where(fn($q) => $q->whereBetween('increment_effective_date', [$fyStart, $fyEnd])
                               ->orWhereBetween('appraised_at', [$fyStart, $fyEnd]))
            ->orderByDesc('increment_effective_date')->get();
        $totalIncrement = $increments->sum('increment_amount');
        $school = \App\Models\SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.increment-history', compact('increments', 'fyStart', 'fyEnd', 'totalIncrement', 'school'))
            ->setPaper('a4', 'portrait');
        return $pdf->download('increment-history-'.now()->format('Y').'.pdf');
    }

    public function incrementHistoryExcel(Request $request)
    {
        $fyStart = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfYear();
        $fyEnd   = $request->to   ? \Carbon\Carbon::parse($request->to)   : now();
        $increments = \App\Models\EmployeeAppraisal::with('employee.department')
            ->whereNotNull('increment_amount')->where('increment_amount', '>', 0)
            ->where(fn($q) => $q->whereBetween('increment_effective_date', [$fyStart, $fyEnd])
                               ->orWhereBetween('appraised_at', [$fyStart, $fyEnd]))
            ->orderByDesc('increment_effective_date')->get();
        $rows = $increments->map(fn($inc) => [
            'Employee'     => $inc->employee?->name,
            'Department'   => $inc->employee?->department?->name ?? '—',
            'Year'         => $inc->appraisal_year,
            'Increment (₹)'=> number_format($inc->increment_amount, 2),
            'Increment %'  => $inc->increment_percent ? number_format($inc->increment_percent, 1).'%' : '—',
            'Effective From' => $inc->increment_effective_date?->format('d M Y') ?? '—',
            'Rating'       => $inc->rating_label ?? '—',
        ])->values();
        return Excel::download(
            new \App\Exports\ArrayExport($rows->toArray(), ['Employee', 'Department', 'Year', 'Increment (₹)', 'Increment %', 'Effective From', 'Rating']),
            'increment-history-'.now()->format('Y').'.xlsx'
        );
    }

    public function leaveEncashmentReportPdf(Request $request)
    {
        $fyStart = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfYear();
        $fyEnd   = $request->to   ? \Carbon\Carbon::parse($request->to)   : now();
        $encashments = \App\Models\LeaveEncashment::with('employee.department')
            ->whereBetween('encashment_date', [$fyStart, $fyEnd])
            ->orderByDesc('encashment_date')->get();
        $totalPaid = $encashments->sum('amount');
        $school = \App\Models\SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.leave-encashment-report', compact('encashments', 'fyStart', 'fyEnd', 'totalPaid', 'school'))
            ->setPaper('a4', 'portrait');
        return $pdf->download('leave-encashment-report.pdf');
    }

    public function leaveEncashmentReportExcel(Request $request)
    {
        $fyStart = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfYear();
        $fyEnd   = $request->to   ? \Carbon\Carbon::parse($request->to)   : now();
        $encashments = \App\Models\LeaveEncashment::with('employee.department')
            ->whereBetween('encashment_date', [$fyStart, $fyEnd])
            ->orderByDesc('encashment_date')->get();
        $rows = $encashments->map(fn($enc) => [
            'Employee'   => $enc->employee?->name,
            'Department' => $enc->employee?->department?->name ?? '—',
            'Date'       => $enc->encashment_date?->format('d M Y'),
            'Days'       => $enc->days,
            'Amount (₹)' => number_format($enc->amount, 2),
            'Remarks'    => $enc->remarks ?? '—',
        ])->values();
        return Excel::download(
            new \App\Exports\ArrayExport($rows->toArray(), ['Employee', 'Department', 'Date', 'Days', 'Amount (₹)', 'Remarks']),
            'leave-encashment-report.xlsx'
        );
    }

    public function salaryRegisterPdf(Request $request)
    {
        $month   = $request->month ?? now()->format('Y-m');
        $records = PayrollRecord::with('employee.department')->where('month', $month)
            ->where('status', '!=', 'draft')->orderBy('employee_id')->get();
        $school  = \App\Models\SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.salary-register', compact('records', 'month', 'school'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('salary-register-'.$month.'.pdf');
    }

    public function salaryRegisterExcel(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');
        $year  = substr($month, 0, 4);
        $mon   = substr($month, 5, 2);
        return Excel::download(new SalaryRegisterExport($mon, $year), 'salary-register-'.$month.'.xlsx');
    }

    public function orgChart(Request $request)
    {
        $departments = Department::with([
            'employees' => function ($q) {
                $q->where('status', 'active')
                  ->with(['designation', 'manager', 'directReports' => fn($r) => $r->where('status', 'active')]);
            },
        ])->get();

        $allEmployees = Employee::where('status', 'active')
            ->with(['designation', 'department'])
            ->orderBy('first_name')
            ->get();

        // Build top-level employees (no manager or manager outside active workforce)
        $roots = $allEmployees->filter(fn($e) => !$e->manager_id || !$allEmployees->contains('id', $e->manager_id));

        return view('hr.org-chart', compact('departments', 'allEmployees', 'roots'));
    }

    public function setManager(Request $request, int $id)
    {
        $request->validate(['manager_id' => 'nullable|exists:employees,id']);
        $employee = Employee::findOrFail($id);
        if ($request->manager_id && (int)$request->manager_id === $id) {
            return back()->with('error', 'An employee cannot be their own manager.');
        }
        $employee->update(['manager_id' => $request->manager_id ?: null]);
        $managerName = $request->manager_id ? Employee::find($request->manager_id)?->full_name : 'none';
        return back()->with('success', "{$employee->full_name}'s reporting manager set to {$managerName}.");
    }

    /**
     * Provision or reset a user portal account for an existing employee.
     */
    public function createPortalLogin(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);

        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'role'     => 'required|string',
        ]);

        $roleName = strtolower(trim($request->input('role')));

        // Prevent unauthorized privilege escalation
        if ($roleName === 'super_admin' && !Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Only Super Administrators can assign the Super Admin role.');
        }

        // Ensure role exists in Spatie
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        DB::beginTransaction();
        try {
            // Find existing user linked to this employee or matching username/email
            $user = User::where('employee_id', $employee->id)
                ->orWhere('username', $employee->employee_code)
                ->orWhere('email', strtolower($request->input('email')))
                ->first();

            if ($user) {
                $user->update([
                    'name'                 => $employee->full_name,
                    'username'             => $employee->employee_code,
                    'email'                => strtolower($request->input('email')),
                    'password'             => \Illuminate\Support\Facades\Hash::make($request->input('password')),
                    'employee_id'          => $employee->id,
                    'is_active'            => true,
                    'must_change_password' => true,
                ]);
            } else {
                $user = User::create([
                    'name'                 => $employee->full_name,
                    'username'             => $employee->employee_code,
                    'email'                => strtolower($request->input('email')),
                    'password'             => \Illuminate\Support\Facades\Hash::make($request->input('password')),
                    'employee_id'          => $employee->id,
                    'is_active'            => true,
                    'must_change_password' => true,
                ]);
            }

            $user->syncRoles([$roleName]);

            // Link employee to user
            $employee->update(['user_id' => $user->id]);

            // Establish or update data scope
            \App\Modules\Rbac\Models\UserDataScope::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'scope_type'  => $employee->employee_type === 'teaching' ? 'assigned_students_only' : 'department',
                    'scope_value' => $employee->employee_type === 'teaching' ? null : ($employee->department_id ? json_encode([(string)$employee->department_id]) : null),
                    'is_active'   => true,
                    'notes'       => 'Configured via Employee Portal Provisioning',
                    'created_by'  => Auth::id(),
                ]
            );

            // Invalidate permission cache
            \App\Modules\Rbac\Services\PermissionResolverService::clearUserCache($user->id);

            // Audit Log
            RbacAuditService::log(
                action: 'USER_PROVISIONED_FROM_EMPLOYEE_PROFILE',
                modelType: 'User',
                modelId: $user->id,
                newValues: [
                    'employee_id'   => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'email'         => $user->email,
                    'role'          => $roleName,
                ],
                actor: Auth::user()
            );

            DB::commit();

            return back()->with('success', "Portal login successfully configured for {$employee->full_name} ({$employee->employee_code}). Assigned role: " . ucwords(str_replace('_', ' ', $roleName)) . ".");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to provision portal account: ' . $e->getMessage());
        }
    }

    /**
     * Synchronize employee role mapping in RBAC.
     */
    public function assignEmployeeRole(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);

        $request->validate([
            'role' => 'required|string',
        ]);

        $roleName = strtolower(trim($request->input('role')));

        // Prevent unauthorized privilege escalation
        if ($roleName === 'super_admin' && !Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Only Super Administrators can assign the Super Admin role.');
        }

        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $user = $employee->user ?? User::where('employee_id', $employee->id)->orWhere('username', $employee->employee_code)->first();

        if (!$user) {
            return back()->with('error', 'No active user account found for this employee. Please provision a portal login account first.');
        }

        $user->syncRoles([$roleName]);
        \App\Modules\Rbac\Services\PermissionResolverService::clearUserCache($user->id);

        RbacAuditService::log(
            action: 'EMPLOYEE_ROLE_UPDATED',
            modelType: 'User',
            modelId: $user->id,
            newValues: ['role' => $roleName],
            actor: Auth::user()
        );

        return back()->with('success', "Role successfully updated to " . ucwords(str_replace('_', ' ', $roleName)) . " for {$employee->full_name}.");
    }
}

// Backward-compatibility alias for case-sensitive environments
if (!class_exists(\App\Http\Controllers\Admin\HRController::class, false)) {
    class_alias(\App\Http\Controllers\Admin\HrController::class, 'App\Http\Controllers\Admin\HRController');
}
