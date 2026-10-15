<?php

namespace App\Modules\Rbac\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rbac\Services\PermissionResolverService;
use App\Modules\Rbac\Services\RbacAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionMatrixController extends Controller
{
    /**
     * Display the Role and Permission Matrix.
     */
    public function index(Request $request)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac') && !$actor->can('view permissions')) {
            abort(403, 'Unauthorized to view Permission Matrix.');
        }

        $roles = Role::with('permissions')->orderBy('name')->get();
        $selectedRoleId = $request->input('role_id', $roles->first()?->id);
        $selectedRole = Role::with('permissions')->find($selectedRoleId) ?? $roles->first();

        $moduleCatalog = PermissionResolverService::getModuleCatalog();
        $selectedRolePermissions = $selectedRole ? $selectedRole->permissions->pluck('name')->toArray() : [];

        // All possible action columns
        $allActions = [
            'view' => 'View',
            'create' => 'Create',
            'edit' => 'Edit / Update',
            'delete' => 'Delete',
            'submit' => 'Submit',
            'approve' => 'Approve',
            'reject' => 'Reject',
            'verify' => 'Verify',
            'assign' => 'Assign',
            'print' => 'Print',
            'download' => 'Download',
            'upload' => 'Upload',
            'import' => 'Import',
            'export' => 'Export',
            'view_reports' => 'Reports',
            'configure' => 'Configure',
            'manage_permissions' => 'Manage Perms',
        ];

        return view('rbac.matrix.index', compact(
            'roles',
            'selectedRole',
            'moduleCatalog',
            'selectedRolePermissions',
            'allActions'
        ));
    }

    /**
     * Update permissions for a specific role from the Matrix.
     */
    public function update(Request $request, Role $role)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac')) {
            abort(403, 'Unauthorized to modify permissions.');
        }

        // Prevent modifying Super Admin permissions directly if needed, or allow
        $requestedPerms = $request->input('permissions', []);

        // Check privilege escalation
        if (!$actor->isSuperAdmin()) {
            $actorPerms = app(PermissionResolverService::class)->getEffectivePermissionsList($actor);
            foreach ($requestedPerms as $perm) {
                if (!in_array($perm, $actorPerms, true)) {
                    return back()->with('error', "Privilege Escalation Detected: You cannot grant '{$perm}' because you do not have it.");
                }
            }
        }

        DB::beginTransaction();
        try {
            $oldPerms = $role->permissions->pluck('name')->toArray();

            $permObjects = [];
            foreach ($requestedPerms as $permName) {
                $p = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                $permObjects[] = $p;
            }

            $role->syncPermissions($permObjects);

            PermissionResolverService::clearAllCache();

            RbacAuditService::log(
                action: 'PERMISSION_MATRIX_UPDATED',
                modelType: 'Role',
                modelId: $role->id,
                oldValues: ['count' => count($oldPerms)],
                newValues: ['count' => count($permObjects), 'permissions' => $requestedPerms],
                actor: $actor
            );

            DB::commit();

            return redirect()->route('rbac.matrix.index', ['role_id' => $role->id])
                ->with('success', "Permissions for role '{$role->name}' successfully saved (" . count($permObjects) . " granted).");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save matrix: ' . $e->getMessage());
        }
    }

    /**
     * Copy permissions from one role to another.
     */
    public function copy(Request $request, Role $targetRole)
    {
        $actor = Auth::user();
        if (!$actor->isSuperAdmin() && !$actor->can('manage rbac')) {
            abort(403, 'Unauthorized.');
        }

        $sourceRoleId = $request->input('source_role_id');
        $sourceRole = Role::with('permissions')->findOrFail($sourceRoleId);

        DB::beginTransaction();
        try {
            $targetRole->syncPermissions($sourceRole->permissions);
            PermissionResolverService::clearAllCache();

            RbacAuditService::log(
                action: 'PERMISSIONS_COPIED',
                modelType: 'Role',
                modelId: $targetRole->id,
                oldValues: ['source_role' => $sourceRole->name],
                actor: $actor
            );

            DB::commit();

            return redirect()->route('rbac.matrix.index', ['role_id' => $targetRole->id])
                ->with('success', "Permissions copied from '{$sourceRole->name}' to '{$targetRole->name}'.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to copy permissions: ' . $e->getMessage());
        }
    }
}
