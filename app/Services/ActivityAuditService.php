<?php

namespace App\Services;

use App\Models\ActivityAuditLog;
use Illuminate\Support\Facades\Auth;

class ActivityAuditService
{
    public static function log(string $action, string $entityType, int $entityId, array $payload = []): void
    {
        try {
            ActivityAuditLog::create([
                'user_id'     => Auth::id(),
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'payload'     => $payload,
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to write activity audit log: ' . $e->getMessage());
        }
    }
}
