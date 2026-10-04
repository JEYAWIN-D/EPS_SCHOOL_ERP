<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\NoticeAcknowledgement;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CircularAndOrderModuleTest extends TestCase
{
    protected User $admin;
    protected User $teacher;
    protected User $student;
    protected User $principal;
    protected User $correspondent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'principal', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'correspondent', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'correspondant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        // Find or create admin
        $this->admin = User::first() ?? User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@schoolerp.in',
        ]);
        if (!$this->admin->hasRole('admin') && !$this->admin->hasRole('super_admin')) {
            $this->admin->assignRole('admin');
        }

        // Create or find principal user
        $this->principal = User::firstOrCreate(
            ['email' => 'test_principal@schoolerp.in'],
            ['name' => 'Principal Test', 'password' => bcrypt('Password@123')]
        );
        $this->principal->syncRoles(['principal']);

        // Create or find correspondent user
        $this->correspondent = User::firstOrCreate(
            ['email' => 'test_correspondent@schoolerp.in'],
            ['name' => 'Correspondent Test', 'password' => bcrypt('Password@123')]
        );
        $this->correspondent->syncRoles(['correspondent']);

        // Create or find teacher user
        $this->teacher = User::firstOrCreate(
            ['email' => 'test_teacher@schoolerp.in'],
            ['name' => 'Teacher Test', 'password' => bcrypt('Password@123')]
        );
        $this->teacher->syncRoles(['teacher']);

        // Create or find student user
        $this->student = User::firstOrCreate(
            ['email' => 'test_student@schoolerp.in'],
            ['name' => 'Student Test', 'password' => bcrypt('Password@123')]
        );
        $this->student->syncRoles(['student']);
    }

    public function test_circular_index_loads_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('circulars.index'));
        $response->assertStatus(200);
        $response->assertSee('Circulars & Orders');
    }

    public function test_create_circular_view_loads(): void
    {
        $response = $this->actingAs($this->admin)->get(route('circulars.create'));
        $response->assertStatus(200);
        $response->assertSee('Official Reference No');
    }

    public function test_store_circular_with_valid_data(): void
    {
        $payload = [
            'title'                    => 'Test Official Circular Automated Suite',
            'reference_no'             => 'EPS/CIR/TEST/' . uniqid(),
            'notice_type'              => 'circular',
            'order_category'           => 'academic',
            'issuing_authority'        => 'Office of the Academic Director',
            'signed_by_name'           => 'Dr. Academic Head',
            'signatory_designation'    => 'Director of Studies',
            'priority'                 => 'high',
            'target_audience'          => 'all',
            'publish_date'             => Carbon::now()->toDateString(),
            'content'                  => 'This is an automated test circular body verifying database persistence.',
            'requires_acknowledgement' => 1,
            'is_pinned'                => 1,
            'is_published'             => 1,
        ];

        $response = $this->actingAs($this->admin)->post(route('circulars.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('notices', [
            'title'        => 'Test Official Circular Automated Suite',
            'notice_type'  => 'circular',
            'reference_no' => $payload['reference_no'],
            'is_pinned'    => true,
        ]);
    }

    public function test_show_circular_with_letterhead(): void
    {
        $circular = Notice::create([
            'title'                    => 'Letterhead Display Verification Notice',
            'reference_no'             => 'EPS/ORD/TEST/' . uniqid(),
            'notice_type'              => 'order',
            'order_category'           => 'administrative',
            'issuing_authority'        => 'Correspondent Office',
            'signed_by_name'           => 'Managing Trustee',
            'signatory_designation'    => 'Correspondent',
            'priority'                 => 'urgent',
            'target_audience'          => 'all',
            'publish_date'             => Carbon::now()->toDateString(),
            'content'                  => 'Official order body with letterhead validation.',
            'is_published'             => true,
            'requires_acknowledgement' => true,
            'created_by'               => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('circulars.show', $circular->id));
        $response->assertStatus(200);
        $response->assertSee('Letterhead Display Verification Notice');
        $response->assertSee('EPS/ORD/TEST/');
    }

    public function test_toggle_pinned_circular(): void
    {
        $circular = Notice::create([
            'title'                    => 'Pin Test Notice',
            'reference_no'             => 'EPS/PIN/' . uniqid(),
            'notice_type'              => 'circular',
            'priority'                 => 'normal',
            'target_audience'          => 'all',
            'publish_date'             => Carbon::now()->toDateString(),
            'content'                  => 'Pin test content',
            'is_pinned'                => false,
            'is_published'             => true,
            'created_by'               => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('circulars.pin', $circular->id));
        $response->assertRedirect();

        $circular->refresh();
        $this->assertTrue($circular->is_pinned);
    }

    public function test_user_can_acknowledge_circular(): void
    {
        $circular = Notice::create([
            'title'                    => 'Acknowledgement Test Circular',
            'reference_no'             => 'EPS/ACK/' . uniqid(),
            'notice_type'              => 'circular',
            'priority'                 => 'high',
            'target_audience'          => 'all',
            'publish_date'             => Carbon::now()->toDateString(),
            'content'                  => 'Please acknowledge this directive.',
            'is_published'             => true,
            'requires_acknowledgement' => true,
            'created_by'               => $this->admin->id,
        ]);

        $response = $this->actingAs($this->teacher)->post(route('circulars.acknowledge', $circular->id), [
            'notes' => 'Acknowledged and noted by teacher.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('notice_acknowledgements', [
            'notice_id' => $circular->id,
            'user_id'   => $this->teacher->id,
        ]);
    }

    public function test_api_get_circulars_returns_json(): void
    {
        $response = $this->actingAs($this->admin, 'web')->getJson('/api/circulars');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'data',
                'total',
            ],
        ]);
    }

    public function test_api_store_circular_succeeds_for_admin(): void
    {
        $payload = [
            'title'           => 'API Created Circular',
            'reference_no'    => 'EPS/API/' . uniqid(),
            'notice_type'     => 'circular',
            'priority'        => 'normal',
            'target_audience' => 'all',
            'publish_date'    => Carbon::now()->toDateString(),
            'content'         => 'Created via REST API endpoint.',
        ];

        $response = $this->actingAs($this->admin, 'web')->postJson('/api/circulars', $payload);
        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('notices', [
            'title' => 'API Created Circular',
        ]);
    }

    public function test_strict_deletion_security_unauthorized_roles_receive_403(): void
    {
        $circular = Notice::create([
            'title'           => 'Protected Circular Cannot Be Deleted by Unauthorized Role',
            'reference_no'    => 'EPS/SEC/' . uniqid(),
            'notice_type'     => 'order',
            'priority'        => 'urgent',
            'target_audience' => 'all',
            'publish_date'    => Carbon::now()->toDateString(),
            'content'         => 'Strict role test content',
            'is_published'    => true,
            'created_by'      => $this->admin->id,
        ]);

        // 1. Teacher cannot delete
        $teacherResponse = $this->actingAs($this->teacher)->delete(route('circulars.destroy', $circular->id));
        $teacherResponse->assertStatus(403);

        // 2. Student cannot delete via API
        $studentApiResponse = $this->actingAs($this->student, 'web')->deleteJson("/api/circulars/{$circular->id}");
        $studentApiResponse->assertStatus(403);

        // Circular must still exist in DB
        $this->assertDatabaseHas('notices', ['id' => $circular->id]);
    }

    public function test_strict_deletion_security_authorized_roles_can_delete(): void
    {
        // 1. Principal can delete
        $circularForPrincipal = Notice::create([
            'title'           => 'Deletable By Principal',
            'reference_no'    => 'EPS/PRIN/' . uniqid(),
            'notice_type'     => 'circular',
            'priority'        => 'normal',
            'target_audience' => 'all',
            'publish_date'    => Carbon::now()->toDateString(),
            'content'         => 'To be deleted by Principal',
            'is_published'    => true,
            'created_by'      => $this->admin->id,
        ]);

        $principalResponse = $this->actingAs($this->principal)->delete(route('circulars.destroy', $circularForPrincipal->id));
        $principalResponse->assertRedirect();
        $this->assertDatabaseMissing('notices', ['id' => $circularForPrincipal->id]);

        // 2. Correspondent can delete
        $circularForCorrespondent = Notice::create([
            'title'           => 'Deletable By Correspondent',
            'reference_no'    => 'EPS/CORR/' . uniqid(),
            'notice_type'     => 'order',
            'priority'        => 'normal',
            'target_audience' => 'all',
            'publish_date'    => Carbon::now()->toDateString(),
            'content'         => 'To be deleted by Correspondent',
            'is_published'    => true,
            'created_by'      => $this->admin->id,
        ]);

        $corrResponse = $this->actingAs($this->correspondent)->delete(route('circulars.destroy', $circularForCorrespondent->id));
        $corrResponse->assertRedirect();
        $this->assertDatabaseMissing('notices', ['id' => $circularForCorrespondent->id]);
    }
}
