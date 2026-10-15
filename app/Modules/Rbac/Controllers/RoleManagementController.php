<?php

namespace App\Modules\Rbac\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Rbac\Models\AccountTargetConfig;
use App\Modules\Rbac\Services\PermissionResolverService;
use App\Modules\Rbac\Services\RbacAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    /**
     * Built-in system roles that cannot be deleted or renamed.
     */
    protected array $systemRoles = [
        'super_admin',
        'admin',
        'principal',
        'vice_principal',
        'teacher',
        'class_teacher',
        'librarian',
        'accountant',
        'hr_manager',
        'receptionist',
        'warehouse_manager',
    ];

    /**
     * List all roles with targets and assigned user counts.
     */
    public function index()
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac') && !$actor->can('view roles')) {
            abort(403, 'Unauthorized.');
        }

        $roles = Role::withCount('users', 'permissions')->orderBy('name')->get();
        $accountTargets = AccountTargetConfig::all()->keyBy('role_name');
        $systemRoles = $this->systemRoles;

        return view('rbac.roles.index', compact('roles', 'accountTargets', 'systemRoles'));
    }

    /**
     * Show custom role creation form.
     */
    public function create()
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac') && !$actor->can('create roles')) {
            abort(403, 'Unauthorized: Cannot create custom roles.');
        }

        $moduleCatalog = PermissionResolverService::getModuleCatalog();
        $existingRoles = Role::orderBy('name')->get();

        return view('rbac.roles.create', compact('moduleCatalog', 'existingRoles'));
    }

    /**
     * Store newly created custom role.
     */
    public function store(Request $request)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac') && !$actor->can('create roles')) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $roleSlug = Str::slug($validated['name'], '_');
        if (empty($roleSlug)) {
            return back()->withInput()->withErrors(['name' => 'The role identifier must contain valid alphanumeric characters.']);
        }

        if (Role::where('name', $roleSlug)->where('guard_name', 'web')->exists()) {
            return back()->withInput()->withErrors(['name' => "A role with the system identifier '{$roleSlug}' already exists."]);
        }

        // Check privilege escalation: creator cannot grant permissions they do not possess
        $requestedPerms = $request->input('permissions', []);
        if (!$actor->isSuperAdmin()) {
            $actorPerms = app(PermissionResolverService::class)->getEffectivePermissionsList($actor);
            foreach ($requestedPerms as $perm) {
                if (!in_array($perm, $actorPerms, true)) {
                    return back()->withInput()->withErrors(['permissions' => "Privilege Escalation Detected: You cannot delegate the '{$perm}' permission because you do not have it."]);
                }
            }
        }

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $roleSlug,
                'guard_name' => 'web',
            ]);

            // Ensure permissions exist in database and sync
            $permObjects = [];
            foreach ($requestedPerms as $permName) {
                $p = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                $permObjects[] = $p;
            }
            $role->syncPermissions($permObjects);

            // Create target config entry if provided
            if ($request->filled('target_count')) {
                AccountTargetConfig::create([
                    'role_name' => $role->name,
                    'target_count' => (int) $request->input('target_count', 1),
                    'notes' => $validated['description'] ?? "Custom role: {$validated['name']}",
                    'created_by' => $actor->id,
                ]);
            }

            PermissionResolverService::clearAllCache();

            RbacAuditService::log(
                action: 'ROLE_CREATED',
                modelType: 'Role',
                modelId: $role->id,
                newValues: ['name' => $role->name, 'permissions_count' => count($permObjects)],
                actor: $actor
            );

            DB::commit();

            return redirect()->route('rbac.roles.index')
                ->with('success', "Custom role '{$role->name}' created successfully with " . count($permObjects) . " permissions.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create role: ' . $e->getMessage()]);
        }
    }

    /**
     * Show edit form for role.
     */
    public function edit(Role $role)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac') && !$actor->can('edit roles')) {
            abort(403, 'Unauthorized.');
        }

        $moduleCatalog = PermissionResolverService::getModuleCatalog();
        $isSystemRole = in_array($role->name, $this->systemRoles, true);
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $targetConfig = AccountTargetConfig::where('role_name', $role->name)->first();

        return view('rbac.roles.edit', compact('role', 'moduleCatalog', 'isSystemRole', 'rolePermissions', 'targetConfig'));
    }

    /**
     * Update custom role.
     */
    public function update(Request $request, Role $role)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac') && !$actor->can('edit roles')) {
            abort(403, 'Unauthorized.');
        }

        $isSystemRole = in_array($role->name, $this->systemRoles, true);

        $rules = [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        if (!$isSystemRole) {
            $rules['name'] = ['required', 'string', 'max:100'];
        }

        $validated = $request->validate($rules);

        $roleSlug = null;
        if (!$isSystemRole && isset($validated['name'])) {
            $roleSlug = Str::slug($validated['name'], '_');
            if (empty($roleSlug)) {
                return back()->withInput()->withErrors(['name' => 'The role identifier must contain valid alphanumeric characters.']);
            }

            $conflicting = Role::where('name', $roleSlug)
                ->where('guard_name', 'web')
                ->where('id', '!=', $role->id)
                ->first();

            if ($conflicting) {
                return back()->withInput()->withErrors([
                    'name' => "A role with the system identifier '{$roleSlug}' already exists (Role #{$conflicting->id}: " . ucwords(str_replace('_', ' ', $conflicting->name)) . ")."
                ]);
            }
        }

        $requestedPerms = $request->input('permissions', []);
        if (!$actor->isSuperAdmin()) {
            $actorPerms = app(PermissionResolverService::class)->getEffectivePermissionsList($actor);
            foreach ($requestedPerms as $perm) {
                if (!in_array($perm, $actorPerms, true)) {
                    return back()->withInput()->withErrors(['permissions' => "Privilege Escalation Detected: You cannot delegate the '{$perm}' permission."]);
                }
            }
        }

        DB::beginTransaction();
        try {
            $oldPerms = $role->permissions->pluck('name')->toArray();

            if (!$isSystemRole && !empty($roleSlug) && $role->name !== $roleSlug) {
                $role->name = $roleSlug;
                $role->save();
            }

            // Sync permissions
            $permObjects = [];
            foreach ($requestedPerms as $permName) {
                $p = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                $permObjects[] = $p;
            }
            $role->syncPermissions($permObjects);

            PermissionResolverService::clearAllCache();

            RbacAuditService::log(
                action: 'ROLE_UPDATED',
                modelType: 'Role',
                modelId: $role->id,
                oldValues: ['permissions' => $oldPerms],
                newValues: ['permissions' => $requestedPerms],
                actor: $actor
            );

            DB::commit();

            return redirect()->route('rbac.roles.index')
                ->with('success', "Role '{$role->name}' updated successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update role: ' . $e->getMessage()]);
        }
    }

    /**
     * Duplicate role into new template.
     */
    public function duplicate(Role $role)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac')) {
            abort(403, 'Unauthorized.');
        }

        $newName = $role->name . '_copy_' . rand(100, 999);
        $newRole = Role::create(['name' => $newName, 'guard_name' => 'web']);
        $newRole->syncPermissions($role->permissions);

        PermissionResolverService::clearAllCache();

        RbacAuditService::log(
            action: 'ROLE_DUPLICATED',
            modelType: 'Role',
            modelId: $newRole->id,
            oldValues: ['cloned_from' => $role->name],
            actor: $actor
        );

        return redirect()->route('rbac.roles.edit', $newRole->id)
            ->with('success', "Role '{$role->name}' duplicated into '{$newRole->name}'. You may now customize its permissions.");
    }

    /**
     * Safe deletion of custom role.
     */
    public function destroy(Request $request, Role $role)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac')) {
            abort(403, 'Unauthorized.');
        }

        if (in_array($role->name, $this->systemRoles, true)) {
            return back()->with('error', "Cannot delete system role '{$role->name}'. Built-in roles are core to system operation.");
        }

        // Check if users are assigned
        $assignedCount = $role->users()->count();
        if ($assignedCount > 0) {
            $fallbackRole = $request->input('reassign_to_role');
            if (empty($fallbackRole)) {
                return back()->with('error', "Role '{$role->name}' is assigned to {$assignedCount} active users. Select a replacement role before deactivating/deleting.");
            }

            $targetRole = Role::where('name', $fallbackRole)->firstOrFail();
            foreach ($role->users as $u) {
                $u->removeRole($role);
                $u->assignRole($targetRole);
            }
        }

        $roleName = $role->name;
        $role->delete();

        AccountTargetConfig::where('role_name', $roleName)->delete();
        PermissionResolverService::clearAllCache();

        RbacAuditService::log(
            action: 'ROLE_DELETED',
            modelType: 'Role',
            modelId: null,
            oldValues: ['name' => $roleName, 'users_reassigned' => $assignedCount],
            actor: $actor
        );

        return redirect()->route('rbac.roles.index')
            ->with('success', "Custom role '{$roleName}' successfully deleted. {$assignedCount} users reassigned.");
    }
}
