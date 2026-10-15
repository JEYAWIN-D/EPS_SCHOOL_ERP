<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "=== PHASE 1: DISCOVERY & READ-ONLY AUDIT ===\n\n";

// 1. Database Connection & System Info
echo "1. SYSTEM & DATABASE INFO\n";
echo "Laravel Version: " . app()->version() . "\n";
echo "Database Connection: " . config('database.default') . "\n";
echo "Database Driver: " . DB::connection()->getDriverName() . "\n";
try {
    $pgVer = DB::select("SELECT version() as v")[0]->v ?? 'Unknown';
    echo "DB Server Version: " . substr($pgVer, 0, 50) . "...\n";
} catch (\Throwable $e) {
    echo "DB Server Version query failed: " . $e->getMessage() . "\n";
}
echo "\n";

// 2. Roles & Permissions Audit
echo "2. ROLES & PERMISSIONS AUDIT\n";
$roles = Role::withCount('users', 'permissions')->get();
echo "Total Roles defined: " . $roles->count() . "\n";
foreach ($roles as $r) {
    echo sprintf(" - Role [%2d] %-20s | Users: %3d | Permissions: %3d\n", $r->id, $r->name, $r->users_count, $r->permissions_count);
}
echo "\n";

$permissions = Permission::all();
echo "Total Permissions defined: " . $permissions->count() . "\n";
$permNames = $permissions->pluck('name')->toArray();
echo "Permission Samples: " . implode(', ', array_slice($permNames, 0, 10)) . "...\n\n";

// 3. User Accounts Audit
echo "3. USER ACCOUNTS AUDIT\n";
$totalUsers = User::count();
$activeUsers = User::where('is_active', true)->count();
$inactiveUsers = User::where('is_active', false)->count();
echo "Total Users: {$totalUsers} (Active: {$activeUsers}, Inactive: {$inactiveUsers})\n";

// Users with multiple roles
$usersMultiRoles = DB::select("SELECT model_id, COUNT(role_id) as rc FROM model_has_roles WHERE model_type = 'App\\\\Models\\\\User' GROUP BY model_id HAVING COUNT(role_id) > 1");
echo "Users with multiple roles: " . count($usersMultiRoles) . "\n";
foreach ($usersMultiRoles as $umr) {
    $u = User::find($umr->model_id);
    $rNames = $u ? $u->getRoleNames()->implode(', ') : 'Unknown';
    echo " - User ID {$umr->model_id} ({$u?->email}): [{$rNames}]\n";
}

// Users without any role
$usersNoRole = User::doesntHave('roles')->get(['id', 'name', 'email']);
echo "Users without ANY role: " . $usersNoRole->count() . "\n";
foreach ($usersNoRole as $unr) {
    echo " - User ID {$unr->id} ({$unr->name} <{$unr->email}>)\n";
}
echo "\n";

// 4. Duplicate Detection (Read-Only)
echo "4. DUPLICATE AUDIT\n";
// Duplicate Role names (case-insensitive or normalized)
$dupRoles = DB::select("SELECT LOWER(name) as lname, COUNT(*) as c, string_agg(name, ', ') as names FROM roles GROUP BY LOWER(name) HAVING COUNT(*) > 1");
echo "Duplicate/Colliding Roles: " . count($dupRoles) . "\n";
foreach ($dupRoles as $dr) {
    echo " - Collision: {$dr->lname} -> variants: [{$dr->names}] (count: {$dr->c})\n";
}

// Duplicate Permissions
$dupPerms = DB::select("SELECT LOWER(name) as lname, COUNT(*) as c, string_agg(name, ', ') as names FROM permissions GROUP BY LOWER(name) HAVING COUNT(*) > 1");
echo "Duplicate/Colliding Permissions: " . count($dupPerms) . "\n";
foreach ($dupPerms as $dp) {
    echo " - Collision: {$dp->lname} -> variants: [{$dp->names}] (count: {$dp->c})\n";
}

// Duplicate model_has_roles
$dupModelRoles = DB::select("SELECT role_id, model_type, model_id, COUNT(*) as c FROM model_has_roles GROUP BY role_id, model_type, model_id HAVING COUNT(*) > 1");
echo "Duplicate user-role assignments (model_has_roles): " . count($dupModelRoles) . "\n";

// Duplicate role_has_permissions
$dupRolePerms = DB::select("SELECT permission_id, role_id, COUNT(*) as c FROM role_has_permissions GROUP BY permission_id, role_id HAVING COUNT(*) > 1");
echo "Duplicate role-permission assignments: " . count($dupRolePerms) . "\n";
echo "\n";

// 5. Database Integrity: Orphaned Records
echo "5. DATABASE INTEGRITY & ORPHAN AUDIT\n";
$orphanModelRoles = DB::select("SELECT mhr.* FROM model_has_roles mhr LEFT JOIN users u ON u.id = mhr.model_id WHERE u.id IS NULL AND mhr.model_type = 'App\\\\Models\\\\User'");
echo "Orphaned user-role assignments (user deleted): " . count($orphanModelRoles) . "\n";

$orphanRolePerms = DB::select("SELECT rhp.* FROM role_has_permissions rhp LEFT JOIN roles r ON r.id = rhp.role_id LEFT JOIN permissions p ON p.id = rhp.permission_id WHERE r.id IS NULL OR p.id IS NULL");
echo "Orphaned role-permission assignments (missing role or perm): " . count($orphanRolePerms) . "\n";
echo "\n";

// 6. Routes & Authorization Coverage Audit
echo "6. ROUTE AUTHORIZATION COVERAGE AUDIT\n";
$allRoutes = Route::getRoutes();
$totalRoutes = count($allRoutes);
$authRoutes = 0;
$permissionRoutes = 0;
$roleRoutes = 0;
$unprotectedRoutes = [];

foreach ($allRoutes as $route) {
    $uri = $route->uri();
    $middleware = $route->middleware();
    $methods = implode('|', $route->methods());

    $hasAuth = in_array('auth', $middleware);
    $hasPerm = false;
    $hasRole = false;

    foreach ($middleware as $m) {
        if (str_starts_with($m, 'permission:')) $hasPerm = true;
        if (str_starts_with($m, 'role:')) $hasRole = true;
    }

    if ($hasAuth) $authRoutes++;
    if ($hasPerm) $permissionRoutes++;
    if ($hasRole) $roleRoutes++;

    // Check if it's admin/sensitive but lacks auth or permission
    if (!str_starts_with($uri, '_') && !str_starts_with($uri, 'sanctum') && !str_starts_with($uri, 'storage')) {
        if (!$hasAuth && !in_array($uri, ['login', 'logout', 'up', 'admission/apply', 'admissions/apply'])) {
            $unprotectedRoutes[] = "{$methods} /{$uri} [" . implode(',', $middleware) . "]";
        }
    }
}

echo "Total Registered Routes: {$totalRoutes}\n";
echo "Routes protected with 'auth' middleware: {$authRoutes}\n";
echo "Routes with 'permission:...' middleware: {$permissionRoutes}\n";
echo "Routes with 'role:...' middleware: {$roleRoutes}\n";
echo "Potentially unprotected routes: " . count($unprotectedRoutes) . "\n";
foreach (array_slice($unprotectedRoutes, 0, 15) as $ur) {
    echo " - {$ur}\n";
}
echo "\n";

// 7. Performance Baseline Measurement
echo "7. PERFORMANCE BASELINE (READ LATENCY)\n";
$t0 = microtime(true);
$sampleUsers = User::with('roles.permissions')->take(20)->get();
$t1 = microtime(true);
echo "User + Roles + Permissions eager-load (20 users): " . number_format(($t1 - $t0) * 1000, 2) . " ms\n";

$t2 = microtime(true);
$allRolesWithPerms = Role::with('permissions')->get();
$t3 = microtime(true);
echo "All Roles + Permissions fetch: " . number_format(($t3 - $t2) * 1000, 2) . " ms\n";

$t4 = microtime(true);
$adminUser = User::role('super_admin')->first();
$hasPermTest = $adminUser ? $adminUser->hasPermissionTo('manage settings') : false;
$t5 = microtime(true);
echo "hasPermissionTo evaluation: " . number_format(($t5 - $t4) * 1000, 2) . " ms (Result: " . ($hasPermTest ? 'YES' : 'NO') . ")\n";

echo "\n=== END PHASE 1 AUDIT SCRIPT ===\n";
