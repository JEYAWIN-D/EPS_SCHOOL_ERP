<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTestReminder extends Model
{
    use HasFactory;

    protected $table = 'staff_test_reminders';

    protected $fillable = [
        'teacher_id',
        'class_id',
        'section_id',
        'subject_id',
        'academic_year_id',
        'test_title',
        'test_type',
        'test_date',
        'start_time',
        'end_time',
        'syllabus_details',
        'max_marks',
        'paper_submission_deadline',
        'evaluation_deadline',
        'result_publication_deadline',
        'status',
        'instructions',
        'created_by',
    ];

    protected $casts = [
        'test_date'                   => 'date',
        'paper_submission_deadline'   => 'date',
        'evaluation_deadline'         => 'date',
        'result_publication_deadline' => 'date',
        'max_marks'                   => 'integer',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTestTypeLabelAttribute(): string
    {
        return match ($this->test_type) {
            'class_test'        => 'Class Test',
            'unit_test'         => 'Unit Test',
            'model_exam'        => 'Model Examination',
            'revision_test'     => 'Revision Test',
            'term_assessment'   => 'Term Assessment',
            default             => ucwords(str_replace('_', ' ', $this->test_type)),
        };
    }
}
