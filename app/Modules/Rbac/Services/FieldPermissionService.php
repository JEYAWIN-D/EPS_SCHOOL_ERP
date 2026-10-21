<?php

namespace App\Modules\Rbac\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FieldPermissionService
{
    /**
     * Filter an array of entity attributes according to user's field permissions.
     */
    public static function filterAttributes(array $attributes, string $entity, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        if (!$user || $user->isSuperAdmin()) {
            return $attributes;
        }

        foreach ($attributes as $key => $val) {
            $permissionType = $user->getEffectiveFieldPermission($entity, $key);

            if ($permissionType === 'hidden') {
                unset($attributes[$key]);
            } elseif ($permissionType === 'masked' && !is_null($val)) {
                $attributes[$key] = self::maskValue($key, (string)$val);
            }
        }

        return $attributes;
    }

    /**
     * Mask a value based on field type conventions.
     */
    public static function maskValue(string $fieldName, string $value): string
    {
        if (empty($value)) {
            return '';
        }

        $lowerField = strtolower($fieldName);

        // Salary or Financial Amounts
        if (str_contains($lowerField, 'salary') || str_contains($lowerField, 'pay') || str_contains($lowerField, 'amount') || str_contains($lowerField, 'balance')) {
            return '₹ ••••••••';
        }

        // Phone numbers
        if (str_contains($lowerField, 'mobile') || str_contains($lowerField, 'phone') || str_contains($lowerField, 'contact')) {
            $len = strlen($value);
            if ($len > 4) {
                return substr($value, 0, 2) . str_repeat('*', max(1, $len - 4)) . substr($value, -2);
            }
            return '******';
        }

        // Bank Accounts / IFSC
        if (str_contains($lowerField, 'bank') || str_contains($lowerField, 'account')) {
            $len = strlen($value);
            if ($len > 4) {
                return '••••••••' . substr($value, -4);
            }
            return '••••••••';
        }

        // Aadhaar, PAN, SSN
        if (str_contains($lowerField, 'aadhaar') || str_contains($lowerField, 'pan') || str_contains($lowerField, 'identity')) {
            $len = strlen($value);
            if ($len > 4) {
                return '••••-••••-' . substr($value, -4);
            }
            return '••••-••••-••••';
        }

        // Medical or health information
        if (str_contains($lowerField, 'medical') || str_contains($lowerField, 'health') || str_contains($lowerField, 'allergy')) {
            return '[RESTRICTED MEDICAL DATA]';
        }

        // Default generic masking
        $len = strlen($value);
        if ($len <= 3) {
            return '***';
        }
        return substr($value, 0, 1) . str_repeat('*', $len - 2) . substr($value, -1);
    }

    /**
     * Check if a field can be edited by the user.
     */
    public static function canEditField(User $user, string $entity, string $fieldName): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $perm = $user->getEffectiveFieldPermission($entity, $fieldName);
        return $perm === 'editable';
    }
}
