<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeRecipient;
use App\Models\NoticeAcknowledgement;
use App\Models\Classes;
use App\Models\Department;
use App\Models\Employee;
use App\Services\ActivityNotificationService;
use App\Services\ActivityAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CircularOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Notice::with(['createdBy', 'targetClass'])
            ->withCount(['acknowledgements', 'reads'])
            ->circulars()
            ->forUser($user)
            ->when($request->category, function ($q, $v) {
                if ($v === 'circular') {
                    $q->where('notice_type', 'circular');
                } elseif ($v === 'order') {
                    $q->whereIn('notice_type', ['order', 'directive', 'memo'])->orWhereNotNull('order_category');
                } elseif ($v === 'urgent') {
                    $q->where('priority', 'urgent');
                } elseif ($v === 'pinned') {
                    $q->where('is_pinned', true);
                }
            })
            ->when($request->audience, fn($q, $v) => $q->where('target_audience', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('title', 'ilike', "%{$v}%")
                        ->orWhere('reference_no', 'ilike', "%{$v}%")
                        ->orWhere('issuing_authority', 'ilike', "%{$v}%")
                        ->orWhere('signed_by_name', 'ilike', "%{$v}%")
                        ->orWhere('content', 'ilike', "%{$v}%");
                });
            })
            ->when($request->from, fn($q, $v) => $q->whereDate('publish_date', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->whereDate('publish_date', '<=', $v))
            ->pinnedFirst();

        $circulars = $query->paginate(15)->withQueryString();

        $totalCirculars = Notice::circulars()->forUser($user)->count();
        $ordersCount    = Notice::orders()->forUser($user)->count();
        $urgentCount    = Notice::circulars()->forUser($user)->where('priority', 'urgent')->count();
        $pinnedCount    = Notice::circulars()->forUser($user)->where('is_pinned', true)->count();

        return view('circulars.index', compact(
            'circulars', 'totalCirculars', 'ordersCount', 'urgentCount', 'pinnedCount'
        ));
    }

    public function create()
    {
        $classes     = Classes::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $employees   = Employee::with('department')->orderBy('first_name')->get();

        // Auto-generate reference number suggestion
        $currentYear = date('Y');
        $countThisYear = Notice::whereYear('created_at', $currentYear)->count() + 1;
        $suggestedRef = sprintf("EPS/CIR/%s/%03d", $currentYear, $countThisYear);

        return view('circulars.form', compact('classes', 'departments', 'employees', 'suggestedRef'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'                    => 'required|string|max:255',
            'reference_no'             => 'nullable|string|max:80',
            'notice_type'              => 'required|in:circular,order,directive,memo',
            'order_category'           => 'nullable|string|max:60',
            'issuing_authority'        => 'nullable|string|max:100',
            'signed_by_name'           => 'nullable|string|max:100',
            'signatory_designation'    => 'nullable|string|max:100',
            'priority'                 => 'required|in:low,normal,high,urgent',
            'target_audience'          => 'required|in:all,staff,students,parents,class',
            'target_class_id'          => 'nullable|exists:classes,id',
            'publish_date'             => 'required|date',
            'expiry_date'              => 'nullable|date|after_or_equal:publish_date',
            'content'                  => 'required|string',
            'attachment'               => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'requires_acknowledgement' => 'boolean',
            'is_pinned'                => 'boolean',
            'is_published'             => 'boolean',
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('circulars/documents', 'public');
        }

        $data['created_by']   = Auth::id();
        $data['is_published'] = $request->boolean('is_published', true);
        $data['status']       = $data['is_published'] ? 'published' : 'draft';

        // Auto fallback for reference number if left blank
        if (empty($data['reference_no'])) {
            $prefix = ($data['notice_type'] === 'order') ? 'EPS/ORD' : 'EPS/CIR';
            $data['reference_no'] = sprintf("%s/%s/%03d", $prefix, date('Y'), Notice::count() + 1);
        }

        $circular = Notice::create($data);

        // Target audience mapping
        NoticeRecipient::create([
            'notice_id'      => $circular->id,
            'recipient_type' => $circular->target_audience,
            'recipient_id'   => $circular->target_class_id,
        ]);

        ActivityAuditService::log('created', 'circular', $circular->id, [
            'title'        => $circular->title,
            'reference_no' => $circular->reference_no,
            'type'         => $circular->notice_type,
        ]);

        if ($circular->is_published) {
            ActivityNotificationService::notifyNoticePublished($circular);
        }

        return redirect()->route('circulars.show', $circular->id)
            ->with('success', "Official {$circular->notice_type} [{$circular->reference_no}] published successfully.");
    }

    public function show(int $id)
    {
        $circular = Notice::with(['createdBy', 'targetClass', 'acknowledgements.user'])
            ->findOrFail($id);

        $myAcknowledgement = Auth::check()
            ? NoticeAcknowledgement::where('notice_id', $id)->where('user_id', Auth::id())->first()
            : null;

        $totalAckCount = $circular->acknowledgements->count();

        return view('circulars.show', compact('circular', 'myAcknowledgement', 'totalAckCount'));
    }

    public function edit(int $id)
    {
        $circular = Notice::findOrFail($id);
        $classes     = Classes::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $employees   = Employee::with('department')->orderBy('first_name')->get();

        return view('circulars.form', compact('circular', 'classes', 'departments', 'employees'));
    }

    public function update(Request $request, int $id)
    {
        $circular = Notice::findOrFail($id);

        $data = $request->validate([
            'title'                    => 'required|string|max:255',
            'reference_no'             => 'nullable|string|max:80',
            'notice_type'              => 'required|in:circular,order,directive,memo',
            'order_category'           => 'nullable|string|max:60',
            'issuing_authority'        => 'nullable|string|max:100',
            'signed_by_name'           => 'nullable|string|max:100',
            'signatory_designation'    => 'nullable|string|max:100',
            'priority'                 => 'required|in:low,normal,high,urgent',
            'target_audience'          => 'required|in:all,staff,students,parents,class',
            'target_class_id'          => 'nullable|exists:classes,id',
            'publish_date'             => 'required|date',
            'expiry_date'              => 'nullable|date|after_or_equal:publish_date',
            'content'                  => 'required|string',
            'attachment'               => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'requires_acknowledgement' => 'boolean',
            'is_pinned'                => 'boolean',
            'is_published'             => 'boolean',
        ]);

        if ($request->hasFile('attachment')) {
            if ($circular->attachment) Storage::disk('public')->delete($circular->attachment);
            $data['attachment'] = $request->file('attachment')->store('circulars/documents', 'public');
        }

        $data['is_published'] = $request->boolean('is_published', true);
        $data['status']       = $data['is_published'] ? 'published' : 'draft';

        $circular->update($data);

        ActivityAuditService::log('updated', 'circular', $circular->id, ['title' => $circular->title]);

        return redirect()->route('circulars.show', $circular->id)->with('success', 'Circular updated successfully.');
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $canDelete = $user && $user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']);

        abort_unless($canDelete, 403, 'Unauthorized: Only administrator, principal, and correspondent can delete circulars and orders.');

        $circular = Notice::findOrFail($id);
        if ($circular->attachment) Storage::disk('public')->delete($circular->attachment);
        $circular->delete();

        ActivityAuditService::log('deleted', 'circular', $id, ['title' => $circular->title]);

        return redirect()->route('circulars.index')->with('success', "Circular [{$circular->reference_no}] deleted successfully.");
    }

    public function acknowledge(Request $request, int $id)
    {
        $user = Auth::user();
        $circular = Notice::findOrFail($id);

        NoticeAcknowledgement::firstOrCreate(
            ['notice_id' => $circular->id, 'user_id' => $user->id],
            [
                'acknowledged_at' => now(),
                'ip_address'      => $request->ip(),
                'feedback_note'   => $request->input('feedback_note'),
            ]
        );

        return back()->with('success', 'Receipt acknowledged successfully.');
    }

    public function togglePin(int $id)
    {
        $user = Auth::user();
        $canManage = $user && $user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']);
        abort_unless($canManage, 403);

        $circular = Notice::findOrFail($id);
        $circular->update(['is_pinned' => !$circular->is_pinned]);

        $status = $circular->is_pinned ? 'pinned to top' : 'unpinned';
        return back()->with('success', "Circular [{$circular->reference_no}] has been {$status}.");
    }
}
