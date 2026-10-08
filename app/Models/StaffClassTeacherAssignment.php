<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffClassTeacherAssignment extends Model
{
    protected $table = 'staff_class_teacher_assignments';

    protected $fillable = [
        'employee_id',
        'class_id',
        'assigned_at',
        'ended_at',
        'is_current',
        'assigned_by',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'ended_at'    => 'date',
        'is_current'  => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_current ? 'Current' : 'Previous';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_current
            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-1 ring-emerald-600/20'
            : 'bg-slate-50 text-slate-600 border-slate-200';
    }
}
