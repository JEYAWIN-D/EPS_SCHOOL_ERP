<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeAcknowledgement;
use App\Models\NoticeRecipient;
use App\Services\ActivityNotificationService;
use App\Services\ActivityAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CircularApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Notice::with(['createdBy', 'targetClass'])
            ->withCount(['acknowledgements', 'reads'])
            ->circulars()
            ->forUser($user)
            ->when($request->type, fn($q, $v) => $q->where('notice_type', $v))
            ->when($request->priority, fn($q, $v) => $q->where('priority', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(fn($sub) => $sub->where('title', 'ilike', "%{$v}%")
                    ->orWhere('reference_no', 'ilike', "%{$v}%")
                    ->orWhere('content', 'ilike', "%{$v}%"));
            })
            ->pinnedFirst();

        $circulars = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data'   => $circulars,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to issue circulars'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'                    => 'required|string|max:255',
            'reference_no'             => 'nullable|string|max:80',
            'notice_type'              => 'required|in:circular,order,directive,memo',
            'priority'                 => 'required|in:low,normal,high,urgent',
            'target_audience'          => 'required|in:all,staff,students,parents,class',
            'publish_date'             => 'required|date',
            'content'                  => 'required|string',
            'requires_acknowledgement' => 'boolean',
            'is_pinned'                => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['created_by']   = $user->id;
        $data['is_published'] = true;
        $data['status']       = 'published';

        if (empty($data['reference_no'])) {
            $prefix = ($data['notice_type'] === 'order') ? 'EPS/ORD' : 'EPS/CIR';
            $data['reference_no'] = sprintf("%s/%s/%03d", $prefix, date('Y'), Notice::count() + 1);
        }

        $circular = Notice::create($data);

        NoticeRecipient::create([
            'notice_id'      => $circular->id,
            'recipient_type' => $circular->target_audience,
        ]);

        ActivityAuditService::log('created', 'circular', $circular->id, ['title' => $circular->title]);
        ActivityNotificationService::notifyNoticePublished($circular);

        return response()->json([
            'status'  => 'success',
            'message' => 'Official Circular / Order issued successfully',
            'data'    => $circular,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $circular = Notice::with(['createdBy', 'targetClass', 'acknowledgements.user'])
            ->findOrFail($id);

        $myAck = $user ? NoticeAcknowledgement::where('notice_id', $id)->where('user_id', $user->id)->first() : null;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'circular'          => $circular,
                'my_acknowledgement'=> $myAck,
                'total_acknowledged'=> $circular->acknowledgements->count(),
            ],
        ]);
    }

    public function acknowledge(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $circular = Notice::findOrFail($id);

        $ack = NoticeAcknowledgement::firstOrCreate(
            ['notice_id' => $circular->id, 'user_id' => $user->id],
            [
                'acknowledged_at' => now(),
                'ip_address'      => $request->ip(),
                'feedback_note'   => $request->input('feedback_note'),
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Receipt acknowledged successfully',
            'data'    => $ack,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Only administrator, principal, and correspondent can delete circulars.',
            ], 403);
        }

        $circular = Notice::findOrFail($id);
        if ($circular->attachment) Storage::disk('public')->delete($circular->attachment);
        $circular->delete();

        ActivityAuditService::log('deleted', 'circular', $id, ['title' => $circular->title]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Circular deleted successfully',
        ]);
    }
}
