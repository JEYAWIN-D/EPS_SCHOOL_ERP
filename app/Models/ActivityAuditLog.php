<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action', // created, updated, published, approved, rejected, cancelled, completed, reassigned, acknowledged
        'entity_type', // notice, event, meeting, task
        'entity_id',
        'payload',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
