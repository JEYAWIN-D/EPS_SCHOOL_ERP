<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'in_app',
        'email',
        'sms',
        'whatsapp',
        'preferences',
    ];

    protected $casts = [
        'in_app'      => 'boolean',
        'email'       => 'boolean',
        'sms'         => 'boolean',
        'whatsapp'    => 'boolean',
        'preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
