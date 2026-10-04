<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Event extends Model
{
    protected $fillable = [
        'name',
        'event_type',
        'category',
        'event_date',
        'start_time',
        'end_time',
        'venue',
        'description',
        'banner_image',
        'is_published',
        'status',
        'allow_rsvp',
        'max_rsvp',
        'audience',
        'created_by',
        'organizer_id',
        'budget_estimated',
        'budget_actual',
        'cancellation_reason',
        'post_event_report',
        'recurrence_rule',
        'school_id',
    ];

    protected $casts = [
        'event_date'       => 'date',
        'is_published'     => 'boolean',
        'allow_rsvp'       => 'boolean',
        'budget_estimated' => 'decimal:2',
        'budget_actual'    => 'decimal:2',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(EventStaff::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(ActivityAttachment::class, 'attachable');
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function scopePublished($q)
    {
        return $q->where(fn($sub) => $sub->where('is_published', true)->orWhere('status', 'published'));
    }

    public function scopeUpcoming($q)
    {
        return $q->where('event_date', '>=', today());
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasAnyRole(['super_admin', 'principal', 'admin'])) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('audience', 'all')
              ->orWhere(function ($sub) use ($user) {
                  if ($user->hasRole('teacher') || $user->employee_id) {
                      $sub->where('audience', 'staff');
                  }
                  if ($user->hasRole('student')) {
                      $sub->orWhere('audience', 'students');
                  }
                  if ($user->hasRole('parent')) {
                      $sub->orWhere('audience', 'parents');
                  }
              })
              ->orWhere('organizer_id', $user->id)
              ->orWhere('created_by', $user->id)
              ->orWhereHas('staff', fn($s) => $s->where('employee_id', $user->employee_id));
        });
    }
}
