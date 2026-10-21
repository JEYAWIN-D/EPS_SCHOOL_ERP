<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherLevelEligibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'teaching_level_id',
        'is_approved',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function teachingLevel(): BelongsTo
    {
        return $this->belongsTo(TeachingLevel::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
