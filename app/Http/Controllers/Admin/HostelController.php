<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\FeeHead;
use App\Models\Hostel;
use App\Models\HostelAllotment;
use App\Models\HostelComplaint;
use App\Models\HostelFloor;
use App\Models\HostelRoom;
use App\Models\HostelVisitor;
use App\Models\HostelVisitingHours;
use App\Models\MessFeedback;
use App\Models\MessRebate;
use App\Models\Student;
use App\Models\StudentFeeCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HostelController extends Controller
{
    public function index()
    {
        $data = Cache::remember('hostel_index_overview', 60, function () {
            $hostels = Hostel::withCount([
                'rooms',
                'rooms as occupied_count' => fn($q) => $q->where('status', 'full'),
            ])->get();

            $roomStats = DB::table('hostel_rooms')->selectRaw("
                count(*) as total,
                count(case when status = 'full' then 1 end) as occupied
            ")->first();

            $totalRooms     = (int) ($roomStats->total ?? 0);
            $occupiedRooms  = (int) ($roomStats->occupied ?? 0);
            $availableRooms = max(0, $totalRooms - $occupiedRooms);
            $residents      = HostelAllotment::where('status', 'active')->count();
            $occupancyPct   = $totalRooms > 0 ? round($occupiedRooms / $totalRooms * 100, 1) : 0;

            $stats = compact('totalRooms', 'occupiedRooms', 'availableRooms', 'residents', 'occupancyPct');

            $pendingComplaints = 0;
            try {
                $pendingComplaints = HostelComplaint::where('status', 'open')->count();
            } catch (\Exception $e) {}

            $pendingOutpasses = 0;
            try {
                $pendingOutpasses = DB::table('hostel_outpasses')->where('status', 'pending')->count();
            } catch (\Exception $e) {}

            $overdueOutpasses = 0;
            try {
                $overdueOutpasses = DB::table('hostel_outpasses')
                    ->where('status', 'approved')
                    ->where('return_date', '<', today()->toDateString())
                    ->count();
            } catch (\Exception $e) {}

            return compact('hostels', 'stats', 'pendingComplaints', 'pendingOutpasses', 'overdueOutpasses');
        });

        return view('hostel.index', $data);
    }

    public function storeBuilding(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:200',
            'type'           => 'required|in:boys,girls,co-ed,staff',
            'total_capacity' => 'nullable|integer|min:1',
            'address'        => 'nullable|string|max:500',
            'contact'        => 'nullable|string|max:50',
        ]);
        Hostel::create(array_merge($data, ['is_active' => true]));
        return back()->with('success', 'Hostel building added.');
    }

    public function updateBuilding(Request $request, int $id)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:200',
            'type'           => 'required|in:boys,girls,co-ed,staff',
            'total_capacity' => 'nullable|integer|min:1',
            'address'        => 'nullable|string|max:500',
            'contact'        => 'nullable|string|max:50',
        ]);
        Hostel::findOrFail($id)->update($data);
        return back()->with('success', 'Hostel updated.');
    }

    public function rooms(Request $request)
    {
        $rooms = HostelRoom::with('hostel')
            ->when($request->hostel_id, fn($q, $v) => $q->where('hostel_id', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->paginate(25);
        $hostels = Hostel::all();
        return view('hostel.rooms', compact('rooms', 'hostels'));
    }

    public function storeRoom(Request $request)
    {
        $request->validate([
            'hostel_id'   => 'required|exists:hostels,id',
            'room_number' => 'required|string|max:20',
            'capacity'    => 'required|integer|min:1|max:20',
            'room_type'   => 'nullable|string|max:50',
            'floor'       => 'nullable|string|max:10',
            'monthly_fee' => 'nullable|numeric|min:0',
        ]);
        HostelRoom::create([
            'hostel_id'   => $request->hostel_id,
            'room_number' => $request->room_number,
            'capacity'    => $request->capacity,
            'room_type'   => $request->room_type ?? 'shared',
            'floor'       => $request->floor,
            'monthly_fee' => $request->monthly_fee ?? 0,
            'status'      => 'available',
            'occupied'    => 0,
            'is_ac'       => $request->boolean('is_ac'),
            'is_attached_bathroom' => $request->boolean('is_attached_bathroom'),
        ]);
        return redirect()->route('hostel.rooms')->with('success', 'Room ' . $request->room_number . ' added.');
    }

    public function updateRoom(Request $request, int $id)
    {
        $room = HostelRoom::findOrFail($id);
        $request->validate([
            'room_number' => 'required|string|max:20',
            'capacity'    => 'required|integer|min:1|max:20',
        ]);
        $room->update($request->only('room_number', 'capacity', 'room_type', 'floor', 'monthly_fee', 'status') + [
            'is_ac'                => $request->boolean('is_ac'),
            'is_attached_bathroom' => $request->boolean('is_attached_bathroom'),
        ]);
        return redirect()->route('hostel.rooms')->with('success', 'Room updated.');
    }

    public function destroyRoom(int $id)
    {
        abort_unless(auth()->user()->can('manage hostel'), 403);
        $room = HostelRoom::findOrFail($id);
        if ($room->occupied > 0) {
            return back()->with('error', 'Cannot delete room with active occupants.');
        }
        $room->delete();
        return redirect()->route('hostel.rooms')->with('success', 'Room deleted.');
    }

    public function allotment(Request $request)
    {
        $rooms    = HostelRoom::with('hostel')->where('status', 'available')->get();
        $students = Student::where('status', 'active')->orderBy('first_name')->get();
        $currentYear = AcademicYear::current();
        $allotments  = HostelAllotment::with(['student', 'room.hostel'])
            ->where('status', 'active')->latest()->paginate(25)->withQueryString();
        return view('hostel.allotment', compact('rooms', 'students', 'allotments', 'currentYear'));
    }

    public function saveAllotment(Request $request)
    {
        $request->validate([
            'student_id'      => 'required|exists:students,id',
            'room_id'         => 'required|exists:hostel_rooms,id',
            'allotment_date'  => 'required|date',
            'monthly_fee'     => 'nullable|numeric|min:0',
        ]);

        $currentYear = AcademicYear::current();

        $allotment = HostelAllotment::create([
            'student_id'            => $request->student_id,
            'room_id'               => $request->room_id,
            'academic_year_id'      => $currentYear?->id,
            'allotment_date'        => $request->allotment_date,
            'monthly_fee'           => $request->monthly_fee ?? 0,
            'status'                => 'active',
            'mess_included'         => $request->boolean('mess_included', true),
            'mess_exclusion_reason' => $request->mess_exclusion_reason,
        ]);

        HostelRoom::where('id', $request->room_id)->increment('occupied');
        HostelRoom::where('id', $request->room_id)
            ->whereColumn('occupied', '>=', 'capacity')
            ->update(['status' => 'full']);

        // Auto-link hostel fee to main Fee Management module
        if (($request->monthly_fee ?? 0) > 0 && $currentYear) {
            $hostelHead = FeeHead::firstOrCreate(
                ['code' => 'HOSTEL_FEE'],
                ['name' => 'Hostel Fee', 'is_active' => true, 'sort_order' => 90]
            );
            StudentFeeCharge::updateOrCreate(
                [
                    'student_id'      => $request->student_id,
                    'academic_year_id'=> $currentYear->id,
                    'source'          => 'hostel_allotment',
                ],
                [
                    'fee_head_id'  => $hostelHead->id,
                    'amount'       => $request->monthly_fee,
                    'description'  => 'Hostel Fee (auto-linked from allotment)',
                    'source_id'    => $allotment->id,
                    'is_active'    => true,
                    'created_by'   => Auth::id(),
                ]
            );
        }

        return redirect()->route('hostel.allotment')->with('success', 'Student allotted to room. Hostel fee linked to fee module.');
    }

    public function mess()
    {
        $hostels = Hostel::where('is_active', true)->get();
        return view('hostel.mess', compact('hostels'));
    }

    public function outpass()
    {
        $allotments = \App\Models\HostelOutpass::with(['student', 'allotment.room'])
            ->latest()
            ->paginate(20);
        return view('hostel.outpass', compact('allotments'));
    }

    public function feeConfig()
    {
        $hostels       = Hostel::where('is_active', true)->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $feeStructures = \App\Models\HostelFeeStructure::with(['hostel', 'academicYear'])->get();
        return view('hostel.fee-config', compact('hostels', 'academicYears', 'feeStructures'));
    }

    public function storeFeeConfig(Request $request)
    {
        $request->validate(['hostel_id' => 'required|exists:hostels,id', 'room_type' => 'required|string', 'monthly_fee' => 'required|numeric|min:0']);
        \App\Models\HostelFeeStructure::updateOrCreate(
            ['hostel_id' => $request->hostel_id, 'room_type' => $request->room_type, 'academic_year_id' => $request->academic_year_id],
            array_merge(
                $request->only(['monthly_fee', 'admission_fee', 'mess_fee', 'security_deposit']),
                ['mess_included_default' => $request->boolean('mess_included_default', true)]
            )
        );
        return back()->with('success', 'Fee structure saved.');
    }

    public function visitors(Request $request)
    {
        $hostels  = Hostel::where('is_active', true)->get();
        $students = Student::where('status', 'active')->orderBy('first_name')->get();
        $visitors = HostelVisitor::with(['student', 'hostel'])
            ->when($request->date, fn($q, $v) => $q->whereDate('in_time', $v))
            ->when($request->hostel_id, fn($q, $v) => $q->where('hostel_id', $v))
            ->latest()->paginate(20)->withQueryString();
        return view('hostel.visitors', compact('hostels', 'visitors', 'students'));
    }

    public function storeVisitor(Request $request)
    {
        $request->validate([
            'visitor_name' => 'required|string|max:100',
            'student_id'   => 'required|exists:students,id',
            'hostel_id'    => 'required|exists:hostels,id',
        ]);

        // Enforce visiting hours
        if (!HostelVisitingHours::isCurrentlyAllowed($request->hostel_id)) {
            $hours = HostelVisitingHours::where('is_active', true)
                ->where(fn($q) => $q->whereIn('day_type', ['all', now()->isWeekend() ? 'weekend' : 'weekday']))
                ->first();
            $msg = $hours
                ? "Visiting not allowed now. Permitted: {$hours->from_time} – {$hours->to_time}."
                : 'Visiting hours not configured.';
            return back()->withErrors(['visitor_name' => $msg])->withInput();
        }

        $photoPath = null;
        if ($request->filled('photo_data')) {
            // Base64 webcam photo
            $data = $request->input('photo_data');
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $img  = base64_decode(substr($data, strpos($data, ',') + 1));
                $ext  = strtolower($type[1]) === 'png' ? 'png' : 'jpg';
                $photoPath = 'hostel/visitors/' . uniqid() . '.' . $ext;
                Storage::disk('public')->put($photoPath, $img);
            }
        }

        $validHours = (int)($request->pass_valid_hours ?? 2);
        $visitor = HostelVisitor::create(array_merge(
            $request->only(['visitor_name', 'visitor_phone', 'visitor_mobile', 'id_type', 'id_number',
                            'relation', 'student_id', 'hostel_id', 'purpose', 'destination',
                            'vehicle_number']),
            [
                'visitor_photo'    => $photoPath,
                'entry_time'       => now(),
                'in_time'          => now()->format('H:i'),
                'pass_valid_until' => now()->addHours($validHours),
                'logged_by'        => Auth::id(),
            ]
        ));

        return redirect()->route('hostel.visitors.pass', $visitor->id)->with('success', 'Visitor logged. Pass generated.');
    }

    public function checkoutVisitor(int $id)
    {
        HostelVisitor::findOrFail($id)->update(['out_time' => now()->format('H:i'), 'exit_time' => now()]);
        return back()->with('success', 'Visitor checked out.');
    }

    public function visitorPass(int $id)
    {
        $visitor = HostelVisitor::with(['student', 'hostel'])->findOrFail($id);
        $school  = \App\Models\SchoolSetting::first();
        return view('hostel.visitor-pass', compact('visitor', 'school'));
    }

    public function visitingHoursConfig()
    {
        $hostels = Hostel::where('is_active', true)->get();
        $rules   = HostelVisitingHours::with('hostel')->orderBy('day_type')->get();
        return view('hostel.visiting-hours', compact('hostels', 'rules'));
    }

    public function storeVisitingHours(Request $request)
    {
        $request->validate([
            'day_type'   => 'required|in:all,weekday,weekend,holiday',
            'from_time'  => 'required|date_format:H:i',
            'to_time'    => 'required|date_format:H:i|after:from_time',
        ]);
        HostelVisitingHours::create($request->only(['hostel_id', 'day_type', 'from_time', 'to_time', 'note']) + ['is_active' => true]);
        return back()->with('success', 'Visiting hours rule saved.');
    }

    public function deleteVisitingHours(int $id)
    {
        HostelVisitingHours::findOrFail($id)->delete();
        return back()->with('success', 'Rule deleted.');
    }

    public function complaints(Request $request)
    {
        $hostels   = Hostel::where('is_active', true)->get();
        $students  = Student::where('status', 'active')->orderBy('first_name')->get();
        $employees = Employee::where('is_active', true)->orderBy('first_name')->get(['id','first_name','last_name','designation']);
        $complaints = HostelComplaint::with(['student', 'assignedTo', 'room'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->priority, fn($q, $v) => $q->where('priority', $v))
            ->when($request->category, fn($q, $v) => $q->where('category', $v))
            ->latest()->paginate(20)->withQueryString();
        return view('hostel.complaints', compact('hostels', 'students', 'employees', 'complaints'));
    }

    public function storeComplaint(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,id',
            'category'    => 'required|string',
            'description' => 'required|string|min:5',
        ]);
        HostelComplaint::create(array_merge(
            $request->only(['student_id', 'category', 'description', 'priority', 'room_id']),
            ['status' => 'open', 'reported_by' => Auth::id()]
        ));
        return back()->with('success', 'Complaint registered.');
    }

    public function updateComplaint(Request $request, int $id)
    {
        $complaint = HostelComplaint::findOrFail($id);
        $data = $request->only(['status', 'assigned_to', 'vendor_name', 'resolution_notes']);
        if ($request->status === 'resolved' && !$complaint->resolved_at) {
            $data['resolved_at'] = now();
        }
        $complaint->update($data);
        return back()->with('success', 'Complaint updated.');
    }

    public function complaintsByRoom(Request $request, int $roomId)
    {
        $room       = HostelRoom::with('hostel')->findOrFail($roomId);
        $complaints = HostelComplaint::with(['student', 'assignedTo'])
            ->where('room_id', $roomId)
            ->latest()->paginate(25);
        return view('hostel.complaints-room', compact('room', 'complaints'));
    }

    public function overdueOutpasses()
    {
        $overdue = \App\Models\HostelOutpass::with(['student', 'allotment.room.hostel'])
            ->where('status', 'approved')
            ->where('to_datetime', '<', now())
            ->orderBy('to_datetime')
            ->get();
        return view('hostel.overdue-outpasses', compact('overdue'));
    }

    public function outpassWorkflow(Request $request)
    {
        $students = Student::where('status', 'active')->orderBy('first_name')->get();
        $status   = $request->status ?? 'pending';
        $outpasses = \App\Models\HostelOutpass::with(['student', 'allotment.room', 'approvedBy'])
            ->where('status', $status)->latest()->paginate(20)->withQueryString();
        $counts = ['pending' => \App\Models\HostelOutpass::where('status', 'pending')->count(),
                   'approved' => \App\Models\HostelOutpass::where('status', 'approved')->count(),
                   'rejected' => \App\Models\HostelOutpass::where('status', 'rejected')->count(),
                   'returned' => \App\Models\HostelOutpass::where('status', 'returned')->count()];
        return view('hostel.outpass-workflow', compact('students', 'outpasses', 'counts', 'status'));
    }

    public function storeOutpass(Request $request)
    {
        $request->validate(['student_id' => 'required|exists:students,id', 'from_datetime' => 'required|date', 'to_datetime' => 'required|date|after:from_datetime']);
        $allotment = HostelAllotment::where('student_id', $request->student_id)->where('status', 'active')->first();
        \App\Models\HostelOutpass::create(array_merge($request->only(['student_id', 'from_datetime', 'to_datetime', 'reason', 'parent_contact', 'destination']), ['allotment_id' => $allotment?->id, 'status' => 'pending', 'created_by' => Auth::id()]));
        return back()->with('success', 'Outpass request submitted.');
    }

    public function approveOutpass(int $id)
    {
        \App\Models\HostelOutpass::findOrFail($id)->update(['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
        return back()->with('success', 'Outpass approved.');
    }

    public function rejectOutpass(int $id)
    {
        \App\Models\HostelOutpass::findOrFail($id)->update(['status' => 'rejected', 'approved_by' => Auth::id()]);
        return back()->with('success', 'Outpass rejected.');
    }

    public function returnOutpass(int $id)
    {
        \App\Models\HostelOutpass::findOrFail($id)->update(['status' => 'returned', 'actual_return_time' => now()]);
        return back()->with('success', 'Marked as returned.');
    }

    public function occupancy(Request $request)
    {
        $hostels = Hostel::with(['rooms'])->where('is_active', true)->get();
        $summary = $hostels->map(function ($hostel) {
            $rooms     = $hostel->rooms;
            $totalCap  = $rooms->sum('capacity');
            $occupied  = $rooms->sum('occupied');
            $available = $totalCap - $occupied;
            $pct       = $totalCap > 0 ? round($occupied / $totalCap * 100, 1) : 0;
            return compact('hostel', 'totalCap', 'occupied', 'available', 'pct');
        });
        $totalRooms     = HostelRoom::count();
        $totalCapacity  = HostelRoom::sum('capacity');
        $totalOccupied  = HostelRoom::sum('occupied');
        $availableRooms = HostelRoom::where('status', 'available')->count();
        return view('hostel.occupancy', compact('hostels', 'summary', 'totalRooms', 'totalCapacity', 'totalOccupied', 'availableRooms'));
    }

    public function roomStudents(Request $request)
    {
        $hostels = Hostel::where('is_active', true)->get();
        $rooms   = HostelRoom::with(['hostel', 'allotments.student'])
            ->when($request->hostel_id, fn($q, $v) => $q->where('hostel_id', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->orderBy('room_number')->paginate(20)->withQueryString();
        return view('hostel.room-students', compact('hostels', 'rooms'));
    }

    public function feeOutstanding(Request $request)
    {
        $currentYear = AcademicYear::current();
        $hostels     = Hostel::where('is_active', true)->get();
        $outstanding = collect();

        $allotments = HostelAllotment::with(['student', 'room.hostel'])
            ->where('status', 'active')
            ->when($request->hostel_id, fn($q, $v) => $q->whereHas('room', fn($q2) => $q2->where('hostel_id', $v)))
            ->get();

        foreach ($allotments as $allotment) {
            $months = now()->diffInMonths($allotment->allotment_date) + 1;
            $totalExpected = $allotment->monthly_fee * $months;
            $totalPaid = \App\Models\FeePayment::where('student_id', $allotment->student_id)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->whereHas('feeHead', fn($q) => $q->where('name', 'like', '%hostel%'))
                ->where('is_cancelled', false)->sum('total_paid');
            $balance = max(0, $totalExpected - $totalPaid);
            if ($balance > 0) {
                $outstanding->push(['allotment' => $allotment, 'totalExpected' => $totalExpected, 'totalPaid' => $totalPaid, 'balance' => $balance]);
            }
        }

        return view('hostel.fee-outstanding', compact('hostels', 'outstanding', 'currentYear'));
    }

    public function wardens(Request $request)
    {
        $hostels    = Hostel::where('is_active', true)->get();
        $employees  = \App\Models\Employee::where('is_active', true)->orderBy('first_name')->get();
        return view('hostel.wardens', compact('hostels', 'employees'));
    }

    public function assignWarden(Request $request)
    {
        $request->validate([
            'hostel_id'    => 'required|exists:hostels,id',
            'warden_name'  => 'required|string|max:100',
            'warden_id'    => 'nullable|exists:employees,id',
            'warden_mobile'=> 'nullable|string|max:20',
        ]);
        Hostel::findOrFail($request->hostel_id)->update($request->only(['warden_name', 'warden_mobile', 'warden_id']));
        return back()->with('success', 'Warden assigned.');
    }

    public function removeWarden(int $id)
    {
        Hostel::findOrFail($id)->update(['warden_name' => null, 'warden_mobile' => null]);
        return back()->with('success', 'Warden removed.');
    }

    public function wardenContacts()
    {
        $hostels = Hostel::where('is_active', true)
            ->whereNotNull('warden_name')
            ->orderBy('name')->get();
        return view('hostel.warden-contacts', compact('hostels'));
    }

    public function saveMealMenu(Request $request)
    {
        $request->validate(['hostel_id' => 'required|exists:hostels,id', 'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday', 'breakfast' => 'nullable|string', 'lunch' => 'nullable|string', 'dinner' => 'nullable|string']);
        $hostel  = Hostel::findOrFail($request->hostel_id);
        $menus   = $hostel->meal_menus ?? [];
        $menus[$request->day] = $request->only(['breakfast', 'lunch', 'dinner', 'snacks']);
        $hostel->update(['meal_menus' => json_encode($menus)]);
        return back()->with('success', 'Meal menu saved.');
    }

    public function complaintsReport(Request $request)
    {
        $hostels    = Hostel::all();
        $complaints = HostelComplaint::with(['student', 'room'])
            ->when($request->hostel_id, fn($q) => $q->whereHas('room', fn($q2) => $q2->where('hostel_id', $request->hostel_id)))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->latest()->paginate(25);
        $statusCounts = HostelComplaint::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        return view('hostel.complaints-report', compact('complaints', 'hostels', 'statusCounts'));
    }

    /* ------------------------------------------------------------------ */
    /*  Hostel Reports — PDF / Excel Exports                               */
    /* ------------------------------------------------------------------ */

    public function occupancyReportPdf()
    {
        $hostels  = Hostel::with(['rooms'])->where('is_active', true)->get();
        $school   = \App\Models\SchoolSetting::first();
        $allotments = HostelAllotment::with(['student', 'room.hostel'])->where('status', 'active')->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.hostel-occupancy-report', compact('hostels', 'allotments', 'school'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('hostel-occupancy-' . now()->format('Y-m-d') . '.pdf');
    }

    public function occupancyReportExcel()
    {
        $allotments = HostelAllotment::with(['student', 'room.hostel'])->where('status', 'active')->get();
        $rows = $allotments->map(fn($a) => [
            'Student'       => $a->student?->full_name ?? '—',
            'Admission No'  => $a->student?->admission_number ?? '—',
            'Hostel'        => $a->room?->hostel?->name ?? '—',
            'Room'          => $a->room?->room_number ?? '—',
            'Room Type'     => $a->room?->room_type ?? '—',
            'Allotted From' => $a->allotment_date?->format('d M Y') ?? '—',
            'Monthly Fee'   => $a->monthly_fee ?? '—',
        ])->toArray();
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ArrayExport($rows), 'hostel-occupancy-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function gatePassRegisterPdf()
    {
        $outpasses = \App\Models\HostelOutpass::with(['student', 'allotment.room'])
            ->whereMonth('from_datetime', now()->month)->orderByDesc('from_datetime')->get();
        $school = \App\Models\SchoolSetting::first();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.hostel-gate-pass-register', compact('outpasses', 'school'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->stream('gate-pass-register-' . now()->format('Y-m') . '.pdf');
    }

    public function gatePassRegisterExcel()
    {
        $outpasses = \App\Models\HostelOutpass::with(['student', 'allotment.room'])
            ->whereMonth('from_datetime', now()->month)->orderByDesc('from_datetime')->get();
        $rows = $outpasses->map(fn($op) => [
            'Student'     => $op->student?->full_name ?? '—',
            'Room'        => $op->allotment?->room?->room_number ?? '—',
            'From'        => $op->from_datetime?->format('d M Y H:i') ?? '—',
            'To'          => $op->to_datetime?->format('d M Y H:i') ?? '—',
            'Destination' => $op->destination ?? '—',
            'Purpose'     => $op->reason ?? '—',
            'Status'      => ucfirst($op->status),
            'Return Time' => $op->actual_return_time?->format('d M Y H:i') ?? '—',
        ])->toArray();
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ArrayExport($rows), 'gate-pass-register-' . now()->format('Y-m') . '.xlsx');
    }

    public function feeOutstandingExcel()
    {
        $currentYear = AcademicYear::current();
        $allotments  = HostelAllotment::with(['student', 'room.hostel'])->where('status', 'active')->get();
        $rows = [];
        foreach ($allotments as $allotment) {
            $months = now()->diffInMonths($allotment->allotment_date) + 1;
            $totalExpected = $allotment->monthly_fee * $months;
            $totalPaid = \App\Models\FeePayment::where('student_id', $allotment->student_id)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->whereHas('feeHead', fn($q) => $q->where('name', 'like', '%hostel%'))
                ->where('is_cancelled', false)->sum('total_paid');
            $balance = max(0, $totalExpected - $totalPaid);
            if ($balance > 0) {
                $rows[] = [
                    'Student'    => $allotment->student?->full_name ?? '—',
                    'Hostel'     => $allotment->room?->hostel?->name ?? '—',
                    'Room'       => $allotment->room?->room_number ?? '—',
                    'Expected'   => $totalExpected,
                    'Paid'       => $totalPaid,
                    'Balance'    => $balance,
                ];
            }
        }
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ArrayExport($rows), 'hostel-fee-outstanding-' . now()->format('Y-m-d') . '.xlsx');
    }

    /* ------------------------------------------------------------------ */
    /*  Night Duty Staff Assignment                                         */
    /* ------------------------------------------------------------------ */

    public function nightDuty(Request $request)
    {
        $hostels    = Hostel::where('is_active', true)->get();
        $month      = $request->month ?? now()->format('Y-m');
        $hostelId   = $request->hostel_id;
        $duties = collect();
        try {
            $duties = \App\Models\NightDutyStaff::with(['employee', 'hostel'])
                ->whereRaw("TO_CHAR(duty_date, 'YYYY-MM') = ?", [$month])
                ->when($hostelId, fn($q, $v) => $q->where('hostel_id', $v))
                ->orderBy('duty_date')
                ->get();
        } catch (\Exception $e) {}
        $employees  = \App\Models\Employee::where('is_active', true)->orderBy('first_name')->get(['id','first_name','last_name','employee_code','designation']);
        return view('hostel.night-duty', compact('duties', 'employees', 'hostels', 'month'));
    }

    public function storeNightDuty(Request $request)
    {
        $request->validate([
            'hostel_id'   => 'required|exists:hostels,id',
            'employee_id' => 'required|exists:employees,id',
            'duty_date'   => 'required|date',
            'shift'       => 'required|in:night,full',
            'notes'       => 'nullable|string|max:255',
        ]);
        \App\Models\NightDutyStaff::updateOrCreate(
            ['hostel_id' => $request->hostel_id, 'employee_id' => $request->employee_id, 'duty_date' => $request->duty_date],
            ['shift' => $request->shift, 'notes' => $request->notes, 'assigned_by' => Auth::id()]
        );
        return back()->with('success', 'Night duty assigned.');
    }

    public function deleteNightDuty(int $id)
    {
        \App\Models\NightDutyStaff::findOrFail($id)->delete();
        return back()->with('success', 'Night duty removed.');
    }

    // ── Floor Master ──────────────────────────────────────

    public function floors(Request $request)
    {
        $hostels = Hostel::orderBy('name')->get();
        $floors  = HostelFloor::with(['hostel', 'rooms'])
            ->when($request->hostel_id, fn($q, $v) => $q->where('hostel_id', $v))
            ->orderBy('hostel_id')->orderBy('floor_number')->paginate(30);
        return view('hostel.floors', compact('hostels', 'floors'));
    }

    public function storeFloor(Request $request)
    {
        $request->validate([
            'hostel_id'    => 'required|exists:hostels,id',
            'name'         => 'required|string|max:50',
            'floor_number' => 'required|integer|min:0',
        ]);
        HostelFloor::create($request->only(['hostel_id', 'name', 'floor_number']));
        return back()->with('success', 'Floor "' . $request->name . '" added.');
    }

    public function updateFloor(Request $request, int $id)
    {
        $floor = HostelFloor::findOrFail($id);
        $request->validate(['name' => 'required|string|max:50', 'floor_number' => 'required|integer|min:0']);
        $floor->update($request->only(['name', 'floor_number']));
        return back()->with('success', 'Floor updated.');
    }

    public function deleteFloor(int $id)
    {
        $floor = HostelFloor::findOrFail($id);
        if ($floor->rooms()->count() > 0) {
            return back()->with('error', 'Cannot delete floor with rooms assigned. Reassign rooms first.');
        }
        $floor->delete();
        return back()->with('success', 'Floor deleted.');
    }

    // ── Room Transfer ────────────────────────────────────

    public function roomTransfer(Request $request)
    {
        $hostels    = Hostel::orderBy('name')->get();
        $allotments = HostelAllotment::with(['student', 'room.hostel'])
            ->where('status', 'active')
            ->when($request->hostel_id, fn($q, $v) => $q->whereHas('room', fn($q2) => $q2->where('hostel_id', $v)))
            ->paginate(25);
        $availableRooms = HostelRoom::where('status', 'available')
            ->with('hostel')->orderBy('hostel_id')->orderBy('room_number')->get();
        return view('hostel.room-transfer', compact('hostels', 'allotments', 'availableRooms'));
    }

    public function processRoomTransfer(Request $request)
    {
        $request->validate([
            'allotment_id'  => 'required|exists:hostel_allotments,id',
            'new_room_id'   => 'required|exists:hostel_rooms,id',
            'transfer_reason'=> 'nullable|string|max:255',
        ]);

        $allotment = HostelAllotment::findOrFail($request->allotment_id);
        $oldRoomId = $allotment->room_id;

        $allotment->update([
            'room_id'                  => $request->new_room_id,
            'transferred_from_room_id' => $oldRoomId,
            'transfer_date'            => today(),
            'transfer_reason'          => $request->transfer_reason,
        ]);

        // Adjust occupancy
        HostelRoom::where('id', $oldRoomId)->decrement('occupied');
        HostelRoom::where('id', $oldRoomId)->update(['status' => 'available']);
        HostelRoom::where('id', $request->new_room_id)->increment('occupied');
        HostelRoom::where('id', $request->new_room_id)
            ->whereColumn('occupied', '>=', 'capacity')
            ->update(['status' => 'full']);

        return back()->with('success', 'Student transferred to new room successfully.');
    }

    // ── Warden Duty Roster ───────────────────────────────

    public function wardenRoster(Request $request)
    {
        $hostels = Hostel::with('wardens')->orderBy('name')->get();
        $week    = $request->week ?? now()->format('Y-W');
        return view('hostel.warden-roster', compact('hostels', 'week'));
    }

    public function saveWardenRoster(Request $request)
    {
        $week = $request->week ?? now()->format('Y-W');
        $roster = $request->input('roster', []);

        DB::transaction(function () use ($week, $roster) {
            foreach ($roster as $hostelId => $days) {
                foreach ($days as $day => $wardenId) {
                    if (!$wardenId) continue;
                    DB::table('hostel_warden_rosters')->updateOrInsert(
                        ['hostel_id' => $hostelId, 'week' => $week, 'day' => $day],
                        ['employee_id' => $wardenId, 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        });

        return back()->with('success', 'Duty roster saved for week ' . $week . '.');
    }

    // ── Student Vacate from Hostel ────────────────────────

    public function vacateStudent(Request $request)
    {
        $allotments = \App\Models\HostelAllotment::with(['student', 'hostel', 'room'])
            ->where('status', 'active')
            ->when($request->search, function ($q, $s) {
                $q->whereHas('student', fn($sq) => $sq->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")->orWhere('admission_no', 'like', "%$s%"));
            })
            ->orderBy('allotment_date', 'desc')
            ->get();

        return view('hostel.vacate', compact('allotments'));
    }

    public function processVacate(Request $request, int $allotmentId)
    {
        $request->validate([
            'vacating_date' => 'required|date',
            'vacate_reason' => 'required|string|max:300',
        ]);

        $allotment = \App\Models\HostelAllotment::where('status', 'active')->findOrFail($allotmentId);
        $allotment->update([
            'status'         => 'vacated',
            'vacating_date'  => $request->vacating_date,
            'vacate_reason'  => $request->vacate_reason,
            'vacated_by'     => auth()->id(),
        ]);

        // Deactivate the linked hostel fee charge in main fee module
        StudentFeeCharge::where('student_id', $allotment->student_id)
            ->where('source', 'hostel_allotment')
            ->where('source_id', $allotment->id)
            ->update(['is_active' => false]);

        // Update room available count if tracked
        if ($allotment->room) {
            \App\Models\HostelRoom::where('id', $allotment->room_id)->decrement('occupied');
        }

        return back()->with('success', 'Student vacated from hostel successfully.');
    }

    // ── Student Hostel ID Card ─────────────────────────────

    public function studentIdCard(int $allotmentId)
    {
        $allotment = \App\Models\HostelAllotment::with(['student', 'hostel', 'room'])
            ->findOrFail($allotmentId);

        $school = \App\Models\SchoolSetting::getAll();

        $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(80)->generate($allotment->student->admission_number ?? 'HOSTEL-'.$allotmentId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.hostel-id-card', compact('allotment', 'school', 'qr'))
            ->setPaper([0, 0, 226, 340], 'portrait');

        return $pdf->download('hostel-id-card-'.$allotment->student->admission_number.'.pdf');
    }

    // ── Bed Master per Room ────────────────────────────────

    public function beds(Request $request, int $roomId)
    {
        $room = \App\Models\HostelRoom::with(['hostel', 'beds'])->findOrFail($roomId);
        return view('hostel.beds', compact('room'));
    }

    public function storeBed(Request $request, int $roomId)
    {
        $request->validate([
            'bed_number' => 'required|string|max:20',
            'notes'      => 'nullable|string|max:200',
        ]);

        $room = \App\Models\HostelRoom::findOrFail($roomId);

        $exists = \App\Models\HostelBed::where('room_id', $roomId)
            ->where('bed_number', $request->bed_number)->exists();
        if ($exists) {
            return back()->with('error', "Bed '{$request->bed_number}' already exists in this room.");
        }

        \App\Models\HostelBed::create([
            'room_id'    => $roomId,
            'bed_number' => $request->bed_number,
            'notes'      => $request->notes,
        ]);

        return back()->with('success', "Bed '{$request->bed_number}' added.");
    }

    public function bulkCreateBeds(Request $request, int $roomId)
    {
        $request->validate(['count' => 'required|integer|min:1|max:50']);

        $room     = \App\Models\HostelRoom::findOrFail($roomId);
        $existing = \App\Models\HostelBed::where('room_id', $roomId)->pluck('bed_number')->toArray();
        $added    = 0;

        for ($i = 1; $i <= $request->count; $i++) {
            $bedNum = 'B' . $i;
            if (!in_array($bedNum, $existing)) {
                \App\Models\HostelBed::create(['room_id' => $roomId, 'bed_number' => $bedNum]);
                $added++;
            }
        }

        return back()->with('success', "{$added} beds created for room {$room->room_number}.");
    }

    public function updateBed(Request $request, int $bedId)
    {
        $request->validate([
            'status' => 'required|in:available,occupied,maintenance',
            'notes'  => 'nullable|string|max:200',
        ]);

        \App\Models\HostelBed::findOrFail($bedId)->update($request->only('status', 'notes'));
        return back()->with('success', 'Bed updated.');
    }

    public function deleteBed(int $bedId)
    {
        $bed = \App\Models\HostelBed::findOrFail($bedId);
        if ($bed->status === 'occupied') {
            return back()->with('error', 'Cannot delete an occupied bed.');
        }
        $bed->delete();
        return back()->with('success', 'Bed removed.');
    }

    /* ------------------------------------------------------------------ */
    /*  Gate Pass QR Code                                                   */
    /* ------------------------------------------------------------------ */

    public function gatePassQr(int $outpassId)
    {
        $outpass = \App\Models\HostelOutpass::with(['student', 'allotment.room'])->findOrFail($outpassId);

        abort_if($outpass->status !== 'approved', 403, 'Gate pass not approved yet.');

        $school = \App\Models\SchoolSetting::first();

        $qrData = implode("\n", array_filter([
            'GATE PASS — ' . ($school?->school_name ?? 'School'),
            'Student: ' . $outpass->student?->full_name,
            'Room: '    . ($outpass->allotment?->room?->room_number ?? '—'),
            'From: '    . \Carbon\Carbon::parse($outpass->from_datetime)->format('d M Y H:i'),
            'To: '      . \Carbon\Carbon::parse($outpass->to_datetime)->format('d M Y H:i'),
            'Purpose: ' . ($outpass->reason ?? ''),
            'Destination: ' . ($outpass->destination ?? ''),
            'Pass ID: ' . $outpass->id,
        ]));

        $qrCode = null;
        try {
            $qrCode = base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(200)->generate($qrData)
            );
        } catch (\Exception $e) {
            // QR optional
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.hostel-gate-pass', compact('outpass', 'school', 'qrCode'));
        $pdf->setPaper('A5', 'portrait');

        return $pdf->stream('gate-pass-' . $outpass->id . '.pdf');
    }

    /* ------------------------------------------------------------------ */
    /*  Mess Attendance                                                     */
    /* ------------------------------------------------------------------ */

    public function messAttendance(Request $request)
    {
        $date    = $request->date ? \Carbon\Carbon::parse($request->date) : today();
        $meal    = $request->meal ?? 'lunch';
        $hostels = Hostel::where('is_active', true)->get();

        $allotments = HostelAllotment::with(['student', 'room'])
            ->where('status', 'active')
            ->when($request->hostel_id, fn($q, $v) => $q->whereHas('room', fn($q2) => $q2->where('hostel_id', $v)))
            ->orderBy('student_id')
            ->get();

        $existingAttendance = \App\Models\MessAttendance::where('date', $date->toDateString())
            ->where('meal', $meal)
            ->pluck('is_present', 'allotment_id');

        return view('hostel.mess-attendance', compact('date', 'meal', 'allotments', 'existingAttendance', 'hostels'));
    }

    public function saveMessAttendance(Request $request)
    {
        $request->validate([
            'date'          => 'required|date',
            'meal'          => 'required|in:breakfast,lunch,snacks,dinner',
            'present_ids'   => 'nullable|array',
            'present_ids.*' => 'exists:hostel_allotments,id',
        ]);

        $allotmentIds = HostelAllotment::where('status', 'active')->pluck('id');
        $presentIds   = collect($request->present_ids ?? []);

        foreach ($allotmentIds as $allotmentId) {
            \App\Models\MessAttendance::updateOrCreate(
                ['allotment_id' => $allotmentId, 'date' => $request->date, 'meal' => $request->meal],
                ['is_present' => $presentIds->contains($allotmentId)]
            );
        }

        return back()->with('success', ucfirst($request->meal) . ' attendance saved for ' . \Carbon\Carbon::parse($request->date)->format('d M Y') . '.');
    }

    public function messAttendanceSummary(Request $request)
    {
        $month   = $request->month ?? now()->format('Y-m');
        $hostels = Hostel::where('is_active', true)->get();

        $allotments = HostelAllotment::with(['student', 'room'])
            ->where('status', 'active')
            ->when($request->hostel_id, fn($q, $v) => $q->whereHas('room', fn($q2) => $q2->where('hostel_id', $v)))
            ->get();

        $summaryData = [];
        foreach ($allotments as $allotment) {
            $total    = \App\Models\MessAttendance::where('allotment_id', $allotment->id)
                ->whereRaw("TO_CHAR(date, 'YYYY-MM') = ?", [$month])->count();
            $present  = \App\Models\MessAttendance::where('allotment_id', $allotment->id)
                ->whereRaw("TO_CHAR(date, 'YYYY-MM') = ?", [$month])
                ->where('is_present', true)->count();
            $summaryData[] = [
                'allotment' => $allotment,
                'student'   => $allotment->student,
                'total'     => $total,
                'present'   => $present,
                'absent'    => $total - $present,
            ];
        }

        return view('hostel.mess-attendance-summary', compact('summaryData', 'month', 'hostels'));
    }

    /* ------------------------------------------------------------------ */
    /*  Mess Rebate for Absentees                                           */
    /* ------------------------------------------------------------------ */

    public function messRebates(Request $request)
    {
        $hostels    = Hostel::where('is_active', true)->get();
        $allotments = HostelAllotment::with(['student', 'room'])
            ->where('status', 'active')
            ->when($request->hostel_id, fn($q, $v) => $q->whereHas('room', fn($q2) => $q2->where('hostel_id', $v)))
            ->get();
        $rebates    = MessRebate::with(['allotment.student', 'allotment.room'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()->paginate(20)->withQueryString();
        return view('hostel.mess-rebates', compact('hostels', 'allotments', 'rebates'));
    }

    public function storeMessRebate(Request $request)
    {
        $request->validate([
            'allotment_id'   => 'required|exists:hostel_allotments,id',
            'from_date'      => 'required|date',
            'to_date'        => 'required|date|after_or_equal:from_date',
            'rebate_per_day' => 'required|numeric|min:0',
            'reason'         => 'nullable|string|max:255',
        ]);
        $days  = \Carbon\Carbon::parse($request->from_date)->diffInDays(\Carbon\Carbon::parse($request->to_date)) + 1;
        MessRebate::create([
            'allotment_id'   => $request->allotment_id,
            'from_date'      => $request->from_date,
            'to_date'        => $request->to_date,
            'days_absent'    => $days,
            'rebate_per_day' => $request->rebate_per_day,
            'total_rebate'   => $days * $request->rebate_per_day,
            'reason'         => $request->reason,
            'status'         => 'pending',
        ]);
        return back()->with('success', "Rebate of ₹" . number_format($days * $request->rebate_per_day, 0) . " for $days days created.");
    }

    public function approveMessRebate(int $id)
    {
        MessRebate::findOrFail($id)->update(['status' => 'approved', 'approved_by' => Auth::id()]);
        return back()->with('success', 'Mess rebate approved.');
    }

    public function deleteMessRebate(int $id)
    {
        MessRebate::findOrFail($id)->delete();
        return back()->with('success', 'Rebate deleted.');
    }

    /* ── Hostel Disciplinary Records & Warnings ── */

    public function hostelDisciplinary(Request $request)
    {
        $students = Student::where('status', 'active')->orderBy('first_name')->get();
        $employees = Employee::where('is_active', true)->orderBy('first_name')->get();
        $records = \DB::table('hostel_disciplinary')
            ->join('students', 'students.id', '=', 'hostel_disciplinary.student_id')
            ->select('hostel_disciplinary.*',
                \DB::raw("CONCAT(students.first_name,' ',students.last_name) as student_name"),
                'students.admission_no')
            ->when($request->student_id, fn($q, $v) => $q->where('hostel_disciplinary.student_id', $v))
            ->when($request->severity, fn($q, $v) => $q->where('hostel_disciplinary.severity', $v))
            ->when($request->warning_only, fn($q) => $q->where('hostel_disciplinary.is_warning', true))
            ->orderByDesc('hostel_disciplinary.incident_date')
            ->paginate(20)->withQueryString();

        // repeat offence count per student
        $repeatCounts = \DB::table('hostel_disciplinary')
            ->select('student_id', \DB::raw('count(*) as total'))
            ->groupBy('student_id')->pluck('total', 'student_id');

        return view('hostel.hostel-disciplinary', compact('records', 'students', 'employees', 'repeatCounts'));
    }

    public function storeHostelDisciplinary(Request $request)
    {
        $data = $request->validate([
            'student_id'    => 'required|exists:students,id',
            'incident_date' => 'required|date',
            'description'   => 'required|string',
            'action_taken'  => 'nullable|string',
            'severity'      => 'nullable|in:minor,moderate,severe',
            'is_warning'    => 'nullable|boolean',
            'reported_by'   => 'nullable|exists:employees,id',
        ]);
        $data['is_warning']    = $request->has('is_warning') ? 1 : 0;
        $data['reported_by']   = $data['reported_by'] ?? Auth::id();
        $data['severity']      = $data['severity'] ?? 'minor';

        // Auto-compute warning number if issuing a warning
        if ($data['is_warning']) {
            $previousWarnings = \DB::table('hostel_disciplinary')
                ->where('student_id', $data['student_id'])
                ->where('is_warning', true)->count();
            $data['warning_number'] = $previousWarnings + 1;
            $data['warning_date']   = $data['incident_date'];
        }

        \DB::table('hostel_disciplinary')->insert(array_merge($data, [
            'parent_notified' => 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]));

        // Email parent notification
        $student = Student::with(['user', 'parent'])->find($data['student_id']);
        $email   = $student?->user?->email ?? $student?->parent?->email ?? null;
        if ($email) {
            $school  = \App\Models\SchoolSetting::first();
            $tpl     = \App\Models\NotificationTemplate::forEvent('discipline_action', 'email');
            $subject = $tpl?->subject ?: 'Hostel Disciplinary Notice – ' . ($school?->school_name ?? config('app.name'));
            $vars = [
                'parent_name'  => $student->parent?->name ?? 'Parent/Guardian',
                'student_name' => $student->full_name,
                'action_type'  => $data['is_warning'] ? 'Warning Issued' : ucfirst($data['action_taken'] ?? 'Disciplinary Action'),
                'date'         => \Carbon\Carbon::parse($data['incident_date'])->format('d M Y'),
                'school_name'  => $school?->school_name ?? config('app.name'),
            ];
            $body = $tpl ? $tpl->getRenderedBody($vars)
                : "Dear {$vars['parent_name']},\n\nA hostel disciplinary action has been recorded for {$vars['student_name']}.\n\nAction: {$vars['action_type']}\nDate: {$vars['date']}\n\nPlease contact the warden for more details.\n\n{$vars['school_name']}";
            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\FeeReminderMail($subject, $body));
            } catch (\Exception $e) { /* fail silently */ }
        }

        return back()->with('success', 'Disciplinary record saved.' . ($data['is_warning'] ? " Warning #{$data['warning_number']} issued." : '') . ($email ? ' Parent notified by email.' : ''));
    }

    public function deleteHostelDisciplinary(int $id)
    {
        \DB::table('hostel_disciplinary')->where('id', $id)->delete();
        return back()->with('success', 'Record deleted.');
    }

    /* ── Mess Expense Tracking ── */

    public function messExpenses(Request $request)
    {
        $hostels  = Hostel::orderBy('name')->get();
        $expenses = \DB::table('mess_expenses')
            ->leftJoin('hostels', 'hostels.id', '=', 'mess_expenses.hostel_id')
            ->select('mess_expenses.*', 'hostels.name as hostel_name')
            ->when($request->hostel_id, fn($q, $v) => $q->where('mess_expenses.hostel_id', $v))
            ->when($request->category,  fn($q, $v) => $q->where('mess_expenses.category', $v))
            ->when($request->month,     fn($q, $v) => $q->whereRaw("TO_CHAR(mess_expenses.expense_date, 'YYYY-MM') = ?", [$v]))
            ->orderByDesc('mess_expenses.expense_date')
            ->paginate(25)->withQueryString();

        $monthTotal = \DB::table('mess_expenses')
            ->when($request->hostel_id, fn($q, $v) => $q->where('hostel_id', $v))
            ->when($request->month,     fn($q, $v) => $q->whereRaw("TO_CHAR(expense_date, 'YYYY-MM') = ?", [$v]))
            ->sum('amount');

        return view('hostel.mess-expenses', compact('hostels', 'expenses', 'monthTotal'));
    }

    public function storeMessExpense(Request $request)
    {
        $data = $request->validate([
            'hostel_id'      => 'nullable|exists:hostels,id',
            'expense_date'   => 'required|date',
            'category'       => 'required|in:ingredients,vendor,utilities,staff,other',
            'description'    => 'required|string|max:255',
            'amount'         => 'required|numeric|min:0',
            'vendor'         => 'nullable|string|max:100',
            'invoice_number' => 'nullable|string|max:50',
            'meal_type'      => 'nullable|in:breakfast,lunch,snacks,dinner,all',
            'notes'          => 'nullable|string',
        ]);
        $data['recorded_by'] = Auth::id();
        \DB::table('mess_expenses')->insert(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
        return back()->with('success', 'Mess expense recorded.');
    }

    public function deleteMessExpense(int $id)
    {
        \DB::table('mess_expenses')->where('id', $id)->delete();
        return back()->with('success', 'Expense deleted.');
    }

    // ── Mess Feedback ──────────────────────────────────────

    public function messFeedback(Request $request)
    {
        $hostels = Hostel::where('is_active', true)->get();
        $students = Student::where('status', 'active')->orderBy('first_name')->get();

        $query = MessFeedback::with(['student'])
            ->when($request->hostel_id, fn($q, $v) => $q->where('hostel_id', $v))
            ->when($request->meal_type, fn($q, $v) => $q->where('meal_type', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('feedback_date', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('feedback_date', '<=', $v))
            ->orderByDesc('feedback_date');

        $feedback = $query->paginate(30);

        // Averages per meal type for current week
        $avgRatings = MessFeedback::selectRaw('meal_type, AVG(rating) as avg_rating, COUNT(*) as total')
            ->whereDate('feedback_date', '>=', now()->startOfWeek())
            ->groupBy('meal_type')
            ->get()->keyBy('meal_type');

        return view('hostel.mess-feedback', compact('hostels', 'students', 'feedback', 'avgRatings'));
    }

    public function storeMessFeedback(Request $request)
    {
        $request->validate([
            'student_id'    => 'required|exists:students,id',
            'feedback_date' => 'required|date|before_or_equal:today',
            'meal_type'     => 'required|in:breakfast,lunch,snacks,dinner',
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'nullable|string|max:500',
        ]);

        MessFeedback::updateOrCreate(
            [
                'student_id'    => $request->student_id,
                'feedback_date' => $request->feedback_date,
                'meal_type'     => $request->meal_type,
            ],
            [
                'hostel_id'    => $request->hostel_id,
                'rating'       => $request->rating,
                'comment'      => $request->comment,
                'is_anonymous' => $request->boolean('is_anonymous'),
            ]
        );

        return back()->with('success', 'Feedback recorded.');
    }
}
