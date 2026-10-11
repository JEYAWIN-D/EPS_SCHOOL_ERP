<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodReport extends Model
{
    use HasFactory;

    protected $table = 'period_reports';

    protected $fillable = [
        'teacher_id',
        'class_id',
        'section_id',
        'subject_id',
        'timetable_id',
        'academic_year_id',
        'date',
        'day',
        'period_number',
        'student_present_count',
        'student_absent_count',
        'present_count',
        'absent_count',
        'description',
        'topic_covered',
        'teaching_report',
        'feedback',
        'suggestion',
        'created_by',
    ];

    protected $casts = [
        'date'                  => 'date',
        'student_present_count' => 'integer',
        'student_absent_count'  => 'integer',
    ];

    protected $appends = [
        'present_count',
        'absent_count',
    ];

    public function teacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    public function class(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function timetable(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Timetable::class, 'timetable_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors / Mutators for present_count and absent_count compatibility
    public function getPresentCountAttribute(): ?int
    {
        return $this->student_present_count;
    }

    public function setPresentCountAttribute($value): void
    {
        $this->attributes['student_present_count'] = $value !== null ? (int)$value : null;
    }

    public function getAbsentCountAttribute(): ?int
    {
        return $this->student_absent_count;
    }

    public function setAbsentCountAttribute($value): void
    {
        $this->attributes['student_absent_count'] = $value !== null ? (int)$value : null;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['description'] ?? ($this->attributes['topic_covered'] ?? null);
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = $value;
        if (empty($this->attributes['topic_covered'])) {
            $this->attributes['topic_covered'] = $value ?? '';
        }
    }
}
