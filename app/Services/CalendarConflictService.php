<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\ExamSchedule;
use Carbon\Carbon;

class CalendarConflictService
{
    /**
     * Check for scheduling conflicts for a meeting or event.
     *
     * @param string|Carbon $start
     * @param string|Carbon $end
     * @param string|null $venue
     * @param array $userIds
     * @param int|null $excludeMeetingId
     * @param int|null $excludeEventId
     * @return array List of conflict warnings
     */
    public static function checkConflicts(
        string|Carbon $start,
        string|Carbon $end,
        ?string $venue = null,
        array $userIds = [],
        ?int $excludeMeetingId = null,
        ?int $excludeEventId = null
    ): array {
        $startTime = Carbon::parse($start);
        $endTime   = Carbon::parse($end);
        $dateStr   = $startTime->toDateString();
        $conflicts = [];

        // 1. Holiday Check
        $holiday = Holiday::whereDate('date', '<=', $dateStr)
            ->where(fn($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $dateStr))
            ->first();
        if ($holiday) {
            $conflicts[] = [
                'type'     => 'holiday',
                'severity' => 'warning',
                'title'    => "School Holiday: {$holiday->name}",
                'details'  => "The selected date ($dateStr) is marked as a {$holiday->type} holiday.",
            ];
        }

        // 2. Exam Schedule Check
        $exam = ExamSchedule::with('exam', 'class', 'subject')
            ->whereDate('exam_date', $dateStr)
            ->first();
        if ($exam) {
            $conflicts[] = [
                'type'     => 'exam',
                'severity' => 'warning',
                'title'    => "Examination Scheduled: {$exam->exam?->name}",
                'details'  => "Exam scheduled for Class {$exam->class?->name} ({$exam->subject?->name}) on this date.",
            ];
        }

        // 3. Venue Conflict Check
        if ($venue && trim($venue) !== '' && strtolower($venue) !== 'online') {
            // Overlapping meetings at the same venue
            $meetingVenueConflict = Meeting::where('venue', 'ilike', trim($venue))
                ->where('status', '!=', 'cancelled')
                ->when($excludeMeetingId, fn($q) => $q->where('id', '!=', $excludeMeetingId))
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where(fn($sub) => $sub->where('start_time', '<', $endTime)->where('end_time', '>', $startTime));
                })
                ->first();

            if ($meetingVenueConflict) {
                $conflicts[] = [
                    'type'     => 'venue_meeting',
                    'severity' => 'error',
                    'title'    => "Venue Conflict: {$venue}",
                    'details'  => "Venue already booked for Meeting '{$meetingVenueConflict->title}' ({$meetingVenueConflict->start_time->format('h:i A')} - {$meetingVenueConflict->end_time->format('h:i A')}).",
                ];
            }

            // Overlapping events at the same venue
            $eventVenueConflict = Event::where('venue', 'ilike', trim($venue))
                ->where('status', '!=', 'cancelled')
                ->whereDate('event_date', $dateStr)
                ->when($excludeEventId, fn($q) => $q->where('id', '!=', $excludeEventId))
                ->first();

            if ($eventVenueConflict) {
                $conflicts[] = [
                    'type'     => 'venue_event',
                    'severity' => 'error',
                    'title'    => "Venue Conflict with Event: {$venue}",
                    'details'  => "Venue already allocated to Event '{$eventVenueConflict->name}' on this day.",
                ];
            }
        }

        // 4. Staff / User Availability Check
        if (!empty($userIds)) {
            $userConflicts = Meeting::whereHas('attendees', fn($a) => $a->whereIn('user_id', $userIds))
                ->where('status', '!=', 'cancelled')
                ->when($excludeMeetingId, fn($q) => $q->where('id', '!=', $excludeMeetingId))
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where(fn($sub) => $sub->where('start_time', '<', $endTime)->where('end_time', '>', $startTime));
                })
                ->with('attendees.user')
                ->get();

            foreach ($userConflicts as $mConflict) {
                $clashingUsers = $mConflict->attendees->whereIn('user_id', $userIds)->pluck('user.name')->implode(', ');
                $conflicts[] = [
                    'type'     => 'staff_overlap',
                    'severity' => 'warning',
                    'title'    => "Staff Double-Booking: {$clashingUsers}",
                    'details'  => "Staff member is already attending meeting '{$mConflict->title}' ({$mConflict->start_time->format('h:i A')} - {$mConflict->end_time->format('h:i A')}).",
                ];
            }
        }

        return $conflicts;
    }
}
