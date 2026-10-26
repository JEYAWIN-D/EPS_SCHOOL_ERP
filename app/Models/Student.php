<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'admission_no', 'admission_date', 'roll_number',
        'first_name', 'middle_name', 'last_name', 'dob', 'gender',
        'blood_group', 'religion', 'caste', 'sub_caste', 'category',
        'nationality', 'mother_tongue', 'aadhaar_no',
        'passport_number', 'passport_expiry',
        'mobile', 'email', 'photo',
        'residential_address', 'permanent_address', 'pincode',
        'is_disabled', 'disability_description',
        'annual_family_income',
        'father_name', 'father_mobile', 'father_occupation', 'father_email', 'father_aadhaar', 'father_photo',
        'mother_name', 'mother_mobile', 'mother_occupation', 'mother_email', 'mother_photo',
        'guardian_name', 'guardian_mobile', 'guardian_relation', 'guardian_photo',
        'emergency_contact_name', 'emergency_contact_mobile',
        'status', 'student_type', 'sibling_group_id', 'parent_employee_id',
        'leaving_date', 'leaving_reason',
        'allergies', 'medical_conditions',
        'previous_school_name', 'previous_school_board', 'tc_number', 'tc_date',
        'previous_percentage', 'migration_certificate_number', 'migration_certificate_date',
        'tc_document', 'marksheet_document', 'migration_document',
        'scholarship_name', 'scholarship_amount', 'scholarship_sanction_letter',
        'portal_blocked', 'portal_block_reason', 'portal_blocked_at',
        'principal_approved_at', 'principal_approved_by', 'principal_notes',
        'admin_approved_at', 'admin_approved_by', 'admin_notes',
        'rejection_reason', 'rejected_by', 'rejected_at',
        'parent_visitor_pass_token',
        'payment_terms', 'total_admission_fee', 'admission_paid_amount',
        'admission_pending_amount', 'excess_amount', 'payment_mode', 'payment_account', 'payment_date', 'payment_status', 'admission_fee_terms',
        'document_token',
        'emis_no', 'identification_mark_1', 'identification_mark_2',
        'is_asp', 'asp_fee',
        'concession_type', 'concession_amount', 'concession_remarks',
        'transport_route_id', 'transport_stop_id', 'transport_distance_km', 'transport_fee',
        'sibling_name', 'sibling_admission_no', 'sibling_class',
        'documents_submitted', 'selected_eca',
        'father_qualification', 'mother_qualification', 'father_income', 'mother_income',
        'stream_group', 'stream_group_allotted', 'is_tc_enclosed', 'is_qualified_promotion', 'year_of_passing',
        'dress_size', 'shoe_size', 'second_language', 'custom_kit_items', 'textbook_custom_fields',
    ];

    protected static function booted(): void
    {
        static::creating(function ($student) {
            if (empty($student->document_token)) {
                $student->document_token = \Illuminate\Support\Str::random(32);
            }
            if (empty($student->parent_visitor_pass_token)) {
                $student->parent_visitor_pass_token = \Illuminate\Support\Str::random(40);
            }
        });

        static::saved(function ($student) {
            \Illuminate\Support\Facades\Cache::forget('students_aadhaar_map');
        });

        static::deleted(function ($student) {
            \Illuminate\Support\Facades\Cache::forget('students_aadhaar_map');
        });
    }

    protected $casts = [
        'dob'            => 'date',
        'admission_date' => 'date',
        'tc_date'        => 'date',
        'leaving_date'   => 'date',
        'payment_date'   => 'date',
        'is_disabled'       => 'boolean',
        'portal_blocked'    => 'boolean',
        'portal_blocked_at' => 'datetime',
        'principal_approved_at' => 'datetime',
        'admin_approved_at'     => 'datetime',
        'rejected_at'           => 'datetime',
        'annual_family_income'          => 'decimal:2',
        'migration_certificate_date'    => 'date',
        'passport_expiry'               => 'date',
        'previous_percentage'           => 'decimal:2',
        'total_admission_fee'           => 'decimal:2',
        'admission_paid_amount'         => 'decimal:2',
        'admission_pending_amount'      => 'decimal:2',
        'excess_amount'                 => 'decimal:2',
        'admission_fee_terms'           => 'array',
        'is_asp'                        => 'boolean',
        'asp_fee'                       => 'decimal:2',
        'concession_amount'             => 'decimal:2',
        'transport_distance_km'         => 'decimal:2',
        'transport_fee'                 => 'decimal:2',
        'documents_submitted'           => 'array',
        'selected_eca'                  => 'array',
        'custom_kit_items'              => 'array',
        'textbook_custom_fields'        => 'array',
        'aadhaar_no'                    => \App\Casts\EncryptedStringResilient::class,
        'father_aadhaar'                => \App\Casts\EncryptedStringResilient::class,
        'passport_number'               => \App\Casts\EncryptedStringResilient::class,
    ];

    public function transportRoute(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function transportStop(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TransportStop::class, 'transport_stop_id');
    }

    public function transportAllotments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransportAllotment::class, 'student_id');
    }

    public function transportAllotment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TransportAllotment::class, 'student_id')->where('is_active', true)->latestOfMany();
    }

    public function transportHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransportAssignmentHistory::class, 'student_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->middle_name ? $this->middle_name . ' ' : '') . $this->last_name);
    }

    public function getAdmissionNumberAttribute(): ?string
    {
        return $this->attributes['admission_no'] ?? null;
    }

    public function getAadhaarNumberAttribute(): ?string
    {
        return $this->aadhaar_no;
    }

    public function getRollNoAttribute(): ?string
    {
        return $this->attributes['roll_number'] ?? null;
    }

    public function siblings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Student::class, 'sibling_group_id', 'sibling_group_id')
            ->where('id', '!=', $this->id);
    }

    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function currentEnrollment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentEnrollment::class)
            ->where('status', 'active')
            ->latest();
    }

    public function parentEmployee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'parent_employee_id');
    }

    public function bookIssues(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\BookIssue::class);
    }

    public function feePayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\FeePayment::class);
    }

    public function attendanceRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentLeaveRequest::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\StudentDocument::class);
    }

    public function bookNoteItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AdmissionBookNoteItem::class, 'student_id');
    }

    public function getDocumentTokenAttribute($value): string
    {
        if (empty($value)) {
            $token = \Illuminate\Support\Str::random(32);
            $this->attributes['document_token'] = $token;
            \Illuminate\Support\Facades\DB::table('students')->where('id', $this->id)->update(['document_token' => $token]);
            return $token;
        }
        return $value;
    }

    public function getDocumentUploadUrlAttribute(): string
    {
        return route('public.student.documents', ['token' => $this->document_token]);
    }

    public function getAttendancePercentageAttribute(): ?float
    {
        $year = AcademicYear::current();
        $total = $this->attendanceRecords()
            ->when($year, fn($q) => $q->where('academic_year_id', $year->id))
            ->count();
        if ($total === 0) return null;

        $present = $this->attendanceRecords()
            ->when($year, fn($q) => $q->where('academic_year_id', $year->id))
            ->whereIn('status', ['present', 'late', 'half_day'])
            ->count();

        return round(($present / $total) * 100, 1);
    }

    public function principalApprover(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'principal_approved_by');
    }

    public function adminApprover(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    public function rejectedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isPendingPrincipalApproval(): bool
    {
        return $this->status === 'pending_principal';
    }

    public function isPrincipalApproved(): bool
    {
        return $this->status === 'principal_approved';
    }

    public function isFullyApproved(): bool
    {
        return $this->status === 'active';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getParentVisitorPassTokenAttribute($value): string
    {
        if (empty($value)) {
            $token = \Illuminate\Support\Str::random(40);
            $this->attributes['parent_visitor_pass_token'] = $token;
            \Illuminate\Support\Facades\DB::table('students')->where('id', $this->id)->update(['parent_visitor_pass_token' => $token]);
            return $token;
        }
        return $value;
    }

    public function getVisitorPassUrlAttribute(): string
    {
        return route('public.visitor-card.view', ['token' => $this->parent_visitor_pass_token]);
    }

    public function getFatherPhotoUrlAttribute(): ?string
    {
        return $this->father_photo ? asset('storage/' . $this->father_photo) : null;
    }

    public function getMotherPhotoUrlAttribute(): ?string
    {
        return $this->mother_photo ? asset('storage/' . $this->mother_photo) : null;
    }

    public function getGuardianPhotoUrlAttribute(): ?string
    {
        return $this->guardian_photo ? asset('storage/' . $this->guardian_photo) : null;
    }

    /**
     * Get the next sequence integer for a given prefix (e.g. 'EPSB' or 'EPSG').
     */
    public static function getNextSequenceNumber(string $prefix): int
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $query  = static::withTrashed()->where('admission_no', 'like', $prefix . '%');

        if ($driver === 'pgsql') {
            $max = $query->whereRaw("admission_no ~ ?", ['^' . $prefix . '[0-9]+$'])
                         ->max(\Illuminate\Support\Facades\DB::raw("CAST(SUBSTRING(admission_no FROM 5) AS INTEGER)")) ?? 0;
        } elseif ($driver === 'sqlite') {
            $max = $query->max(\Illuminate\Support\Facades\DB::raw("CAST(SUBSTR(admission_no, 5) AS INTEGER)")) ?? 0;
        } else {
            $max = $query->whereRaw("admission_no REGEXP ?", ['^' . $prefix . '[0-9]+$'])
                         ->max(\Illuminate\Support\Facades\DB::raw("CAST(SUBSTRING(admission_no, 5) AS UNSIGNED)")) ?? 0;
        }

        return ((int)$max) + 1;
    }

    /**
     * Generate the next sequential official admission number based on gender:
     * - Boys: EPSB0001, EPSB0002, ... (Pattern: EPSB0000)
     * - Girls: EPSG0001, EPSG0002, ... (Pattern: EPSG000 / EPSG0000)
     */
    public static function generateAdmissionNumber(?string $gender = null): string
    {
        $g = strtolower(trim((string)$gender));
        $isFemale = in_array($g, ['female', 'f', 'girl', 'g']) || str_starts_with($g, 'fem');
        $prefix = $isFemale ? 'EPSG' : 'EPSB';

        $next = static::getNextSequenceNumber($prefix);
        do {
            $candidate = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (static::withTrashed()->where('admission_no', $candidate)->exists());

        return $candidate;
    }

    /**
     * Invalidate cached Aadhaar registry.
     */
    public static function clearAadhaarCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('students_aadhaar_map');
    }

    /**
     * Find an existing active student by 12-digit Aadhaar number.
     * Decrypts encrypted strings seamlessly and returns student info or null.
     */
    public static function findStudentByAadhaar(?string $aadhaar, ?int $excludeStudentId = null): ?array
    {
        $clean = preg_replace('/[^0-9]/', '', (string)$aadhaar);
        if (strlen($clean) !== 12) {
            return null;
        }

        $map = \Illuminate\Support\Facades\Cache::remember('students_aadhaar_map', 300, function () {
            $rows = static::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->whereNotNull('aadhaar_no')
                ->where('aadhaar_no', '!=', '')
                ->with(['currentEnrollment.class', 'currentEnrollment.section'])
                ->select(['id', 'first_name', 'last_name', 'admission_no', 'roll_number', 'aadhaar_no'])
                ->get();

            $out = [];
            foreach ($rows as $r) {
                $c = preg_replace('/[^0-9]/', '', (string)$r->aadhaar_no);
                if (strlen($c) === 12) {
                    $className = $r->currentEnrollment?->class?->name ?? 'Standard Class';
                    $secName = $r->currentEnrollment?->section?->name ? ' (' . $r->currentEnrollment->section->name . ')' : '';
                    $out[$c] = [
                        'id'           => (int)$r->id,
                        'name'         => trim($r->first_name . ' ' . ($r->last_name ?? '')),
                        'admission_no' => $r->admission_no ?? 'N/A',
                        'roll_number'  => $r->roll_number ?? '',
                        'class'        => $className . $secName,
                    ];
                }
            }
            return $out;
        });

        if (isset($map[$clean])) {
            $record = $map[$clean];
            if ($excludeStudentId && (int)$record['id'] === (int)$excludeStudentId) {
                return null;
            }
            return $record;
        }

        return null;
    }

    /**
     * Find an existing active student by EMIS / PEN Number.
     */
    public static function findStudentByEmis(?string $emis, ?int $excludeStudentId = null): ?array
    {
        $clean = trim((string)$emis);
        if ($clean === '') {
            return null;
        }

        $query = static::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNotNull('emis_no')
            ->where('emis_no', '!=', '')
            ->whereRaw('LOWER(TRIM(emis_no)) = ?', [strtolower($clean)])
            ->with(['currentEnrollment.class', 'currentEnrollment.section'])
            ->select(['id', 'first_name', 'last_name', 'admission_no', 'roll_number', 'emis_no']);

        if ($excludeStudentId) {
            $query->where('id', '!=', $excludeStudentId);
        }

        $student = $query->first();
        if (!$student) {
            return null;
        }

        $className = $student->currentEnrollment?->class?->name ?? 'Standard Class';
        $secName = $student->currentEnrollment?->section?->name ? ' (' . $student->currentEnrollment->section->name . ')' : '';

        return [
            'id'           => (int)$student->id,
            'name'         => trim($student->first_name . ' ' . ($student->last_name ?? '')),
            'admission_no' => $student->admission_no ?? 'N/A',
            'roll_number'  => $student->roll_number ?? '',
            'class'        => $className . $secName,
            'emis_no'      => $student->emis_no,
        ];
    }

    /**
     * Find an existing active student by Student Email Address.
     */
    public static function findStudentByEmail(?string $email, ?int $excludeStudentId = null): ?array
    {
        $clean = strtolower(trim((string)$email));
        if ($clean === '' || !filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $query = static::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$clean])
            ->with(['currentEnrollment.class', 'currentEnrollment.section'])
            ->select(['id', 'first_name', 'last_name', 'admission_no', 'roll_number', 'email']);

        if ($excludeStudentId) {
            $query->where('id', '!=', $excludeStudentId);
        }

        $student = $query->first();
        if (!$student) {
            return null;
        }

        $className = $student->currentEnrollment?->class?->name ?? 'Standard Class';
        $secName = $student->currentEnrollment?->section?->name ? ' (' . $student->currentEnrollment->section->name . ')' : '';

        return [
            'id'           => (int)$student->id,
            'name'         => trim($student->first_name . ' ' . ($student->last_name ?? '')),
            'admission_no' => $student->admission_no ?? 'N/A',
            'roll_number'  => $student->roll_number ?? '',
            'class'        => $className . $secName,
            'email'        => $student->email,
        ];
    }
}

