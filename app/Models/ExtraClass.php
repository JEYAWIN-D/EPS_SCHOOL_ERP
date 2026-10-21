<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraClass extends Model
{
    use HasFactory;

    protected $table = 'extra_classes';

    protected $fillable = [
        'teacher_id',
        'class_id',
        'section_id',
        'subject_id',
        'academic_year_id',
        'date',
        'period_number',
        'start_time',
        'end_time',
        'room',
        'reason',
        'approval_status',
        'approved_by',
        'approved_at',
        'completion_status',
        'completion_notes',
        'created_by',
    ];

    protected $casts = [
        'date'        => 'date',
        'approved_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
