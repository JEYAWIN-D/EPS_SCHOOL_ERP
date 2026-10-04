<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code', 'first_name', 'last_name',
        'gender', 'dob', 'mobile', 'personal_email', 'official_email', 'email', 'address', 'residential_address',
        'photo', 'aadhaar_no', 'aadhaar_number', 'pan_no', 'pan_number',
        'department_id', 'designation_id', 'department', 'designation',
        'joining_date', 'employment_type', 'status', 'employee_type', 'is_active',
        'bank_name', 'bank_account_no', 'bank_account_number', 'bank_ifsc', 'bank_branch', 'pf_account_no', 'esi_no',
        'basic_salary', 'hra', 'ta', 'gross_salary',
        'qualification', 'experience_years',
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
    ];

    protected $casts = [
        'dob'                     => 'date',
        'joining_date'            => 'date',
        'first_appointment_date'  => 'date',
        'previous_from_date'      => 'date',
        'previous_to_date'        => 'date',
        'license_expiry'          => 'date',
        'basic_salary'            => 'decimal:2',
        'hra'                     => 'decimal:2',
        'ta'                      => 'decimal:2',
        'gross_salary'            => 'decimal:2',
        'academic_percentage'     => 'decimal:2',
        'professional_percentage' => 'decimal:2',
        'other_qual_1_percentage' => 'decimal:2',
        'other_qual_2_percentage' => 'decimal:2',
        'number_of_children'      => 'integer',
        'children_male'           => 'integer',
        'children_female'         => 'integer',
        'academic_year_of_passing'=> 'integer',
        'professional_year_of_passing' => 'integer',
        'other_qual_1_year_of_passing' => 'integer',
        'other_qual_2_year_of_passing' => 'integer',
        'is_active'               => 'boolean',
        'aadhaar_no'              => \App\Casts\EncryptedStringResilient::class,
        'pan_no'                  => \App\Casts\EncryptedStringResilient::class,
        'bank_account_no'         => \App\Casts\EncryptedStringResilient::class,
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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
