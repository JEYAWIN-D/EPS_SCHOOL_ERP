<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendee extends Model
{
    protected $fillable = [
        'meeting_id',
        'user_id',
        'is_optional',
        'rsvp_status', // pending, accepted, declined, tentative
        'rsvp_note',
        'attendance_status', // pending, present, absent, excused
        'attended_at',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'attended_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
