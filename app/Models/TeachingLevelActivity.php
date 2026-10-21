<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingLevelActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'section_id',
        'academic_year_id',
        'activity_name',
        'activity_type',
        'start_time',
        'end_time',
        'day_of_week',
        'teacher_id',
        'assistant_teacher_id',
        'learning_outcome',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active'   => 'boolean',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    public function assistantTeacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assistant_teacher_id');
    }
}
