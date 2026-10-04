<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderJob extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'reminder_type',
        'scheduled_at',
        'status', // pending, sent, failed
        'payload',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'payload'      => 'array',
    ];
}
