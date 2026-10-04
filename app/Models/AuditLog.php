<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'user_id', 'module', 'action', 'model_type', 'model_id',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'url',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Keys whose values must be redacted from audit logs.
     */
    protected static array $redactedKeys = [
        'password',
        'password_confirmation',
        'remember_token',
        'aadhaar_no',
        'father_aadhaar',
        'passport_number',
        'pan_no',
        'bank_account_no',
        'token',
        'secret',
        'cvv',
        'card_number',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /**
     * Recursively redact sensitive fields from audit payloads.
     */
    public static function sanitizePayload(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            if (in_array($lowerKey, static::$redactedKeys, true)) {
                $sanitized[$key] = '********';
            } elseif (is_array($value)) {
                $sanitized[$key] = static::sanitizePayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    public static function record(string $action, $model = null, array $old = [], array $new = [], ?string $module = null): void
    {
        try {
            $data = [
                'user_id'     => auth()->id(),
                'module'      => $module ?? ($model ? strtolower(class_basename($model)) : 'system'),
                'action'      => $action,
                'model_type'  => $model ? get_class($model) : null,
                'model_id'    => $model?->id,
                'old_values'  => !empty($old) ? static::sanitizePayload($old) : null,
                'new_values'  => !empty($new) ? static::sanitizePayload($new) : null,
                'ip_address'  => request()->ip(),
                'user_agent'  => substr((string) request()->userAgent(), 0, 255),
                'url'         => substr((string) request()->fullUrl(), 0, 255),
                'created_at'  => now(),
            ];

            if (function_exists('dispatch')) {
                dispatch(function () use ($data) {
                    try {
                        static::create($data);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Async AuditLog::record failed: ' . $e->getMessage());
                    }
                })->afterResponse();
            } else {
                static::create($data);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AuditLog::record failed: ' . $e->getMessage());
        }
    }
}
