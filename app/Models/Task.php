<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'creator_id',
        'priority', // low, normal, high, urgent
        'start_date',
        'due_date',
        'status', // pending, accepted, in_progress, blocked, submitted_for_review, completed, reopened, cancelled
        'source_type', // independent, meeting, event, notice
        'source_id',
        'department_id',
        'estimated_hours',
        'completion_notes',
        'reviewed_by',
        'reviewed_at',
        'school_id',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'due_date'        => 'date',
        'reviewed_at'     => 'datetime',
        'estimated_hours' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(TaskAssignee::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(TaskUpdate::class)->orderBy('created_at', 'desc');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(ActivityAttachment::class, 'attachable');
    }

    /**
     * Check if task is overdue as a calculated condition.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasAnyRole(['super_admin', 'principal', 'admin'])) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)
              ->orWhereHas('assignees', fn($a) => $a->where('user_id', $user->id));
        });
    }
}
