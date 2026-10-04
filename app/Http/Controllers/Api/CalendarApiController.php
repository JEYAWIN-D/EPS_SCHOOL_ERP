<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Notice;
use App\Models\Holiday;
use App\Models\ExamSchedule;
use App\Services\CalendarConflictService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CalendarApiController extends Controller
{
    public function activities(Request $request): JsonResponse
    {
        $user = Auth::user();

        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year  ?? now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->subDays(7);
        $end   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->addDays(7);

        $typeFilter     = $request->type; // all, event, meeting, task, notice, holiday, exam
        $deptFilter     = $request->department_id;
        $priorityFilter = $request->priority;

        $items = [];

        // 1. Events
        if (!$typeFilter || $typeFilter === 'all' || $typeFilter === 'event') {
            $events = Event::with('organizer')
                ->where('is_published', true)
                ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
                ->forUser($user)
                ->get();

            foreach ($events as $ev) {
                $items[] = [
                    'id'          => "event-{$ev->id}",
                    'raw_id'      => $ev->id,
                    'type'        => 'event',
                    'title'       => $ev->name,
                    'category'    => $ev->category ?? ucfirst($ev->event_type),
                    'start'       => $ev->event_date->toDateString() . ($ev->start_time ? 'T' . $ev->start_time : ''),
                    'end'         => $ev->event_date->toDateString() . ($ev->end_time ? 'T' . $ev->end_time : ''),
                    'venue'       => $ev->venue,
                    'color'       => match($ev->event_type) {
                        'sports'    => '#10b981', // emerald
                        'cultural'  => '#ec4899', // pink
                        'academic'  => '#6366f1', // indigo
                        default     => '#3b82f6', // blue
                    },
                    'status'      => $ev->status,
                    'url'         => route('activities.events.show', $ev->id),
                ];
            }
        }

        // 2. Meetings
        if (!$typeFilter || $typeFilter === 'all' || $typeFilter === 'meeting') {
            $meetings = Meeting::with('organizer')
                ->where('status', '!=', 'cancelled')
                ->whereBetween('start_time', [$start, $end])
                ->when($deptFilter, fn($q, $v) => $q->where('department_id', $v))
                ->forUser($user)
                ->get();

            foreach ($meetings as $m) {
                $items[] = [
                    'id'       => "meeting-{$m->id}",
                    'raw_id'   => $m->id,
                    'type'     => 'meeting',
                    'title'    => $m->title,
                    'category' => ucfirst($m->meeting_type) . ' Meeting',
                    'start'    => $m->start_time->toIso8601String(),
                    'end'      => $m->end_time->toIso8601String(),
                    'venue'    => $m->venue ?? ($m->meeting_link ? 'Online Link' : 'School Campus'),
                    'color'    => '#8b5cf6', // purple
                    'status'   => $m->status,
                    'url'      => route('activities.meetings.show', $m->id),
                ];
            }
        }

        // 3. Tasks (Deadlines)
        if (!$typeFilter || $typeFilter === 'all' || $typeFilter === 'task') {
            $tasks = Task::with('creator')
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
                ->when($deptFilter, fn($q, $v) => $q->where('department_id', $v))
                ->when($priorityFilter, fn($q, $v) => $q->where('priority', $v))
                ->forUser($user)
                ->get();

            foreach ($tasks as $t) {
                $items[] = [
                    'id'       => "task-{$t->id}",
                    'raw_id'   => $t->id,
                    'type'     => 'task',
                    'title'    => "Due: {$t->title}",
                    'category' => ucfirst($t->priority) . ' Priority Task',
                    'start'    => $t->due_date->toDateString(),
                    'end'      => $t->due_date->toDateString(),
                    'venue'    => null,
                    'color'    => match($t->priority) {
                        'urgent' => '#ef4444', // red
                        'high'   => '#f97316', // orange
                        default  => '#eab308', // amber
                    },
                    'status'   => $t->status,
                    'url'      => route('activities.tasks.show', $t->id),
                ];
            }
        }

        // 4. Notices (Publish Dates)
        if (!$typeFilter || $typeFilter === 'all' || $typeFilter === 'notice') {
            $notices = Notice::where('is_published', true)
                ->whereBetween('publish_date', [$start->toDateString(), $end->toDateString()])
                ->forUser($user)
                ->get();

            foreach ($notices as $n) {
                $items[] = [
                    'id'       => "notice-{$n->id}",
                    'raw_id'   => $n->id,
                    'type'     => 'notice',
                    'title'    => "Notice: {$n->title}",
                    'category' => ucfirst($n->notice_type) . ' Notice',
                    'start'    => $n->publish_date->toDateString(),
                    'end'      => $n->publish_date->toDateString(),
                    'venue'    => null,
                    'color'    => '#06b6d4', // cyan
                    'status'   => $n->status,
                    'url'      => route('activities.notices.show', $n->id),
                ];
            }
        }

        // 5. School Holidays
        if (!$typeFilter || $typeFilter === 'all' || $typeFilter === 'holiday') {
            $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])->get();
            foreach ($holidays as $h) {
                $items[] = [
                    'id'       => "holiday-{$h->id}",
                    'raw_id'   => $h->id,
                    'type'     => 'holiday',
                    'title'    => "Holiday: {$h->name}",
                    'category' => ucfirst($h->type ?? 'School') . ' Holiday',
                    'start'    => $h->date->toDateString(),
                    'end'      => ($h->end_date ?? $h->date)->toDateString(),
                    'venue'    => 'School Closed',
                    'color'    => '#f59e0b', // amber
                    'status'   => 'holiday',
                    'url'      => '#',
                ];
            }
        }

        // 6. Examination Schedules
        if (!$typeFilter || $typeFilter === 'all' || $typeFilter === 'exam') {
            $exams = ExamSchedule::with(['exam', 'class', 'subject'])
                ->whereBetween('exam_date', [$start->toDateString(), $end->toDateString()])
                ->get();

            foreach ($exams as $ex) {
                $items[] = [
                    'id'       => "exam-{$ex->id}",
                    'raw_id'   => $ex->id,
                    'type'     => 'exam',
                    'title'    => "Exam: " . ($ex->exam?->name ?? 'Exam') . " - {$ex->subject?->name}",
                    'category' => "Class " . ($ex->class?->name ?? '') . " Examination",
                    'start'    => $ex->exam_date->toDateString() . ($ex->start_time ? 'T' . $ex->start_time : ''),
                    'end'      => $ex->exam_date->toDateString() . ($ex->end_time ? 'T' . $ex->end_time : ''),
                    'venue'    => $ex->venue ?? 'Exam Hall',
                    'color'    => '#475569', // slate
                    'status'   => 'exam',
                    'url'      => '#',
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => $items,
        ]);
    }

    public function conflicts(Request $request): JsonResponse
    {
        $start = $request->input('start');
        $end   = $request->input('end');
        $venue = $request->input('venue');
        $users = (array) $request->input('user_ids', []);

        if (!$start || !$end) {
            return response()->json(['status' => 'error', 'message' => 'start and end times are required'], 422);
        }

        $conflicts = CalendarConflictService::checkConflicts(
            $start,
            $end,
            $venue,
            $users,
            $request->input('exclude_meeting_id'),
            $request->input('exclude_event_id')
        );

        return response()->json([
            'status'         => 'success',
            'has_conflicts'  => !empty($conflicts),
            'conflict_count' => count($conflicts),
            'data'           => $conflicts,
        ]);
    }
}
