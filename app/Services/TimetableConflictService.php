<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\Employee;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherSubjectAllocation;
use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimetableConflictService
{
    /**
     * Standard institutional period timings.
     */
    public static function getPeriodTimings(): array
    {
        return [
            1 => ['09:10:00', '09:50:00'],
            2 => ['09:50:00', '10:30:00'],
            3 => ['10:40:00', '11:20:00'],
            4 => ['11:20:00', '12:00:00'],
            5 => ['12:00:00', '12:40:00'],
            6 => ['01:20:00', '02:00:00'],
            7 => ['02:10:00', '02:50:00'],
            8 => ['02:50:00', '03:00:00'],
        ];
    }

    public static function getDayNames(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    /**
     * Check whether two time intervals overlap.
     */
    public static function intervalsOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        $a1 = Carbon::parse($startA)->format('H:i:s');
        $a2 = Carbon::parse($endA)->format('H:i:s');
        $b1 = Carbon::parse($startB)->format('H:i:s');
        $b2 = Carbon::parse($endB)->format('H:i:s');

        return ($a1 < $b2) && ($b1 < $a2);
    }

    /**
     * Comprehensive single slot validation (Rules 1 - 12).
     *
     * @param array $params [
     *   'class_id' => int,
     *   'section_id' => ?int,
     *   'day_of_week' => int,
     *   'period_number' => int,
     *   'teacher_id' => ?int,
     *   'subject_id' => ?int,
     *   'period_type' => ?string, // class, free, substitution, holiday, etc.
     *   'start_time' => ?string,
     *   'end_time' => ?string,
     *   'academic_year_id' => ?int,
     *   'exclude_timetable_id' => ?int,
     * ]
     * @return array [
     *   'is_valid' => bool,
     *   'errors' => array,
     *   'conflict_details' => ?array,
     * ]
     */
    public function validateSlot(array $params): array
    {
        $classId = (int) $params['class_id'];
        $sectionId = !empty($params['section_id']) ? (int) $params['section_id'] : null;
        $dayOfWeek = (int) $params['day_of_week'];
        $periodNumber = (int) $params['period_number'];
        $teacherId = !empty($params['teacher_id']) ? (int) $params['teacher_id'] : null;
        $subjectId = !empty($params['subject_id']) ? (int) $params['subject_id'] : null;
        $periodType = $params['period_type'] ?? 'class';
        $excludeId = !empty($params['exclude_timetable_id']) ? (int) $params['exclude_timetable_id'] : null;

        $timings = self::getPeriodTimings();
        $startTime = $params['start_time'] ?? ($timings[$periodNumber][0] ?? '09:10:00');
        $endTime = $params['end_time'] ?? ($timings[$periodNumber][1] ?? '09:50:00');

        $academicYearId = !empty($params['academic_year_id'])
            ? (int) $params['academic_year_id']
            : (AcademicYear::current()?->id ?? AcademicYear::latest('id')->value('id') ?? 1);

        $dayNames = self::getDayNames();
        $dayName = $dayNames[$dayOfWeek] ?? "Day {$dayOfWeek}";

        // Free periods and holidays do not have teacher collisions
        if (in_array($periodType, ['free', 'holiday']) || !$teacherId) {
            return ['is_valid' => true, 'errors' => [], 'conflict_details' => null];
        }

        $teacher = Employee::find($teacherId);
        if (!$teacher) {
            return [
                'is_valid' => false,
                'errors' => ['Selected staff member does not exist in the system.'],
                'conflict_details' => null,
            ];
        }

        // Rule 6: Availability (Active status, Inactive/Relieved check, and configured unavailable slots)
        if (!$teacher->is_active || in_array(strtolower($teacher->status ?? ''), ['inactive', 'relieved', 'terminated'])) {
            $statusLabel = ucfirst($teacher->status ?? 'Inactive');
            return [
                'is_valid' => false,
                'errors' => ["Staff member {$teacher->full_name} is currently marked as {$statusLabel} and cannot be assigned to timetable schedules."],
                'conflict_details' => [
                    'type' => 'status_inactive',
                    'staff_name' => $teacher->full_name,
                    'status' => $statusLabel,
                ],
            ];
        }

        if (!$teacher->isAvailableOn($dayOfWeek, $periodNumber)) {
            return [
                'is_valid' => false,
                'errors' => ["Staff member {$teacher->full_name} is marked as unavailable during Period {$periodNumber} on {$dayName}."],
                'conflict_details' => [
                    'type' => 'staff_unavailable',
                    'staff_name' => $teacher->full_name,
                    'day' => $dayName,
                    'period' => $periodNumber,
                ],
            ];
        }

        // Rule 5: Subject & Class Teaching Eligibility
        if ($subjectId && $periodType === 'class') {
            if (!$teacher->canTeachClassSubject($classId, $subjectId, $sectionId)) {
                $subjName = Subject::where('id', $subjectId)->value('name') ?? 'Selected Subject';
                $clsName = Classes::where('id', $classId)->value('name') ?? 'Selected Class';
                return [
                    'is_valid' => false,
                    'errors' => ["Teaching Eligibility Violation: {$teacher->full_name} is not authorized or qualified to teach '{$subjName}' for Class {$clsName}. Please configure staff teaching eligibility first."],
                    'conflict_details' => [
                        'type' => 'ineligible_subject',
                        'staff_name' => $teacher->full_name,
                        'subject_name' => $subjName,
                        'class_name' => $clsName,
                    ],
                ];
            }
        }

        // Rule 2: Class Collision (Class Double-Booking Prevention)
        // If assigning a slot, verify this class/section is not already occupied by another teacher in the same period
        $existingClassSlot = Timetable::with(['teacher', 'subject'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->where('is_active', true)
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();

        if ($existingClassSlot && $existingClassSlot->teacher_id != $teacherId) {
            $tName = $existingClassSlot->teacher?->full_name ?? 'another faculty';
            $sName = $existingClassSlot->subject?->name ?? 'another subject';
            $cls = Classes::where('id', $classId)->value('name') ?? 'Class';
            $msg = "Class Collision: This class slot is already assigned to {$tName} ({$sName}) during Period {$periodNumber} on {$dayName}. One class cannot have two teachers simultaneously.";

            return [
                'is_valid' => false,
                'errors' => [$msg],
                'conflict_details' => [
                    'type' => 'class_double_booked',
                    'class_name' => $cls,
                    'day_name' => $dayName,
                    'period' => $periodNumber,
                    'existing_teacher' => $tName,
                    'existing_subject' => $sName,
                ],
            ];
        }

        // Rule 1: Staff Collision (Exact Period Conflict across other classes/sections)
        $conflictingSlotQuery = Timetable::with(['class', 'section', 'subject'])
            ->where('academic_year_id', $academicYearId)
            ->where('day_of_week', $dayOfWeek)
            ->where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId));

        $otherClassConflicts = (clone $conflictingSlotQuery)
            ->where('period_number', $periodNumber)
            ->where(function ($q) use ($classId, $sectionId) {
                $q->where('class_id', '!=', $classId);
                if ($sectionId) {
                    $q->orWhere('section_id', '!=', $sectionId);
                }
            })
            ->first();

        if ($otherClassConflicts) {
            $cClass = ($otherClassConflicts->class?->name ?? 'Class') . ($otherClassConflicts->section ? ' - ' . $otherClassConflicts->section->name : '');
            $cSubj = $otherClassConflicts->subject?->name ?? 'Class Session';
            $msg = "Timetable Conflict: {$teacher->full_name} is already assigned to {$cClass} ({$cSubj}) during Period {$periodNumber} on {$dayName}. One staff member cannot teach two classes simultaneously.";

            return [
                'is_valid' => false,
                'errors' => [$msg],
                'conflict_details' => [
                    'type' => 'teacher_double_booked',
                    'staff_id' => $teacher->id,
                    'staff_name' => $teacher->full_name,
                    'conflicting_class' => $cClass,
                    'conflicting_subject' => $cSubj,
                    'day_name' => $dayName,
                    'period' => $periodNumber,
                    'start_time' => $otherClassConflicts->start_time,
                    'end_time' => $otherClassConflicts->end_time,
                ],
            ];
        }

        // Rule 3: Time Overlap across different period numbers
        $allDaySlots = (clone $conflictingSlotQuery)->get();
        foreach ($allDaySlots as $existingSlot) {
            if ($existingSlot->period_number != $periodNumber && $existingSlot->start_time && $existingSlot->end_time) {
                if (self::intervalsOverlap($startTime, $endTime, $existingSlot->start_time, $existingSlot->end_time)) {
                    $cClass = ($existingSlot->class?->name ?? 'Class') . ($existingSlot->section ? ' - ' . $existingSlot->section->name : '');
                    $cSubj = $existingSlot->subject?->name ?? 'Class Session';
                    $msg = "Time Overlap Conflict: {$teacher->full_name} has an overlapping schedule ({$existingSlot->start_time} - {$existingSlot->end_time}) in {$cClass} ({$cSubj}) on {$dayName}.";

                    return [
                        'is_valid' => false,
                        'errors' => [$msg],
                        'conflict_details' => [
                            'type' => 'time_overlap',
                            'staff_id' => $teacher->id,
                            'staff_name' => $teacher->full_name,
                            'conflicting_class' => $cClass,
                            'conflicting_subject' => $cSubj,
                            'day_name' => $dayName,
                            'period' => $existingSlot->period_number,
                            'start_time' => $existingSlot->start_time,
                            'end_time' => $existingSlot->end_time,
                        ],
                    ];
                }
            }
        }

        // Rule 7: Daily and Weekly Workload Limits
        $maxDaily = $teacher->max_daily_periods ?: 6;
        $currentDailyCount = Timetable::where('academic_year_id', $academicYearId)
            ->where('day_of_week', $dayOfWeek)
            ->where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->count();

        if ($currentDailyCount >= $maxDaily) {
            $msg = "Daily Workload Exceeded: {$teacher->full_name} is already assigned {$currentDailyCount} periods on {$dayName} (Daily maximum limit is {$maxDaily} periods).";
            return [
                'is_valid' => false,
                'errors' => [$msg],
                'conflict_details' => [
                    'type' => 'workload_daily_exceeded',
                    'staff_name' => $teacher->full_name,
                    'current_daily' => $currentDailyCount,
                    'max_daily' => $maxDaily,
                ],
            ];
        }

        $maxWeekly = $teacher->max_weekly_periods ?: 30;
        $currentWeeklyCount = Timetable::where('academic_year_id', $academicYearId)
            ->where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->count();

        if ($currentWeeklyCount >= $maxWeekly) {
            $msg = "Weekly Workload Exceeded: {$teacher->full_name} has reached the weekly workload capacity of {$maxWeekly} periods ({$currentWeeklyCount} assigned).";
            return [
                'is_valid' => false,
                'errors' => [$msg],
                'conflict_details' => [
                    'type' => 'workload_weekly_exceeded',
                    'staff_name' => $teacher->full_name,
                    'current_weekly' => $currentWeeklyCount,
                    'max_weekly' => $maxWeekly,
                ],
            ];
        }

        return ['is_valid' => true, 'errors' => [], 'conflict_details' => null];
    }

    /**
     * Validate an entire day batch for a class/section (e.g. from saveDay).
     */
    public function validateBatch(array $slots, int $classId, ?int $sectionId, int $dayOfWeek, int $academicYearId): array
    {
        $dayNames = self::getDayNames();
        $dayName = $dayNames[$dayOfWeek] ?? "Day {$dayOfWeek}";

        // 1. Intra-batch duplicate check: same teacher assigned to multiple slots at same period?
        $seenSlots = [];
        $teacherSlotCounts = [];

        foreach ($slots as $slot) {
            $pNum = (int) ($slot['period_number'] ?? 0);
            $tId = !empty($slot['teacher_id']) ? (int) $slot['teacher_id'] : null;
            $pType = $slot['period_type'] ?? 'class';

            if ($tId && !in_array($pType, ['free', 'holiday'])) {
                $teacherSlotCounts[$tId] = ($teacherSlotCounts[$tId] ?? 0) + 1;
            }
        }

        // 2. Validate consecutive teacher conflict (same teacher adjacent periods on same day)
        for ($i = 0; $i < count($slots) - 1; $i++) {
            $cur = $slots[$i];
            $next = $slots[$i + 1];
            $tCur = !empty($cur['teacher_id']) ? (int) $cur['teacher_id'] : null;
            $tNext = !empty($next['teacher_id']) ? (int) $next['teacher_id'] : null;
            $typeCur = $cur['period_type'] ?? 'class';
            $typeNext = $next['period_type'] ?? 'class';

            if ($tCur && $tNext && $tCur === $tNext && !in_array($typeCur, ['free', 'holiday']) && !in_array($typeNext, ['free', 'holiday'])) {
                $teacher = Employee::find($tCur);
                $name = $teacher?->full_name ?? 'Teacher';
                return [
                    'is_valid' => false,
                    'consecutive_conflict' => true,
                    'errors' => ["Consecutive Period Conflict: {$name} is assigned to adjacent periods (Period {$cur['period_number']} and Period {$next['period_number']}). Pedagogical policy requires alternating staff."],
                    'conflict_details' => [
                        'type' => 'consecutive_conflict',
                        'staff_name' => $name,
                        'period_1' => $cur['period_number'],
                        'period_2' => $next['period_number'],
                    ],
                ];
            }
        }

        // 3. Validate each slot against cross-class collisions & staff constraints
        foreach ($slots as $slot) {
            $tId = !empty($slot['teacher_id']) ? (int) $slot['teacher_id'] : null;
            $pType = $slot['period_type'] ?? 'class';

            if ($tId && !in_array($pType, ['free', 'holiday'])) {
                // Find existing timetable id if updating
                $existingId = Timetable::where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('period_number', (int) $slot['period_number'])
                    ->where('academic_year_id', $academicYearId)
                    ->value('id');

                $res = $this->validateSlot([
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'day_of_week' => $dayOfWeek,
                    'period_number' => (int) $slot['period_number'],
                    'teacher_id' => $tId,
                    'subject_id' => !empty($slot['subject_id']) ? (int) $slot['subject_id'] : null,
                    'period_type' => $pType,
                    'academic_year_id' => $academicYearId,
                    'exclude_timetable_id' => $existingId,
                ]);

                if (!$res['is_valid']) {
                    return $res;
                }
            }
        }

        return ['is_valid' => true, 'errors' => [], 'conflict_details' => null];
    }

    /**
     * Fetch all eligible and completely available staff for a specific slot.
     * Used by the front-end to dynamically populate the staff dropdown and assistance engine.
     */
    public function getEligibleAndAvailableStaff(
        int $classId,
        ?int $sectionId,
        ?int $subjectId,
        int $dayOfWeek,
        int $periodNumber,
        ?int $academicYearId = null,
        ?int $excludeTimetableId = null
    ): array {
        $ayId = $academicYearId ?: (AcademicYear::current()?->id ?? 1);
        $timings = self::getPeriodTimings();
        $startTime = $timings[$periodNumber][0] ?? '09:10:00';
        $endTime = $timings[$periodNumber][1] ?? '09:50:00';

        // 1. Find all active teaching staff
        $allTeachers = Employee::where('is_active', true)
            ->where(function ($q) {
                $q->where('employee_type', 'teaching')->orWhereNull('employee_type');
            })
            ->whereNotIn('status', ['inactive', 'relieved', 'terminated'])
            ->with(['department', 'designation', 'eligibleTeachingLevels', 'teacherSubjectAllocations'])
            ->orderBy('first_name')
            ->get();

        // 2. Query busy teachers in this slot across other classes
        $busyTeacherIds = Timetable::where('academic_year_id', $ayId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->where('is_active', true)
            ->whereNotNull('teacher_id')
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->when($excludeTimetableId, fn($q) => $q->where('id', '!=', $excludeTimetableId))
            ->pluck('teacher_id')
            ->toArray();

        // 3. Query busy teacher details to provide clear conflict tooltips
        $busyDetails = Timetable::with(['class', 'section', 'subject'])
            ->where('academic_year_id', $ayId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->where('is_active', true)
            ->whereNotNull('teacher_id')
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->when($excludeTimetableId, fn($q) => $q->where('id', '!=', $excludeTimetableId))
            ->get()
            ->keyBy('teacher_id');

        // Pre-aggregate daily workload counts for all teachers in one query
        $dailyCounts = Timetable::where('academic_year_id', $ayId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->whereNotNull('teacher_id')
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->when($excludeTimetableId, fn($q) => $q->where('id', '!=', $excludeTimetableId))
            ->groupBy('teacher_id')
            ->selectRaw('teacher_id, count(*) as count')
            ->pluck('count', 'teacher_id')
            ->toArray();

        // Pre-aggregate weekly workload counts for all teachers in one query
        $weeklyCounts = Timetable::where('academic_year_id', $ayId)
            ->where('is_active', true)
            ->whereNotNull('teacher_id')
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->when($excludeTimetableId, fn($q) => $q->where('id', '!=', $excludeTimetableId))
            ->groupBy('teacher_id')
            ->selectRaw('teacher_id, count(*) as count')
            ->pluck('count', 'teacher_id')
            ->toArray();

        $eligibleAndAvailable = [];
        $conflictedOrIneligible = [];

        foreach ($allTeachers as $teacher) {
            $isBusy = in_array($teacher->id, $busyTeacherIds);
            $isAvailable = $teacher->isAvailableOn($dayOfWeek, $periodNumber);
            $isEligible = $subjectId ? $teacher->canTeachClassSubject($classId, $subjectId, $sectionId) : true;

            // Daily & weekly workload check from pre-aggregated counts
            $curDaily = $dailyCounts[$teacher->id] ?? 0;
            $maxDaily = $teacher->max_daily_periods ?: 6;
            $exceedsDaily = ($curDaily >= $maxDaily);

            $curWeekly = $weeklyCounts[$teacher->id] ?? 0;
            $maxWeekly = $teacher->max_weekly_periods ?: 30;
            $exceedsWeekly = ($curWeekly >= $maxWeekly);

            $item = [
                'id' => $teacher->id,
                'name' => $teacher->full_name,
                'employee_code' => $teacher->employee_code,
                'designation' => $teacher->designationName,
                'department' => $teacher->departmentName,
                'current_daily_periods' => $curDaily,
                'max_daily_periods' => $maxDaily,
                'current_weekly_periods' => $curWeekly,
                'max_weekly_periods' => $maxWeekly,
                'is_eligible' => $isEligible,
                'is_available' => $isAvailable && !$isBusy && !$exceedsDaily && !$exceedsWeekly,
            ];

            if ($isBusy) {
                $busyRow = $busyDetails[$teacher->id] ?? null;
                $busyClass = ($busyRow?->class?->name ?? 'Class') . ($busyRow?->section ? '-' . $busyRow->section->name : '');
                $busySubj = $busyRow?->subject?->name ?? 'Session';
                $item['blocked_reason'] = "Already assigned to {$busyClass} ({$busySubj}) in Period {$periodNumber}";
                $conflictedOrIneligible[] = $item;
            } elseif (!$isAvailable) {
                $item['blocked_reason'] = "Marked unavailable during this period";
                $conflictedOrIneligible[] = $item;
            } elseif ($exceedsDaily) {
                $item['blocked_reason'] = "Daily workload limit reached ({$curDaily}/{$maxDaily} periods)";
                $conflictedOrIneligible[] = $item;
            } elseif ($exceedsWeekly) {
                $item['blocked_reason'] = "Weekly workload limit reached ({$curWeekly}/{$maxWeekly} periods)";
                $conflictedOrIneligible[] = $item;
            } elseif (!$isEligible) {
                $item['blocked_reason'] = "Not eligible/configured for this subject & class";
                $conflictedOrIneligible[] = $item;
            } else {
                // Completely eligible and available!
                // Calculate smart assistance recommendation score: lower workload gets higher priority
                $workloadPercent = round(($curWeekly / max(1, $maxWeekly)) * 100);
                $item['workload_percent'] = $workloadPercent;
                $item['recommendation_score'] = 100 - $workloadPercent;
                $eligibleAndAvailable[] = $item;
            }
        }

        // Sort eligible teachers: lowest workload first (Smart Assistance)
        usort($eligibleAndAvailable, fn($a, $b) => $b['recommendation_score'] <=> $a['recommendation_score']);

        return [
            'available_count' => count($eligibleAndAvailable),
            'available_staff' => $eligibleAndAvailable,
            'blocked_count' => count($conflictedOrIneligible),
            'blocked_staff' => $conflictedOrIneligible,
        ];
    }

    /**
     * Atomically save a single slot inside a transaction with concurrency protection (Rule 10).
     */
    public function atomicSaveSlot(array $params): Timetable
    {
        return DB::transaction(function () use ($params) {
            $classId = (int) $params['class_id'];
            $sectionId = !empty($params['section_id']) ? (int) $params['section_id'] : null;
            $dayOfWeek = (int) $params['day_of_week'];
            $periodNumber = (int) $params['period_number'];
            $teacherId = !empty($params['teacher_id']) ? (int) $params['teacher_id'] : null;
            $subjectId = !empty($params['subject_id']) ? (int) $params['subject_id'] : (Subject::first()?->id ?? 1);
            $periodType = $params['period_type'] ?? 'class';
            $room = $params['room'] ?? null;
            $academicYearId = !empty($params['academic_year_id'])
                ? (int) $params['academic_year_id']
                : (AcademicYear::current()?->id ?? 1);

            // Pessimistic lock for concurrency protection (Rule 10):
            // Lock any existing record for this target class/section/slot
            $existing = Timetable::where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('day_of_week', $dayOfWeek)
                ->where('period_number', $periodNumber)
                ->where('academic_year_id', $academicYearId)
                ->lockForUpdate()
                ->first();

            // Also lock teacher's records on this day/period to prevent simultaneous allocation
            if ($teacherId && !in_array($periodType, ['free', 'holiday'])) {
                Timetable::where('teacher_id', $teacherId)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('period_number', $periodNumber)
                    ->where('academic_year_id', $academicYearId)
                    ->lockForUpdate()
                    ->get();
            }

            // Run strict authoritative validation
            $validation = $this->validateSlot([
                'class_id' => $classId,
                'section_id' => $sectionId,
                'day_of_week' => $dayOfWeek,
                'period_number' => $periodNumber,
                'teacher_id' => $teacherId,
                'subject_id' => $subjectId,
                'period_type' => $periodType,
                'academic_year_id' => $academicYearId,
                'exclude_timetable_id' => $existing?->id,
            ]);

            if (!$validation['is_valid']) {
                $err = implode(' ', $validation['errors']);
                throw new \InvalidArgumentException($err);
            }

            $timings = self::getPeriodTimings();
            $startTime = $timings[$periodNumber][0] ?? '09:10:00';
            $endTime = $timings[$periodNumber][1] ?? '09:50:00';

            $effectiveTeacherId = in_array($periodType, ['free', 'holiday']) ? null : $teacherId;
            $effectiveSubjectId = in_array($periodType, ['free', 'holiday']) ? null : $subjectId;
            $defaultRoom = $periodType === 'holiday' ? 'Holiday' : ($room ?: 'Room 1-A');

            $timetable = Timetable::updateOrCreate(
                [
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'day_of_week' => $dayOfWeek,
                    'period_number' => $periodNumber,
                    'academic_year_id' => $academicYearId,
                ],
                [
                    'subject_id' => $effectiveSubjectId ?: (Subject::first()?->id ?? 1),
                    'teacher_id' => $effectiveTeacherId,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'room' => $defaultRoom,
                    'is_active' => true,
                    'status' => 'published',
                    'period_type' => $periodType,
                    'effective_from' => '2025-06-01',
                    'conflict_note' => null,
                ]
            );

            // Maintain TeacherSubjectAllocation eligibility record if not already recorded
            if ($effectiveSubjectId && $effectiveTeacherId && $periodType === 'class') {
                TeacherSubjectAllocation::firstOrCreate([
                    'employee_id' => $effectiveTeacherId,
                    'subject_id' => $effectiveSubjectId,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'academic_year_id' => $academicYearId,
                ], [
                    'is_active' => true,
                    'created_by' => Auth::id(),
                ]);
            }

            // Record audit log
            AuditLog::record('timetable_slot_updated', $timetable, [], [
                'class_id' => $classId,
                'section_id' => $sectionId,
                'day_of_week' => $dayOfWeek,
                'period_number' => $periodNumber,
                'teacher_id' => $effectiveTeacherId,
                'period_type' => $periodType,
                'updated_by' => Auth::id(),
            ]);

            return $timetable;
        });
    }

    /**
     * Atomically save a full day batch inside a transaction with concurrency protection.
     */
    public function atomicSaveDay(array $params): array
    {
        return DB::transaction(function () use ($params) {
            $classId = (int) $params['class_id'];
            $sectionId = !empty($params['section_id']) ? (int) $params['section_id'] : null;
            $dayOfWeek = (int) $params['day_of_week'];
            $slots = $params['slots'] ?? [];
            $academicYearId = !empty($params['academic_year_id'])
                ? (int) $params['academic_year_id']
                : (AcademicYear::current()?->id ?? 1);

            // Concurrency lock: lock existing rows for this class/day
            Timetable::where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('day_of_week', $dayOfWeek)
                ->where('academic_year_id', $academicYearId)
                ->lockForUpdate()
                ->get();

            // Run authoritative batch validation
            $batchValidation = $this->validateBatch($slots, $classId, $sectionId, $dayOfWeek, $academicYearId);
            if (!$batchValidation['is_valid']) {
                $err = implode(' ', $batchValidation['errors']);
                throw new \InvalidArgumentException($err);
            }

            $savedSlots = [];
            $timings = self::getPeriodTimings();
            $firstSubject = Subject::first();

            foreach ($slots as $slot) {
                $periodNum = (int) $slot['period_number'];
                $periodTiming = $timings[$periodNum] ?? ['09:10:00', '09:50:00'];
                $periodType = $slot['period_type'] ?? 'class';
                $tId = (!empty($slot['teacher_id']) && !in_array($periodType, ['free', 'holiday']))
                    ? (int) $slot['teacher_id']
                    : null;
                $sId = (!empty($slot['subject_id']) && !in_array($periodType, ['free', 'holiday']))
                    ? (int) $slot['subject_id']
                    : ($firstSubject?->id ?? 1);
                $room = $slot['room'] ?? ($periodType === 'holiday' ? 'Holiday' : 'Room 1-A');

                $timetable = Timetable::updateOrCreate(
                    [
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'day_of_week' => $dayOfWeek,
                        'period_number' => $periodNum,
                        'academic_year_id' => $academicYearId,
                    ],
                    [
                        'subject_id' => $sId,
                        'teacher_id' => $tId,
                        'start_time' => $periodTiming[0],
                        'end_time' => $periodTiming[1],
                        'room' => $room,
                        'is_active' => true,
                        'status' => 'published',
                        'period_type' => $periodType,
                        'effective_from' => '2025-06-01',
                        'conflict_note' => null,
                    ]
                );

                if ($tId && $sId && $periodType === 'class') {
                    TeacherSubjectAllocation::firstOrCreate([
                        'employee_id' => $tId,
                        'subject_id' => $sId,
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'academic_year_id' => $academicYearId,
                    ], [
                        'is_active' => true,
                        'created_by' => Auth::id(),
                    ]);
                }

                $savedSlots[] = $timetable;
            }

            AuditLog::record('timetable_day_saved', null, [], [
                'class_id' => $classId,
                'section_id' => $sectionId,
                'day_of_week' => $dayOfWeek,
                'periods_count' => count($savedSlots),
                'saved_by' => Auth::id(),
            ]);

            return $savedSlots;
        });
    }

    /**
     * Scan existing database for conflicts and return structured conflict matrix (Module 11 & 12).
     */
    public function scanAllExistingConflicts(?int $academicYearId = null): array
    {
        $ayId = $academicYearId ?: (AcademicYear::current()?->id ?? 1);

        // Find all collisions where same teacher is in multiple classes during same day & period
        $conflicts = Timetable::with(['class', 'section', 'subject', 'teacher'])
            ->where('academic_year_id', $ayId)
            ->whereNotNull('teacher_id')
            ->where('is_active', true)
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->get()
            ->groupBy(fn($t) => $t->teacher_id . '_' . $t->day_of_week . '_' . $t->period_number)
            ->filter(fn($group) => $group->count() > 1)
            ->map(function ($group) {
                $first = $group->first();
                $dayNames = self::getDayNames();
                $dayName = $dayNames[$first->day_of_week] ?? "Day {$first->day_of_week}";

                return [
                    'key' => $first->teacher_id . '_' . $first->day_of_week . '_' . $first->period_number,
                    'teacher_id' => $first->teacher_id,
                    'teacher_name' => $first->teacher?->full_name ?? "Teacher #{$first->teacher_id}",
                    'teacher_code' => $first->teacher?->employee_code ?? 'N/A',
                    'day_of_week' => $first->day_of_week,
                    'day_name' => $dayName,
                    'period_number' => $first->period_number,
                    'start_time' => $first->start_time,
                    'end_time' => $first->end_time,
                    'total_overlapping_classes' => $group->count(),
                    'conflicting_entries' => $group->map(fn($t) => [
                        'timetable_id' => $t->id,
                        'class_id' => $t->class_id,
                        'class_name' => $t->class?->name ?? '?',
                        'section_id' => $t->section_id,
                        'section_name' => $t->section?->name ?? 'A',
                        'subject_id' => $t->subject_id,
                        'subject_name' => $t->subject?->name ?? 'Subject',
                        'room' => $t->room,
                    ])->values()->all(),
                ];
            })
            ->values();

        return [
            'total_conflicts' => $conflicts->count(),
            'conflicts' => $conflicts->all(),
        ];
    }

    /**
     * Resolve an existing conflict record (Module 12).
     */
    public function resolveConflict(int $timetableId, string $resolution, ?int $newTeacherId = null): array
    {
        $tt = Timetable::with(['class', 'section', 'subject', 'teacher'])->findOrFail($timetableId);

        if ($resolution === 'free_period') {
            $tt->update([
                'period_type' => 'free',
                'teacher_id' => null,
                'room' => 'Self Study',
                'conflict_note' => 'Resolved: converted to Free Period by admin',
            ]);

            AuditLog::record('timetable_conflict_resolved', $tt, [], [
                'action' => 'marked_free_period',
                'resolved_by' => Auth::id(),
            ]);

            return ['success' => true, 'message' => 'Conflict resolved: Slot updated to Free Period.'];
        }

        if ($resolution === 'reassign' && $newTeacherId) {
            $newTeacher = Employee::findOrFail($newTeacherId);

            // Validate new assignment
            $validation = $this->validateSlot([
                'class_id' => $tt->class_id,
                'section_id' => $tt->section_id,
                'day_of_week' => $tt->day_of_week,
                'period_number' => $tt->period_number,
                'teacher_id' => $newTeacherId,
                'subject_id' => $tt->subject_id,
                'period_type' => 'class',
                'academic_year_id' => $tt->academic_year_id,
                'exclude_timetable_id' => $tt->id,
            ]);

            if (!$validation['is_valid']) {
                return [
                    'success' => false,
                    'message' => implode(' ', $validation['errors']),
                ];
            }

            $oldTeacherName = $tt->teacher?->full_name ?? 'Previous Teacher';
            $tt->update([
                'teacher_id' => $newTeacherId,
                'conflict_note' => "Reassigned from {$oldTeacherName} to {$newTeacher->full_name}",
            ]);

            AuditLog::record('timetable_conflict_resolved', $tt, [], [
                'action' => 'reassigned_teacher',
                'from_teacher' => $oldTeacherName,
                'to_teacher' => $newTeacher->full_name,
                'resolved_by' => Auth::id(),
            ]);

            return ['success' => true, 'message' => "Conflict resolved: Period reassigned to {$newTeacher->full_name}."];
        }

        return ['success' => false, 'message' => 'Invalid resolution parameters.'];
    }

    /**
     * Check if a staff member who is being made inactive or relieved has future timetable allocations.
     */
    public function checkInactiveStaffAllocations(int $employeeId, ?int $academicYearId = null): array
    {
        $allocations = Timetable::with(['class', 'section', 'subject'])
            ->where('teacher_id', $employeeId)
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->where('is_active', true)
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->get();

        $dayNames = self::getDayNames();

        return [
            'can_deactivate' => $allocations->isEmpty(),
            'total_allocations' => $allocations->count(),
            'has_active_allocations' => $allocations->isNotEmpty(),
            'count' => $allocations->count(),
            'allocations' => $allocations->map(fn($t) => [
                'id' => $t->id,
                'day' => $dayNames[$t->day_of_week] ?? "Day {$t->day_of_week}",
                'period' => $t->period_number,
                'class' => ($t->class?->name ?? 'Class') . ($t->section ? ' - ' . $t->section->name : ''),
                'subject' => $t->subject?->name ?? 'Subject',
            ])->all(),
        ];
    }
}
