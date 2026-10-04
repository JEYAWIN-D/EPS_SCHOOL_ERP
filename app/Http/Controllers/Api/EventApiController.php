<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventStaff;
use App\Models\EventParticipant;
use App\Services\ActivityNotificationService;
use App\Services\ActivityAuditService;
use App\Services\CalendarConflictService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EventApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Event::with(['createdBy', 'organizer', 'staff.employee'])
            ->withCount(['rsvps', 'participants'])
            ->forUser($user)
            ->when($request->event_type, fn($q, $v) => $q->where('event_type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->from, fn($q, $v) => $q->whereDate('event_date', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->whereDate('event_date', '<=', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(fn($sub) => $sub->where('name', 'ilike', "%{$v}%")->orWhere('description', 'ilike', "%{$v}%"));
            })
            ->orderBy('event_date', 'asc');

        $events = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data'   => $events,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'event_type'       => 'required|string|in:academic,cultural,sports,holiday,meeting,other',
            'category'         => 'nullable|string|max:60',
            'event_date'       => 'required|date',
            'start_time'       => 'nullable',
            'end_time'         => 'nullable',
            'venue'            => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'audience'         => 'nullable|string|in:all,students,staff,parents',
            'budget_estimated' => 'nullable|numeric|min:0',
            'allow_rsvp'       => 'boolean',
            'max_rsvp'         => 'nullable|integer',
            'staff'            => 'nullable|array',
            'staff.*.employee_id' => 'required_with:staff|exists:employees,id',
            'staff.*.role'        => 'nullable|string|in:in_charge,coordinator,support',
            'staff.*.duties'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Conflict check
        $conflicts = [];
        if ($request->venue && $request->start_time && $request->end_time) {
            $startDt = Carbon::parse($request->event_date . ' ' . $request->start_time);
            $endDt   = Carbon::parse($request->event_date . ' ' . $request->end_time);
            $conflicts = CalendarConflictService::checkConflicts($startDt, $endDt, $request->venue);
        }

        $event = DB::transaction(function () use ($request, $user) {
            $ev = Event::create([
                'name'             => $request->name,
                'event_type'       => $request->event_type,
                'category'         => $request->category,
                'event_date'       => $request->event_date,
                'start_time'       => $request->start_time,
                'end_time'         => $request->end_time,
                'venue'            => $request->venue,
                'description'      => $request->description,
                'audience'         => $request->input('audience', 'all'),
                'budget_estimated' => $request->input('budget_estimated', 0),
                'allow_rsvp'       => $request->boolean('allow_rsvp', false),
                'max_rsvp'         => $request->max_rsvp,
                'is_published'     => true,
                'status'           => 'published',
                'created_by'       => $user->id,
                'organizer_id'     => $user->id,
            ]);

            if ($request->has('staff') && is_array($request->staff)) {
                foreach ($request->staff as $st) {
                    EventStaff::create([
                        'event_id'    => $ev->id,
                        'employee_id' => $st['employee_id'],
                        'role'        => $st['role'] ?? 'support',
                        'duties'      => $st['duties'] ?? null,
                    ]);
                }
            }

            ActivityAuditService::log('created', 'event', $ev->id, ['name' => $ev->name]);
            ActivityNotificationService::notifyEventUpdated($ev, 'Scheduled');

            return $ev;
        });

        return response()->json([
            'status'    => 'success',
            'message'   => 'Event scheduled successfully',
            'data'      => $event->load(['staff.employee', 'createdBy']),
            'conflicts' => $conflicts,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $event = Event::with(['createdBy', 'organizer', 'staff.employee', 'participants', 'photos', 'rsvps.user'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $event,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $event->update($request->only([
            'name', 'event_type', 'category', 'event_date', 'start_time', 'end_time',
            'venue', 'description', 'audience', 'budget_estimated', 'budget_actual',
            'allow_rsvp', 'max_rsvp', 'status'
        ]));

        ActivityAuditService::log('updated', 'event', $event->id, ['name' => $event->name]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Event updated successfully',
            'data'    => $event,
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $action = $request->input('action', 'approve');

        if ($action === 'approve') {
            $event->update(['status' => 'approved', 'is_published' => true]);
            ActivityAuditService::log('approved', 'event', $event->id);
            ActivityNotificationService::notifyEventUpdated($event, 'Approved');
        } else {
            $event->update([
                'status'              => 'cancelled',
                'cancellation_reason' => $request->input('reason', 'Approval rejected'),
            ]);
            ActivityAuditService::log('rejected', 'event', $event->id, ['reason' => $request->input('reason')]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Event {$action}d successfully",
            'data'    => $event,
        ]);
    }

    public function addParticipants(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'participants' => 'required|array',
            'participants.*.type' => 'required|string|in:user,student,employee',
            'participants.*.id'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        foreach ($request->participants as $p) {
            EventParticipant::updateOrInsert(
                [
                    'event_id'         => $event->id,
                    'participant_type' => $p['type'],
                    'participant_id'   => $p['id'],
                ],
                [
                    'status'     => 'invited',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Participants invited successfully',
        ]);
    }

    public function recordAttendance(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'attendance' => 'required|array',
            'attendance.*.participant_id' => 'required|integer',
            'attendance.*.status'         => 'required|string|in:attended,absent,confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        foreach ($request->attendance as $att) {
            EventParticipant::where('event_id', $event->id)
                ->where('id', $att['participant_id'])
                ->update([
                    'status'      => $att['status'],
                    'check_in_at' => $att['status'] === 'attended' ? now() : null,
                ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Event attendance recorded successfully',
        ]);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $event->update([
            'status'            => 'completed',
            'budget_actual'     => $request->input('budget_actual', $event->budget_actual),
            'post_event_report' => $request->input('post_event_report'),
        ]);

        ActivityAuditService::log('completed', 'event', $event->id, [
            'budget_actual' => $event->budget_actual,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Event marked completed with expense report',
            'data'    => $event,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Only administrator, principal, and correspondent can delete events.',
            ], 403);
        }

        $event = Event::findOrFail($id);
        if ($event->banner_image) Storage::disk('public')->delete($event->banner_image);
        $event->delete();

        ActivityAuditService::log('deleted', 'event', $id, ['name' => $event->name]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Event deleted successfully',
        ]);
    }
}
