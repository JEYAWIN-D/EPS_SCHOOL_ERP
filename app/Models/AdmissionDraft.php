<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AdmissionDraft extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'draft_number',
        'enquiry_id',
        'created_by',
        'academic_year_id',
        'class_id',
        'student_name',
        'parent_name',
        'parent_mobile',
        'form_data',
        'completion_percentage',
        'current_step',
        'status',
        'last_saved_at',
        'submission_attempted_at',
        'submission_error',
        'idempotency_token',
        'converted_student_id',
    ];

    protected $casts = [
        'form_data'               => 'array',
        'completion_percentage'   => 'integer',
        'current_step'            => 'integer',
        'last_saved_at'           => 'datetime',
        'submission_attempted_at' => 'datetime',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class, 'enquiry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AdmissionDraftDocument::class, 'draft_id');
    }

    public static function generateDraftNumber(): string
    {
        $year = date('Y');
        $count = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
        $num = 'ADMD-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        while (static::withTrashed()->where('draft_number', $num)->exists()) {
            $count++;
            $num = 'ADMD-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }
        return $num;
    }

    public static function calculateCompletion(array $data): int
    {
        $totalWeight = 0;
        $earnedWeight = 0;

        $checkpoints = [
            // Section 1: Student details (Weight: 25)
            ['key' => 'first_name', 'weight' => 7],
            ['key' => 'dob', 'weight' => 6],
            ['key' => 'gender', 'weight' => 6],
            ['key' => 'class_id', 'weight' => 6],

            // Section 2: Parent details (Weight: 25)
            ['key' => 'parent_name', 'weight' => 10],
            ['key' => 'parent_mobile', 'weight' => 10],
            ['key' => 'address', 'weight' => 5],

            // Section 3: Academic & Previous (Weight: 15)
            ['key' => 'previous_school', 'weight' => 8],
            ['key' => 'previous_class', 'weight' => 7],

            // Section 4: Fee & Payment (Weight: 20)
            ['key' => 'payment_terms', 'weight' => 10],
            ['key' => 'payment_mode', 'weight' => 10],

            // Section 5: Documents / Photos (Weight: 15)
            ['key' => 'photo_uploaded', 'weight' => 8],
            ['key' => 'doc_birth_certificate_uploaded', 'weight' => 7],
        ];

        foreach ($checkpoints as $cp) {
            $totalWeight += $cp['weight'];
            if (!empty($data[$cp['key']])) {
                $earnedWeight += $cp['weight'];
            }
        }

        if ($totalWeight === 0) return 0;
        return (int) round(($earnedWeight / $totalWeight) * 100);
    }
}

