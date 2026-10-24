<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_code', 'first_name', 'last_name',
        'gender', 'dob', 'mobile', 'personal_email', 'official_email', 'email', 'address', 'residential_address',
        'photo', 'aadhaar_no', 'aadhaar_number', 'pan_no', 'pan_number',
        'department_id', 'designation_id', 'department', 'designation',
        'joining_date', 'employment_type', 'status', 'employee_type', 'is_active',
        'bank_name', 'bank_account_no', 'bank_account_number', 'bank_ifsc', 'bank_branch', 'pf_account_no', 'esi_no',
        'basic_salary', 'hra', 'ta', 'gross_salary',
        'qualification', 'experience_years', 'experience_months', 'kg_experience_years',
        'qualified_subjects', 'approved_teaching_levels', 'secondary_subject_expertise', 'higher_sec_subject_expertise', 'max_weekly_periods',
        'emergency_contact_name', 'emergency_contact_mobile',
        'driver_name', 'driver_mobile', 'license_number', 'license_expiry',
        'assigned_vehicle', 'assigned_block', 'shift_timing',
        'salary_on_hold', 'investment_80c', 'investment_80d', 'hra_exemption', 'tax_regime',
        'manager_id',
        'approval_status', 'approved_by', 'approved_at', 'rejection_reason',
        // Excel Master Fields
        'nationality', 'religion', 'category', 'mother_tongue', 'caste', 'marital_status',
        'number_of_children', 'children_male', 'children_female',
        'first_appointment_date', 'current_employment_status',
        'epf_no',
        'contact_person', 'name_of_contact_person', 'contact_relationship', 'contact_address', 'contact_no',
        'previous_from_date', 'previous_to_date', 'previous_school', 'previous_designation',
        'academic_qualification', 'academic_university', 'academic_medium', 'academic_institution', 'academic_percentage', 'academic_year_of_passing',
        'professional_qualification', 'professional_university', 'professional_medium', 'professional_institution', 'professional_percentage', 'professional_year_of_passing',
        'other_qual_1_name', 'other_qual_1_university', 'other_qual_1_medium', 'other_qual_1_institution', 'other_qual_1_percentage', 'other_qual_1_year_of_passing',
        'other_qual_2_name', 'other_qual_2_university', 'other_qual_2_medium', 'other_qual_2_institution', 'other_qual_2_percentage', 'other_qual_2_year_of_passing',
        'additional_qual_after_recruitment', 'training_attended_before_recruitment',
        // 13 Staff Data Collection Headings additions
        'alternate_phone', 'permanent_address', 'pincode',
        'pay_scale', 'last_promotion_date', 'contract_end_date', 'confirmation_date',
        'probation_start_date', 'probation_end_date',
        'major_subject', 'minor_subject', 'special_skills',
        'preferred_teaching_level', 'lab_subjects',
        'uan_no', 'professional_tax_no', 'passport_no',
        'annual_increment', 'increment_effective_date',
        'nominee_name', 'nominee_relationship', 'nominee_share',
        // Smart Timetable & Staff Teaching Eligibility Fields
        'max_daily_periods', 'preferred_days', 'preferred_periods',
        'unavailable_days', 'unavailable_periods', 'eligible_classes', 'eligible_subjects',
    ];

    protected $casts = [
        'dob'                      => 'date',
        'joining_date'             => 'date',
        'first_appointment_date'   => 'date',
        'previous_from_date'       => 'date',
        'previous_to_date'         => 'date',
        'license_expiry'           => 'date',
        'last_promotion_date'      => 'date',
        'contract_end_date'        => 'date',
        'confirmation_date'        => 'date',
        'probation_start_date'     => 'date',
        'probation_end_date'       => 'date',
        'increment_effective_date' => 'date',
        'basic_salary'             => 'decimal:2',
        'hra'                      => 'decimal:2',
        'ta'                       => 'decimal:2',
        'gross_salary'             => 'decimal:2',
        'annual_increment'         => 'decimal:2',
        'nominee_share'            => 'decimal:2',
        'academic_percentage'      => 'decimal:2',
        'professional_percentage'  => 'decimal:2',
        'other_qual_1_percentage'  => 'decimal:2',
        'other_qual_2_percentage'  => 'decimal:2',
        'number_of_children'       => 'integer',
        'children_male'            => 'integer',
        'children_female'          => 'integer',
        'academic_year_of_passing' => 'integer',
        'professional_year_of_passing' => 'integer',
        'experience_months'        => 'integer',
        'kg_experience_years'      => 'integer',
        'max_weekly_periods'       => 'integer',
        'max_daily_periods'        => 'integer',
        'qualified_subjects'       => 'array',
        'approved_teaching_levels' => 'array',
        'preferred_days'           => 'array',
        'preferred_periods'        => 'array',
        'unavailable_days'         => 'array',
        'unavailable_periods'      => 'array',
        'eligible_classes'         => 'array',
        'eligible_subjects'        => 'array',
        'is_active'                => 'boolean',
        'aadhaar_no'               => \App\Casts\EncryptedStringResilient::class,
        'pan_no'                   => \App\Casts\EncryptedStringResilient::class,
        'bank_account_no'          => \App\Casts\EncryptedStringResilient::class,
    ];

    public function getFullNameAttribute(): string
    {
        $first = trim((string) ($this->first_name ?? ''));
        $last  = trim((string) ($this->last_name ?? ''));
        if ($last === '' || strcasecmp($first, $last) === 0) {
            return $first;
        }
        return trim("{$first} {$last}");
    }

    public function getEmployeeNumberAttribute(): ?string
    {
        return $this->employee_code;
    }

    public function getCategoryLabelAttribute(): string
    {
        return match (strtolower($this->employee_type ?? 'teaching')) {
            'teaching'     => 'Teaching Staff',
            'non_teaching' => 'Non-Teaching Staff',
            'driver'       => 'Driver',
            'cleaner'      => 'Cleaner',
            'nanny', 'naani' => 'Nanny (Naani)',
            default        => ucfirst(str_replace('_', ' ', $this->employee_type ?? 'Staff')),
        };
    }

    public function getCategoryBadgeClassAttribute(): string
    {
        return match (strtolower($this->employee_type ?? 'teaching')) {
            'teaching'     => 'badge-indigo',
            'non_teaching' => 'badge-purple',
            'driver'       => 'badge-amber',
            'cleaner'      => 'badge-teal',
            'nanny', 'naani' => 'badge-rose',
            default        => 'badge-blue',
        };
    }

    public function getDepartmentNameAttribute(): string
    {
        if (!empty($this->attributes['department'])) {
            return $this->attributes['department'];
        }
        return $this->department?->name ?? 'General';
    }

    public function getDesignationNameAttribute(): string
    {
        if (!empty($this->attributes['designation'])) {
            return $this->attributes['designation'];
        }
        return $this->designation?->name ?? 'Staff';
    }

    /**
     * Calculate duration of experience from joining date to current date.
     * Returns an array with ['years' => int, 'months' => int, 'days' => int] or null.
     */
    public function getExperienceBreakdownAttribute(): ?array
    {
        if (!$this->joining_date) {
            return null;
        }

        $joiningDate = \Carbon\Carbon::parse($this->joining_date)->startOfDay();
        $currentDate = \Carbon\Carbon::now()->startOfDay();

        if ($joiningDate->greaterThan($currentDate)) {
            return ['years' => 0, 'months' => 0, 'days' => 0];
        }

        $diff = $joiningDate->diff($currentDate);

        return [
            'years'  => (int) $diff->y,
            'months' => (int) $diff->m,
            'days'   => (int) $diff->d,
        ];
    }

    /**
     * Get the experience years (derived automatically from joining date, or fallback to column).
     */
    public function getExperienceYearsAttribute(): ?int
    {
        $breakdown = $this->experience_breakdown;
        if ($breakdown !== null) {
            return $breakdown['years'];
        }

        return isset($this->attributes['experience_years']) ? (int) $this->attributes['experience_years'] : null;
    }

    /**
     * Get experience string formatted as "X Years Y Months".
     * Example: "4 Years 7 Months"
     */
    public function getExperienceDurationAttribute(): string
    {
        $breakdown = $this->experience_breakdown;
        if ($breakdown === null) {
            return '—';
        }

        $years = $breakdown['years'];
        $months = $breakdown['months'];

        $yearLabel = $years === 1 ? 'Year' : 'Years';
        $monthLabel = $months === 1 ? 'Month' : 'Months';

        return "{$years} {$yearLabel} {$months} {$monthLabel}";
    }

    /**
     * Get full formatted experience string, e.g. "Experience: 4 Years 7 Months".
     */
    public function getExperienceFormattedAttribute(): string
    {
        $duration = $this->experience_duration;
        if ($duration === '—') {
            return 'Experience: —';
        }

        return "Experience: {$duration}";
    }

    /**
     * Calculate overall career work experience breakdown from first appointment date (or joining date).
     */
    public function getOverallExperienceBreakdownAttribute(): ?array
    {
        $startDate = $this->first_appointment_date ?? $this->joining_date;
        if (!$startDate) {
            return null;
        }

        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $now = \Carbon\Carbon::now()->startOfDay();

        if ($start->greaterThan($now)) {
            return ['years' => 0, 'months' => 0, 'days' => 0];
        }

        $diff = $start->diff($now);

        return [
            'years'  => (int) $diff->y,
            'months' => (int) $diff->m,
            'days'   => (int) $diff->d,
        ];
    }

    /**
     * Overall experience duration string, e.g. "5 Years 8 Months".
     */
    public function getOverallExperienceDurationAttribute(): string
    {
        $breakdown = $this->overall_experience_breakdown;
        if ($breakdown === null) {
            $y = (int) ($this->attributes['experience_years'] ?? 0);
            $m = (int) ($this->attributes['experience_months'] ?? 0);
            if ($y > 0 || $m > 0) {
                return "{$y} " . ($y === 1 ? 'Year' : 'Years') . " {$m} " . ($m === 1 ? 'Month' : 'Months');
            }
            return '—';
        }

        $years = $breakdown['years'];
        $months = $breakdown['months'];
        $yLabel = $years === 1 ? 'Year' : 'Years';
        $mLabel = $months === 1 ? 'Month' : 'Months';

        return "{$years} {$yLabel} {$months} {$mLabel}";
    }

    /**
     * Full formatted overall experience string, e.g. "Overall Experience: 5 Years 8 Months".
     */
    public function getOverallExperienceFormattedAttribute(): string
    {
        $duration = $this->overall_experience_duration;
        return $duration === '—' ? 'Overall Experience: —' : "Overall Experience: {$duration}";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function leaveRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payrollRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function staffAttendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function staffEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaffEvent::class);
    }

    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function directReports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function qualifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeQualification::class);
    }

    public function experiences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeExperience::class);
    }

    public function familyMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeFamilyMember::class);
    }

    public function certifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeCertification::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function appraisals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeAppraisal::class);
    }

    public function careerHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeCareerHistory::class)->orderByDesc('effective_date');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teachingLevelEligibilities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherLevelEligibility::class);
    }

    public function eligibleTeachingLevels(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(TeachingLevel::class, 'teacher_level_eligibilities')->wherePivot('is_approved', true);
    }

    public function teacherSubjectAllocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherSubjectAllocation::class, 'employee_id');
    }

    public function isEligibleForLevel(string|int $level): bool
    {
        if (is_numeric($level)) {
            return $this->teachingLevelEligibilities()->where('teaching_level_id', $level)->where('is_approved', true)->exists();
        }
        return $this->eligibleTeachingLevels()->where('code', $level)->exists();
    }

    public function classTeacherAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaffClassTeacherAssignment::class, 'employee_id')
            ->orderByDesc('is_current')
            ->orderByDesc('assigned_at')
            ->orderByDesc('id');
    }

    public function currentClassTeacherAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StaffClassTeacherAssignment::class, 'employee_id')
            ->where('is_current', true);
    }

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function getClassTeacherBadgeAttribute(): ?string
    {
        $current = $this->currentClassTeacherAssignment;
        if (!$current || !$current->class) {
            return null;
        }
        return 'Class Teacher — ' . ($current->class->class_teacher_title ?? ('Class ' . $current->class->name));
    }

    /**
     * Check if staff member is available on a specific day of week and period.
     */
    public function isAvailableOn(int $dayOfWeek, int $periodNumber): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $empStatus = strtolower($this->status ?? 'active');
        if (in_array($empStatus, ['inactive', 'relieved', 'terminated', 'resigned'])) {
            return false;
        }

        $unavDays = is_array($this->unavailable_days) ? $this->unavailable_days : [];
        if (in_array($dayOfWeek, $unavDays) || in_array((string)$dayOfWeek, $unavDays)) {
            return false;
        }

        $unavPeriods = is_array($this->unavailable_periods) ? $this->unavailable_periods : [];
        if (in_array($periodNumber, $unavPeriods) || in_array((string)$periodNumber, $unavPeriods)) {
            return false;
        }

        // Slot composite key e.g. "1-3" (Day 1, Period 3)
        $slotKey = "{$dayOfWeek}-{$periodNumber}";
        if (in_array($slotKey, $unavPeriods)) {
            return false;
        }

        return true;
    }

    /**
     * Check if staff member is eligible to teach a specific subject in a specific class.
     */
    public function canTeachClassSubject(int $classId, int $subjectId, ?int $sectionId = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $empStatus = strtolower($this->status ?? 'active');
        if (in_array($empStatus, ['inactive', 'relieved'])) {
            return false;
        }

        // 1. Direct explicit allocation record in TeacherSubjectAllocation
        $hasAllocation = $this->teacherSubjectAllocations()
            ->where('subject_id', $subjectId)
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->when($sectionId, function ($q) use ($sectionId) {
                $q->where(function ($sub) use ($sectionId) {
                    $sub->whereNull('section_id')->orWhere('section_id', $sectionId);
                });
            })
            ->exists();

        if ($hasAllocation) {
            return true;
        }

        // 2. Check eligible_classes and eligible_subjects json lists if configured
        $elClasses = is_array($this->eligible_classes) ? $this->eligible_classes : [];
        $elSubjects = is_array($this->eligible_subjects) ? $this->eligible_subjects : [];

        $classAllowed = empty($elClasses) || in_array($classId, $elClasses) || in_array((string)$classId, $elClasses);
        $subjectAllowed = empty($elSubjects) || in_array($subjectId, $elSubjects) || in_array((string)$subjectId, $elSubjects);

        if (!empty($elClasses) || !empty($elSubjects)) {
            return $classAllowed && $subjectAllowed;
        }

        // 3. Fallback to qualified_subjects text/array or secondary/higher sec expertise
        $qualSubjects = is_array($this->qualified_subjects) ? $this->qualified_subjects : [];
        if (!empty($qualSubjects)) {
            $subjName = \App\Models\Subject::where('id', $subjectId)->value('name');
            if ($subjName) {
                foreach ($qualSubjects as $qs) {
                    if (strcasecmp(trim($qs), trim($subjName)) === 0 || str_contains(strtolower($subjName), strtolower(trim($qs)))) {
                        return true;
                    }
                }
            }
        }

        // If no explicit restrictions exist, check teaching level
        $classModel = \App\Models\Classes::find($classId);
        if ($classModel && $classModel->teaching_level_id) {
            return $this->isEligibleForLevel($classModel->teaching_level_id);
        }

        return true;
    }

    /**
     * Get active timetable periods count for current academic year.
     */
    public function getActiveWeeklyTimetableCount(?int $academicYearId = null): int
    {
        $ayId = $academicYearId ?? (\App\Models\AcademicYear::current()?->id ?? \App\Models\AcademicYear::first()?->id);
        return \App\Models\Timetable::where('teacher_id', $this->id)
            ->when($ayId, fn($q) => $q->where('academic_year_id', $ayId))
            ->where('is_active', true)
            ->whereNotIn('period_type', ['free', 'holiday'])
            ->count();
    }
}
