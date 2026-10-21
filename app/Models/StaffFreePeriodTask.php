<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffFreePeriodTask extends Model
{
    use HasFactory;

    protected $table = 'staff_free_period_tasks';

    protected $fillable = [
        'teacher_id',
        'date',
        'period_number',
        'activity_type',
        'title',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivityLabelAttribute(): string
    {
        return match ($this->activity_type) {
            'lesson_preparation'    => 'Lesson Preparation',
            'assignment_correction' => 'Assignment Correction',
            'student_mentoring'     => 'Student Mentoring',
            'remedial_planning'     => 'Remedial Planning',
            'qp_preparation'        => 'Question Paper Preparation',
            'teaching_report'       => 'Teaching Report Completion',
            'academic_meeting'      => 'Academic Meeting',
            'library_lab_prep'      => 'Library / Laboratory Prep',
            'administrative_work'   => 'Administrative Work',
            default                 => ucwords(str_replace('_', ' ', $this->activity_type)),
        };
    }
}
