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

    public function getIsAssignedAttribute(): bool
    {
        if ($this->relationLoaded('staff')) {
            return $this->staff->isNotEmpty();
        }
        return $this->staff()->exists();
    }

    public function getAssignmentStatusLabelAttribute(): string
    {
        return $this->is_assigned ? 'Assigned' : 'Pending Assignment';
    }

    public function scopePublished($q)
    {
        return $q->where(fn($sub) => $sub->where('is_published', true)->orWhere('status', 'published'));
    }

    public function scopeUpcoming($q)
    {
        return $q->where('event_date', '>=', today());
    }

    public function scopeForUser($query, ?User $user = null)
    {
        if (!$user) {
            return $query;
        }

        if ($user->hasAnyRole(['super_admin', 'principal', 'admin', 'vice_principal', 'correspondent', 'correspondant'])) {
            if (!empty($user->school_id)) {
                $query->where(fn($sq) => $sq->where('school_id', $user->school_id)->orWhereNull('school_id'));
            }
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('audience', 'all')
              ->orWhere(function ($sub) use ($user) {
                  $rolesFound = false;
                  if ($user->hasRole('teacher') || !empty($user->employee_id)) {
                      $sub->where('audience', 'staff');
                      $rolesFound = true;
                  }
                  if ($user->hasRole('student')) {
                      $sub->orWhere('audience', 'students');
                      $rolesFound = true;
                  }
                  if ($user->hasRole('parent')) {
                      $sub->orWhere('audience', 'parents');
                      $rolesFound = true;
                  }
                  if (!$rolesFound) {
                      $sub->whereRaw('1 = 0');
                  }
              })
              ->orWhere('organizer_id', $user->id)
              ->orWhere('created_by', $user->id);

            if (!empty($user->employee_id)) {
                $q->orWhereHas('staff', fn($s) => $s->where('employee_id', $user->employee_id));
            }
        })
        ->when(!empty($user->school_id), fn($sq) => $sq->where(fn($sub) => $sub->where('school_id', $user->school_id)->orWhereNull('school_id')));
    }
}
