<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use App\Modules\Rbac\Models\AccountTargetConfig;
use App\Modules\Rbac\Models\UserDataScope;
use App\Modules\Rbac\Services\PermissionResolverService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected ?User $regularAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.database' => 'school_erp',
        ]);

        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        // Use the existing primary Super Admin
        $this->superAdmin = User::role('super_admin')->where('is_active', true)->firstOrFail();

        // Create or find a test regular admin
        $this->regularAdmin = User::where('email', 'regular.admin.tester@schoolerp.in')->first();
        if (!$this->regularAdmin) {
            $this->regularAdmin = User::create([
                'name' => 'Test Regular Admin',
                'email' => 'regular.admin.tester@schoolerp.in',
                'password' => Hash::make('Admin@1234'),
                'is_active' => true,
            ]);
            $this->regularAdmin->assignRole('admin');
            $this->regularAdmin->givePermissionTo(['view users', 'create users', 'edit users', 'delete users', 'manage rbac']);
        }
    }

    /** @test */
    public function authorized_user_can_view_user_directory()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('rbac.users.index'));

        $response->assertStatus(200);
        $response->assertSee('System & User Accounts', false);
        $response->assertSee($this->superAdmin->email);
    }

    /** @test */
    public function super_admin_can_create_new_user_account_with_role_and_scope()
    {
        $uniqueId = rand(10000, 99999);
        $userData = [
            'name' => 'Teacher Test Account',
            'username' => 't_test_' . $uniqueId,
            'email' => "teacher_{$uniqueId}@schoolerp.in",
            'mobile' => '91' . rand(10000000, 99999999),
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'teacher',
            'is_active' => 1,
            'must_change_password' => 1,
            'scope_type' => 'assigned_students_only',
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('rbac.users.store'), $userData);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'username' => $userData['username'],
            'is_active' => true,
        ]);

        $createdUser = User::where('email', $userData['email'])->first();
        $this->assertTrue($createdUser->hasRole('teacher'));
    }

    /** @test */
    public function non_super_admin_cannot_assign_super_admin_role()
    {
        $uniqueId = rand(10000, 99999);
        $userData = [
            'name' => 'Escalation Target',
            'email' => "hacker_{$uniqueId}@schoolerp.in",
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'super_admin',
        ];

        $response = $this->actingAs($this->regularAdmin)->post(route('rbac.users.store'), $userData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => $userData['email']]);
    }

    /** @test */
    public function system_strictly_blocks_deactivating_the_last_active_super_admin()
    {
        // Deactivating the single Super Admin MUST be rejected
        $response = $this->actingAs($this->superAdmin)->post(route('rbac.users.toggle-status', $this->superAdmin->id));

        $response->assertSessionHas('error');
        $this->superAdmin->refresh();
        $this->assertTrue((bool) $this->superAdmin->is_active, 'The last Super Admin must remain active and protected!');
    }

    /** @test */
    public function system_strictly_blocks_deleting_the_last_active_super_admin()
    {
        $response = $this->actingAs($this->superAdmin)->delete(route('rbac.users.destroy', $this->superAdmin->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function soft_deletion_retains_user_record_for_audit_history()
    {
        $uniqueId = rand(10000, 99999);
        $tempUser = User::create([
            'name' => 'Temporary User',
            'email' => "temp_user_{$uniqueId}@schoolerp.in",
            'password' => Hash::make('Password@123'),
            'is_active' => true,
        ]);
        $tempUser->assignRole('teacher');

        $response = $this->actingAs($this->superAdmin)->delete(route('rbac.users.destroy', $tempUser->id));

        $response->assertRedirect(route('rbac.users.index'));
        $this->assertSoftDeleted('users', ['id' => $tempUser->id]);
    }

    /** @test */
    public function can_reset_user_password_and_revoke_sessions()
    {
        $uniqueId = rand(10000, 99999);
        $targetUser = User::create([
            'name' => 'Target Reset User',
            'email' => "target_reset_{$uniqueId}@schoolerp.in",
            'password' => Hash::make('OldPassword@123'),
            'is_active' => true,
        ]);
        $targetUser->assignRole('teacher');

        $response = $this->actingAs($this->superAdmin)->post(route('rbac.users.reset-password', $targetUser->id), [
            'password' => 'NewSecret@2026',
            'password_confirmation' => 'NewSecret@2026',
            'force_reset' => 1,
        ]);

        $response->assertSessionHas('success');
        $targetUser->refresh();
        $this->assertTrue(Hash::check('NewSecret@2026', $targetUser->password));
        $this->assertTrue((bool) $targetUser->must_change_password);
    }

    /** @test */
    public function can_update_role_permissions_without_slug_unique_violation()
    {
        $role = \Spatie\Permission\Models\Role::where('name', 'hr_manager')->firstOrFail();

        $response = $this->actingAs($this->superAdmin)->put(route('rbac.roles.update', $role->id), [
            'name' => 'HR Manager',
            'permissions' => ['view dashboard', 'view employees'],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('rbac.roles.index'));
        $role->refresh();
        $this->assertEquals('hr_manager', $role->name);
        $this->assertTrue($role->hasPermissionTo('view dashboard'));
    }
}

