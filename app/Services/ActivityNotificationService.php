<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notice;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityNotificationService
{
    /**
     * Dispatch an in-app notification to one or multiple users.
     */
    public static function send(
        array|int|User $recipients,
        string $module,
        int $sourceId,
        string $title,
        string $message,
        string $actionUrl,
        string $type = 'info'
    ): void {
        $userIds = [];

        if ($recipients instanceof User) {
            $userIds = [$recipients->id];
        } elseif (is_int($recipients)) {
            $userIds = [$recipients];
        } elseif (is_array($recipients)) {
            foreach ($recipients as $r) {
                if ($r instanceof User) {
                    $userIds[] = $r->id;
                } elseif (is_numeric($r)) {
                    $userIds[] = (int) $r;
                }
            }
        }

        $userIds = array_unique(array_filter($userIds));
        if (empty($userIds)) return;

        $now = now();
        $records = [];

        try {
            foreach ($userIds as $userId) {
                // Deduplicate: avoid identical unread notification in the last 10 minutes (PostgreSQL text-column compatible)
                $exists = false;
                try {
                    $exists = DB::table('notifications')
                        ->where('notifiable_type', User::class)
                        ->where('notifiable_id', $userId)
                        ->whereNull('read_at')
                        ->where('created_at', '>=', now()->subMinutes(10))
                        ->where(function ($q) use ($sourceId, $module) {
                            $q->where('data', 'like', '%"source_id":' . $sourceId . '%')
                              ->orWhere('data', 'like', '%"source_id":"' . $sourceId . '"%');
                        })
                        ->exists();
                } catch (\Throwable $dedupEx) {
                    $exists = false;
                }

                if ($exists) continue;

                $records[] = [
                    'id'              => (string) Str::uuid(),
                    'type'            => 'App\Notifications\ActivityHubNotification',
                    'notifiable_type' => User::class,
                    'notifiable_id'   => $userId,
                    'data'            => json_encode([
                        'module'     => $module,
                        'source_id'  => $sourceId,
                        'title'      => $title,
                        'message'    => $message,
                        'action_url' => $actionUrl,
                        'type'       => $type,
                        'created_at' => $now->toIso8601String(),
                    ]),
                    'read_at'         => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            if (!empty($records)) {
                DB::table('notifications')->insert($records);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ActivityNotificationService::send failed: ' . $e->getMessage());
        }
    }

    /**
     * Notify target audience when a notice is published.
     */
    public static function notifyNoticePublished(Notice $notice): void
    {
        $query = User::where('is_active', true);

        if ($notice->target_audience === 'staff') {
            $query->where(fn($q) => $q->whereNotNull('employee_id')->orWhereHas('roles', fn($r) => $r->whereIn('name', ['teacher', 'principal', 'admin', 'staff', 'hr_manager'])));
        } elseif ($notice->target_audience === 'students') {
            $query->whereNotNull('student_id');
            if ($notice->target_class_id) {
                $query->whereHas('student', fn($s) => $s->where('class_id', $notice->target_class_id));
            }
        } elseif ($notice->target_audience === 'parents') {
            $query->whereHas('roles', fn($r) => $r->where('name', 'parent'));
        }

        $userIds = $query->pluck('id')->toArray();

        // Also add explicit recipients
        $explicit = $notice->recipients()->get();
        foreach ($explicit as $rec) {
            if ($rec->recipient_type === 'employee') {
                $empUser = User::where('employee_id', $rec->recipient_id)->value('id');
                if ($empUser) $userIds[] = $empUser;
            } elseif ($rec->recipient_type === 'student') {
                $stdUser = User::where('student_id', $rec->recipient_id)->value('id');
                if ($stdUser) $userIds[] = $stdUser;
            }
        }

        self::send(
            $userIds,
            'notice',
            $notice->id,
            "Notice: {$notice->title}",
            Str::limit($notice->content, 120),
            route('activities.notices.show', $notice->id),
            $notice->priority === 'urgent' ? 'warning' : 'info'
        );
    }

    /**
     * Notify in-charge staff and participants for an event.
     */
    public static function notifyEventUpdated(Event $event, string $action = 'Created'): void
    {
        $userIds = [];
        if ($event->organizer_id) $userIds[] = $event->organizer_id;

        // In-charge staff
        foreach ($event->staff as $st) {
            $u = User::where('employee_id', $st->employee_id)->value('id');
            if ($u) $userIds[] = $u;
        }

        self::send(
            $userIds,
            'event',
            $event->id,
            "Event {$action}: {$event->name}",
            "Date: {$event->event_date->format('d M Y')} at " . ($event->venue ?? 'School Campus'),
            route('activities.events.show', $event->id),
            'info'
        );
    }

    /**
     * Notify attendees about meeting invitation or updates.
     */
    public static function notifyMeetingScheduled(Meeting $meeting, string $action = 'Invitation'): void
    {
        $attendeeIds = $meeting->attendees()->pluck('user_id')->toArray();

        self::send(
            $attendeeIds,
            'meeting',
            $meeting->id,
            "Meeting {$action}: {$meeting->title}",
            "Scheduled on {$meeting->start_time->format('d M Y, h:i A')} at " . ($meeting->venue ?? 'Online'),
            route('activities.meetings.show', $meeting->id),
            'info'
        );
    }

    /**
     * Notify assigned staff about tasks.
     */
    public static function notifyTaskAssigned(Task $task): void
    {
        $assigneeIds = $task->assignees()->pluck('user_id')->toArray();

        self::send(
            $assigneeIds,
            'task',
            $task->id,
            "New Task Assigned: {$task->title}",
            "Priority: " . ucfirst($task->priority) . ($task->due_date ? " | Due: {$task->due_date->format('d M Y')}" : ""),
            route('activities.tasks.show', $task->id),
            $task->priority === 'urgent' ? 'warning' : 'info'
        );
    }
}
