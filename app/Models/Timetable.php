<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    protected $fillable = [
        'class_id', 'section_id', 'subject_id', 'teacher_id', 'employee_id',
        'day', 'day_of_week', 'period_number', 'start_time', 'end_time',
        'room', 'is_active', 'status', 'conflict_note', 'period_type', 'effective_from', 'academic_year_id',
    ];

    protected $attributes = [
        'period_type' => 'regular',
        'status'      => 'scheduled',
        'is_active'   => true,
    ];

    protected $casts = [
        'effective_from' => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeActiveTeaching($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('teacher_id')
            ->whereNotIn('period_type', ['free', 'holiday']);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    public function periodReports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PeriodReport::class, 'timetable_id');
    }

    // Mutator for employee_id compatibility
    public function setEmployeeIdAttribute($value)
    {
        $this->attributes['teacher_id'] = $value;
    }

    public function getEmployeeIdAttribute()
    {
        return $this->teacher_id ?? ($this->attributes['employee_id'] ?? null);
    }

    public function getTeacherNameAttribute(): string
    {
        return $this->teacher?->full_name ?: $this->teacher?->first_name ?: 'Faculty In-charge';
    }

    public function getDayNameAttribute(): string
    {
        $dayMap = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        return $dayMap[$this->day_of_week] ?? (string)$this->day_of_week;
    }
}

