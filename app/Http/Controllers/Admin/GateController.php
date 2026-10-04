<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use App\Models\GateVisitor;
use App\Models\GateBlacklist;
use App\Models\StudentOutpass;
use App\Models\Student;
use App\Models\Employee;
use App\Models\Enquiry;
use App\Models\Vendor;
use App\Models\Department;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GateController extends Controller
{
    /**
     * Display visitor dashboard and list with tabs & filters.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'active'); // active, pending, today, all, my_approvals
        $user = Auth::user();
        $employeeId = $user->employee_id ?? null;

        $query = Visitor::with(['loggedBy', 'hostEmployee', 'student', 'approver'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('visitor_name', 'like', "%{$search}%")
                       ->orWhere('visitor_phone', 'like', "%{$search}%")
                       ->orWhere('pass_number', 'like', "%{$search}%")
                       ->orWhere('company_name', 'like', "%{$search}%")
                       ->orWhere('child_name', 'like', "%{$search}%")
                       ->orWhere('whom_to_meet', 'like', "%{$search}%")
                       ->orWhereHas('student', fn($sq2) => $sq2->where('first_name', 'like', "%{$search}%")->orWhere('admission_no', 'like', "%{$search}%"));
                });
            })
            ->when($request->category, fn($q, $cat) => $q->where('category', $cat))
            ->when($request->department, fn($q, $dept) => $q->where('department', $dept))
            ->when($request->date, fn($q, $date) => $q->whereDate('visit_date', $date));

        // Tab-specific filters
        match ($tab) {
            'active' => $query->activeInside(),
            'pending' => $query->pendingApproval(),
            'today' => $query->today(),
            'my_approvals' => $query->when($employeeId, fn($q) => $q->where('host_employee_id', $employeeId)->pendingApproval(), fn($q) => $q->pendingApproval()),
            'overstayed' => $query->whereNull('out_time')
                                  ->whereIn('status', [Visitor::STATUS_CHECKED_IN, Visitor::STATUS_APPROVED])
                                  ->whereNotNull('expected_exit_time')
                                  ->where('expected_exit_time', '<', now()),
            default => null, // 'all'
        };

        $visitors = $query->latest('id')->paginate(25)->withQueryString();

        // High-level KPI counts
        $insideCount    = Visitor::activeInside()->count();
        $todayTotal     = Visitor::today()->count();
        $pendingCount   = Visitor::pendingApproval()->count();
        $myPendingCount = $employeeId ? Visitor::where('host_employee_id', $employeeId)->pendingApproval()->count() : 0;
        $overstayCount  = Visitor::whereNull('out_time')
            ->whereIn('status', [Visitor::STATUS_CHECKED_IN, Visitor::STATUS_APPROVED])
            ->whereNotNull('expected_exit_time')
            ->where('expected_exit_time', '<', now())
            ->count();
        $blacklistCount = GateBlacklist::where('is_active', true)->count();

        $pendingOutpasses = 0;
        try {
            $pendingOutpasses = StudentOutpass::where('status', 'overdue')->count();
        } catch (\Exception $e) {}

        // Categories list for filter dropdown
        $categories = [
            Visitor::CATEGORY_PARENT    => 'Parent / Guardian',
            Visitor::CATEGORY_ADMISSION => 'Admission Enquiry',
            Visitor::CATEGORY_VENDOR    => 'Vendor / Maintenance',
            Visitor::CATEGORY_INTERVIEW => 'Staff Interview',
            Visitor::CATEGORY_GUEST     => 'Official Guest',
            Visitor::CATEGORY_OTHER     => 'Other / General',
        ];

        // Unique departments for filter
        $departments = Visitor::whereNotNull('department')->distinct()->pluck('department')->filter()->values();

        return view('gate.index', compact(
            'visitors', 'insideCount', 'todayTotal', 'pendingCount', 'myPendingCount',
            'overstayCount', 'blacklistCount', 'pendingOutpasses', 'tab', 'categories', 'departments'
        ));
    }

    /**
     * Show the dynamic visitor registration form.
     */
    public function create(Request $request)
    {
        $staff = Employee::where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'designation', 'department']);

        $departments = Department::orderBy('name')->pluck('name');
        if ($departments->isEmpty()) {
            $departments = collect(['Academic', 'Admissions', 'HR & Recruitment', 'Facilities & Maintenance', 'Administration', 'Principal Office', 'Accounts & Finance', 'Sports']);
        }

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get(['id', 'name', 'contact_person', 'phone']);

        $prefillCategory = $request->get('category', Visitor::CATEGORY_PARENT);

        return view('gate.create', compact('staff', 'departments', 'vendors', 'prefillCategory'));
    }

    /**
     * Store new visitor record and process category dynamic routing & approval triggers.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'visitor_name'            => 'required|string|max:150',
            'category'                => 'required|string|in:parent,admission_enquiry,vendor,interview,guest,other',
            'visitor_phone'           => 'nullable|string|max:20',
            'visitor_email'           => 'nullable|email|max:150',
            'visitor_id_type'         => 'nullable|string|max:50',
            'visitor_id_number'       => 'nullable|string|max:50',
            'purpose'                 => 'required|string|max:255',
            'department'              => 'nullable|string|max:100',
            'whom_to_meet'            => 'nullable|string|max:150',
            'host_employee_id'        => 'nullable|exists:employees,id',
            'expected_exit_time'      => 'nullable|string',
            'visitor_count'           => 'nullable|integer|min:1|max:50',
            'vehicle_type'            => 'nullable|string|max:30',
            'vehicle_number'          => 'nullable|string|max:30',
            'items_carried'           => 'nullable|string',
            'remarks'                 => 'nullable|string',
            'action_type'             => 'nullable|string|in:direct_checkin,request_approval',
            'photo_data'              => 'nullable|string',
            'id_proof_file'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

            // Dynamic fields based on category:
            // 1. Parent
            'student_id'              => 'nullable|exists:students,id',
            'relationship_to_student' => 'nullable|string|max:50',

            // 2. Admission Enquiry
            'child_name'              => 'nullable|string|max:150',
            'grade_applying_for'      => 'nullable|string|max:50',
            'enquiry_source'          => 'nullable|string|max:100',
            'create_admission_lead'   => 'nullable|boolean',

            // 3. Vendor
            'company_name'            => 'nullable|string|max:150',
            'work_order_number'       => 'nullable|string|max:100',
            'vendor_id'               => 'nullable|exists:vendors,id',

            // 4. Staff Interview
            'candidate_ref_number'    => 'nullable|string|max:100',
            'job_role_applied'        => 'nullable|string|max:100',
        ]);

        // 1. Blacklist Check
        if (GateBlacklist::isBlacklisted($data['visitor_phone'] ?? null, $data['visitor_id_number'] ?? null)) {
            return back()->withInput()->withErrors([
                'visitor_phone' => 'SECURITY ALERT: This visitor/ID is flagged in the Gate Blacklist and cannot be admitted without supervisor override.'
            ]);
        }

        // 2. Process Webcam Photo (Base64)
        if (!empty($data['photo_data']) && str_starts_with($data['photo_data'], 'data:image')) {
            $base64 = explode(',', $data['photo_data'])[1];
            $photoPath = 'gate/visitors/' . uniqid('vp_') . '.jpg';
            Storage::disk('public')->put($photoPath, base64_decode($base64));
            $data['visitor_photo'] = $photoPath;
        }
        unset($data['photo_data']);

        // 3. Process Scanned ID Proof Document Upload
        if ($request->hasFile('id_proof_file')) {
            $docPath = $request->file('id_proof_file')->store('gate/documents', 'public');
            $data['id_proof_document'] = $docPath;
        }
        unset($data['id_proof_file']);

        // 4. Determine Host and Department relationships
        if (!empty($data['host_employee_id'])) {
            $hostEmployee = Employee::find($data['host_employee_id']);
            if ($hostEmployee) {
                $data['whom_to_meet'] = $hostEmployee->full_name . ($hostEmployee->designation ? ' (' . $hostEmployee->designation . ')' : '');
                if (empty($data['department']) && $hostEmployee->department) {
                    $data['department'] = $hostEmployee->department;
                }
            }
        }

        // 5. Calculate Timestamps
        $data['visit_date'] = today();
        $actionType = $data['action_type'] ?? 'direct_checkin';
        unset($data['action_type']);

        if ($actionType === 'request_approval') {
            $data['status'] = Visitor::STATUS_PENDING;
            $data['in_time'] = null; // will be stamped when approved
        } else {
            $data['status'] = Visitor::STATUS_CHECKED_IN;
            $data['in_time'] = now();
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        }

        if (!empty($data['expected_exit_time'])) {
            try {
                // If only time provided e.g. "14:30", combine with today's date
                if (strlen($data['expected_exit_time']) <= 5) {
                    $data['expected_exit_time'] = Carbon::parse(today()->format('Y-m-d') . ' ' . $data['expected_exit_time']);
                } else {
                    $data['expected_exit_time'] = Carbon::parse($data['expected_exit_time']);
                }
            } catch (\Exception $e) {
                $data['expected_exit_time'] = null;
            }
        }

        $data['visitor_count'] = $data['visitor_count'] ?? 1;
        $data['logged_by']     = Auth::id();
        $data['badge_color']   = Visitor::getDefaultBadgeColor($data['category']);

        // 6. Dynamic CRM / ERP Link: Admission Enquiry Integration
        if ($data['category'] === Visitor::CATEGORY_ADMISSION && ($request->boolean('create_admission_lead') || !empty($data['child_name']))) {
            try {
                $enquiryNumber = 'ENQ-' . now()->format('Ymd') . '-' . str_pad((string) (Enquiry::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
                $enquiry = Enquiry::create([
                    'enquiry_number' => $enquiryNumber,
                    'student_name'   => $data['child_name'] ?? $data['visitor_name'],
                    'parent_name'    => $data['visitor_name'],
                    'parent_mobile'  => $data['visitor_phone'] ?? '0000000000',
                    'parent_email'   => $data['visitor_email'],
                    'source'         => $data['enquiry_source'] ?? 'Walk-in / Gate Enquiry',
                    'notes'          => 'Registered via Visitor Management Gate Entry: ' . $data['purpose'],
                    'status'         => 'new',
                    'follow_up_date' => today()->addDays(2),
                    'created_by'     => Auth::id(),
                ]);
                $data['enquiry_id'] = $enquiry->id;
            } catch (\Exception $e) {
                // Ignore failure if Enquiry table schema has additional strict columns
            }
        }
        unset($data['create_admission_lead']);

        // 7. Create Visitor Record
        $visitor = Visitor::create($data);

        // 8. Record Audit Log
        AuditLog::record('created', $visitor, [], $visitor->toArray(), 'visitor_management');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $visitor->status === Visitor::STATUS_PENDING
                    ? 'Visit request submitted for Host approval.'
                    : 'Visitor checked in successfully.',
                'visitor' => $visitor,
                'pass_url' => route('gate.pass', $visitor->id),
            ]);
        }

        if ($visitor->status === Visitor::STATUS_PENDING) {
            return redirect()->route('gate.index', ['tab' => 'pending'])
                ->with('success', "Visit request for '{$visitor->visitor_name}' sent to {$visitor->whom_to_meet} for approval. Pass #{$visitor->pass_number}");
        }

        return redirect()->route('gate.pass', $visitor->id)
            ->with('success', "Visitor '{$visitor->visitor_name}' logged and checked in successfully.");
    }

    /**
     * Show printable Visitor Pass (Badges / Thermal Slips).
     */
    public function pass(int $id)
    {
        $visitor = Visitor::with(['loggedBy', 'hostEmployee', 'student', 'approver'])->findOrFail($id);
        $school = \App\Models\SchoolSetting::first();
        return view('gate.pass', compact('visitor', 'school'));
    }

    /**
     * Detailed Visitor Profile & Inspection View.
     */
    public function show(int $id)
    {
        $visitor = Visitor::with(['loggedBy', 'hostEmployee', 'student', 'enquiry', 'vendor', 'approver', 'rejecter'])->findOrFail($id);
        $school = \App\Models\SchoolSetting::first();
        return view('gate.show', compact('visitor', 'school'));
    }

    /**
     * Host/Admin Action: Approve incoming visit request.
     */
    public function approve(Request $request, int $id)
    {
        $visitor = Visitor::findOrFail($id);
        $notes = $request->input('notes');

        $visitor->approve(Auth::user(), $notes);
        AuditLog::record('approved', $visitor, ['status' => Visitor::STATUS_PENDING], ['status' => Visitor::STATUS_CHECKED_IN], 'visitor_management');

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => "Visit request for {$visitor->visitor_name} approved.",
                'pass_url' => route('gate.pass', $visitor->id),
            ]);
        }

        return back()->with('success', "Visit request for '{$visitor->visitor_name}' approved. Gate pass is now active.");
    }

    /**
     * Host/Admin Action: Reject incoming visit request with reason.
     */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $visitor = Visitor::findOrFail($id);
        $visitor->reject(Auth::user(), $request->input('rejection_reason'));
        AuditLog::record('rejected', $visitor, ['status' => Visitor::STATUS_PENDING], ['status' => Visitor::STATUS_REJECTED, 'reason' => $request->input('rejection_reason')], 'visitor_management');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Visit request for {$visitor->visitor_name} has been rejected.",
            ]);
        }

        return back()->with('success', "Visit request for '{$visitor->visitor_name}' was rejected.");
    }

    /**
     * Gatekeeper Action: Checkout visitor (Logs out_time).
     */
    public function checkout(Request $request, int $id)
    {
        $visitor = Visitor::findOrFail($id);
        $visitor->checkOut();

        AuditLog::record('checked_out', $visitor, ['out_time' => null], ['out_time' => now()], 'visitor_management');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Visitor {$visitor->visitor_name} checked out at " . now()->format('h:i A'),
                'out_time' => now()->format('h:i A'),
            ]);
        }

        return back()->with('success', "Visitor '{$visitor->visitor_name}' checked out at " . now()->format('h:i A'));
    }

    /**
     * 1-Second Fast QR Scan Check-out Endpoint.
     */
    public function scanCheckout(Request $request)
    {
        $code = trim($request->input('code') ?? $request->input('pass_token') ?? '');

        if (empty($code)) {
            return response()->json(['success' => false, 'message' => 'Please provide a pass token or pass number.'], 422);
        }

        $visitor = Visitor::where('pass_token', $code)
            ->orWhere('pass_number', $code)
            ->first();

        if (!$visitor) {
            return response()->json(['success' => false, 'message' => 'Pass not found or invalid barcode.'], 404);
        }

        if ($visitor->status === Visitor::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'status'  => 'pending',
                'message' => "Pass #{$visitor->pass_number} is currently pending approval by {$visitor->whom_to_meet}.",
                'visitor' => $visitor,
            ]);
        }

        if ($visitor->status === Visitor::STATUS_REJECTED) {
            return response()->json([
                'success' => false,
                'status'  => 'rejected',
                'message' => "Pass #{$visitor->pass_number} was rejected. Reason: {$visitor->rejection_reason}",
                'visitor' => $visitor,
            ]);
        }

        if ($visitor->out_time) {
            return response()->json([
                'success' => true,
                'already_checked_out' => true,
                'message' => "Visitor '{$visitor->visitor_name}' was already checked out at " . $visitor->out_time->format('d M Y, h:i A'),
                'visitor' => $visitor,
            ]);
        }

        // Perform Check-out
        $visitor->checkOut();
        AuditLog::record('checked_out_qr', $visitor, ['out_time' => null], ['out_time' => now()], 'visitor_management');

        $durationMinutes = $visitor->in_time ? $visitor->in_time->diffInMinutes($visitor->out_time) : 0;
        $hours = floor($durationMinutes / 60);
        $mins  = $durationMinutes % 60;
        $durationFormatted = ($hours > 0 ? "{$hours}h " : '') . "{$mins}m";

        return response()->json([
            'success'          => true,
            'checked_out'      => true,
            'message'          => "✓ CHECKED OUT: {$visitor->visitor_name} (Stayed: {$durationFormatted})",
            'visitor'          => $visitor,
            'duration'         => $durationFormatted,
            'out_time_display' => $visitor->out_time->format('h:i A'),
        ]);
    }

    /**
     * Dedicated QR & Barcode Scanner Page for Security Gates.
     */
    public function scanner()
    {
        $recentCheckouts = Visitor::whereNotNull('out_time')
            ->whereDate('out_time', today())
            ->latest('out_time')
            ->limit(8)
            ->get();

        $activeInsideCount = Visitor::activeInside()->count();

        return view('gate.scanner', compact('recentCheckouts', 'activeInsideCount'));
    }

    /**
     * Public / Gatekeeper Pass Verification JSON API.
     */
    public function verifyPass(string $token)
    {
        $visitor = Visitor::with(['hostEmployee', 'student'])->where('pass_token', $token)->orWhere('pass_number', $token)->first();

        if (!$visitor) {
            return response()->json(['valid' => false, 'message' => 'Invalid or expired visitor pass.'], 404);
        }

        return response()->json([
            'valid'         => true,
            'pass_number'   => $visitor->pass_number,
            'name'          => $visitor->visitor_name,
            'category'      => $visitor->category_label,
            'phone'         => $visitor->visitor_phone,
            'purpose'       => $visitor->purpose,
            'whom_to_meet'  => $visitor->whom_to_meet,
            'department'    => $visitor->department,
            'visitor_count' => $visitor->visitor_count,
            'vehicle'       => $visitor->vehicle_number ? ($visitor->vehicle_type . ' - ' . $visitor->vehicle_number) : 'None',
            'in_time'       => $visitor->in_time ? $visitor->in_time->format('d M Y, h:i A') : 'Pending',
            'out_time'      => $visitor->out_time ? $visitor->out_time->format('d M Y, h:i A') : null,
            'status'        => $visitor->status,
            'is_inside'     => $visitor->isInside(),
            'photo_url'     => $visitor->visitor_photo ? Storage::url($visitor->visitor_photo) : null,
        ]);
    }

    /**
     * Autocomplete API: Search active students for Parent/Guardian category.
     */
    public function lookupStudents(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $students = Student::where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                      ->orWhere('last_name', 'like', "%{$q}%")
                      ->orWhere('admission_no', 'like', "%{$q}%")
                      ->orWhere('roll_number', 'like', "%{$q}%");
            })
            ->limit(15)
            ->get([
                'id', 'first_name', 'last_name', 'admission_no', 'roll_number',
                'father_name', 'father_mobile', 'father_email',
                'mother_name', 'mother_mobile', 'mother_email',
                'guardian_name', 'guardian_mobile', 'guardian_relation',
                'mobile'
            ]);

        $formatted = $students->map(function ($s) {
            return [
                'id'               => $s->id,
                'name'             => trim($s->first_name . ' ' . $s->last_name),
                'admission_no'     => $s->admission_no,
                'roll_number'      => $s->roll_number,
                'father_name'      => $s->father_name,
                'father_mobile'    => $s->father_mobile ?? $s->mobile,
                'father_email'     => $s->father_email,
                'mother_name'      => $s->mother_name,
                'mother_mobile'    => $s->mother_mobile ?? $s->mobile,
                'mother_email'     => $s->mother_email,
                'guardian_name'    => $s->guardian_name,
                'guardian_mobile'  => $s->guardian_mobile ?? $s->mobile,
                'guardian_relation'=> $s->guardian_relation,
                'mobile'           => $s->mobile,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Autocomplete API: Search active staff/teachers for Person To Meet.
     */
    public function lookupHosts(Request $request)
    {
        $q = trim($request->input('q', ''));

        $staff = Employee::where('is_active', true)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sq) use ($q) {
                    $sq->where('first_name', 'like', "%{$q}%")
                       ->orWhere('last_name', 'like', "%{$q}%")
                       ->orWhere('designation', 'like', "%{$q}%")
                       ->orWhere('department', 'like', "%{$q}%");
                });
            })
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'designation', 'department', 'official_email', 'mobile']);

        $formatted = $staff->map(function ($e) {
            return [
                'id'          => $e->id,
                'name'        => trim($e->first_name . ' ' . $e->last_name),
                'designation' => $e->designation,
                'department'  => $e->department,
                'email'       => $e->official_email ?? $e->personal_email,
                'display'     => trim($e->first_name . ' ' . $e->last_name) . ($e->designation ? ' (' . $e->designation . ')' : '') . ($e->department ? ' - ' . $e->department : ''),
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Comprehensive Visitor Analytics & Logs Report with CSV Export.
     */
    public function report(Request $request)
    {
        $from = $request->from ?? today()->subDays(7)->toDateString();
        $to   = $request->to   ?? today()->toDateString();
        $category = $request->category;
        $department = $request->department;

        $query = Visitor::with(['loggedBy', 'hostEmployee', 'student'])
            ->whereBetween('visit_date', [$from, $to])
            ->when($category, fn($q, $c) => $q->where('category', $c))
            ->when($department, fn($q, $d) => $q->where('department', $d));

        $visitors = (clone $query)->orderBy('in_time', 'desc')->get();

        $totalIn     = $visitors->count();
        $totalOut    = $visitors->whereNotNull('out_time')->count();
        $stillInside = $visitors->filter(fn($v) => $v->isInside())->count();

        // Analytics breakdowns
        $byCategory = $visitors->groupBy('category')->map(fn($group) => $group->count());
        $byDepartment = $visitors->whereNotNull('department')->groupBy('department')->map(fn($group) => $group->count());

        // Calculate average duration in minutes
        $durations = $visitors->filter(fn($v) => $v->in_time && $v->out_time)->map(fn($v) => $v->in_time->diffInMinutes($v->out_time));
        $avgDuration = $durations->isNotEmpty() ? round($durations->average()) : 0;

        if ($request->export === 'csv') {
            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=visitor-report-{$from}-to-{$to}.csv"
            ];

            $callback = function () use ($visitors) {
                $f = fopen('php://output', 'w');
                fputcsv($f, [
                    '#', 'Pass No', 'Visitor Name', 'Category', 'Phone', 'ID Proof', 'Purpose',
                    'Host / Person to Meet', 'Department', 'Accompanying Count', 'Vehicle',
                    'Visit Date', 'In Time', 'Out Time', 'Duration (Mins)', 'Status'
                ]);

                foreach ($visitors as $i => $v) {
                    $duration = ($v->in_time && $v->out_time) ? $v->in_time->diffInMinutes($v->out_time) : '';
                    fputcsv($f, [
                        $i + 1,
                        $v->pass_number,
                        $v->visitor_name,
                        $v->category_label,
                        $v->visitor_phone ?? '—',
                        $v->visitor_id_type ? ($v->visitor_id_type . ': ' . $v->visitor_id_number) : '—',
                        $v->purpose,
                        $v->whom_to_meet ?? '—',
                        $v->department ?? '—',
                        $v->visitor_count,
                        $v->vehicle_number ? ($v->vehicle_type . ' ' . $v->vehicle_number) : '—',
                        $v->visit_date?->format('d/m/Y') ?? '—',
                        $v->in_time?->format('d/m/Y H:i') ?? '—',
                        $v->out_time?->format('d/m/Y H:i') ?? '—',
                        $duration,
                        $v->status,
                    ]);
                }
                fclose($f);
            };

            return response()->stream($callback, 200, $headers);
        }

        $categories = [
            Visitor::CATEGORY_PARENT    => 'Parent / Guardian',
            Visitor::CATEGORY_ADMISSION => 'Admission Enquiry',
            Visitor::CATEGORY_VENDOR    => 'Vendor / Maintenance',
            Visitor::CATEGORY_INTERVIEW => 'Staff Interview',
            Visitor::CATEGORY_GUEST     => 'Official Guest',
            Visitor::CATEGORY_OTHER     => 'Other / General',
        ];

        $departmentsList = Visitor::whereNotNull('department')->distinct()->pluck('department')->filter()->values();

        return view('gate.report', compact(
            'visitors', 'from', 'to', 'totalIn', 'totalOut', 'stillInside',
            'byCategory', 'byDepartment', 'avgDuration', 'categories', 'departmentsList', 'category', 'department'
        ));
    }

    // ── Gate Blacklist Sub-Module ────────────────────────────

    public function blacklist(Request $request)
    {
        $list = GateBlacklist::with('addedBy')
            ->when($request->search, fn($q, $v) => $q->where(fn($q2) =>
                $q2->where('name', 'like', "%{$v}%")
                   ->orWhere('phone', 'like', "%{$v}%")
                   ->orWhere('id_number', 'like', "%{$v}%")))
            ->latest()->paginate(20)->withQueryString();

        return view('gate.blacklist', compact('list'));
    }

    public function storeBlacklist(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'phone'     => 'nullable|string|max:20',
            'id_number' => 'nullable|string|max:50',
            'reason'    => 'required|string',
        ]);
        $data['added_by'] = Auth::id();
        GateBlacklist::create($data);

        AuditLog::record('blacklist_added', null, [], $data, 'visitor_management');

        return back()->with('success', 'Visitor added to Gate Blacklist.');
    }

    public function removeBlacklist(int $id)
    {
        $entry = GateBlacklist::findOrFail($id);
        $entry->update(['is_active' => false]);

        AuditLog::record('blacklist_removed', $entry, ['is_active' => true], ['is_active' => false], 'visitor_management');

        return back()->with('success', 'Visitor removed from Gate Blacklist.');
    }

    // ── Student Outpass Sub-Module ───────────────────────────

    public function outpass(Request $request)
    {
        $outpasses = StudentOutpass::with('student')
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest('out_time')->paginate(20)->withQueryString();

        // Auto-mark overdue outpasses
        StudentOutpass::where('status', 'active')
            ->where('expected_return', '<', now())
            ->update(['status' => 'overdue']);

        return view('gate.outpass', compact('outpasses'));
    }

    public function createOutpass()
    {
        $students = Student::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'admission_no']);
        $passNumber = 'OP-' . now()->format('Ymd') . '-' . str_pad(
            StudentOutpass::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT
        );
        return view('gate.outpass-form', compact('students', 'passNumber'));
    }

    public function storeOutpass(Request $request)
    {
        $data = $request->validate([
            'student_id'      => 'required|exists:students,id',
            'pass_number'     => 'required|unique:student_outpasses,pass_number',
            'out_time'        => 'required|date',
            'expected_return' => 'nullable|date|after:out_time',
            'reason'          => 'required|string|max:200',
            'authorized_by'   => 'required|string|max:100',
        ]);
        $data['issued_by'] = Auth::id();
        $data['status']    = 'active';
        StudentOutpass::create($data);

        return redirect()->route('gate.outpass')->with('success', 'Student outpass issued.');
    }

    public function returnOutpass(int $id)
    {
        StudentOutpass::findOrFail($id)->update([
            'actual_return' => now(),
            'status'        => 'returned',
        ]);
        return back()->with('success', 'Student marked returned.');
    }
}
