<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Employee;

echo "=======================================================\n";
echo "           FULL RBAC SYSTEM AUDIT REPORT               \n";
echo "=======================================================\n\n";

echo "--- 1. ROLES DEFINED IN DATABASE ---\n";
$roles = Role::with('permissions')->get();
echo "Total Roles: " . $roles->count() . "\n";
foreach ($roles as $r) {
    echo "• Role [{$r->id}] '{$r->name}' (guard: {$r->guard_name}) -> " . $r->permissions->count() . " permissions\n";
}

echo "\n--- 2. PERMISSIONS DEFINED IN DATABASE ---\n";
$permissions = Permission::all();
echo "Total Permissions: " . $permissions->count() . "\n";
$groupedPerms = $permissions->groupBy(function($p) {
    $parts = explode(' ', $p->name, 2);
    return count($parts) > 1 ? $parts[1] : 'other';
});
foreach ($groupedPerms as $module => $perms) {
    echo "  Module '{$module}': " . $perms->pluck('name')->implode(', ') . "\n";
}

echo "\n--- 3. DETAILED ROLE-PERMISSION MAPPINGS ---\n";
foreach ($roles as $r) {
    echo "Role: {$r->name}\n";
    $permList = $r->permissions->pluck('name');
    if ($permList->isEmpty()) {
        echo "   [NO PERMISSIONS ASSIGNED!]\n";
    } else {
        echo "   " . $permList->implode(', ') . "\n";
    }
}

echo "\n--- 4. USERS AND THEIR ROLE MAPPINGS ---\n";
$users = User::with(['roles', 'employee'])->get();
echo "Total Users: " . $users->count() . "\n";
$noRoleUsers = [];
$activeUsersByRole = [];
foreach ($users as $u) {
    $roleNames = $u->roles->pluck('name')->implode(', ');
    if (empty($roleNames)) {
        $noRoleUsers[] = "User #{$u->id} ({$u->username} / {$u->email}) - Employee: " . ($u->employee?->full_name ?? 'None');
    }
}
echo "Users WITHOUT any role: " . count($noRoleUsers) . "\n";
foreach (array_slice($noRoleUsers, 0, 10) as $nu) {
    echo "  • {$nu}\n";
}

echo "\n--- 5. STAFF (EMPLOYEES) WITHOUT USER ACCOUNTS ---\n";
$unlinkedEmployees = Employee::whereNull('user_id')->whereIn('employee_type', ['teaching', 'non_teaching'])->get();
echo "Teaching/Non-Teaching Employees without User Account: " . $unlinkedEmployees->count() . "\n";
foreach ($unlinkedEmployees->take(10) as $ue) {
    echo "  • [{$ue->id}] {$ue->employee_code} - {$ue->full_name} ({$ue->employee_type} - {$ue->designation})\n";
}

echo "\n=======================================================\n";
