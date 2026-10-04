<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeAcknowledgement;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\ActivityAuditLog;
use App\Models\Classes;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ActivityHubController extends Controller
{
    /**
     * My Day personal activities cockpit
     */
    public function myDay()
    {
        $user  = Auth::user();
        $today = today();

        $meetings = Meeting::with(['organizer', 'department'])
            ->whereDate('start_time', $today)
            ->where('status', '!=', 'cancelled')
            ->forUser($user)
            ->orderBy('start_time')
            ->get();

        $events = Event::with('organizer')
            ->whereDate('event_date', $today)
            ->where('is_published', true)
            ->forUser($user)
            ->get();

        $allTasks = Task::with('creator')
            ->whereHas('assignees', fn($a) => $a->where('user_id', $user->id))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('due_date')
            ->get();

        $pendingTasks = $allTasks->where('is_overdue', false);
        $overdueTasks = $allTasks->where('is_overdue', true);

        $ackNoticeIds = NoticeAcknowledgement::where('user_id', $user->id)->pluck('notice_id');
        $unreadNotices = Notice::active()
            ->forUser($user)
            ->where('requires_acknowledgement', true)
            ->whereNotIn('id', $ackNoticeIds)
            ->orderBy('priority', 'desc')
            ->get();

        return view('activities.my-day', compact(
            'meetings', 'events', 'pendingTasks', 'overdueTasks', 'unreadNotices'
        ));
    }

    /**
     * Notices List
     */
    public function notices(Request $request)
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

        $notices = $query->paginate(15)->withQueryString();

        $totalNotices  = Notice::count();
        $urgentNotices = Notice::where('priority', 'urgent')->where('status', 'published')->count();
        $pendingCount  = Notice::where('status', 'pending_approval')->count();

        return view('activities.notices.index', compact('notices', 'totalNotices', 'urgentNotices', 'pendingCount'));
    }

    public function createNotice()
    {
        $classes     = Classes::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $employees   = Employee::with('department')->orderBy('first_name')->get();

        return view('activities.notices.create', compact('classes', 'departments', 'employees'));
    }

    public function showNotice(int $id)
    {
        $notice = Notice::with(['createdBy', 'targetClass', 'recipients', 'acknowledgements.user'])
            ->findOrFail($id);

        $myAcknowledgement = Auth::check()
            ? NoticeAcknowledgement::where('notice_id', $id)->where('user_id', Auth::id())->first()
            : null;

        return view('activities.notices.show', compact('notice', 'myAcknowledgement'));
    }

    /**
     * Events List
     */
    public function events(Request $request)
    {
        $user = Auth::user();
        $query = Event::with(['createdBy', 'organizer', 'staff.employee'])
            ->withCount(['rsvps', 'participants'])
            ->forUser($user)
            ->when($request->event_type, fn($q, $v) => $q->where('event_type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(fn($sub) => $sub->where('name', 'ilike', "%{$v}%")->orWhere('description', 'ilike', "%{$v}%"));
            })
            ->orderBy('event_date', 'asc');

        $events = $query->paginate(15)->withQueryString();

        $totalEvents    = Event::count();
        $upcomingEvents = Event::where('event_date', '>=', today())->where('is_published', true)->count();
        $thisMonthCount = Event::whereMonth('event_date', now()->month)->count();

        return view('activities.events.index', compact('events', 'totalEvents', 'upcomingEvents', 'thisMonthCount'));
    }

    public function createEvent()
    {
        $employees = Employee::orderBy('first_name')->get();
        $users     = User::where('is_active', true)->orderBy('name')->get();

        return view('activities.events.create', compact('employees', 'users'));
    }

    public function showEvent(int $id)
    {
        $event = Event::with(['createdBy', 'organizer', 'staff.employee', 'participants', 'photos', 'rsvps.user'])
            ->findOrFail($id);

        return view('activities.events.show', compact('event'));
    }

    /**
     * Meetings List
     */
    public function meetings(Request $request)
    {
        $user = Auth::user();
        $query = Meeting::with(['organizer', 'chairperson', 'department'])
            ->withCount('attendees')
            ->forUser($user)
            ->when($request->meeting_type, fn($q, $v) => $q->where('meeting_type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(fn($sub) => $sub->where('title', 'ilike', "%{$v}%")->orWhere('agenda', 'ilike', "%{$v}%"));
            })
            ->orderBy('start_time', 'asc');

        $meetings = $query->paginate(15)->withQueryString();

        $upcomingCount  = Meeting::where('start_time', '>=', now())->where('status', 'scheduled')->count();
        $completedCount = Meeting::where('status', 'completed')->count();

        return view('activities.meetings.index', compact('meetings', 'upcomingCount', 'completedCount'));
    }

    public function createMeeting()
    {
        $departments = Department::orderBy('name')->get();
        $users       = User::where('is_active', true)->orderBy('name')->get();

        return view('activities.meetings.create', compact('departments', 'users'));
    }

    public function showMeeting(int $id)
    {
        $meeting = Meeting::with([
            'organizer', 'chairperson', 'department', 'attendees.user',
            'minutes.recorder', 'tasks.assignees.user'
        ])->findOrFail($id);

        $myRsvp = Auth::check()
            ? $meeting->attendees->firstWhere('user_id', Auth::id())
            : null;

        $staffUsers = User::where('is_active', true)->orderBy('name')->get();

        return view('activities.meetings.show', compact('meeting', 'myRsvp', 'staffUsers'));
    }

    /**
     * Tasks List
     */
    public function tasks(Request $request)
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

        $tasks = $query->paginate(15)->withQueryString();

        $totalTasks     = Task::count();
        $myPendingTasks = Task::whereHas('assignees', fn($a) => $a->where('user_id', $user->id))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        $overdueCount   = Task::where('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count();
        $inReviewCount  = Task::where('status', 'submitted_for_review')->count();

        $departments = Department::orderBy('name')->get();

        return view('activities.tasks.index', compact(
            'tasks', 'totalTasks', 'myPendingTasks', 'overdueCount', 'inReviewCount', 'departments'
        ));
    }

    public function createTask()
    {
        $departments = Department::orderBy('name')->get();
        $users       = User::where('is_active', true)->orderBy('name')->get();

        return view('activities.tasks.create', compact('departments', 'users'));
    }

    public function showTask(int $id)
    {
        $task = Task::with(['creator', 'reviewer', 'department', 'assignees.user', 'updates.user'])
            ->findOrFail($id);

        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('activities.tasks.show', compact('task', 'users'));
    }

    /**
     * Unified Interactive Calendar
     */
    public function calendar(Request $request)
    {
        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year  ?? now()->year);

        $departments = Department::orderBy('name')->get();

        return view('activities.calendar', compact('month', 'year', 'departments'));
    }

    /**
     * Approvals Desk Inbox
     */
    public function approvals()
    {
        $pendingNotices = Notice::with('createdBy')->where('status', 'pending_approval')->orderBy('created_at', 'desc')->get();
        $pendingEvents  = Event::with(['createdBy', 'organizer'])->where('status', 'pending_approval')->orderBy('created_at', 'desc')->get();
        $pendingTasks   = Task::with(['creator', 'assignees.user'])->where('status', 'submitted_for_review')->orderBy('updated_at', 'desc')->get();

        return view('activities.approvals', compact('pendingNotices', 'pendingEvents', 'pendingTasks'));
    }

    /**
     * Management Activity Reports & Audit Trail
     */
    public function reports(Request $request)
    {
        $auditLogs = ActivityAuditLog::with('user')
            ->when($request->entity_type, fn($q, $v) => $q->where('entity_type', $v))
            ->when($request->action, fn($q, $v) => $q->where('action', $v))
            ->orderBy('created_at', 'desc')
            ->paginate(25)->withQueryString();

        $noticeCount  = Notice::count();
        $eventCount   = Event::count();
        $meetingCount = Meeting::count();
        $taskCount    = Task::count();

        $completedTasks = Task::where('status', 'completed')->count();
        $taskCompletionRate = $taskCount > 0 ? round(($completedTasks / $taskCount) * 100, 1) : 0;

        return view('activities.reports', compact(
            'auditLogs', 'noticeCount', 'eventCount', 'meetingCount', 'taskCount', 'taskCompletionRate'
        ));
    }
}
