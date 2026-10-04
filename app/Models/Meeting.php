<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Meeting extends Model
{
    protected $fillable = [
        'title',
        'agenda',
        'meeting_type', // staff, hod, management, ptm, departmental, general
        'organizer_id',
        'chairperson_id',
        'department_id',
        'start_time',
        'end_time',
        'venue',
        'meeting_link',
        'status', // scheduled, in_progress, completed, cancelled
        'cancellation_reason',
        'follow_up_meeting_id',
        'school_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function chairperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chairperson_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function minutes(): HasMany
    {
        return $this->hasMany(MeetingMinute::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'source_id')->where('source_type', 'meeting');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(ActivityAttachment::class, 'attachable');
    }

    public function followUpMeeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'follow_up_meeting_id');
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasAnyRole(['super_admin', 'principal', 'admin'])) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('organizer_id', $user->id)
              ->orWhere('chairperson_id', $user->id)
              ->orWhereHas('attendees', fn($a) => $a->where('user_id', $user->id));
        });
    }
}
