<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\TaskUpdate;
use App\Services\ActivityNotificationService;
use App\Services\ActivityAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Task::with(['creator', 'assignees.user', 'department', 'reviewer'])
            ->withCount('updates')
            ->forUser($user)
            ->when($request->status, function ($q, $v) {
                if ($v === 'overdue') {
                    $q->where('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled']);
                } else {
                    $q->where('status', $v);
                }
            })
            ->when($request->priority, fn($q, $v) => $q->where('priority', $v))
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->when($request->filter === 'assigned_to_me', fn($q) => $q->whereHas('assignees', fn($a) => $a->where('user_id', $user->id)))
            ->when($request->filter === 'created_by_me', fn($q) => $q->where('creator_id', $user->id))
            ->when($request->search, function ($q, $v) {
                $q->where(fn($sub) => $sub->where('title', 'ilike', "%{$v}%")->orWhere('description', 'ilike', "%{$v}%"));
            })
            ->orderBy('due_date', 'asc');

        $tasks = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data'   => $tasks,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'priority'        => 'nullable|in:low,normal,high,urgent',
            'start_date'      => 'nullable|date',
            'due_date'        => 'nullable|date|after_or_equal:start_date',
            'source_type'     => 'nullable|string|in:independent,meeting,event,notice',
            'source_id'       => 'nullable|integer',
            'department_id'   => 'nullable|exists:departments,id',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assignees'       => 'required|array|min:1',
            'assignees.*'     => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        $task = DB::transaction(function () use ($request, $user) {
            $t = Task::create([
                'title'           => $request->title,
                'description'     => $request->description,
                'creator_id'      => $user->id,
                'priority'        => $request->input('priority', 'normal'),
                'start_date'      => $request->start_date ?? today(),
                'due_date'        => $request->due_date,
                'status'          => 'pending',
                'source_type'     => $request->input('source_type', 'independent'),
                'source_id'       => $request->source_id,
                'department_id'   => $request->department_id,
                'estimated_hours' => $request->estimated_hours,
            ]);

            $isFirst = true;
            foreach (array_unique($request->assignees) as $assigneeId) {
                TaskAssignee::create([
                    'task_id' => $t->id,
                    'user_id' => $assigneeId,
                    'role'    => $isFirst ? 'primary' : 'collaborator',
                ]);
                $isFirst = false;
            }

            ActivityAuditService::log('created', 'task', $t->id, ['title' => $t->title]);
            ActivityNotificationService::notifyTaskAssigned($t);

            return $t;
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Task created and assigned successfully',
            'data'    => $task->load(['assignees.user', 'creator']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $task = Task::with([
            'creator',
            'reviewer',
            'department',
            'assignees.user',
            'updates.user',
            'attachments',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $task,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);

        $task->update($request->only([
            'title', 'description', 'priority', 'start_date', 'due_date',
            'department_id', 'estimated_hours', 'status'
        ]));

        ActivityAuditService::log('updated', 'task', $task->id, ['title' => $task->title]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Task updated successfully',
            'data'    => $task,
        ]);
    }

    public function postUpdate(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'status'              => 'nullable|string|in:accepted,in_progress,blocked,submitted_for_review,completed',
            'comment'             => 'required|string',
            'progress_percentage' => 'nullable|integer|between:0,100',
            'evidence_path'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $prevStatus = $task->status;
        $newStatus  = $request->status ?? $task->status;

        $taskUpdate = TaskUpdate::create([
            'task_id'             => $task->id,
            'user_id'             => $user->id,
            'status_from'         => $prevStatus,
            'status_to'           => $newStatus,
            'comment'             => $request->comment,
            'progress_percentage' => $request->progress_percentage,
            'evidence_path'       => $request->evidence_path,
        ]);

        if ($newStatus !== $prevStatus) {
            $task->update(['status' => $newStatus]);
        }

        ActivityAuditService::log('progress_update', 'task', $task->id, [
            'status'   => $newStatus,
            'progress' => $request->progress_percentage,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Progress update recorded',
            'data'    => $taskUpdate->load('user'),
        ], 201);
    }

    public function submitForReview(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $user = Auth::user();

        $task->update([
            'status'           => 'submitted_for_review',
            'completion_notes' => $request->input('completion_notes', 'Task work completed. Submitted for review.'),
        ]);

        TaskUpdate::create([
            'task_id'             => $task->id,
            'user_id'             => $user->id,
            'status_from'         => $task->getOriginal('status'),
            'status_to'           => 'submitted_for_review',
            'comment'             => 'Submitted task for review upon completion.',
            'progress_percentage' => 100,
        ]);

        ActivityAuditService::log('submitted_for_review', 'task', $task->id);

        if ($task->creator_id) {
            ActivityNotificationService::send(
                $task->creator_id,
                'task',
                $task->id,
                "Task Review Request: {$task->title}",
                "Work completed by " . $user->name . ". Awaiting your review.",
                route('activities.tasks.show', $task->id),
                'info'
            );
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Task submitted for administrative review',
            'data'    => $task,
        ]);
    }

    public function review(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $user = Auth::user();
        $action = $request->input('action', 'approve'); // approve, reopen
        $notes = $request->input('notes');

        if ($action === 'approve') {
            $task->update([
                'status'           => 'completed',
                'reviewed_by'      => $user->id,
                'reviewed_at'      => now(),
                'completion_notes' => $notes ?? $task->completion_notes,
            ]);

            TaskUpdate::create([
                'task_id'     => $task->id,
                'user_id'     => $user->id,
                'status_from' => 'submitted_for_review',
                'status_to'   => 'completed',
                'comment'     => "Review Approved: " . ($notes ?? 'Task signed off successfully.'),
            ]);

            ActivityAuditService::log('approved_completion', 'task', $task->id);
        } else {
            $task->update([
                'status'      => 'reopened',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            TaskUpdate::create([
                'task_id'     => $task->id,
                'user_id'     => $user->id,
                'status_from' => 'submitted_for_review',
                'status_to'   => 'reopened',
                'comment'     => "Review Reopened: " . ($notes ?? 'Please make necessary revisions.'),
            ]);

            ActivityAuditService::log('reopened', 'task', $task->id, ['notes' => $notes]);
        }

        // Notify assignees of review outcome
        $assigneeIds = $task->assignees()->pluck('user_id')->toArray();
        ActivityNotificationService::send(
            $assigneeIds,
            'task',
            $task->id,
            "Task Review: {$task->title} (" . ucfirst($task->status) . ")",
            $notes ?? "Task has been {$task->status} by " . $user->name,
            route('activities.tasks.show', $task->id),
            $task->status === 'completed' ? 'success' : 'warning'
        );

        return response()->json([
            'status'  => 'success',
            'message' => "Task review processed ({$task->status})",
            'data'    => $task,
        ]);
    }

    public function reassign(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'new_assignee_id' => 'required|exists:users,id',
            'reason'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $newAssignee = TaskAssignee::updateOrInsert(
            ['task_id' => $task->id, 'user_id' => $request->new_assignee_id],
            ['role' => 'primary', 'updated_at' => now(), 'created_at' => now()]
        );

        TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => "Task reassigned. Reason: " . ($request->reason ?? 'Workload rebalancing'),
        ]);

        ActivityAuditService::log('reassigned', 'task', $task->id, ['new_assignee_id' => $request->new_assignee_id]);
        ActivityNotificationService::notifyTaskAssigned($task);

        return response()->json([
            'status'  => 'success',
            'message' => 'Task reassigned successfully',
        ]);
    }
}
