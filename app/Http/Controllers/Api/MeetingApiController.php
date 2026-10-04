<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\MeetingMinute;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Services\ActivityNotificationService;
use App\Services\ActivityAuditService;
use App\Services\CalendarConflictService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MeetingApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Meeting::with(['organizer', 'chairperson', 'department'])
            ->withCount('attendees')
            ->forUser($user)
            ->when($request->meeting_type, fn($q, $v) => $q->where('meeting_type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->from, fn($q, $v) => $q->whereDate('start_time', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->whereDate('start_time', '<=', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(fn($sub) => $sub->where('title', 'ilike', "%{$v}%")->orWhere('agenda', 'ilike', "%{$v}%"));
            })
            ->orderBy('start_time', 'asc');

        $meetings = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data'   => $meetings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'agenda'         => 'nullable|string',
            'meeting_type'   => 'required|string|in:staff,hod,management,ptm,departmental,general',
            'start_time'     => 'required|date',
            'end_time'       => 'required|date|after:start_time',
            'venue'          => 'nullable|string|max:255',
            'meeting_link'   => 'nullable|url|max:255',
            'chairperson_id' => 'nullable|exists:users,id',
            'department_id'  => 'nullable|exists:departments,id',
            'attendees'      => 'required|array|min:1',
            'attendees.*'    => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Conflict check
        $conflicts = CalendarConflictService::checkConflicts(
            $request->start_time,
            $request->end_time,
            $request->venue,
            $request->attendees
        );

        $meeting = DB::transaction(function () use ($request, $user) {
            $m = Meeting::create([
                'title'          => $request->title,
                'agenda'         => $request->agenda,
                'meeting_type'   => $request->meeting_type,
                'organizer_id'   => $user->id,
                'chairperson_id' => $request->chairperson_id ?? $user->id,
                'department_id'  => $request->department_id,
                'start_time'     => $request->start_time,
                'end_time'       => $request->end_time,
                'venue'          => $request->venue,
                'meeting_link'   => $request->meeting_link,
                'status'         => 'scheduled',
            ]);

            // Add attendees
            foreach (array_unique($request->attendees) as $attId) {
                MeetingAttendee::create([
                    'meeting_id'        => $m->id,
                    'user_id'           => $attId,
                    'is_optional'       => false,
                    'rsvp_status'       => $attId == $user->id ? 'accepted' : 'pending',
                    'attendance_status' => 'pending',
                ]);
            }

            ActivityAuditService::log('created', 'meeting', $m->id, ['title' => $m->title]);
            ActivityNotificationService::notifyMeetingScheduled($m, 'Invitation');

            return $m;
        });

        return response()->json([
            'status'    => 'success',
            'message'   => 'Meeting scheduled successfully',
            'data'      => $meeting->load(['attendees.user', 'organizer', 'chairperson']),
            'conflicts' => $conflicts,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $meeting = Meeting::with([
            'organizer',
            'chairperson',
            'department',
            'attendees.user',
            'minutes.recorder',
            'tasks.assignees.user',
            'attachments',
        ])->findOrFail($id);

        $myRsvp = Auth::check()
            ? MeetingAttendee::where('meeting_id', $id)->where('user_id', Auth::id())->first()
            : null;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'meeting' => $meeting,
                'my_rsvp' => $myRsvp,
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $meeting = Meeting::findOrFail($id);

        $meeting->update($request->only([
            'title', 'agenda', 'meeting_type', 'start_time', 'end_time',
            'venue', 'meeting_link', 'status', 'cancellation_reason'
        ]));

        ActivityAuditService::log('updated', 'meeting', $meeting->id, ['title' => $meeting->title]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Meeting updated successfully',
            'data'    => $meeting,
        ]);
    }

    public function rsvp(Request $request, int $id): JsonResponse
    {
        $meeting = Meeting::findOrFail($id);
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,declined,tentative',
            'note'   => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $attendee = MeetingAttendee::updateOrInsert(
            ['meeting_id' => $meeting->id, 'user_id' => $user->id],
            [
                'rsvp_status' => $request->status,
                'rsvp_note'   => $request->note,
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        ActivityAuditService::log('rsvp', 'meeting', $meeting->id, ['status' => $request->status]);

        return response()->json([
            'status'  => 'success',
            'message' => 'RSVP updated successfully',
        ]);
    }

    public function recordAttendance(Request $request, int $id): JsonResponse
    {
        $meeting = Meeting::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'attendance' => 'required|array',
            'attendance.*.user_id' => 'required|exists:users,id',
            'attendance.*.status'  => 'required|in:present,absent,excused',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        foreach ($request->attendance as $att) {
            MeetingAttendee::where('meeting_id', $meeting->id)
                ->where('user_id', $att['user_id'])
                ->update([
                    'attendance_status' => $att['status'],
                    'attended_at'       => $att['status'] === 'present' ? now() : null,
                ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Meeting attendance recorded successfully',
        ]);
    }

    public function recordMinutes(Request $request, int $id): JsonResponse
    {
        $meeting = Meeting::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'summary'       => 'required|string',
            'key_decisions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $minute = MeetingMinute::create([
            'meeting_id'    => $meeting->id,
            'recorded_by'   => Auth::id(),
            'summary'       => $request->summary,
            'key_decisions' => $request->key_decisions,
        ]);

        $meeting->update(['status' => 'completed']);
        ActivityAuditService::log('recorded_mom', 'meeting', $meeting->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Minutes of Meeting recorded successfully',
            'data'    => $minute,
        ], 201);
    }

    public function createActionItems(Request $request, int $id): JsonResponse
    {
        $meeting = Meeting::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'action_items'                => 'required|array|min:1',
            'action_items.*.title'        => 'required|string|max:255',
            'action_items.*.description'  => 'nullable|string',
            'action_items.*.assignee_id'  => 'required|exists:users,id',
            'action_items.*.due_date'     => 'nullable|date',
            'action_items.*.priority'     => 'nullable|in:low,normal,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $createdTasks = [];
        $user = Auth::user();

        DB::transaction(function () use ($request, $meeting, $user, &$createdTasks) {
            foreach ($request->action_items as $item) {
                $t = Task::create([
                    'title'         => $item['title'],
                    'description'   => $item['description'] ?? null,
                    'creator_id'    => $user->id,
                    'priority'      => $item['priority'] ?? 'normal',
                    'due_date'      => $item['due_date'] ?? null,
                    'status'        => 'pending',
                    'source_type'   => 'meeting',
                    'source_id'     => $meeting->id,
                    'department_id' => $meeting->department_id,
                ]);

                TaskAssignee::create([
                    'task_id' => $t->id,
                    'user_id' => $item['assignee_id'],
                    'role'    => 'primary',
                ]);

                ActivityAuditService::log('created_from_meeting', 'task', $t->id, ['meeting_id' => $meeting->id]);
                ActivityNotificationService::notifyTaskAssigned($t);
                $createdTasks[] = $t;
            }

            MeetingMinute::where('meeting_id', $meeting->id)->increment('action_items_count', count($createdTasks));
        });

        return response()->json([
            'status'  => 'success',
            'message' => count($createdTasks) . ' action items converted into assigned tasks',
            'data'    => $createdTasks,
        ], 201);
    }
}
