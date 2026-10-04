<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\NoticeAcknowledgement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardApiController extends Controller
{
    public function myActivities(): JsonResponse
    {
        $user = Auth::user();
        $today = today();

        // 1. Today's Meetings
        $todaysMeetings = Meeting::with(['organizer', 'department'])
            ->whereDate('start_time', $today)
            ->where('status', '!=', 'cancelled')
            ->forUser($user)
            ->orderBy('start_time', 'asc')
            ->get();

        // 2. Today's Events
        $todaysEvents = Event::with('organizer')
            ->whereDate('event_date', $today)
            ->where('is_published', true)
            ->forUser($user)
            ->get();

        // 3. Pending & Overdue Tasks
        $myTasks = Task::with('creator')
            ->whereHas('assignees', fn($a) => $a->where('user_id', $user->id))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('due_date', 'asc')
            ->get();

        $pendingTasks = $myTasks->where('is_overdue', false)->values();
        $overdueTasks = $myTasks->where('is_overdue', true)->values();

        // 4. Unacknowledged Notices
        $ackNoticeIds = NoticeAcknowledgement::where('user_id', $user->id)->pluck('notice_id');
        $unreadNotices = Notice::active()
            ->forUser($user)
            ->where('requires_acknowledgement', true)
            ->whereNotIn('id', $ackNoticeIds)
            ->orderBy('priority', 'desc')
            ->orderBy('publish_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'todays_meetings' => $todaysMeetings,
                'todays_events'   => $todaysEvents,
                'pending_tasks'   => $pendingTasks,
                'overdue_tasks'   => $overdueTasks,
                'unread_notices'  => $unreadNotices,
                'counts'          => [
                    'meetings' => $todaysMeetings->count(),
                    'events'   => $todaysEvents->count(),
                    'tasks'    => $myTasks->count(),
                    'overdue'  => $overdueTasks->count(),
                    'notices'  => $unreadNotices->count(),
                ],
            ],
        ]);
    }

    public function managementSummary(): JsonResponse
    {
        $activeNotices = Notice::active()->count();
        $monthEvents   = Event::where('is_published', true)->whereMonth('event_date', now()->month)->count();
        $upcomingMeetings = Meeting::where('status', 'scheduled')->where('start_time', '>=', now())->count();
        
        $totalTasks     = Task::count();
        $completedTasks = Task::where('status', 'completed')->count();
        $overdueTasks   = Task::where('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count();
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        $pendingNotices = Notice::where('status', 'pending_approval')->count();
        $pendingEvents  = Event::where('status', 'pending_approval')->count();
        $pendingReviews = Task::where('status', 'submitted_for_review')->count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'active_notices'     => $activeNotices,
                'month_events'       => $monthEvents,
                'upcoming_meetings'  => $upcomingMeetings,
                'total_tasks'        => $totalTasks,
                'completed_tasks'    => $completedTasks,
                'overdue_tasks'      => $overdueTasks,
                'completion_rate'    => $completionRate,
                'pending_approvals'  => [
                    'notices'       => $pendingNotices,
                    'events'        => $pendingEvents,
                    'task_reviews'  => $pendingReviews,
                    'total_pending' => $pendingNotices + $pendingEvents + $pendingReviews,
                ],
            ],
        ]);
    }
}
