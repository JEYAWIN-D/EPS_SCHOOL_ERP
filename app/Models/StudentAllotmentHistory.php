<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAllotmentHistory extends Model
{
    protected $table = 'student_allotment_histories';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'allotment_type',
        'from_value',
        'to_value',
        'reason',
        'changed_by',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public static function log(int $studentId, string $type, ?string $from, ?string $to, ?string $reason = null, ?int $academicYearId = null): self
    {
        return static::create([
            'student_id'       => $studentId,
            'academic_year_id' => $academicYearId ?? AcademicYear::current()?->id,
            'allotment_type'   => $type,
            'from_value'       => $from,
            'to_value'         => $to,
            'reason'           => $reason,
            'changed_by'       => \Illuminate\Support\Facades\Auth::id(),
        ]);
    }
}

