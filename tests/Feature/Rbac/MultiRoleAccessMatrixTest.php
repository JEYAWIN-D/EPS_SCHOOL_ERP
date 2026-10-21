<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use App\Models\Employee;
use App\Modules\Rbac\Services\PermissionResolverService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MultiRoleAccessMatrixTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.database' => 'school_erp',
        ]);

        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
    }

    /**
     * 1. Super Admin: Full administrative access.
     * Can manage users, roles, permissions, matrix, and all authorized ERP modules.
     * @test
     */
    public function super_admin_has_full_administrative_access()
    {
        $superAdmin = User::role('super_admin')->where('is_active', true)->firstOrFail();
        $superAdmin->update(['must_change_password' => false]);

        // 1. Can view RBAC directory
        $response = $this->actingAs($superAdmin)->get(route('rbac.users.index'));
        $response->assertStatus(200);

        // 2. Can view Permission Matrix
        $response = $this->actingAs($superAdmin)->get(route('rbac.matrix.index'));
        $response->assertStatus(200);

        // 3. Can access core administrative modules
        $this->actingAs($superAdmin)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($superAdmin)->get(route('hr.index'))->assertStatus(200);
    }

    /**
     * 2. Admin: Configured administrative permissions.
     * Can access modules granted (admissions, students, hr, library, warehouse)
     * but strictly blocked from escalating privileges to super_admin.
     * @test
     */
    public function admin_has_configured_permissions_and_cannot_escalate()
    {
        $admin = User::role('admin')->where('is_active', true)->firstOrFail();
        $admin->update(['must_change_password' => false]);

        // 1. Can access operational modules
        $this->actingAs($admin)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admissions.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('students.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('warehouse.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('alumni.index'))->assertStatus(200);

        // 2. Cannot assign super_admin role to any account (Privilege Escalation Prevention)
        $uniqueId = rand(10000, 99999);
        $response = $this->actingAs($admin)->post(route('rbac.users.store'), [
            'name'                  => 'Escalation Test',
            'email'                 => "escalate_{$uniqueId}@schoolerp.in",
            'password'              => 'Secret@2026',
            'password_confirmation' => 'Secret@2026',
            'role'                  => 'super_admin',
        ]);
        $response->assertStatus(403);
    }

    /**
     * 3. Principal: Permitted academic and approval functions.
     * Can access academic, admissions, and reports; blocked from RBAC user management.
     * @test
     */
    public function principal_can_access_academics_and_reports_but_blocked_from_rbac()
    {
        $principal = User::role('principal')->where('is_active', true)->first();
        if (!$principal) {
            $principal = User::create([
                'name'                 => 'Test Principal',
                'email'                => 'test.principal@schoolerp.in',
                'password'             => Hash::make('Demo@2026'),
                'is_active'            => true,
                'must_change_password' => false,
            ]);
            $principal->assignRole('principal');
        } else {
            $principal->update(['must_change_password' => false]);
        }

        // Permitted
        $this->actingAs($principal)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($principal)->get(route('admissions.index'))->assertStatus(200);
        $this->actingAs($principal)->get(route('reports.general-register'))->assertStatus(200);

        // Blocked from sensitive RBAC administration
        $this->actingAs($principal)->get(route('rbac.users.index'))->assertStatus(403);
        $this->actingAs($principal)->get(route('rbac.matrix.index'))->assertStatus(403);
    }

    /**
     * 4. Teacher: Permitted teaching functions.
     * Can access class rosters, attendance, examinations; blocked from fees collection and payroll.
     * @test
     */
    public function teacher_can_access_teaching_functions_but_blocked_from_fees_and_payroll()
    {
        $teacher = User::role('teacher')->where('is_active', true)->firstOrFail();
        $teacher->update(['must_change_password' => false]);

        // Permitted
        $this->actingAs($teacher)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($teacher)->get(route('classes.index'))->assertStatus(200);
        $this->actingAs($teacher)->get(route('attendance.index'))->assertStatus(200);

        // Blocked: Fees & Payroll
        $this->actingAs($teacher)->get(route('fees.collect'))->assertStatus(403);
        $this->actingAs($teacher)->get(route('hr.payroll'))->assertStatus(403);
        $this->actingAs($teacher)->get(route('rbac.users.index'))->assertStatus(403);
    }

    /**
     * 5. Class Teacher: Permitted class student & attendance functions.
     * @test
     */
    public function class_teacher_can_manage_class_attendance_and_students()
    {
        $classTeacher = User::role('class_teacher')->where('is_active', true)->first();
        if (!$classTeacher) {
            $classTeacher = User::create([
                'name'                 => 'Test Class Teacher',
                'email'                => 'test.classteacher@schoolerp.in',
                'password'             => Hash::make('Demo@2026'),
                'is_active'            => true,
                'must_change_password' => false,
            ]);
            $classTeacher->assignRole('class_teacher');
        } else {
            $classTeacher->update(['must_change_password' => false]);
        }

        $this->actingAs($classTeacher)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($classTeacher)->get(route('attendance.index'))->assertStatus(200);
        $this->actingAs($classTeacher)->get(route('students.index'))->assertStatus(200);

        // Blocked from financial records & user management
        $this->actingAs($classTeacher)->get(route('fees.collect'))->assertStatus(403);
        $this->actingAs($classTeacher)->get(route('rbac.users.index'))->assertStatus(403);
    }

    /**
     * 6. Librarian: Permitted library functions only.
     * @test
     */
    public function librarian_can_access_library_functions_only()
    {
        $librarian = User::role('librarian')->where('is_active', true)->first();
        if (!$librarian) {
            $librarian = User::create([
                'name'                 => 'Test Librarian',
                'email'                => 'test.librarian@schoolerp.in',
                'password'             => Hash::make('Demo@2026'),
                'is_active'            => true,
                'must_change_password' => false,
            ]);
            $librarian->assignRole('librarian');
        } else {
            $librarian->update(['must_change_password' => false]);
        }

        // Permitted
        $this->actingAs($librarian)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($librarian)->get(route('library.index'))->assertStatus(200);

        // Blocked: Academics, Fees, Payroll, RBAC
        $this->actingAs($librarian)->get(route('classes.index'))->assertStatus(403);
        $this->actingAs($librarian)->get(route('fees.index'))->assertStatus(403);
        $this->actingAs($librarian)->get(route('rbac.users.index'))->assertStatus(403);
    }

    /**
     * 7. Read-Only / Baseline Staff: View-only permissions.
     * Cannot modify records or access financial/admissions routes.
     * @test
     */
    public function read_only_staff_cannot_perform_write_actions()
    {
        $staff = User::role('staff')->where('is_active', true)->first();
        if (!$staff) {
            $staff = User::create([
                'name'                 => 'Test Staff Baseline',
                'email'                => 'test.staff@schoolerp.in',
                'password'             => Hash::make('Demo@2026'),
                'is_active'            => true,
                'must_change_password' => false,
            ]);
            $staff->assignRole('staff');
        } else {
            $staff->update(['must_change_password' => false]);
        }

        // Permitted: Baseline dashboard and events
        $this->actingAs($staff)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($staff)->get(route('events.index'))->assertStatus(200);

        // Blocked from create/edit admissions, fees, payroll, RBAC
        $this->actingAs($staff)->get(route('admissions.create'))->assertStatus(403);
        $this->actingAs($staff)->get(route('fees.index'))->assertStatus(403);
        $this->actingAs($staff)->get(route('hr.index'))->assertStatus(403);
        $this->actingAs($staff)->get(route('rbac.users.index'))->assertStatus(403);
    }

    /**
     * 8. Newly Created Staff A: Restricted Teacher Account (e.g. EPSS9790).
     * Receives only teacher permissions and is blocked from HR/RBAC.
     * @test
     */
    public function newly_created_staff_a_has_strictly_isolated_permissions()
    {
        $staffA = User::where('username', 'EPSS9790')->first();
        if (!$staffA) {
            $staffA = User::create([
                'name'                 => 'Staff A Test',
                'username'             => 'EPSS9790',
                'email'                => 'staff.a.test@schoolerp.in',
                'password'             => Hash::make('15091998'),
                'is_active'            => true,
                'must_change_password' => false,
            ]);
            $staffA->assignRole('teacher');
        } else {
            $staffA->update(['must_change_password' => false]);
        }

        $this->assertTrue($staffA->hasRole('teacher'));
        $this->assertFalse($staffA->hasRole('super_admin'));
        $this->assertFalse($staffA->hasRole('admin'));

        // Permitted
        $this->actingAs($staffA)->get(route('classes.index'))->assertStatus(200);
        $this->actingAs($staffA)->get(route('attendance.index'))->assertStatus(200);

        // Blocked
        $this->actingAs($staffA)->get(route('rbac.users.index'))->assertStatus(403);
        $this->actingAs($staffA)->get(route('fees.collect'))->assertStatus(403);
    }

    /**
     * 9. Newly Created Staff B: Different Restricted Role (Receptionist).
     * Receives admissions and front-desk permissions, blocked from academics & payroll.
     * @test
     */
    public function newly_created_staff_b_receives_different_restricted_permissions()
    {
        $uniqueId = rand(1000, 9999);
        $staffB = User::create([
            'name'                 => 'Staff B Front Desk',
            'username'             => 'EPSS' . $uniqueId,
            'email'                => "staffb_{$uniqueId}@schoolerp.in",
            'password'             => Hash::make('StaffB@2026'),
            'is_active'            => true,
            'must_change_password' => false,
        ]);
        $staffB->assignRole('receptionist');

        // Permitted: Admissions & Students
        $this->actingAs($staffB)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($staffB)->get(route('admissions.index'))->assertStatus(200);
        $this->actingAs($staffB)->get(route('students.index'))->assertStatus(200);

        // Blocked: Examinations, Payroll, RBAC, Fees collect
        $this->actingAs($staffB)->get(route('examinations.index'))->assertStatus(403);
        $this->actingAs($staffB)->get(route('hr.payroll'))->assertStatus(403);
        $this->actingAs($staffB)->get(route('rbac.users.index'))->assertStatus(403);
        $this->actingAs($staffB)->get(route('fees.collect'))->assertStatus(403);
    }

    /**
     * 10. Multi-User Session Isolation & Invalidation:
     * A second user logging in does not inherit the first user's permissions.
     * @test
     */
    public function accounts_are_isolated_and_do_not_leak_permissions_on_switching()
    {
        $superAdmin = User::role('super_admin')->where('is_active', true)->firstOrFail();
        $superAdmin->update(['must_change_password' => false]);
        $staff = User::role('staff')->where('is_active', true)->firstOrFail();
        $staff->update(['must_change_password' => false]);

        // 1. Log in as Super Admin
        $this->actingAs($superAdmin);
        $this->get(route('rbac.users.index'))->assertStatus(200);

        // 2. Log out
        Auth::logout();
        $this->flushSession();

        // 3. Log in as Staff
        $this->actingAs($staff);

        // 4. Staff MUST NOT inherit Super Admin access
        $this->get(route('rbac.users.index'))->assertStatus(403);
        $this->get(route('rbac.matrix.index'))->assertStatus(403);
    }

    /**
     * 11. Real-time Permission Revocation:
     * When a permission or role is revoked, authorization immediately blocks access.
     * @test
     */
    public function revoking_permission_immediately_blocks_access()
    {
        $uniqueId = rand(1000, 9999);
        $customUser = User::create([
            'name'                 => 'Dynamic Perm User',
            'email'                => "dynamic_{$uniqueId}@schoolerp.in",
            'password'             => Hash::make('Demo@2026'),
            'is_active'            => true,
            'must_change_password' => false,
        ]);
        $customUser->givePermissionTo('view admissions');
        PermissionResolverService::clearUserCache($customUser->id);

        // Currently can view admissions
        $this->actingAs($customUser)->get(route('admissions.index'))->assertStatus(200);

        // Revoke permission
        $customUser->revokePermissionTo('view admissions');
        PermissionResolverService::clearUserCache($customUser->id);

        // Immediate block
        $this->actingAs($customUser)->get(route('admissions.index'))->assertStatus(403);
    }

    /**
     * 12. Deactivated accounts are rejected at middleware.
     * @test
     */
    public function deactivated_user_cannot_access_any_protected_routes()
    {
        $uniqueId = rand(1000, 9999);
        $deactivatedUser = User::create([
            'name'      => 'Deactivated Staff',
            'email'     => "deactivated_{$uniqueId}@schoolerp.in",
            'password'  => Hash::make('Demo@2026'),
            'is_active' => false,
        ]);
        $deactivatedUser->assignRole('teacher');

        $this->assertFalse((bool) $deactivatedUser->is_active);

        $loginResponse = $this->post(route('login'), [
            'email'    => $deactivatedUser->email,
            'password' => 'Demo@2026',
        ]);

        $loginResponse->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
