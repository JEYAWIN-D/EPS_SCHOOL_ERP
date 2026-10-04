<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeRecipient;
use App\Models\NoticeAcknowledgement;
use App\Services\ActivityNotificationService;
use App\Services\ActivityAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NoticeApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Notice::with(['createdBy', 'targetClass'])
            ->withCount(['acknowledgements', 'reads'])
            ->forUser($user)
            ->when($request->priority, fn($q, $v) => $q->where('priority', $v))
            ->when($request->notice_type, fn($q, $v) => $q->where('notice_type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(fn($sub) => $sub->where('title', 'ilike', "%{$v}%")->orWhere('content', 'ilike', "%{$v}%"));
            })
            ->orderBy('created_at', 'desc');

        $notices = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data'   => $notices,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'                    => 'required|string|max:255',
            'content'                  => 'required|string',
            'notice_type'              => 'nullable|string',
            'priority'                 => 'nullable|in:low,normal,high,urgent',
            'target_audience'          => 'required|string|in:all,staff,students,parents,class_specific',
            'target_class_id'          => 'nullable|exists:classes,id',
            'publish_date'             => 'required|date',
            'expiry_date'              => 'nullable|date|after_or_equal:publish_date',
            'requires_approval'        => 'boolean',
            'requires_acknowledgement' => 'boolean',
            'recipients'               => 'nullable|array',
            'recipients.*.type'        => 'required_with:recipients|string',
            'recipients.*.id'          => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $requiresApproval = $request->boolean('requires_approval');
        $initialStatus = $requiresApproval ? 'pending_approval' : 'published';

        $notice = DB::transaction(function () use ($request, $user, $requiresApproval, $initialStatus) {
            $n = Notice::create([
                'title'                    => $request->title,
                'content'                  => $request->content,
                'notice_type'              => $request->input('notice_type', 'general'),
                'priority'                 => $request->input('priority', 'normal'),
                'target_audience'          => $request->target_audience,
                'target_class_id'          => $request->target_class_id,
                'publish_date'             => $request->publish_date,
                'expiry_date'              => $request->expiry_date,
                'requires_approval'        => $requiresApproval,
                'requires_acknowledgement' => $request->boolean('requires_acknowledgement'),
                'status'                   => $initialStatus,
                'is_published'             => !$requiresApproval,
                'approval_status'          => $requiresApproval ? 'pending' : 'not_required',
                'created_by'               => $user->id,
            ]);

            if ($request->has('recipients') && is_array($request->recipients)) {
                foreach ($request->recipients as $rec) {
                    NoticeRecipient::create([
                        'notice_id'      => $n->id,
                        'recipient_type' => $rec['type'],
                        'recipient_id'   => $rec['id'] ?? null,
                    ]);
                }
            }

            ActivityAuditService::log('created', 'notice', $n->id, ['title' => $n->title, 'status' => $n->status]);

            if ($n->status === 'published') {
                ActivityNotificationService::notifyNoticePublished($n);
            }

            return $n;
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Notice created successfully',
            'data'    => $notice->load('recipients'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $notice = Notice::with(['createdBy', 'targetClass', 'recipients', 'attachments', 'acknowledgements.user'])
            ->findOrFail($id);

        $myAcknowledgement = Auth::check()
            ? NoticeAcknowledgement::where('notice_id', $id)->where('user_id', Auth::id())->first()
            : null;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'notice'             => $notice,
                'my_acknowledgement' => $myAcknowledgement,
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $notice = Notice::findOrFail($id);

        $notice->update($request->only([
            'title', 'content', 'notice_type', 'priority', 'target_audience',
            'target_class_id', 'publish_date', 'expiry_date', 'requires_acknowledgement'
        ]));

        ActivityAuditService::log('updated', 'notice', $notice->id, ['title' => $notice->title]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Notice updated successfully',
            'data'    => $notice,
        ]);
    }

    public function submitForApproval(int $id): JsonResponse
    {
        $notice = Notice::findOrFail($id);
        $notice->update([
            'status'          => 'pending_approval',
            'approval_status' => 'pending',
        ]);

        ActivityAuditService::log('submitted_approval', 'notice', $notice->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Notice submitted for administrative approval',
            'data'    => $notice,
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $notice = Notice::findOrFail($id);
        $action = $request->input('action', 'approve'); // approve, reject
        $reason = $request->input('reason');

        if ($action === 'approve') {
            $notice->update([
                'status'          => 'published',
                'is_published'    => true,
                'approval_status' => 'approved',
            ]);
            ActivityNotificationService::notifyNoticePublished($notice);
            ActivityAuditService::log('approved', 'notice', $notice->id);
        } else {
            $notice->update([
                'status'           => 'rejected',
                'approval_status'  => 'rejected',
                'rejection_reason' => $reason,
            ]);
            ActivityAuditService::log('rejected', 'notice', $notice->id, ['reason' => $reason]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Notice {$action}d successfully",
            'data'    => $notice,
        ]);
    }

    public function publish(int $id): JsonResponse
    {
        $notice = Notice::findOrFail($id);
        $notice->update([
            'status'       => 'published',
            'is_published' => true,
        ]);

        ActivityNotificationService::notifyNoticePublished($notice);
        ActivityAuditService::log('published', 'notice', $notice->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Notice published and notifications dispatched to audience',
            'data'    => $notice,
        ]);
    }

    public function acknowledge(Request $request, int $id): JsonResponse
    {
        $notice = Notice::findOrFail($id);
        $user = Auth::user();

        $ack = NoticeAcknowledgement::updateOrInsert(
            ['notice_id' => $notice->id, 'user_id' => $user->id],
            [
                'acknowledged_at' => now(),
                'ip_address'      => $request->ip(),
                'feedback_note'   => $request->input('feedback_note'),
                'updated_at'      => now(),
                'created_at'      => now(),
            ]
        );

        ActivityAuditService::log('acknowledged', 'notice', $notice->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Notice acknowledged successfully',
        ]);
    }

    public function acknowledgements(int $id): JsonResponse
    {
        $notice = Notice::findOrFail($id);
        $acks = NoticeAcknowledgement::with('user')
            ->where('notice_id', $id)
            ->orderBy('acknowledged_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $acks,
        ]);
    }
}
