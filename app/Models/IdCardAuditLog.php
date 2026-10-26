<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdCardAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'card_id',
        'action',
        'previous_values',
        'new_values',
        'performed_by',
        'reason',
        'ip_address',
        'user_agent',
        'timestamp',
    ];

    protected $casts = [
        'previous_values' => 'array',
        'new_values'      => 'array',
        'timestamp'       => 'datetime',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(IdCard::class, 'card_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public static function record(int $cardId, string $action, ?array $previousValues = null, ?array $newValues = null, ?string $reason = null): self
    {
        return self::create([
            'card_id'         => $cardId,
            'action'          => $action,
            'previous_values' => $previousValues,
            'new_values'      => $newValues,
            'performed_by'    => auth()->id(),
            'reason'          => $reason,
            'ip_address'      => request()->ip(),
            'user_agent'      => substr(request()->userAgent() ?? '', 0, 255),
            'timestamp'       => now(),
        ]);
    }
}
