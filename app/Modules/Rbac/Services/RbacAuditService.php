<?php

namespace App\Modules\Rbac\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class RbacAuditService
{
    /**
     * Log an RBAC event to audit_logs.
     */
    public static function log(
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $actor = null
    ): void {
        $actor = $actor ?? Auth::user();

        // Sanitize sensitive credentials from audit logs
        $oldSanitized = self::sanitize($oldValues);
        $newSanitized = self::sanitize($newValues);

        try {
            DB::table('audit_logs')->insert([
                'user_id' => $actor ? $actor->id : null,
                'module' => 'RBAC',
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'old_values' => $oldSanitized ? json_encode($oldSanitized) : null,
                'new_values' => $newSanitized ? json_encode($newSanitized) : null,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => substr(Request::userAgent() ?? 'System', 0, 500),
                'url' => substr(Request::fullUrl() ?? '/rbac', 0, 500),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Fail safely without disrupting the main user transaction
            \Illuminate\Support\Facades\Log::warning("RBAC Audit logging failed: " . $e->getMessage());
        }
    }

    /**
     * Remove sensitive fields such as password, tokens, hashes.
     */
    protected static function sanitize(?array $data): ?array
    {
        if (empty($data)) {
            return null;
        }

        $sensitiveKeys = ['password', 'password_confirmation', 'admin_pin', 'remember_token', 'token', 'secret'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = self::sanitize($value);
            }
        }

        return $data;
    }
}
