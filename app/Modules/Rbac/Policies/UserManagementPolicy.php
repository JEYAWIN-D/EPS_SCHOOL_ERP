<?php

namespace App\Modules\Rbac\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;

class UserManagementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view user management.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->can('view users') || $actor->can('manage users') || $actor->can('manage rbac');
    }

    /**
     * Determine whether the user can create accounts.
     */
    public function create(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->can('create users') || $actor->can('manage users') || $actor->can('manage rbac');
    }

    /**
     * Determine whether the user can update target account.
     */
    public function update(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        // Non-super admin cannot edit a super admin account
        if ($target->isSuperAdmin()) {
            return false;
        }

        return $actor->can('edit users') || $actor->can('manage users') || $actor->can('manage rbac');
    }

    /**
     * Determine whether the user can deactivate target account.
     * PROTECTS AGAINST ACCIDENTAL REMOVAL OR DEACTIVATION OF LAST ACTIVE SUPER ADMIN.
     */
    public function deactivate(User $actor, User $target): bool
    {
        // 1. If target is Super Admin, ensure at least one other ACTIVE Super Admin remains!
        if ($target->isSuperAdmin() && $target->isActive()) {
            $activeSuperAdminCount = User::role('super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $target->id)
                ->whereNull('deleted_at')
                ->count();

            if ($activeSuperAdminCount < 1) {
                return false; // CANNOT DEACTIVATE LAST ACTIVE SUPER ADMIN
            }
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($target->isSuperAdmin()) {
            return false;
        }

        return $actor->can('edit users') || $actor->can('manage users') || $actor->can('manage rbac');
    }

    /**
     * Determine whether the user can soft-delete target account.
     * PROTECTS AGAINST ACCIDENTAL DELETION OF LAST ACTIVE SUPER ADMIN.
     */
    public function delete(User $actor, User $target): bool
    {
        // 1. If target is Super Admin, ensure at least one other ACTIVE Super Admin remains!
        if ($target->isSuperAdmin()) {
            $remainingSuperAdmins = User::role('super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $target->id)
                ->whereNull('deleted_at')
                ->count();

            if ($remainingSuperAdmins < 1) {
                return false; // CANNOT DELETE LAST ACTIVE SUPER ADMIN
            }
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($target->isSuperAdmin()) {
            return false;
        }

        return $actor->can('delete users') || $actor->can('manage users') || $actor->can('manage rbac');
    }

    /**
     * Determine whether the actor can assign a given role to target.
     */
    public function assignRole(User $actor, string $roleName): bool
    {
        // Only Super Admin can assign super_admin
        if ($roleName === 'super_admin' && !$actor->isSuperAdmin()) {
            return false;
        }

        return $actor->isSuperAdmin() || $actor->can('manage rbac') || $actor->can('assign roles');
    }
}
