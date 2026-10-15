<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Modules\Rbac\Services\PermissionResolverService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Register Missing Permissions
        $missingPermissions = [
            'view lms',
            'manage lms',
            'view alumni',
            'manage alumni',
            'view inventory',
            'manage inventory',
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        foreach ($missingPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 2. Ensure standard roles exist
        $standardRoles = [
            'super_admin', 'admin', 'principal', 'vice_principal',
            'accountant', 'teacher', 'class_teacher', 'librarian',
            'transport_manager', 'warden', 'hr_manager', 'receptionist',
            'security_guard', 'warehouse_manager', 'staff', 'student', 'parent'
        ];

        foreach ($standardRoles as $rName) {
            Role::firstOrCreate(['name' => $rName, 'guard_name' => 'web']);
        }

        // 3. Super Admin -> All Permissions
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $allPerms = Permission::all();
            $superAdminRole->syncPermissions($allPerms);
        }

        // 4. Admin Role -> Broad Operational Permissions
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'view lms', 'manage lms',
                'view alumni', 'manage alumni',
                'view inventory', 'manage inventory',
                'view users', 'create users', 'edit users', 'delete users',
            ]);
        }

        // 5. Vice Principal -> Academic, Staff & Operational Permissions
        $vpRole = Role::where('name', 'vice_principal')->first();
        if ($vpRole) {
            $vpPermissions = [
                'view dashboard', 'view admissions', 'view students', 'view academics',
                'view attendance', 'mark attendance', 'view examinations', 'enter marks',
                'publish results', 'view fees', 'view employees', 'view payroll',
                'view reports', 'export reports', 'manage circulars', 'send sms',
                'send whatsapp', 'send email', 'view activities', 'view events',
                'approve events', 'view gate', 'manage gate', 'view lms', 'view alumni',
            ];
            $vpRole->syncPermissions(Permission::whereIn('name', $vpPermissions)->get());
        }

        // 6. HR Manager -> Full HR & Payroll Management
        $hrRole = Role::where('name', 'hr_manager')->first();
        if ($hrRole) {
            $hrPermissions = [
                'view dashboard', 'view employees', 'create employees', 'edit employees',
                'delete employees', 'process payroll', 'view payroll', 'export payroll',
                'view reports', 'export reports', 'view activities', 'view events',
            ];
            $hrRole->syncPermissions(Permission::whereIn('name', $hrPermissions)->get());
        }

        // 7. Security Guard -> Gate & Visitor Pass Management
        $sgRole = Role::where('name', 'security_guard')->first();
        if ($sgRole) {
            $sgPermissions = [
                'view dashboard', 'view gate', 'manage gate', 'view activities',
            ];
            $sgRole->syncPermissions(Permission::whereIn('name', $sgPermissions)->get());
        }

        // 8. Warehouse Manager -> Inventory & Store Management
        $wmRole = Role::where('name', 'warehouse_manager')->first();
        if ($wmRole) {
            $wmPermissions = [
                'view dashboard', 'view inventory', 'manage inventory', 'view reports',
            ];
            $wmRole->syncPermissions(Permission::whereIn('name', $wmPermissions)->get());
        }

        // 9. Staff Baseline -> Dashboard & Activity Views
        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole) {
            $staffPermissions = [
                'view dashboard', 'view activities', 'view events',
            ];
            $staffRole->syncPermissions(Permission::whereIn('name', $staffPermissions)->get());
        }

        // Invalidate cached permissions across the entire system
        PermissionResolverService::clearAllCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $addedPermissions = [
            'view lms', 'manage lms',
            'view alumni', 'manage alumni',
            'view inventory', 'manage inventory',
        ];

        Permission::whereIn('name', $addedPermissions)->delete();
        PermissionResolverService::clearAllCache();
    }
};
