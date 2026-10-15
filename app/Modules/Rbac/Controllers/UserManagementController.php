<?php

namespace App\Modules\Rbac\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Modules\Rbac\Models\AccountTargetConfig;
use App\Modules\Rbac\Models\FieldPermission;
use App\Modules\Rbac\Models\UserDataScope;
use App\Modules\Rbac\Policies\UserManagementPolicy;
use App\Modules\Rbac\Services\PermissionResolverService;
use App\Modules\Rbac\Services\RbacAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    protected UserManagementPolicy $policy;

    public function __construct(UserManagementPolicy $policy)
    {
        $this->policy = $policy;
    }

    /**
     * Display a listing of user accounts.
     */
    public function index(Request $request)
    {
        $actor = Auth::user();
        if (!$this->policy->viewAny($actor)) {
            abort(403, 'Unauthorized access to User Management.');
        }

        $query = User::with(['roles', 'employee.department', 'employee.designation', 'dataScopes'])->latest();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhere('mobile', 'ilike', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('employee_code', 'ilike', "%{$search}%")
                         ->orWhere('first_name', 'ilike', "%{$search}%")
                         ->orWhere('last_name', 'ilike', "%{$search}%");
                  });
            });
        }

        // Staff Category Filter (Teaching, Non-Teaching, System)
        if ($category = $request->input('category')) {
            if ($category === 'teaching' || $category === 'non_teaching') {
                $query->whereHas('employee', fn($eq) => $eq->where('employee_type', $category));
            } elseif ($category === 'system') {
                $query->whereNull('employee_id')->whereDoesntHave('employee');
            }
        }

        // Department Filter
        if ($deptId = $request->input('department_id')) {
            $query->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
        }

        // Role Filter
        if ($roleFilter = $request->input('role')) {
            $query->role($roleFilter);
        }

        // Permission Status Filter
        if ($permStatus = $request->input('permission_status')) {
            if ($permStatus === 'no_role') {
                $query->doesntHave('roles');
            } elseif ($permStatus === 'assigned') {
                $query->has('roles');
            }
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        $users = $query->paginate(15)->withQueryString();

        $allRoles = Role::orderBy('name')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        $accountTargets = AccountTargetConfig::all()->keyBy('role_name');

        // Role counts for dashboard cards
        $roleCounts = [];
        foreach ($allRoles as $role) {
            $roleCounts[$role->name] = User::role($role->name)->where('is_active', true)->count();
        }

        return view('rbac.users.index', compact('users', 'allRoles', 'departments', 'accountTargets', 'roleCounts'));
    }

    /**
     * Show form for creating a new user account.
     */
    public function create()
    {
        $actor = Auth::user();
        if (!$this->policy->create($actor)) {
            abort(403, 'Unauthorized: Cannot create users.');
        }

        $roles = Role::orderBy('name')->get();
        // If actor is not Super Admin, do not allow assigning super_admin
        if (!$actor->isSuperAdmin()) {
            $roles = $roles->reject(fn($r) => $r->name === 'super_admin');
        }

        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code', 'department_id']);
        $classes = DB::table('classes')->orderBy('name')->get(['id', 'name']);
        $sections = DB::table('sections')->orderBy('name')->get(['id', 'name', 'class_id']);

        return view('rbac.users.create', compact('roles', 'employees', 'classes', 'sections'));
    }

    /**
     * Store a newly created user account.
     */
    public function store(Request $request)
    {
        $actor = Auth::user();
        if (!$this->policy->create($actor)) {
            abort(403, 'Unauthorized: Cannot create users.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'is_active' => ['boolean'],
            'must_change_password' => ['boolean'],
            'scope_type' => ['nullable', 'string'],
            'scope_value' => ['nullable'],
        ]);

        if (!$this->policy->assignRole($actor, $validated['role'])) {
            abort(403, 'Unauthorized: You cannot assign the Super Admin role.');
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'] ?: null,
                'email' => $validated['email'],
                'mobile' => $validated['mobile'] ?? null,
                'password' => Hash::make($validated['password']),
                'employee_id' => $validated['employee_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'must_change_password' => $request->boolean('must_change_password', true),
            ]);

            $user->assignRole($validated['role']);

            // Data scope
            if (!empty($validated['scope_type']) && $validated['scope_type'] !== 'none') {
                $scopeVal = is_array($request->input('scope_value')) 
                    ? json_encode($request->input('scope_value')) 
                    : $request->input('scope_value');

                UserDataScope::create([
                    'user_id' => $user->id,
                    'scope_type' => $validated['scope_type'],
                    'scope_value' => $scopeVal,
                    'created_by' => $actor->id,
                ]);
            }

            RbacAuditService::log(
                action: 'USER_CREATED',
                modelType: 'User',
                modelId: $user->id,
                newValues: ['name' => $user->name, 'email' => $user->email, 'role' => $validated['role']],
                actor: $actor
            );

            DB::commit();

            return redirect()->route('rbac.users.show', $user->id)
                ->with('success', "User account for {$user->name} created successfully with role '{$validated['role']}'.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified user profile, effective permissions, scopes, and history.
     */
    public function show(User $user)
    {
        $actor = Auth::user();
        if (!$this->policy->viewAny($actor)) {
            abort(403, 'Unauthorized to view user details.');
        }

        $user->load(['roles.permissions', 'employee', 'dataScopes', 'fieldPermissions']);
        $resolver = app(PermissionResolverService::class);
        $effectivePermissions = $resolver->getEffectivePermissionsList($user);
        $moduleCatalog = PermissionResolverService::getModuleCatalog();

        $auditLogs = DB::table('audit_logs')
            ->where(function ($q) use ($user) {
                $q->where('model_type', 'User')->where('model_id', $user->id)
                  ->orWhere('user_id', $user->id);
            })
            ->latest('created_at')
            ->take(25)
            ->get();

        return view('rbac.users.show', compact('user', 'effectivePermissions', 'moduleCatalog', 'auditLogs'));
    }

    /**
     * Show form for editing the specified user account.
     */
    public function edit(User $user)
    {
        $actor = Auth::user();
        if (!$this->policy->update($actor, $user)) {
            abort(403, 'Unauthorized to edit this user account.');
        }

        $roles = Role::orderBy('name')->get();
        if (!$actor->isSuperAdmin()) {
            $roles = $roles->reject(fn($r) => $r->name === 'super_admin');
        }

        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code', 'department_id']);
        $classes = DB::table('classes')->orderBy('name')->get(['id', 'name']);
        $sections = DB::table('sections')->orderBy('name')->get(['id', 'name', 'class_id']);

        $user->load(['roles', 'dataScopes', 'fieldPermissions']);

        return view('rbac.users.edit', compact('user', 'roles', 'employees', 'classes', 'sections'));
    }

    /**
     * Update the specified user account.
     */
    public function update(Request $request, User $user)
    {
        $actor = Auth::user();
        if (!$this->policy->update($actor, $user)) {
            abort(403, 'Unauthorized to edit this user account.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'is_active' => ['boolean'],
            'scope_type' => ['nullable', 'string'],
            'scope_value' => ['nullable'],
        ]);

        if (!$this->policy->assignRole($actor, $validated['role'])) {
            abort(403, 'Unauthorized: Cannot assign Super Admin role.');
        }

        // If trying to change role from super_admin or deactivate, verify last super admin rule
        if ($user->isSuperAdmin() && $validated['role'] !== 'super_admin') {
            $activeSuperAdmins = User::role('super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->whereNull('deleted_at')
                ->count();
            if ($activeSuperAdmins < 1) {
                return back()->withErrors(['role' => 'Cannot remove Super Admin role from the last active Super Admin account.']);
            }
        }

        DB::beginTransaction();
        try {
            $oldValues = $user->only(['name', 'email', 'username', 'mobile', 'is_active']);
            $oldRoles = $user->getRoleNames()->toArray();

            $user->update([
                'name' => $validated['name'],
                'username' => $validated['username'] ?: null,
                'email' => $validated['email'],
                'mobile' => $validated['mobile'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $user->syncRoles([$validated['role']]);

            // Update Data Scope
            if ($request->has('scope_type')) {
                $user->dataScopes()->delete();
                if (!empty($validated['scope_type']) && $validated['scope_type'] !== 'none') {
                    $scopeVal = is_array($request->input('scope_value')) 
                        ? json_encode($request->input('scope_value')) 
                        : $request->input('scope_value');

                    UserDataScope::create([
                        'user_id' => $user->id,
                        'scope_type' => $validated['scope_type'],
                        'scope_value' => $scopeVal,
                        'created_by' => $actor->id,
                    ]);
                }
            }

            PermissionResolverService::clearUserCache($user->id);

            RbacAuditService::log(
                action: 'USER_UPDATED',
                modelType: 'User',
                modelId: $user->id,
                oldValues: array_merge($oldValues, ['roles' => $oldRoles]),
                newValues: array_merge($user->only(['name', 'email', 'username', 'mobile', 'is_active']), ['roles' => [$validated['role']]]),
                actor: $actor
            );

            DB::commit();

            return redirect()->route('rbac.users.show', $user->id)
                ->with('success', "User account for {$user->name} updated successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle active/inactive status with Last Super Admin protection.
     */
    public function toggleStatus(User $user)
    {
        $actor = Auth::user();
        $targetStatus = !$user->is_active;

        if (!$targetStatus && !$this->policy->deactivate($actor, $user)) {
            return back()->with('error', 'Action rejected: Cannot deactivate the last remaining active Super Admin account.');
        }

        $user->is_active = $targetStatus;
        $user->save();

        PermissionResolverService::clearUserCache($user->id);

        RbacAuditService::log(
            action: $targetStatus ? 'USER_ACTIVATED' : 'USER_DEACTIVATED',
            modelType: 'User',
            modelId: $user->id,
            actor: $actor
        );

        $statusLabel = $targetStatus ? 'activated' : 'deactivated';
        return back()->with('success', "User account {$user->name} has been {$statusLabel}.");
    }

    /**
     * Secure password reset.
     */
    public function resetPassword(Request $request, User $user)
    {
        $actor = Auth::user();
        if (!$this->policy->update($actor, $user)) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'force_reset' => ['nullable', 'boolean'],
        ]);

        $user->password = Hash::make($request->input('password'));
        $user->must_change_password = $request->boolean('force_reset', true);
        $user->save();

        // Invalidate sessions
        DB::table('sessions')->where('user_id', $user->id)->delete();

        RbacAuditService::log(
            action: 'PASSWORD_RESET',
            modelType: 'User',
            modelId: $user->id,
            actor: $actor
        );

        return back()->with('success', "Password for {$user->name} reset successfully. Active sessions revoked.");
    }

    /**
     * Revoke all active sessions for a user.
     */
    public function revokeSessions(User $user)
    {
        $actor = Auth::user();
        if (!$this->policy->update($actor, $user)) {
            abort(403, 'Unauthorized.');
        }

        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->remember_token = null;
        $user->save();

        RbacAuditService::log(
            action: 'SESSIONS_REVOKED',
            modelType: 'User',
            modelId: $user->id,
            actor: $actor
        );

        return back()->with('success', "All active web sessions for {$user->name} have been revoked.");
    }

    /**
     * Soft delete user with Last Super Admin protection.
     */
    public function destroy(User $user)
    {
        $actor = Auth::user();
        if (!$this->policy->delete($actor, $user)) {
            return back()->with('error', 'Cannot delete user: Protected as the last active Super Admin or insufficient permissions.');
        }

        $userName = $user->name;
        $user->delete();

        PermissionResolverService::clearUserCache($user->id);

        RbacAuditService::log(
            action: 'USER_DELETED',
            modelType: 'User',
            modelId: $user->id,
            actor: $actor
        );

        return redirect()->route('rbac.users.index')
            ->with('success', "User account for {$userName} safely soft-deleted.");
    }

    /**
     * Export user accounts to CSV.
     */
    public function export(Request $request)
    {
        $actor = Auth::user();
        if (!$this->policy->viewAny($actor)) {
            abort(403, 'Unauthorized to export users.');
        }

        $users = User::with('roles')->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="school_erp_users_' . date('Y_m_d_His') . '.csv"',
        ];

        $callback = function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Username', 'Email', 'Mobile', 'Role', 'Status', 'Last Login', 'Created At']);

            foreach ($users as $u) {
                fputcsv($handle, [
                    $u->id,
                    $u->name,
                    $u->username ?? '-',
                    $u->email,
                    $u->mobile ?? '-',
                    $u->roles->pluck('name')->join(', '),
                    $u->is_active ? 'Active' : 'Inactive',
                    $u->last_login_at ? $u->last_login_at->format('Y-m-d H:i') : 'Never',
                    $u->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
