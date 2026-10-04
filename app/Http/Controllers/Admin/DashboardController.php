<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\DashboardWidget;
use App\Models\Student;
use App\Models\User;
use App\Services\DashboardCardService;
use App\Services\StudentFilterQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user        = auth()->user();
        $userRoles   = $user->getRoleNames()->toArray();
        $currentYear = AcademicYear::current();

        $managementRoles  = ['owner', 'admin', 'principal', 'vice_principal', 'it_admin', 'super_admin'];
        $teachingRoles    = ['hod', 'class_teacher', 'teacher', 'subject_teacher'];
        $financeRoles     = ['accountant'];
        $hrRoles          = ['hr_manager'];
        $libraryRoles     = ['librarian'];
        $transportRoles   = ['transport_manager'];
        $hostelRoles      = ['hostel_warden', 'warden'];
        $admissionRoles   = ['admission_counsellor', 'receptionist'];
        $operationsRoles  = ['inventory_manager', 'event_coordinator', 'alumni_coordinator'];
        $portalRoles      = ['student', 'parent'];

        if (array_intersect($userRoles, $portalRoles)) {
            return in_array('student', $userRoles)
                ? redirect()->route('portal.student.dashboard')
                : redirect()->route('portal.parent.dashboard');
        }

        if (array_intersect($userRoles, $teachingRoles)) {
            return $this->teacherDashboard($user, $currentYear);
        }
        if (array_intersect($userRoles, $financeRoles)) {
            return $this->financeDashboard($currentYear);
        }
        if (array_intersect($userRoles, $hrRoles)) {
            return $this->hrDashboard($currentYear);
        }
        if (array_intersect($userRoles, $libraryRoles)) {
            return $this->libraryDashboard();
        }
        if (array_intersect($userRoles, $transportRoles)) {
            return $this->transportDashboard();
        }
        if (array_intersect($userRoles, $hostelRoles)) {
            return $this->hostelDashboard();
        }
        if (array_intersect($userRoles, $admissionRoles)) {
            return $this->admissionDashboard($currentYear);
        }
        if (array_intersect($userRoles, $operationsRoles)) {
            return $this->operationsDashboard($user, $userRoles);
        }

        // Default: management / admin dashboard
        return $this->managementDashboard($currentYear);
    }

    public static function clearCache(): void
    {
        try {
            $currentYear = AcademicYear::current();
            $yearId = $currentYear?->id ?? 0;
            $today = today()->toDateString();
            $month = now()->format('Y-m');

            Cache::forget("dash_mod_students_{$yearId}");
            Cache::forget("dash_mod_att_{$yearId}_{$today}");
            Cache::forget("dash_mod_fees_{$yearId}_{$today}");
            Cache::forget("dash_mod_rev_{$yearId}_{$month}");
            Cache::forget("dash_mod_events_{$today}");
            Cache::forget("dash_mod_notices_{$today}");
            Cache::forget("dash_mod_circulars_{$today}");
            Cache::forget("dash_mod_birthdays_{$today}");
            Cache::forget("dash_mod_adm_{$yearId}_{$today}");

            Cache::forget('mgmt_dashboard_v9_' . $yearId . '_' . $today);
            Cache::forget('mgmt_dashboard_v8_' . $yearId . '_' . $today);
            Cache::forget('mgmt_dashboard_v7_' . $yearId);
            Cache::forget('mgmt_dashboard_v2_' . $yearId);
            Cache::forget('mgmt_dashboard_v2_0');
            Cache::forget('hr_employee_category_counts');
            Cache::forget('hr_departments_list');
        } catch (\Exception $e) {}
    }

    private function managementDashboard($currentYear)
    {
        // ── Catalogs ──
        $singleValueCatalog = DashboardWidget::singleValueCatalog();
        $singleValueKeys    = array_keys($singleValueCatalog);
        $briefInfoCatalog   = DashboardWidget::briefInfoCatalog();
        $briefInfoKeys      = array_keys($briefInfoCatalog);
        $defaultBriefKeys   = DashboardWidget::defaultBriefSources();

        // Ensure default brief info widgets exist in dashboard_widgets (atomic check & bulk insert once)
        Cache::remember('brief_widgets_seeded_v4', 86400 * 7, function () use ($briefInfoCatalog, $defaultBriefKeys) {
            $existing = DashboardWidget::whereIn('source', array_keys($briefInfoCatalog))->pluck('source')->toArray();
            $missing  = array_diff(array_keys($briefInfoCatalog), $existing);
            if (!empty($missing)) {
                $order = 50;
                $rows = [];
                foreach ($missing as $src) {
                    $meta = $briefInfoCatalog[$src] ?? [];
                    $rows[] = [
                        'name'       => $meta['label'] ?? ucfirst(str_replace('_', ' ', $src)),
                        'module'     => $meta['group'] ?? 'Brief Information',
                        'source'     => $src,
                        'icon_color' => 'from-indigo-500 to-blue-600',
                        'sort_order' => $order++,
                        'is_active'  => in_array($src, $defaultBriefKeys, true),
                        'is_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DashboardWidget::insert($rows);
            }
            return true;
        });

        // ── Fetch active widgets ─────────────────────────────────────────────
        $activeWidgets = DashboardWidget::active()->get();

        $activeBriefWidgets = $activeWidgets
            ->whereIn('source', $briefInfoKeys)
            ->pluck('source')
            ->values()
            ->toArray();

        // ── Resolve only active single-value KPI cards ───────────────────────
        $singleValueWidgets = $activeWidgets
            ->whereIn('source', $singleValueKeys)
            ->values()
            ->map(function ($widget) {
                $resolved             = DashboardCardService::resolve($widget);
                $widget->live_display = $resolved['display'];   // formatted string
                $widget->live_value   = $resolved['value'];     // raw number
                $widget->live_format  = $resolved['format'] ?? 'count';
                $widget->live_details = null; // Top cards display title + one overall value only
                $widget->link_url     = $resolved['link_url'];
                $widget->link_label   = $resolved['link_label'];
                $widget->badge_text   = $resolved['badge'];
                return $widget;
            });

        // ── Modular & Lazy Data Loading (Only query modules active on dashboard) ──

        // 1. Students & Class/Section distribution
        $needsStudentData = !empty(array_intersect([
            'student_overview', 'student_demographics', 'class_distribution', 'section_distribution'
        ], $activeBriefWidgets));

        if ($needsStudentData) {
            $studentData = Cache::remember('dash_mod_students_' . ($currentYear?->id ?? 0), 300, function () use ($currentYear) {
                $classStrength = DB::table('student_enrollments as se')
                    ->join('classes as c', 'c.id', '=', 'se.class_id')
                    ->where('se.status', 'active')
                    ->when($currentYear, fn($q) => $q->where('se.academic_year_id', $currentYear->id))
                    ->select('c.name as class_name', DB::raw('COUNT(*) as student_count'))
                    ->groupBy('c.id', 'c.name')->orderBy('c.name')->get();

                $sectionDistribution = DB::table('student_enrollments as se')
                    ->leftJoin('sections as s', 's.id', '=', 'se.section_id')
                    ->where('se.status', 'active')
                    ->when($currentYear, fn($q) => $q->where('se.academic_year_id', $currentYear->id))
                    ->select(DB::raw("COALESCE(s.name, 'Unassigned') as section_name"), DB::raw('COUNT(*) as student_count'))
                    ->groupBy(DB::raw("COALESCE(s.name, 'Unassigned')"))
                    ->orderBy('section_name')
                    ->get();

                $demographicsRow = DB::table('student_enrollments as se')
                    ->join('students as s', 's.id', '=', 'se.student_id')
                    ->where('se.status', 'active')
                    ->when($currentYear, fn($q) => $q->where('se.academic_year_id', $currentYear->id))
                    ->selectRaw("
                        COUNT(*) as total,
                        COUNT(CASE WHEN s.gender = 'male' THEN 1 END) as boys,
                        COUNT(CASE WHEN s.gender = 'female' THEN 1 END) as girls,
                        COUNT(CASE WHEN s.student_type = 'day_scholar' THEN 1 END) as day_scholars,
                        COUNT(CASE WHEN s.student_type = 'hosteller' THEN 1 END) as hostellers
                    ")
                    ->first();

                $studentDemographics = [
                    'total'          => $demographicsRow->total ?? 0,
                    'boys'           => $demographicsRow->boys ?? 0,
                    'girls'          => $demographicsRow->girls ?? 0,
                    'day_scholars'   => $demographicsRow->day_scholars ?? 0,
                    'hostellers'     => $demographicsRow->hostellers ?? 0,
                    'new_this_month' => DB::table('students')->whereMonth('admission_date', now()->month)->whereYear('admission_date', now()->year)->count(),
                ];

                $sections = DB::table('sections')->orderBy('name')->pluck('name')->unique()->values();

                return compact('classStrength', 'sectionDistribution', 'studentDemographics', 'sections');
            });
            $classStrength       = $studentData['classStrength'];
            $sectionDistribution = $studentData['sectionDistribution'];
            $studentDemographics = $studentData['studentDemographics'];
            $sections            = $studentData['sections'];
        } else {
            $classStrength       = collect();
            $sectionDistribution = collect();
            $studentDemographics = ['total' => 0, 'boys' => 0, 'girls' => 0, 'day_scholars' => 0, 'hostellers' => 0, 'new_this_month' => 0];
            $sections            = collect();
        }

        // 2. Attendance & Staff Overview
        $needsAttendance = in_array('attendance_overview', $activeBriefWidgets);

        if ($needsAttendance) {
            $attData = Cache::remember('dash_mod_att_' . ($currentYear?->id ?? 0) . '_' . today()->toDateString(), 300, function () {
                $staffRow = DB::table('employees')
                    ->where('is_active', true)
                    ->selectRaw("
                        COUNT(*) as total,
                        COUNT(CASE WHEN employee_type = 'teaching' THEN 1 END) as teaching,
                        COUNT(CASE WHEN employee_type != 'teaching' THEN 1 END) as non_teaching
                    ")
                    ->first();
                $staffTotal       = $staffRow->total ?? 0;
                $staffTeaching    = $staffRow->teaching ?? 0;
                $staffNonTeaching = $staffRow->non_teaching ?? 0;
                $staffPresent     = DB::table('staff_attendance')->whereDate('date', today())->where('status', 'present')->count();
                $staffOnLeave     = DB::table('leave_requests')->where('status', 'approved')->whereDate('from_date', '<=', today())->whereDate('to_date', '>=', today())->count();

                $staffSummary = [
                    'total'         => $staffTotal,
                    'teaching'      => $staffTeaching,
                    'non_teaching'  => $staffNonTeaching,
                    'present_today' => $staffPresent,
                    'on_leave_today'=> $staffOnLeave,
                    'absent_today'  => max($staffOnLeave, max(0, $staffTotal - $staffPresent)),
                ];

                $attStats = DB::table('attendance_records')
                    ->whereDate('date', today())
                    ->selectRaw("
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                        COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_day,
                        COUNT(CASE WHEN status = 'late' THEN 1 END) as late
                    ")
                    ->first();

                $todayAttStats = [
                    'total'      => $attStats->total ?? 0,
                    'present'    => $attStats->present ?? 0,
                    'absent'     => $attStats->absent ?? 0,
                    'half_day'   => $attStats->half_day ?? 0,
                    'late'       => $attStats->late ?? 0,
                    'percentage' => ($attStats && $attStats->total > 0)
                        ? round(($attStats->present + $attStats->half_day) / $attStats->total * 100, 1)
                        : null,
                ];
                $todayAttendance = $todayAttStats['percentage'];

                return compact('staffSummary', 'todayAttStats', 'todayAttendance');
            });
            $staffSummary    = $attData['staffSummary'];
            $todayAttStats   = $attData['todayAttStats'];
            $todayAttendance = $attData['todayAttendance'];
        } else {
            $staffSummary    = ['total' => 0, 'teaching' => 0, 'non_teaching' => 0, 'present_today' => 0, 'on_leave_today' => 0, 'absent_today' => 0];
            $todayAttStats   = ['total' => 0, 'present' => 0, 'absent' => 0, 'half_day' => 0, 'late' => 0, 'percentage' => null];
            $todayAttendance = null;
        }

        // 3. Fee Overview & Revenue Analytics
        $needsFeeOverview     = in_array('fee_overview', $activeBriefWidgets);
        $needsRevenueAnalytics = in_array('revenue_analytics', $activeBriefWidgets);

        if ($needsFeeOverview || $needsRevenueAnalytics) {
            $feeData = Cache::remember('dash_mod_fees_' . ($currentYear?->id ?? 0) . '_' . today()->toDateString(), 300, function () use ($currentYear, $needsRevenueAnalytics) {
                $totalIncome = (float) DB::table('fee_payments')->where('is_cancelled', false)
                    ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))->sum('amount');
                if ($totalIncome == 0) {
                    $totalIncome = (float) DB::table('fee_payments')->where('is_cancelled', false)->sum('amount');
                }
                $monthIncome = (float) DB::table('fee_payments')->where('is_cancelled', false)
                    ->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount');

                $feeSummary = [
                    'collected_total' => $totalIncome,
                    'collected_month' => $monthIncome,
                ];

                $totalExpenses = (float) DB::table('expenses')->where('approval_status', 'approved')
                    ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))->sum('amount');
                if ($totalExpenses == 0) {
                    $totalExpenses = (float) DB::table('expenses')->where('approval_status', 'approved')->sum('amount');
                }
                $totalDueFees  = (float) DB::table('fee_structures')
                    ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))->sum('amount');
                if ($totalDueFees == 0) {
                    $totalDueFees  = (float) DB::table('fee_structures')->sum('amount');
                }
                $pendingFees   = (float) max(0, $totalDueFees - $totalIncome);
                $netOperating  = (float) ($totalIncome - $totalExpenses);

                $revenueAnalytics = collect();
                if ($needsRevenueAnalytics) {
                    $monthlyIncome = DB::table('fee_payments')
                        ->where('is_cancelled', false)
                        ->whereRaw("payment_date >= CURRENT_DATE - INTERVAL '5 months'")
                        ->selectRaw("TO_CHAR(payment_date, 'YYYY-MM') as month, SUM(amount) as total")
                        ->groupByRaw("TO_CHAR(payment_date, 'YYYY-MM')")
                        ->pluck('total', 'month');

                    $monthlyExpenses = DB::table('expenses')
                        ->where('approval_status', 'approved')
                        ->whereRaw("expense_date >= CURRENT_DATE - INTERVAL '5 months'")
                        ->selectRaw("TO_CHAR(expense_date, 'YYYY-MM') as month, SUM(amount) as total")
                        ->groupByRaw("TO_CHAR(expense_date, 'YYYY-MM')")
                        ->pluck('total', 'month');

                    for ($i = 5; $i >= 0; $i--) {
                        $mDate  = now()->subMonths($i);
                        $mKey   = $mDate->format('Y-m');
                        $mLabel = $mDate->format('F Y');
                        $mShort = $mDate->format('M Y');
                        $inc    = (float) ($monthlyIncome[$mKey] ?? 0);
                        $exp    = (float) ($monthlyExpenses[$mKey] ?? 0);
                        $net    = $inc - $exp;

                        $revenueAnalytics->push([
                            'month'       => $mKey,
                            'month_label' => $mLabel,
                            'short_label' => $mShort,
                            'income'      => $inc,
                            'expense'     => $exp,
                            'expenses'    => $exp,
                            'net'         => $net,
                        ]);
                    }
                }

                return compact('feeSummary', 'totalIncome', 'totalExpenses', 'netOperating', 'pendingFees', 'revenueAnalytics');
            });
            $feeSummary       = $feeData['feeSummary'];
            $totalIncome      = $feeData['totalIncome'];
            $totalExpenses    = $feeData['totalExpenses'];
            $netOperating     = $feeData['netOperating'];
            $pendingFees      = $feeData['pendingFees'];
            $revenueAnalytics = $feeData['revenueAnalytics'];
        } else {
            $feeSummary       = ['collected_total' => 0, 'collected_month' => 0];
            $totalIncome      = 0;
            $totalExpenses    = 0;
            $netOperating     = 0;
            $pendingFees      = 0;
            $revenueAnalytics = collect();
        }

        // 4. Notices & Circulars
        $needsNotices   = !empty(array_intersect(['school_notices', 'notices_active'], $activeBriefWidgets));
        $needsCirculars = !empty(array_intersect(['circulars_orders', 'circulars_active'], $activeBriefWidgets));

        if ($needsNotices) {
            $recentNotices = Cache::remember('dash_mod_notices_' . today()->toDateString(), 600, function () {
                return DB::table('notices')->where('is_published', true)
                    ->where('notice_type', '!=', 'circular')
                    ->latest('publish_date')->limit(6)
                    ->get(['id', 'title', 'notice_type', 'content', 'publish_date']);
            });
        } else {
            $recentNotices = collect();
        }

        if ($needsCirculars) {
            $recentCirculars = Cache::remember('dash_mod_circulars_' . today()->toDateString(), 600, function () {
                return DB::table('notices')->where('is_published', true)
                    ->where('notice_type', 'circular')
                    ->latest('publish_date')->limit(6)
                    ->get(['id', 'title', 'content', 'publish_date']);
            });
        } else {
            $recentCirculars = collect();
        }

        // 5. Events
        $needsEvents = !empty(array_intersect(['upcoming_events', 'events_upcoming'], $activeBriefWidgets));

        if ($needsEvents) {
            $upcomingEvents = Cache::remember('dash_mod_events_' . today()->toDateString(), 600, function () {
                return DB::table('events')->where('is_published', true)
                    ->where('event_date', '>=', today())->orderBy('event_date')->limit(6)
                    ->get(['id', 'name', 'event_type', 'event_date', 'description', 'venue'])
                    ->map(fn($e) => tap($e, fn($e) => $e->event_date = \Carbon\Carbon::parse($e->event_date)));
            });
        } else {
            $upcomingEvents = collect();
        }

        // 6. Birthday Wishes
        $needsBirthdays = in_array('birthday_wishes', $activeBriefWidgets);

        if ($needsBirthdays) {
            $todayBirthdays = Cache::remember('dash_mod_birthdays_' . today()->toDateString(), 1800, function () use ($currentYear) {
                $currentMonth = now()->month;
                $currentDay   = now()->day;

                $studentBirthdays = DB::table('students as s')
                    ->leftJoin('student_enrollments as se', function ($join) use ($currentYear) {
                        $join->on('se.student_id', '=', 's.id')
                             ->where('se.status', '=', 'active');
                        if ($currentYear) {
                            $join->where('se.academic_year_id', '=', $currentYear->id);
                        }
                    })
                    ->leftJoin('classes as c', 'c.id', '=', 'se.class_id')
                    ->leftJoin('sections as sec', 'sec.id', '=', 'se.section_id')
                    ->where('s.status', 'active')
                    ->whereNotNull('s.dob')
                    ->whereRaw('EXTRACT(MONTH FROM s.dob) = ?', [$currentMonth])
                    ->whereRaw('EXTRACT(DAY FROM s.dob) = ?', [$currentDay])
                    ->select(['s.id', 's.first_name', 's.last_name', 's.dob', 'c.name as class_name', 'sec.name as section_name'])
                    ->get()
                    ->map(function ($s) {
                        $sub = ($s->class_name ? 'Class ' . $s->class_name : 'Student') . ($s->section_name ? ' • Sec ' . $s->section_name : '');
                        return (object) [
                            'id'          => $s->id,
                            'name'        => trim($s->first_name . ' ' . $s->last_name),
                            'type'        => 'student',
                            'type_label'  => 'Student',
                            'subtitle'    => $sub,
                            'role_detail' => $sub,
                            'dob'         => $s->dob,
                        ];
                    });

                $staffBirthdays = DB::table('employees as e')
                    ->where('e.is_active', true)
                    ->whereNull('e.deleted_at')
                    ->whereNotNull('e.dob')
                    ->whereRaw('EXTRACT(MONTH FROM e.dob) = ?', [$currentMonth])
                    ->whereRaw('EXTRACT(DAY FROM e.dob) = ?', [$currentDay])
                    ->select(['e.id', 'e.first_name', 'e.last_name', 'e.dob', 'e.designation', 'e.department', 'e.employee_type'])
                    ->get()
                    ->map(function ($e) {
                        $roleLabel = ($e->employee_type === 'teaching') ? 'Teacher' : 'Staff';
                        $sub = [];
                        if (!empty($e->designation)) $sub[] = $e->designation;
                        if (!empty($e->department))  $sub[] = $e->department;
                        $subStr = !empty($sub) ? implode(' • ', $sub) : $roleLabel;
                        return (object) [
                            'id'          => $e->id,
                            'name'        => trim($e->first_name . ' ' . $e->last_name),
                            'type'        => 'staff',
                            'type_label'  => $roleLabel,
                            'subtitle'    => $subStr,
                            'role_detail' => $subStr,
                            'dob'         => $e->dob,
                        ];
                    });

                return $studentBirthdays->concat($staffBirthdays);
            });
        } else {
            $todayBirthdays = collect();
        }

        // 7. Admissions Overview & Pending Alerts
        $needsAdmissions = !empty(array_intersect(['admissions_overview', 'recent_admissions', 'pending_approvals'], $activeBriefWidgets));

        if ($needsAdmissions) {
            $admData = Cache::remember('dash_mod_adm_' . ($currentYear?->id ?? 0) . '_' . today()->toDateString(), 300, function () {
                $pendingAlerts = [
                    'admissions' => DB::table('enquiries')->whereIn('status', ['new', 'pending', 'pending_principal_approval'])->count(),
                    'leaves'     => DB::table('leave_requests')->where('status', 'pending')->count(),
                    'tc'         => DB::table('tc_requests')->where('status', 'pending')->count(),
                ];

                $recentAdmissions = DB::table('enquiries as e')
                    ->leftJoin('classes as c', 'c.id', '=', 'e.class_id')
                    ->orderByDesc('e.created_at')
                    ->limit(5)
                    ->select('e.id', 'e.student_name', 'c.name as class_applied', 'e.parent_mobile as phone', 'e.status', 'e.created_at')
                    ->get();

                return compact('pendingAlerts', 'recentAdmissions');
            });
            $pendingAlerts    = $admData['pendingAlerts'];
            $recentAdmissions = $admData['recentAdmissions'];
        } else {
            $pendingAlerts    = ['admissions' => 0, 'leaves' => 0, 'tc' => 0];
            $recentAdmissions = collect();
        }

        // ── All Active Widget Sources (for modal chip selection & duplicate prevention) ──
        $activeWidgetKeys = array_values(array_unique(array_merge(
            $singleValueWidgets->pluck('source')->toArray(),
            $activeBriefWidgets
        )));

        $classes            = Classes::activeCached();
        $colorOptions       = DashboardWidget::colorOptions();
        $sourceCatalog      = DashboardWidget::sourceCatalog();
        $sourceGroups       = DashboardWidget::sourceCatalogGrouped();
        $singleValueGroups  = DashboardWidget::singleValueCatalogGrouped();
        $briefInfoGroups    = DashboardWidget::briefInfoCatalogGrouped();
        $activeKpiKeys      = $singleValueWidgets->pluck('source')->toArray();
        $activeDetailedKeys = $activeBriefWidgets;
        $academicYear       = $currentYear?->name ?? '—';
        $existingSources    = $activeWidgetKeys;
        $canManageWidgets   = auth()->user()->hasAnyRole(['owner', 'admin', 'principal', 'vice_principal', 'it_admin', 'super_admin']);

        return view('dashboard.index', [
            'dashboardType'       => 'management',
            'allWidgets'          => $singleValueWidgets,
            'singleValueWidgets'  => $singleValueWidgets,
            'activeBriefWidgets'  => $activeBriefWidgets,
            'activeDetailedKeys'  => $activeDetailedKeys,
            'activeKpiKeys'       => $activeKpiKeys,
            'activeWidgetKeys'    => $activeWidgetKeys,
            'singleValueCatalog'  => $singleValueCatalog,
            'singleValueGroups'   => $singleValueGroups,
            'briefInfoCatalog'    => $briefInfoCatalog,
            'briefInfoGroups'     => $briefInfoGroups,
            'classes'             => $classes,
            'colorOptions'        => $colorOptions,
            'sourceCatalog'       => $sourceCatalog,
            'sourceGroups'        => $sourceGroups,
            'academicYear'        => $academicYear,
            'existingSources'     => $existingSources,
            'canManageWidgets'    => $canManageWidgets,

            // Modular data
            'classStrength'       => $classStrength,
            'sectionDistribution' => $sectionDistribution,
            'studentDemographics' => $studentDemographics,
            'staffSummary'        => $staffSummary,
            'feeSummary'          => $feeSummary,
            'pendingAlerts'       => $pendingAlerts,
            'revenueAnalytics'    => $revenueAnalytics,
            'totalIncome'         => $totalIncome,
            'totalExpenses'       => $totalExpenses,
            'netOperating'        => $netOperating,
            'pendingFees'         => $pendingFees,
            'recentNotices'       => $recentNotices,
            'recentCirculars'     => $recentCirculars,
            'upcomingEvents'      => $upcomingEvents,
            'todayBirthdays'      => $todayBirthdays,
            'todayAttendance'     => $todayAttendance,
            'todayAttStats'       => $todayAttStats,
            'recentAdmissions'    => $recentAdmissions,
            'sections'            => $sections,
        ]);
    }

    private function teacherDashboard($user, $currentYear)
    {
        $employee = $user->employee ?? ($user->employee_id ? DB::table('employees')->find($user->employee_id) : null);
        $myClasses  = collect();
        $myScheduleToday = collect();

        if ($employee) {
            try {
                $myClasses = DB::table('timetables as t')
                    ->join('classes as c', 'c.id', '=', 't.class_id')
                    ->leftJoin('subjects as s', 's.id', '=', 't.subject_id')
                    ->where('t.teacher_id', $employee->id)
                    ->when($currentYear, fn($q) => $q->where('t.academic_year_id', $currentYear->id))
                    ->select('t.class_id', 'c.name as class_name', 's.name as subject_name', 't.day_of_week', 't.start_time', 't.end_time')
                    ->orderBy('c.name')->orderBy('t.start_time')
                    ->get();

                $todayDay = now()->format('l');
                $myScheduleToday = $myClasses->filter(fn($r) => strtolower($r->day_of_week) === strtolower($todayDay));
            } catch (\Exception $e) {}
        }

        $myAttendancePending = 0;
        try {
            $classIds = $myClasses->pluck('class_id')->filter()->unique();
            if ($classIds->isNotEmpty()) {
                $total = DB::table('student_enrollments')
                    ->whereIn('class_id', $classIds)->where('status', 'active')
                    ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                    ->count();
                $marked = DB::table('attendance_records')
                    ->whereDate('date', today())->whereIn('class_id', $classIds)->count();
                $myAttendancePending = max(0, $total - $marked);
            }
        } catch (\Exception $e) {}

        $pendingHomework = 0;
        try {
            $pendingHomework = DB::table('homework')
                ->where('assigned_by', $employee?->id ?? 0)
                ->where('due_date', '>=', today()->toDateString())->count();
        } catch (\Exception $e) {}

        $upcomingExams = collect();
        try {
            $upcomingExams = DB::table('exam_schedules as es')
                ->join('exams as e', 'e.id', '=', 'es.exam_id')
                ->join('classes as c', 'c.id', '=', 'es.class_id')
                ->where('es.exam_date', '>=', today()->toDateString())
                ->orderBy('es.exam_date')->limit(5)
                ->get(['es.id', 'e.name as title', 'es.exam_date', 'c.name as class_name'])
                ->map(fn($r) => tap($r, fn($r) => $r->exam_date = \Carbon\Carbon::parse($r->exam_date)));
        } catch (\Exception $e) {}

        $recentNotices = collect();
        try {
            $recentNotices = DB::table('notices')->where('is_published', true)
                ->latest('publish_date')->limit(4)->get(['id', 'title', 'publish_date']);
        } catch (\Exception $e) {}

        return view('dashboard.index', compact(
            'employee', 'myClasses', 'myScheduleToday', 'myAttendancePending',
            'pendingHomework', 'upcomingExams', 'recentNotices'
        ) + ['dashboardType' => 'teaching', 'currentYear' => $currentYear]);
    }

    private function financeDashboard($currentYear)
    {
        $todayCollection = DB::table('fee_payments')->where('is_cancelled', false)
            ->whereDate('payment_date', today())->sum('amount');

        $monthCollection = DB::table('fee_payments')->where('is_cancelled', false)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)->sum('amount');

        $totalPaid = DB::table('fee_payments')->where('is_cancelled', false)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))->sum('amount');
        $totalDue  = DB::table('fee_structures')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))->sum('amount');
        $outstanding = max(0, $totalDue - $totalPaid);

        $paymentModes = DB::table('fee_payments')->where('is_cancelled', false)
            ->whereMonth('payment_date', now()->month)
            ->selectRaw('payment_mode, SUM(amount) as total')
            ->groupBy('payment_mode')->get();

        $recentPayments = DB::table('fee_payments as fp')
            ->join('students as s', 's.id', '=', 'fp.student_id')
            ->where('fp.is_cancelled', false)
            ->orderByDesc('fp.payment_date')->limit(10)
            ->select('fp.receipt_number', 'fp.amount', 'fp.payment_date', 'fp.payment_mode',
                     's.first_name', 's.last_name', 's.admission_no')->get();

        $collectionTrend = DB::table('fee_payments')->where('is_cancelled', false)
            ->whereRaw("payment_date >= CURRENT_DATE - INTERVAL '5 months'")
            ->selectRaw("TO_CHAR(payment_date, 'YYYY-MM') as month, SUM(amount) as total")
            ->groupByRaw("TO_CHAR(payment_date, 'YYYY-MM')")->orderBy('month')->get();

        return view('dashboard.index', compact(
            'todayCollection', 'monthCollection', 'outstanding',
            'paymentModes', 'recentPayments', 'collectionTrend', 'currentYear'
        ) + ['dashboardType' => 'finance', 'stats' => ['academic_year' => $currentYear?->name ?? '—']]);
    }

    private function hrDashboard($currentYear)
    {
        $totalStaff  = DB::table('employees')->where('is_active', true)->count();
        $staffPresent = 0;
        try {
            $staffPresent = DB::table('staff_attendance')->whereDate('date', today())
                ->where('status', 'present')->count();
        } catch (\Exception $e) {}

        $pendingLeaves = 0;
        try {
            $pendingLeaves = DB::table('leave_requests')
                ->where('status', 'pending')->count();
        } catch (\Exception $e) {}

        $departmentStrength = collect();
        try {
            $departmentStrength = DB::table('employees as e')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                ->where('e.is_active', true)
                ->select('d.name as department', DB::raw('COUNT(*) as count'))
                ->groupBy('d.id', 'd.name')->orderBy('d.name')->get();
        } catch (\Exception $e) {}

        $recentJoinings = DB::table('employees')
            ->where('is_active', true)->orderByDesc('joining_date')
            ->limit(5)->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"), 'designation', 'joining_date')->get();

        return view('dashboard.index', compact(
            'totalStaff', 'staffPresent', 'pendingLeaves',
            'departmentStrength', 'recentJoinings'
        ) + ['dashboardType' => 'hr', 'stats' => ['academic_year' => $currentYear?->name ?? '—']]);
    }

    private function libraryDashboard()
    {
        $totalBooks   = 0;
        $issuedBooks  = 0;
        $overdueBooks = 0;
        $recentIssues = collect();

        try {
            $totalBooks  = DB::table('books')->count();
            $issuedBooks = DB::table('book_issues')->whereNull('return_date')->count();
            $overdueBooks = DB::table('book_issues')
                ->whereNull('return_date')
                ->where('due_date', '<', today())->count();
            $recentIssues = DB::table('book_issues as bi')
                ->join('books as b', 'b.id', '=', 'bi.book_id')
                ->leftJoin('students as s', 's.id', '=', 'bi.student_id')
                ->orderByDesc('bi.issue_date')->limit(10)
                ->select('b.title', 's.first_name', 's.last_name', 'bi.issue_date', 'bi.due_date', 'bi.return_date')
                ->get();
        } catch (\Exception $e) {}

        return view('dashboard.index', compact(
            'totalBooks', 'issuedBooks', 'overdueBooks', 'recentIssues'
        ) + ['dashboardType' => 'library', 'stats' => ['academic_year' => '—']]);
    }

    private function transportDashboard()
    {
        $totalVehicles  = 0;
        $activeRoutes   = 0;
        $studentsOnBus  = 0;
        $recentFuelLogs = collect();

        try {
            $totalVehicles  = DB::table('vehicles')->where('status', 'active')->count();
            $activeRoutes   = DB::table('transport_routes')->where('is_active', true)->count();
            $studentsOnBus  = DB::table('transport_allotments')->where('is_active', true)->count();
            $recentFuelLogs = DB::table('vehicle_fuel_logs as f')
                ->join('vehicles as v', 'v.id', '=', 'f.vehicle_id')
                ->orderByDesc('f.date')->limit(8)
                ->select('v.registration_number', 'f.date', 'f.litres', 'f.amount', 'f.odometer')
                ->get();
        } catch (\Exception $e) {}

        return view('dashboard.index', compact(
            'totalVehicles', 'activeRoutes', 'studentsOnBus', 'recentFuelLogs'
        ) + ['dashboardType' => 'transport', 'stats' => ['academic_year' => '—']]);
    }

    private function hostelDashboard()
    {
        $totalRooms     = 0;
        $occupiedRooms  = 0;
        $totalResidents = 0;
        $pendingOutpass = 0;

        try {
            $totalRooms     = DB::table('hostel_rooms')->count();
            $occupiedRooms  = DB::table('hostel_rooms')->where('status', 'occupied')->count();
            $totalResidents = DB::table('hostel_allotments')->where('status', 'active')->count();
            $pendingOutpass = DB::table('hostel_outpasses')->where('status', 'pending')->count();
        } catch (\Exception $e) {}

        $recentOutpass = collect();
        try {
            $recentOutpass = DB::table('hostel_outpasses as o')
                ->join('students as s', 's.id', '=', 'o.student_id')
                ->orderByDesc('o.created_at')->limit(8)
                ->select('s.first_name', 's.last_name', 'o.reason', 'o.from_datetime as from_date', 'o.to_datetime as to_date', 'o.status')
                ->get();
        } catch (\Exception $e) {}

        return view('dashboard.index', compact(
            'totalRooms', 'occupiedRooms', 'totalResidents', 'pendingOutpass', 'recentOutpass'
        ) + ['dashboardType' => 'hostel', 'stats' => ['academic_year' => '—']]);
    }

    private function admissionDashboard($currentYear)
    {
        $totalEnquiries   = 0;
        $pendingFollowups = 0;
        $convertedMonth   = 0;
        $recentEnquiries  = collect();

        try {
            $totalEnquiries   = DB::table('enquiries')->count();
            $pendingFollowups = DB::table('enquiries')->where('status', 'pending')->orWhereNull('status')->count();
            $convertedMonth   = DB::table('enquiries')->where('status', 'converted')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count();
            $recentEnquiries  = DB::table('enquiries as e')
                ->leftJoin('classes as c', 'c.id', '=', 'e.class_id')
                ->orderByDesc('e.created_at')->limit(10)
                ->select('e.id', 'e.student_name', 'c.name as class_applied', 'e.parent_mobile as phone', 'e.status', 'e.created_at')
                ->get();
        } catch (\Exception $e) {}

        $newAdmissionsMonth = DB::table('students')->where('status', 'active')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();

        return view('dashboard.index', compact(
            'totalEnquiries', 'pendingFollowups', 'convertedMonth',
            'recentEnquiries', 'newAdmissionsMonth'
        ) + ['dashboardType' => 'admissions', 'stats' => ['academic_year' => $currentYear?->name ?? '—']]);
    }

    private function operationsDashboard($user, $userRoles)
    {
        $subType = 'general';
        if (in_array('inventory_manager', $userRoles))   $subType = 'inventory';
        elseif (in_array('event_coordinator', $userRoles)) $subType = 'events';
        elseif (in_array('alumni_coordinator', $userRoles)) $subType = 'alumni';

        $data = ['dashboardType' => 'operations', 'subType' => $subType,
                 'stats' => ['academic_year' => '—']];

        if ($subType === 'inventory') {
            try {
                $data['totalItems']     = DB::table('inventory_items')->count();
                $data['lowStockItems']  = DB::table('inventory_items')->whereRaw('quantity <= minimum_stock')->count();
                $data['recentMovements'] = DB::table('inventory_transactions as m')
                    ->join('inventory_items as i', 'i.id', '=', 'm.item_id')
                    ->orderByDesc('m.created_at')->limit(8)
                    ->select('i.name', 'm.transaction_type as type', 'm.quantity', 'm.created_at')->get();
            } catch (\Exception $e) {
                $data += ['totalItems' => 0, 'lowStockItems' => 0, 'recentMovements' => collect()];
            }
        } elseif ($subType === 'events') {
            try {
                $data['upcomingEvents'] = DB::table('events')->where('is_published', true)
                    ->where('event_date', '>=', today())->orderBy('event_date')->limit(10)
                    ->get(['id', 'name', 'event_type', 'event_date'])
                    ->map(fn($e) => tap($e, fn($e) => $e->event_date = \Carbon\Carbon::parse($e->event_date)));
                $data['totalEvents']    = DB::table('events')->count();
            } catch (\Exception $e) {
                $data += ['upcomingEvents' => collect(), 'totalEvents' => 0];
            }
        } elseif ($subType === 'alumni') {
            try {
                $data['totalAlumni']   = DB::table('alumni')->count();
                $data['recentAlumni']  = DB::table('alumni')->orderByDesc('created_at')->limit(8)
                    ->get(['id', 'name', 'graduation_year', 'current_occupation']);
            } catch (\Exception $e) {
                $data += ['totalAlumni' => 0, 'recentAlumni' => collect()];
            }
        }

        return view('dashboard.index', $data);
    }

    public function executiveDashboard()
    {
        return $this->managementDashboard(AcademicYear::current());
    }

    // ── Custom Dashboard Widget CRUD ──────────────────────────────



    /**
     * Store a new custom count widget or batch add cards from catalog.
     * POST /dashboard/widgets
     */
    public function widgetStore(Request $request)
    {
        // Only management roles can manage custom widgets
        $allowedRoles = ['owner', 'admin', 'principal', 'vice_principal', 'it_admin', 'super_admin'];
        if (!auth()->user()->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized');
        }

        $catalog = DashboardWidget::sourceCatalog();

        // ── 1. Batch Selection from Master Card Catalog ──
        if ($request->has('sources') && is_array($request->input('sources'))) {
            $selectedSources = array_filter($request->input('sources'));
            $activatedCount  = 0;
            $maxOrder        = DashboardWidget::max('sort_order') ?? 0;

            foreach ($selectedSources as $sourceKey) {
                if (!isset($catalog[$sourceKey])) continue;

                $config = $catalog[$sourceKey];
                $group  = $config['group'] ?? 'general';
                $module = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($group)));

                $existing = DashboardWidget::where('source', $sourceKey)->first();
                if ($existing) {
                    if (!$existing->is_active) {
                        $maxOrder++;
                        $existing->update([
                            'is_active'  => true,
                            'sort_order' => $maxOrder,
                        ]);
                        $activatedCount++;
                    }
                } else {
                    $maxOrder++;
                    DashboardWidget::create([
                        'name'       => $config['label'],
                        'module'     => $module,
                        'source'     => $sourceKey,
                        'filters'    => [],
                        'icon_color' => 'from-blue-500 to-indigo-600',
                        'sort_order' => $maxOrder,
                        'is_active'  => true,
                        'is_default' => false,
                        'created_by' => auth()->id(),
                    ]);
                    $activatedCount++;
                }
            }

            $currentYear = AcademicYear::current();
            Cache::forget('mgmt_dashboard_v8_' . ($currentYear?->id ?? 0) . '_' . today()->toDateString());
            Cache::forget('mgmt_dashboard_v7_' . ($currentYear?->id ?? 0));

            return redirect()->route('dashboard')->with('success', "{$activatedCount} dashboard " . ($activatedCount === 1 ? 'card' : 'cards') . " added to your dashboard.");
        }

        // ── 2. Single Card Creation / Custom Filtered Card ──
        $sourceKeys = implode(',', array_keys($catalog));

        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'source'              => 'nullable|string|in:' . $sourceKeys,
            'icon_color'          => 'nullable|string|max:80',
            'filter_class_id'     => 'nullable|integer|exists:classes,id',
            'filter_section'      => 'nullable|string|max:10',
            'filter_gender'       => 'nullable|in:male,female,other',
            'filter_student_type' => 'nullable|in:day_scholar,hosteller,day_boarder',
            'filter_status'       => 'nullable|in:active,inactive,transferred,left,alumni,all',
        ]);

        $source = $validated['source'] ?? 'student_count';
        $config = $catalog[$source] ?? ($catalog['student_count'] ?? []);

        // Duplicate card prevention: only 1 instance of non-custom cards allowed
        if ($source !== 'student_count') {
            $existing = DashboardWidget::where('is_active', true)->where('source', $source)->first();
            if ($existing) {
                return redirect()->route('dashboard')->with('error', "The card \"{$existing->name}\" is already added to your dashboard. Duplicate cards are not allowed.");
            }
        }

        $group  = $config['group'] ?? 'general';
        $module = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($group)));

        $filters = [];
        if (!empty($config['has_student_filters'])) {
            $filters = array_filter([
                'class_id'     => $validated['filter_class_id']    ?? null,
                'section'      => $validated['filter_section']      ?? null,
                'gender'       => $validated['filter_gender']       ?? null,
                'student_type' => $validated['filter_student_type'] ?? null,
                'status'       => $validated['filter_status']       ?? 'active',
            ], fn($v) => $v !== null && $v !== '');
        }

        $maxOrder = DashboardWidget::max('sort_order') ?? 0;

        // If an inactive row exists for this source (non-custom), reactivate it
        if ($source !== 'student_count') {
            $inactive = DashboardWidget::where('source', $source)->where('is_active', false)->first();
            if ($inactive) {
                $inactive->update([
                    'name'       => $validated['name'],
                    'icon_color' => $validated['icon_color'] ?? $inactive->icon_color,
                    'sort_order' => $maxOrder + 1,
                    'is_active'  => true,
                ]);
                $currentYear = AcademicYear::current();
                Cache::forget('mgmt_dashboard_v8_' . ($currentYear?->id ?? 0) . '_' . today()->toDateString());
                Cache::forget('mgmt_dashboard_v7_' . ($currentYear?->id ?? 0));
                return redirect()->route('dashboard')->with('success', "Dashboard card \"{$validated['name']}\" restored.");
            }
        }

        DashboardWidget::create([
            'name'       => $validated['name'],
            'module'     => $module,
            'source'     => $source,
            'filters'    => $filters,
            'icon_color' => $validated['icon_color'] ?? 'from-violet-500 to-purple-600',
            'sort_order' => $maxOrder + 1,
            'is_active'  => true,
            'is_default' => false,
            'created_by' => auth()->id(),
        ]);

        static::clearCache();

        return redirect()->route('dashboard')->with('success', "Dashboard card \"{$validated['name']}\" created.");
    }

    /**
     * Update an existing custom widget.
     * PUT /dashboard/widgets/{id}
     */
    public function widgetUpdate(Request $request, int $id)
    {
        // Only management roles can manage custom widgets
        $allowedRoles = ['owner', 'admin', 'principal', 'vice_principal', 'it_admin', 'super_admin'];
        if (!auth()->user()->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized');
        }

        $widget = DashboardWidget::findOrFail($id);
        $catalog = DashboardWidget::sourceCatalog();
        $sourceKeys = implode(',', array_keys($catalog));

        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'source'              => 'nullable|string|in:' . $sourceKeys,
            'icon_color'          => 'nullable|string|max:80',
            'filter_class_id'     => 'nullable|integer|exists:classes,id',
            'filter_section'      => 'nullable|string|max:10',
            'filter_gender'       => 'nullable|in:male,female,other',
            'filter_student_type' => 'nullable|in:day_scholar,hosteller,day_boarder',
            'filter_status'       => 'nullable|in:active,inactive,transferred,left,alumni,all',
            'is_active'           => 'nullable|boolean',
        ]);

        $source = $validated['source'] ?? $widget->source ?? 'student_count';
        $config = $catalog[$source] ?? ($catalog['student_count'] ?? []);

        // Prevent updating to a source that already exists on another active card
        if ($source !== 'student_count') {
            $duplicate = DashboardWidget::where('is_active', true)
                ->where('source', $source)
                ->where('id', '!=', $id)
                ->first();
            if ($duplicate) {
                return redirect()->route('dashboard')->with('error', "Another card (\"{$duplicate->name}\") already uses this source. Duplicate cards are not allowed.");
            }
        }

        $filters = $widget->filters ?? [];
        if (!empty($config['has_student_filters'])) {
            $filters = array_filter([
                'class_id'     => $validated['filter_class_id']    ?? null,
                'section'      => $validated['filter_section']      ?? null,
                'gender'       => $validated['filter_gender']       ?? null,
                'student_type' => $validated['filter_student_type'] ?? null,
                'status'       => $validated['filter_status']       ?? 'active',
            ], fn($v) => $v !== null && $v !== '');
        }

        $group  = $config['group'] ?? 'general';
        $module = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($group)));

        $widget->update([
            'name'       => $validated['name'],
            'module'     => $module,
            'source'     => $source,
            'filters'    => $filters,
            'icon_color' => $validated['icon_color'] ?? $widget->icon_color,
            'is_active'  => $request->boolean('is_active', $widget->is_active),
        ]);

        static::clearCache();

        return redirect()->route('dashboard')->with('success', "Card \"{$widget->name}\" updated.");
    }

    /**
     * Apply dashboard settings: update active brief info widgets and optionally single value cards.
     * POST /dashboard/widgets/apply-settings
     */
    public function applySettings(Request $request)
    {
        $allowedRoles = ['owner', 'admin', 'principal', 'vice_principal', 'it_admin', 'super_admin'];
        if (!auth()->user()->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized');
        }

        $kpiAliasMap = [
            'upcoming_events'  => 'events_upcoming',
            'school_notices'   => 'notices_active',
            'circulars_orders' => 'circulars_active',
            'events_upcoming'  => 'events_upcoming',
            'notices_active'   => 'notices_active',
            'circulars_active' => 'circulars_active',
        ];

        $detailedAliasMap = [
            'events_upcoming'  => 'upcoming_events',
            'notices_active'   => 'school_notices',
            'circulars_active' => 'circulars_orders',
            'upcoming_events'  => 'upcoming_events',
            'school_notices'   => 'school_notices',
            'circulars_orders' => 'circulars_orders',
        ];

        $singleValueCatalog = DashboardWidget::singleValueCatalog();
        $allSingleKeys      = array_keys($singleValueCatalog);

        $briefCatalog       = DashboardWidget::briefInfoCatalog();
        $allBriefKeys       = array_keys($briefCatalog);

        // 1. Determine KPI and Detailed selections
        $hasExplicitKpi      = $request->has('kpi_widgets');
        $hasExplicitDetailed = $request->has('detailed_widgets');

        if ($hasExplicitKpi || $hasExplicitDetailed) {
            $rawKpi = $request->input('kpi_widgets', []);
            $normalizedKpi = [];
            if (is_array($rawKpi)) {
                foreach ($rawKpi as $key) {
                    $canon = $kpiAliasMap[$key] ?? $key;
                    if (in_array($canon, $allSingleKeys, true)) {
                        $normalizedKpi[] = $canon;
                    }
                }
            }
            $kpiSelected = array_values(array_unique($normalizedKpi));

            $rawDetailed = $request->input('detailed_widgets', []);
            $normalizedDetailed = [];
            if (is_array($rawDetailed)) {
                foreach ($rawDetailed as $key) {
                    $canon = $detailedAliasMap[$key] ?? $key;
                    if (in_array($canon, $allBriefKeys, true) || isset($briefCatalog[$canon])) {
                        $normalizedDetailed[] = $canon;
                    }
                }
            }
            $detailedSelected = array_values(array_unique($normalizedDetailed));
        } else {
            // Legacy fallback
            $legacySelected = $request->input('selected_widgets', $request->input('widgets', []));
            if (!is_array($legacySelected)) {
                $legacySelected = [];
            }
            $kpiSelected = array_values(array_intersect($legacySelected, $allSingleKeys));
            $normalizedDetailed = [];
            foreach ($legacySelected as $key) {
                $canon = $detailedAliasMap[$key] ?? $key;
                if (in_array($canon, $allBriefKeys, true)) {
                    $normalizedDetailed[] = $canon;
                }
            }
            $detailedSelected = array_values(array_unique($normalizedDetailed));
        }

        // 2. Update KPI Cards (Single Value Cards)
        if ($hasExplicitKpi || !empty($kpiSelected)) {
            // Ensure selected KPI cards exist in DB
            $existingKpi = DashboardWidget::whereIn('source', $allSingleKeys)->pluck('source')->toArray();
            $missingKpi  = array_diff($kpiSelected, $existingKpi);
            if (!empty($missingKpi)) {
                $insertRows = [];
                $order = 1;
                foreach ($missingKpi as $mKey) {
                    $meta = $singleValueCatalog[$mKey] ?? [];
                    $insertRows[] = [
                        'name'       => $meta['label'] ?? ucfirst(str_replace('_', ' ', $mKey)),
                        'module'     => $meta['group'] ?? 'KPI Metrics',
                        'source'     => $mKey,
                        'icon_color' => 'from-indigo-500 to-blue-600',
                        'sort_order' => $order++,
                        'is_active'  => true,
                        'is_default' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DashboardWidget::insert($insertRows);
            }

            // Deactivate unselected KPI cards
            DashboardWidget::whereIn('source', $allSingleKeys)
                ->whereNotIn('source', $kpiSelected)
                ->update(['is_active' => false]);

            // Activate selected KPI cards and sync module name if needed
            if (!empty($kpiSelected)) {
                DashboardWidget::whereIn('source', $kpiSelected)
                    ->update(['is_active' => true]);

                // Ensure Campus & Events KPI cards have proper module
                DashboardWidget::whereIn('source', ['events_upcoming', 'notices_active', 'circulars_active'])
                    ->update(['module' => 'Campus & Events']);
            }
        }

        // 3. Update Detailed Information Widgets
        if ($hasExplicitDetailed || !empty($detailedSelected)) {
            // Ensure all brief widgets exist in DB
            $existingBrief = DashboardWidget::whereIn('source', $allBriefKeys)->pluck('source')->toArray();
            $missingBrief  = array_diff($allBriefKeys, $existingBrief);
            if (!empty($missingBrief)) {
                $insertRows = [];
                $bOrder = 50;
                foreach ($missingBrief as $mKey) {
                    $meta = $briefCatalog[$mKey] ?? [];
                    $insertRows[] = [
                        'name'       => $meta['label'] ?? ucfirst(str_replace('_', ' ', $mKey)),
                        'module'     => $meta['group'] ?? 'Detailed Information',
                        'source'     => $mKey,
                        'icon_color' => 'from-indigo-500 to-blue-600',
                        'sort_order' => $bOrder++,
                        'is_active'  => in_array($mKey, $detailedSelected, true),
                        'is_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DashboardWidget::insert($insertRows);
            }

            // Deactivate unselected detailed widgets
            DashboardWidget::whereIn('source', $allBriefKeys)
                ->whereNotIn('source', $detailedSelected)
                ->update(['is_active' => false]);

            // Activate selected detailed widgets
            if (!empty($detailedSelected)) {
                DashboardWidget::whereIn('source', $detailedSelected)
                    ->update(['is_active' => true]);
            }
        }

        static::clearCache();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok'              => true,
                'message'         => 'Dashboard settings applied successfully.',
                'active_kpis'     => $kpiSelected,
                'active_detailed' => $detailedSelected,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Dashboard settings applied successfully.');
    }

    /**
     * Delete/Hide a widget from dashboard (preserves underlying data).
     * DELETE /dashboard/widgets/{id}
     */
    public function widgetDestroy(int $id)
    {
        // Only management roles can manage custom widgets
        $allowedRoles = ['owner', 'admin', 'principal', 'vice_principal', 'it_admin', 'super_admin'];
        if (!auth()->user()->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized');
        }

        $widget = DashboardWidget::findOrFail($id);
        $name   = $widget->name;

        // Safe removal: update is_active to false. Does NOT delete any actual ERP data.
        $widget->update(['is_active' => false]);

        static::clearCache();

        return redirect()->route('dashboard')->with('success', "Card \"{$name}\" removed from dashboard. You can re-add it anytime from \"+ Add Card\".");
    }

    /**
     * Live preview for the "Add / Edit" slide-over.
     * GET /dashboard/widgets/preview?source=student_count&filter_class_id=3...
     * Returns JSON: {count: 34, display: "34", url: "/students?..."}
     */
    public function widgetPreview(Request $request)
    {
        $source  = $request->input('source', 'student_count');
        $filters = array_filter([
            'class_id'     => $request->input('filter_class_id'),
            'section'      => $request->input('filter_section'),
            'gender'       => $request->input('filter_gender'),
            'student_type' => $request->input('filter_student_type'),
            'status'       => $request->input('filter_status', 'active'),
        ], fn($v) => $v !== null && $v !== '');

        $result = DashboardCardService::preview($source, $filters);

        return response()->json([
            'count'   => $result['value'],
            'display' => $result['display'],
            'url'     => $result['link_url'],
        ]);
    }

    /**
     * Reorder widgets via drag-and-drop.
     * POST /dashboard/widgets/reorder
     * Body: {order: [3, 1, 5, 2]}  (array of widget IDs in new order)
     */
    public function widgetReorder(Request $request)
    {
        // Only management roles can manage custom widgets
        $allowedRoles = ['owner', 'admin', 'principal', 'vice_principal', 'it_admin', 'super_admin'];
        if (!auth()->user()->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized');
        }

        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($request->order as $index => $widgetId) {
            DashboardWidget::where('id', $widgetId)->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true]);
    }

    // ── Standard Registers ────────────────────────────────

    public function generalRegister(Request $request)
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::active()->orderBy('name')->get();

        $query = DB::table('students as s')
            ->leftJoin('student_enrollments as se', function($j) use ($currentYear) {
                $j->on('se.student_id', '=', 's.id')
                  ->where('se.status', 'active');
                if ($currentYear) $j->where('se.academic_year_id', $currentYear->id);
            })
            ->leftJoin('classes as c', 'c.id', '=', 'se.class_id')
            ->where('s.status', 'active')
            ->select('s.id', 's.first_name', 's.last_name', 's.admission_no as admission_number',
                     's.admission_date', 's.dob', 's.gender', 's.religion', 's.caste',
                     's.blood_group', 's.father_name', 's.mother_name', 's.mobile as phone',
                     's.residential_address as address', 'c.name as class_name')
            ->when($request->class_id, fn($q, $v) => $q->where('se.class_id', $v))
            ->when($request->gender, fn($q, $v) => $q->where('s.gender', $v))
            ->orderBy('s.admission_no');

        $students = $query->paginate(50)->withQueryString();

        return view('reports.general-register', compact('students', 'classes', 'currentYear'));
    }

    public function tcRegister(Request $request)
    {
        $students = DB::table('students as s')
            ->leftJoin('student_enrollments as se', function($j) {
                $j->on('se.student_id', '=', 's.id')->where('se.status', 'active');
            })
            ->leftJoin('classes as c', 'c.id', '=', 'se.class_id')
            ->whereNotNull('s.tc_number')
            ->select('s.first_name', 's.last_name', 's.admission_no as admission_number', 's.dob',
                     's.gender', 's.father_name', 's.tc_number', 's.tc_date as issue_date',
                     's.tc_date as leaving_date', 'c.name as last_class')
            ->when($request->from, fn($q, $v) => $q->whereDate('s.tc_date', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->whereDate('s.tc_date', '<=', $v))
            ->orderBy('s.tc_number')
            ->paginate(50)->withQueryString();

        return view('reports.tc-register', compact('students'));
    }

    public function feeCollectionRegister(Request $request)
    {
        $currentYear = AcademicYear::current();
        $from = $request->from ?? today()->startOfMonth()->toDateString();
        $to   = $request->to   ?? today()->toDateString();

        $payments = DB::table('fee_payments as fp')
            ->join('students as s', 's.id', '=', 'fp.student_id')
            ->leftJoin('student_enrollments as se', function($j) use ($currentYear) {
                $j->on('se.student_id', '=', 'fp.student_id')
                  ->where('se.status', 'active');
                if ($currentYear) $j->where('se.academic_year_id', $currentYear->id);
            })
            ->leftJoin('classes as c', 'c.id', '=', 'se.class_id')
            ->leftJoin('fee_heads as fh', 'fh.id', '=', 'fp.fee_head_id')
            ->where('fp.is_cancelled', false)
            ->whereBetween('fp.payment_date', [$from, $to])
            ->select('fp.receipt_number', 'fp.payment_date', 'fp.amount', 'fp.payment_mode',
                     's.first_name', 's.last_name', 's.admission_no as admission_number',
                     'c.name as class_name', 'fh.name as fee_head')
            ->orderBy('fp.payment_date')->orderBy('fp.receipt_number')
            ->paginate(50)->withQueryString();

        $totalAmount = DB::table('fee_payments')
            ->where('is_cancelled', false)
            ->whereBetween('payment_date', [$from, $to])->sum('amount');

        return view('reports.fee-collection-register', compact('payments', 'from', 'to', 'totalAmount'));
    }

    public function attendanceRegister(Request $request)
    {
        $classes     = Classes::active()->orderBy('name')->get();
        $currentYear = AcademicYear::current();

        $month   = $request->month ?? now()->month;
        $year    = $request->year  ?? now()->year;
        $classId = $request->class_id;

        $students = collect();
        $days     = [];
        $records  = collect();

        if ($classId) {
            $start = \Carbon\Carbon::createFromDate($year, $month, 1);
            $end   = $start->copy()->endOfMonth();
            $days  = range(1, $end->day);

            $students = DB::table('student_enrollments as se')
                ->join('students as s', 's.id', '=', 'se.student_id')
                ->where('se.class_id', $classId)
                ->where('se.status', 'active')
                ->when($currentYear, fn($q) => $q->where('se.academic_year_id', $currentYear->id))
                ->orderBy('s.first_name')
                ->select('s.id', 's.first_name', 's.last_name', 's.admission_no')
                ->get();

            $records = DB::table('attendance_records')
                ->whereIn('student_id', $students->pluck('id'))
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->groupBy(fn($r) => $r->student_id . '_' . \Carbon\Carbon::parse($r->date)->day);
        }

        return view('reports.attendance-register', compact(
            'classes', 'students', 'days', 'records', 'month', 'year', 'classId'
        ));
    }
}
