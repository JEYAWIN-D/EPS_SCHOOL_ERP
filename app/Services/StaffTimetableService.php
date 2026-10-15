<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Employee;
use App\Models\ExtraClass;
use App\Models\Holiday;
use App\Models\PeriodReport;
use App\Models\Section;
use App\Models\StaffDuty;
use App\Models\StaffFreePeriodTask;
use App\Models\Subject;
use App\Models\Substitution;
use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffTimetableService
{
    /**
     * Standard institutional period definitions including timings and breaks.
     */
    public static function getStandardPeriodsConfig(): array
    {
        return [
            1 => [
                'period_number' => 1,
                'name'          => 'Period 1',
                'start_time'    => '09:15',
                'end_time'      => '10:00',
                'duration_mins' => 45,
                'is_break'      => false,
            ],
            2 => [
                'period_number' => 2,
                'name'          => 'Period 2',
                'start_time'    => '10:00',
                'end_time'      => '10:45',
                'duration_mins' => 45,
                'is_break'      => false,
            ],
            // Morning Break (10:45 - 11:00)
            3 => [
                'period_number' => 3,
                'name'          => 'Period 3',
                'start_time'    => '11:00',
                'end_time'      => '11:45',
                'duration_mins' => 45,
                'is_break'      => false,
            ],
            4 => [
                'period_number' => 4,
                'name'          => 'Period 4',
                'start_time'    => '11:45',
                'end_time'      => '12:30',
                'duration_mins' => 45,
                'is_break'      => false,
            ],
            // Lunch Break (12:30 - 13:15)
            5 => [
                'period_number' => 5,
                'name'          => 'Period 5',
                'start_time'    => '13:15',
                'end_time'      => '14:00',
                'duration_mins' => 45,
                'is_break'      => false,
            ],
            6 => [
                'period_number' => 6,
                'name'          => 'Period 6',
                'start_time'    => '14:00',
                'end_time'      => '14:45',
                'duration_mins' => 45,
                'is_break'      => false,
            ],
            // Afternoon Break (14:45 - 15:00)
            7 => [
                'period_number' => 7,
                'name'          => 'Period 7',
                'start_time'    => '15:00',
                'end_time'      => '15:45',
                'duration_mins' => 45,
                'is_break'      => false,
            ],
            8 => [
                'period_number' => 8,
                'name'          => 'Period 8',
                'start_time'    => '15:45',
                'end_time'      => '16:30',
                'duration_mins' => 45,
                'is_break'      => false,
            ],
        ];
    }

    /**
     * Institutional breaks schedule for display reference.
     */
    public static function getBreaksConfig(): array
    {
        return [
            ['name' => 'Morning Refreshment', 'start_time' => '10:45', 'end_time' => '11:00', 'duration' => '15 mins'],
            ['name' => 'Lunch Break',         'start_time' => '12:30', 'end_time' => '13:15', 'duration' => '45 mins'],
            ['name' => 'Afternoon Dispersal', 'start_time' => '14:45', 'end_time' => '15:00', 'duration' => '15 mins'],
        ];
    }

    /**
     * Automatically derive an individual staff member's complete schedule for a given date.
     */
    public function getDaySchedule(Employee $teacher, Carbon|string $date): array
    {
        $targetDate = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $dateStr = $targetDate->toDateString();
        $dayOfWeek = $targetDate->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
        $dayName = $targetDate->format('l');

        // Check if Sunday or Holiday
        $isSunday = ($dayOfWeek === 7);
        $holiday = Holiday::whereDate('date', $dateStr)->first();

        if ($isSunday || $holiday) {
            return [
                'date'           => $dateStr,
                'formatted_date' => $targetDate->format('d M Y'),
                'day_name'       => $dayName,
                'day_of_week'    => $dayOfWeek,
                'is_off_day'     => true,
                'off_reason'     => $isSunday ? 'Sunday Holiday' : ($holiday?->name ?? 'Institutional Holiday'),
                'periods'        => [],
                'metrics'        => [
                    'teaching_periods' => 0,
                    'free_periods'     => 0,
                    'extra_classes'    => 0,
                    'substitutions'    => 0,
                    'duties'           => 0,
                ],
            ];
        }

        $standardSlots = self::getStandardPeriodsConfig();

        // 1. Regular Timetable Entries for this teacher on this day of week
        $regularTimetables = Timetable::with(['subject', 'class', 'section'])
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get()
            ->keyBy('period_number');

        // 2. Extra Classes for this teacher on this exact date
        $extraClasses = ExtraClass::with(['subject', 'class', 'section'])
            ->where('teacher_id', $teacher->id)
            ->whereDate('date', $dateStr)
            ->where('approval_status', 'approved')
            ->get()
            ->keyBy('period_number');

        // 3. Substitutions where this teacher is covering (substitute_teacher_id)
        $coveringSubstitutions = Substitution::with(['subject', 'class', 'section', 'absentTeacher'])
            ->where('substitute_teacher_id', $teacher->id)
            ->whereDate('date', $dateStr)
            ->get()
            ->keyBy('period_number');

        // 4. Substitutions where this teacher is absent and someone else is covering (absent_teacher_id)
        $absentSubstitutions = Substitution::with(['subject', 'class', 'section', 'substituteTeacher'])
            ->where('absent_teacher_id', $teacher->id)
            ->whereDate('date', $dateStr)
            ->get()
            ->keyBy('period_number');

        // 5. Staff Duties for this teacher on this date
        $staffDuties = StaffDuty::where('teacher_id', $teacher->id)
            ->whereDate('date', $dateStr)
            ->where('status', 'scheduled')
            ->get()
            ->keyBy('period_number');

        // 6. Free Period Tasks / Bookings
        $freePeriodTasks = StaffFreePeriodTask::where('teacher_id', $teacher->id)
            ->whereDate('date', $dateStr)
            ->get()
            ->keyBy('period_number');

        // 7. Submitted Period Reports
        $periodReports = PeriodReport::where('teacher_id', $teacher->id)
            ->whereDate('date', $dateStr)
            ->get()
            ->keyBy('period_number');

        $schedulePeriods = [];
        $teachingCount = 0;
        $freeCount = 0;
        $extraCount = 0;
        $subCount = 0;
        $dutyCount = 0;

        foreach ($standardSlots as $periodNum => $slot) {
            $entry = [
                'period_number'  => $periodNum,
                'period_name'    => $slot['name'],
                'start_time'     => $slot['start_time'],
                'end_time'       => $slot['end_time'],
                'duration_mins'  => $slot['duration_mins'],
                'time_range'     => Carbon::parse($slot['start_time'])->format('h:i A') . ' – ' . Carbon::parse($slot['end_time'])->format('h:i A'),
                'period_type'    => 'free_period',
                'period_label'   => 'Free Period',
                'badge_class'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'dot_color'      => '#10b981',
                'status'         => 'Available',
                'class_name'     => null,
                'class_id'       => null,
                'section_name'   => null,
                'section_id'     => null,
                'subject_name'   => null,
                'subject_code'   => null,
                'subject_id'     => null,
                'room'           => null,
                'timetable_id'   => null,
                'can_report'     => false,
                'has_report'     => false,
                'report'         => null,
                'details'        => null,
                'planned_task'   => null,
            ];

            // A. Check if Extra Class exists
            if (isset($extraClasses[$periodNum])) {
                $ec = $extraClasses[$periodNum];
                $entry['period_type']  = 'extra_class';
                $entry['period_label'] = 'Extra Class';
                $entry['badge_class']  = 'bg-purple-50 text-purple-700 border-purple-200';
                $entry['dot_color']    = '#8b5cf6';
                $entry['status']       = $ec->completion_status === 'completed' ? 'Completed' : 'Scheduled';
                $entry['class_name']   = $ec->class?->name;
                $entry['class_id']     = $ec->class_id;
                $entry['section_name'] = $ec->section?->name;
                $entry['section_id']   = $ec->section_id;
                $entry['subject_name'] = $ec->subject?->name;
                $entry['subject_code'] = $ec->subject?->code;
                $entry['subject_id']   = $ec->subject_id;
                $entry['room']         = $ec->room ?: 'Main Hall';
                $entry['details']      = 'Extra Class: ' . ($ec->reason ?: 'Syllabus coverage & revision');
                $entry['can_report']   = true;
                $extraCount++;
                $teachingCount++;
            }
            // B. Check if Covering for Another Teacher (Substitution)
            elseif (isset($coveringSubstitutions[$periodNum])) {
                $sub = $coveringSubstitutions[$periodNum];
                $absentName = $sub->absentTeacher ? ($sub->absentTeacher->first_name . ' ' . $sub->absentTeacher->last_name) : 'Staff';
                $entry['period_type']  = 'substitution';
                $entry['period_label'] = 'Substitution Period';
                $entry['badge_class']  = 'bg-amber-50 text-amber-800 border-amber-200';
                $entry['dot_color']    = '#f59e0b';
                $entry['status']       = 'Assigned Duty';
                $entry['class_name']   = $sub->class?->name;
                $entry['class_id']     = $sub->class_id;
                $entry['section_name'] = $sub->section?->name;
                $entry['section_id']   = $sub->section_id;
                $entry['subject_name'] = $sub->subject?->name;
                $entry['subject_code'] = $sub->subject?->code;
                $entry['subject_id']   = $sub->subject_id;
                $entry['room']         = 'Classroom';
                $entry['details']      = "Substitution duty covering for {$absentName}. " . ($sub->remarks ?: '');
                $entry['can_report']   = true;
                $subCount++;
                $teachingCount++;
            }
            // C. Check if Absent and Substituted by Another Teacher
            elseif (isset($absentSubstitutions[$periodNum])) {
                $sub = $absentSubstitutions[$periodNum];
                $subName = $sub->substituteTeacher ? ($sub->substituteTeacher->first_name . ' ' . $sub->substituteTeacher->last_name) : 'Substitute';
                $entry['period_type']  = 'leave_unavailable';
                $entry['period_label'] = 'Substituted Out';
                $entry['badge_class']  = 'bg-slate-100 text-slate-600 border-slate-200';
                $entry['dot_color']    = '#94a3b8';
                $entry['status']       = "Covered by {$subName}";
                $entry['class_name']   = $sub->class?->name;
                $entry['class_id']     = $sub->class_id;
                $entry['section_name'] = $sub->section?->name;
                $entry['section_id']   = $sub->section_id;
                $entry['subject_name'] = $sub->subject?->name;
                $entry['subject_code'] = $sub->subject?->code;
                $entry['subject_id']   = $sub->subject_id;
                $entry['details']      = "Regular period transferred to {$subName} for substitution.";
                $entry['can_report']   = false;
            }
            // D. Check Regular Teaching Period
            elseif (isset($regularTimetables[$periodNum])) {
                $tt = $regularTimetables[$periodNum];
                $entry['period_type']  = 'regular_class';
                $entry['period_label'] = 'Regular Class';
                $entry['badge_class']  = 'bg-blue-50 text-blue-700 border-blue-200';
                $entry['dot_color']    = '#3b82f6';
                $entry['status']       = 'Scheduled';
                $entry['class_name']   = $tt->class?->name;
                $entry['class_id']     = $tt->class_id;
                $entry['section_name'] = $tt->section?->name;
                $entry['section_id']   = $tt->section_id;
                $entry['subject_name'] = $tt->subject?->name;
                $entry['subject_code'] = $tt->subject?->code;
                $entry['subject_id']   = $tt->subject_id;
                $entry['room']         = $tt->room ?: 'Room ' . ($tt->class?->name ?? '1');
                $entry['timetable_id'] = $tt->id;
                $entry['details']      = "Assigned curriculum: {$tt->subject?->name} for {$tt->class?->name}-{$tt->section?->name}";
                $entry['can_report']   = true;
                $teachingCount++;
            }
            // E. Check Staff Duties (Exams, Meetings, Remedials)
            elseif (isset($staffDuties[$periodNum])) {
                $duty = $staffDuties[$periodNum];
                $entry['period_type']  = $duty->duty_type;
                $entry['period_label'] = $duty->duty_label;
                $entry['badge_class']  = 'bg-orange-50 text-orange-800 border-orange-200';
                $entry['dot_color']    = '#ea580c';
                $entry['status']       = 'Assigned Duty';
                $entry['room']         = $duty->room ?: 'Campus Venue';
                $entry['details']      = $duty->title . ($duty->description ? " — {$duty->description}" : '');
                $entry['can_report']   = false;
                $dutyCount++;
            }
            // F. Free Period (Unallocated working slot)
            else {
                $freeCount++;
                if (isset($freePeriodTasks[$periodNum])) {
                    $task = $freePeriodTasks[$periodNum];
                    $entry['status']       = 'Task Planned';
                    $entry['badge_class']  = 'bg-cyan-50 text-cyan-800 border-cyan-200';
                    $entry['dot_color']    = '#06b6d4';
                    $entry['planned_task'] = [
                        'id'            => $task->id,
                        'title'         => $task->title,
                        'activity_type' => $task->activity_type,
                        'activity_label'=> $task->activity_label,
                        'notes'         => $task->notes,
                    ];
                    $entry['details'] = "Reserved: {$task->activity_label} — {$task->title}";
                } else {
                    $entry['details'] = 'Unallocated Working Period. Available for lesson prep, correction, mentoring or substitution.';
                }
            }

            // Check if Teaching Report is already filed
            if (isset($periodReports[$periodNum])) {
                $rep = $periodReports[$periodNum];
                $entry['has_report'] = true;
                $entry['report']     = [
                    'id'            => $rep->id,
                    'present_count' => $rep->student_present_count,
                    'absent_count'  => $rep->student_absent_count,
                    'description'   => $rep->description,
                ];
                $entry['status'] = 'Report Submitted';
            }

            $schedulePeriods[$periodNum] = $entry;
        }

        return [
            'date'           => $dateStr,
            'formatted_date' => $targetDate->format('d M Y'),
            'day_name'       => $dayName,
            'day_of_week'    => $dayOfWeek,
            'is_off_day'     => false,
            'periods'        => $schedulePeriods,
            'metrics'        => [
                'teaching_periods' => $teachingCount,
                'free_periods'     => $freeCount,
                'extra_classes'    => $extraCount,
                'substitutions'    => $subCount,
                'duties'           => $dutyCount,
            ],
        ];
    }

    /**
     * Generate the complete 6-day (Monday to Saturday) weekly schedule matrix for an employee.
     */
    public function getWeeklyMatrix(Employee $teacher, ?Carbon $referenceDate = null): array
    {
        $ref = $referenceDate ? $referenceDate->copy() : today();
        $startOfWeek = $ref->copy()->startOfWeek(Carbon::MONDAY);

        $days = [];
        $totalTeaching = 0;
        $totalFree = 0;
        $totalExtra = 0;
        $totalSub = 0;
        $totalHours = 0.0;

        for ($i = 0; $i < 6; $i++) {
            $curDate = $startOfWeek->copy()->addDays($i);
            $daySched = $this->getDaySchedule($teacher, $curDate);
            $days[$i + 1] = $daySched;

            $totalTeaching += $daySched['metrics']['teaching_periods'];
            $totalFree     += $daySched['metrics']['free_periods'];
            $totalExtra    += $daySched['metrics']['extra_classes'];
            $totalSub      += $daySched['metrics']['substitutions'];
        }

        // Each period is 45 minutes = 0.75 hours
        $totalHours = round($totalTeaching * 0.75, 1);

        return [
            'week_label'     => $startOfWeek->format('d M Y') . ' – ' . $startOfWeek->copy()->addDays(5)->format('d M Y'),
            'start_date'     => $startOfWeek->toDateString(),
            'end_date'       => $startOfWeek->copy()->addDays(5)->toDateString(),
            'days'           => $days,
            'standard_slots' => self::getStandardPeriodsConfig(),
            'breaks'         => self::getBreaksConfig(),
            'summary'        => [
                'weekly_teaching_periods' => $totalTeaching,
                'weekly_teaching_hours'   => $totalHours,
                'weekly_free_periods'     => $totalFree,
                'weekly_extra_classes'    => $totalExtra,
                'weekly_substitutions'    => $totalSub,
            ],
        ];
    }

    /**
     * Strict conflict detection engine before scheduling extra classes or timetable modifications.
     */
    public function detectConflicts(array $params): array
    {
        $teacherId = (int) $params['teacher_id'];
        $date = $params['date'];
        $periodNumber = isset($params['period_number']) ? (int) $params['period_number'] : null;
        $classId = isset($params['class_id']) ? (int) $params['class_id'] : null;
        $sectionId = isset($params['section_id']) ? (int) $params['section_id'] : null;
        $room = !empty($params['room']) ? trim($params['room']) : null;
        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;

        $conflicts = [];

        // 1. Check if Teacher is already teaching a regular class at that slot
        if ($periodNumber) {
            $existingRegular = Timetable::with(['class', 'section', 'subject'])
                ->where('teacher_id', $teacherId)
                ->where('day_of_week', $dayOfWeek)
                ->where('period_number', $periodNumber)
                ->where('is_active', true)
                ->first();

            if ($existingRegular) {
                $conflicts[] = "Teacher double-booking: Faculty is already assigned to {$existingRegular->class?->name}-{$existingRegular->section?->name} ({$existingRegular->subject?->name}) in Period {$periodNumber}.";
            }

            // 2. Check if Teacher already has an extra class at that slot
            $existingExtra = ExtraClass::with(['class', 'section', 'subject'])
                ->where('teacher_id', $teacherId)
                ->whereDate('date', $date)
                ->where('period_number', $periodNumber)
                ->where('approval_status', 'approved')
                ->first();

            if ($existingExtra) {
                $conflicts[] = "Teacher double-booking: An Extra Class is already scheduled for this teacher in Period {$periodNumber} ({$existingExtra->class?->name}-{$existingExtra->section?->name}).";
            }

            // 3. Check if Teacher is on substitution duty
            $existingSub = Substitution::where('substitute_teacher_id', $teacherId)
                ->whereDate('date', $date)
                ->where('period_number', $periodNumber)
                ->first();

            if ($existingSub) {
                $conflicts[] = "Teacher double-booking: Faculty is already assigned to a Substitution Duty in Period {$periodNumber}.";
            }

            // 4. Check Class collision: is the targeted class/section already having another class in this period?
            if ($classId && $sectionId) {
                $classBusy = Timetable::with('subject')
                    ->where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('period_number', $periodNumber)
                    ->where('is_active', true)
                    ->first();

                if ($classBusy) {
                    $conflicts[] = "Class collision: Target class already has {$classBusy->subject?->name} scheduled in Period {$periodNumber}.";
                }
            }

            // 5. Room collision check
            if ($room && strtolower($room) !== 'online' && strtolower($room) !== 'ground') {
                $roomBusy = Timetable::where('room', $room)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('period_number', $periodNumber)
                    ->where('is_active', true)
                    ->first();

                if ($roomBusy) {
                    $conflicts[] = "Room collision: Room '{$room}' is already booked for another class during Period {$periodNumber}.";
                }
            }
        }

        return [
            'has_conflict' => count($conflicts) > 0,
            'conflicts'    => $conflicts,
        ];
    }

    /**
     * Find all teaching staff members who are completely free during a given date and period slot.
     */
    public function getAvailableFreeStaff(string $date, int $periodNumber, ?int $excludeTeacherId = null): Collection
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;

        // IDs of busy teachers during this slot
        $busyTeacherIds = collect();

        // 1. Regular class busy
        $regularBusy = Timetable::where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->whereNotNull('teacher_id')
            ->where('is_active', true)
            ->pluck('teacher_id');
        $busyTeacherIds = $busyTeacherIds->merge($regularBusy);

        // 2. Extra class busy
        $extraBusy = ExtraClass::whereDate('date', $date)
            ->where('period_number', $periodNumber)
            ->where('approval_status', 'approved')
            ->pluck('teacher_id');
        $busyTeacherIds = $busyTeacherIds->merge($extraBusy);

        // 3. Substitution busy
        $subBusy = Substitution::whereDate('date', $date)
            ->where('period_number', $periodNumber)
            ->pluck('substitute_teacher_id');
        $busyTeacherIds = $busyTeacherIds->merge($subBusy);

        // 4. Duties busy
        $dutyBusy = StaffDuty::whereDate('date', $date)
            ->where('period_number', $periodNumber)
            ->where('status', 'scheduled')
            ->pluck('teacher_id');
        $busyTeacherIds = $busyTeacherIds->merge($dutyBusy);

        $busyIds = $busyTeacherIds->unique()->filter()->values()->all();

        // Fetch free teachers
        return Employee::where('is_active', true)
            ->where(fn($q) => $q->where('employee_type', 'teaching')->orWhereNull('employee_type'))
            ->when($excludeTeacherId, fn($q) => $q->where('id', '!=', $excludeTeacherId))
            ->whereNotIn('id', $busyIds)
            ->with(['department', 'designation'])
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Staff Workload & Academic Analytics Aggregator.
     */
    public function getWorkloadAnalytics(?int $academicYearId = null, ?int $departmentId = null): array
    {
        $teachersQuery = Employee::where('is_active', true)
            ->where(fn($q) => $q->where('employee_type', 'teaching')->orWhereNull('employee_type'))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->with(['department', 'designation'])
            ->orderBy('first_name');

        $teachers = $teachersQuery->get();
        $teacherRows = [];

        $totalSchoolPeriods = 0;
        $totalSchoolFree = 0;
        $totalSchoolExtra = 0;
        $totalSchoolSubs = 0;

        $maxWeeklyPeriods = 30; // CBSE standard teacher workload

        foreach ($teachers as $t) {
            // Count active weekly regular timetable periods
            $regularCount = Timetable::where('teacher_id', $t->id)
                ->where('is_active', true)
                ->count();

            // Count extra classes scheduled this month
            $extraCount = ExtraClass::where('teacher_id', $t->id)
                ->whereMonth('date', now()->month)
                ->where('approval_status', 'approved')
                ->count();

            // Count substitutions covering this month
            $subCount = Substitution::where('substitute_teacher_id', $t->id)
                ->whereMonth('date', now()->month)
                ->count();

            // Free periods per week (48 total slots in a 6-day x 8-period week minus regular classes)
            $weeklyFree = max(0, 48 - $regularCount);
            $weeklyHours = round($regularCount * 0.75, 1);

            // Teaching reports count
            $reportsCount = PeriodReport::where('teacher_id', $t->id)->count();

            $totalSchoolPeriods += $regularCount;
            $totalSchoolFree    += $weeklyFree;
            $totalSchoolExtra   += $extraCount;
            $totalSchoolSubs    += $subCount;

            $utilizationPct = min(100, round(($regularCount / $maxWeeklyPeriods) * 100));

            $teacherRows[] = [
                'teacher'           => $t,
                'regular_periods'   => $regularCount,
                'teaching_hours'    => $weeklyHours,
                'free_periods'      => $weeklyFree,
                'extra_classes'     => $extraCount,
                'substitutions'     => $subCount,
                'reports_submitted' => $reportsCount,
                'utilization_pct'   => $utilizationPct,
                'status'            => $regularCount > $maxWeeklyPeriods ? 'Overloaded' : ($regularCount < 15 ? 'Under-utilized' : 'Balanced'),
            ];
        }

        return [
            'total_teachers'       => count($teachers),
            'total_school_periods' => $totalSchoolPeriods,
            'total_school_free'    => $totalSchoolFree,
            'total_school_extra'   => $totalSchoolExtra,
            'total_school_subs'    => $totalSchoolSubs,
            'avg_utilization'      => count($teachers) > 0 ? round(($totalSchoolPeriods / (count($teachers) * $maxWeeklyPeriods)) * 100) : 0,
            'teacher_rows'         => $teacherRows,
        ];
    }
}
