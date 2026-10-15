<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDuty extends Model
{
    use HasFactory;

    protected $table = 'staff_duties';

    protected $fillable = [
        'teacher_id',
        'academic_year_id',
        'duty_type',
        'title',
        'description',
        'date',
        'day_of_week',
        'period_number',
        'start_time',
        'end_time',
        'room',
        'status',
        'assigned_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getDutyLabelAttribute(): string
    {
        return match ($this->duty_type) {
            'exam_duty'          => 'Examination Duty',
            'meeting'            => 'Staff Meeting / Admin Duty',
            'remedial'           => 'Remedial Class',
            'practical_lab'      => 'Practical / Laboratory Period',
            'activity_period'    => 'Activity Period',
            'leave_unavailable'  => 'Leave / Unavailable',
            default              => ucwords(str_replace('_', ' ', $this->duty_type)),
        };
    }
}
