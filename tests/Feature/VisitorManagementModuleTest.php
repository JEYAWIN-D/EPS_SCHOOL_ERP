<?php

namespace Tests\Feature;

use App\Models\Visitor;
use App\Models\GateBlacklist;
use App\Models\Student;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VisitorManagementModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $hostTeacher;
    protected Employee $employee;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        // Find or create admin
        $this->admin = User::whereHas('roles', fn($q) => $q->where('name', 'super_admin')->orWhere('name', 'admin'))->first()
            ?? User::first()
            ?? User::factory()->create(['name' => 'Admin Test', 'email' => 'admin_test@schoolerp.in']);

        // Find or create an employee
        $this->employee = Employee::where('is_active', true)->first();
        if (!$this->employee) {
            $this->employee = Employee::create([
                'employee_code'  => 'EMP-TEST-99',
                'first_name'     => 'Kavitha',
                'last_name'      => 'Sundaram',
                'designation'    => 'Senior TGT Science',
                'department'     => 'Academic',
                'joining_date'   => today()->subYears(2),
                'is_active'      => true,
                'official_email' => 'kavitha.test@schoolerp.in',
            ]);
        }

        // Host User
        $this->hostTeacher = User::where('employee_id', $this->employee->id)->first();
        if (!$this->hostTeacher) {
            $this->hostTeacher = User::create([
                'name'        => $this->employee->full_name,
                'email'       => 'host_teacher_' . uniqid() . '@schoolerp.in',
                'password'    => bcrypt('Password@123'),
                'employee_id' => $this->employee->id,
            ]);
        }

        // Find or create a student
        $this->student = Student::where('status', 'active')->first();
        if (!$this->student) {
            $this->student = Student::create([
                'admission_no'   => 'ADM-TEST-999',
                'first_name'     => 'Aarav',
                'last_name'      => 'Sharma',
                'gender'         => 'male',
                'status'         => 'active',
                'father_name'    => 'Rajesh Sharma',
                'father_mobile'  => '9876543210',
                'admission_date' => today(),
            ]);
        }
    }

    public function test_visitor_dashboard_loads_successfully_with_tabs_and_metrics()
    {
        $response = $this->actingAs($this->admin)->get(route('gate.index'));
        $response->assertStatus(200);
        $response->assertSee('Gate &amp; Visitor Management', false);
        $response->assertSee('Inside Now');
        $response->assertSee('Pending Approvals');
    }

    public function test_visitor_create_form_renders_dynamic_category_options()
    {
        $response = $this->actingAs($this->admin)->get(route('gate.create'));
        $response->assertStatus(200);
        $response->assertSee('Visitor Entry Registration');
        $response->assertSee('Parent / Guardian');
        $response->assertSee('Admission Enquiry');
        $response->assertSee('Vendor / Maint.');
        $response->assertSee('Staff Interview');
    }

    public function test_it_registers_parent_visitor_and_links_to_student()
    {
        $payload = [
            'visitor_name'            => 'Rajesh Sharma',
            'category'                => 'parent',
            'visitor_phone'           => '9876543210',
            'visitor_id_type'         => 'Aadhaar Card',
            'visitor_id_number'       => 'XXXX-XXXX-9999',
            'purpose'                 => 'Discussing midterm progress report with class teacher',
            'host_employee_id'        => $this->employee->id,
            'student_id'              => $this->student->id,
            'relationship_to_student' => 'Father',
            'visitor_count'           => 2,
            'vehicle_type'            => 'four_wheeler',
            'vehicle_number'          => 'TN-33-AA-9999',
            'action_type'             => 'direct_checkin',
        ];

        $response = $this->actingAs($this->admin)->post(route('gate.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('visitors', [
            'visitor_name'            => 'Rajesh Sharma',
            'category'                => 'parent',
            'student_id'              => $this->student->id,
            'relationship_to_student' => 'Father',
            'status'                  => Visitor::STATUS_CHECKED_IN,
        ]);

        $visitor = Visitor::where('visitor_name', 'Rajesh Sharma')->latest()->first();
        $this->assertNotNull($visitor->pass_number);
        $this->assertNotNull($visitor->pass_token);
        $this->assertNotNull($visitor->in_time);
        $this->assertEquals('emerald', $visitor->badge_color);
    }

    public function test_it_registers_admission_enquiry_and_routes_to_crm_lead()
    {
        $payload = [
            'visitor_name'          => 'Dr. Ananya Iyer',
            'category'              => 'admission_enquiry',
            'visitor_phone'         => '9876500001',
            'visitor_email'         => 'ananya.iyer@example.com',
            'purpose'               => 'Campus visit and Grade 6 admission enquiry',
            'child_name'            => 'Vikram Iyer',
            'grade_applying_for'    => 'Grade 6',
            'enquiry_source'        => 'Social Media',
            'create_admission_lead' => 1,
            'action_type'           => 'direct_checkin',
        ];

        $response = $this->actingAs($this->admin)->post(route('gate.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('visitors', [
            'visitor_name'       => 'Dr. Ananya Iyer',
            'category'           => 'admission_enquiry',
            'child_name'         => 'Vikram Iyer',
            'grade_applying_for' => 'Grade 6',
        ]);

        $visitor = Visitor::where('visitor_name', 'Dr. Ananya Iyer')->latest()->first();
        $this->assertEquals('blue', $visitor->badge_color);
    }

    public function test_it_registers_vendor_visitor_with_work_order_and_items_carried()
    {
        $payload = [
            'visitor_name'      => 'Suresh Electrician',
            'category'          => 'vendor',
            'company_name'      => 'Sun Power Technologies',
            'work_order_number' => 'WO-2026-ELECT-01',
            'purpose'           => 'Server room UPS battery inspection',
            'items_carried'     => '1x Fluke Multimeter, 1x Insulated Toolkit',
            'visitor_phone'     => '9876500002',
            'action_type'       => 'direct_checkin',
        ];

        $response = $this->actingAs($this->admin)->post(route('gate.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('visitors', [
            'visitor_name'      => 'Suresh Electrician',
            'category'          => 'vendor',
            'company_name'      => 'Sun Power Technologies',
            'work_order_number' => 'WO-2026-ELECT-01',
            'items_carried'     => '1x Fluke Multimeter, 1x Insulated Toolkit',
        ]);
    }

    public function test_blacklist_blocks_blacklisted_visitors_from_entry()
    {
        GateBlacklist::create([
            'name'      => 'Suspicious Person',
            'phone'     => '9999900000',
            'reason'    => 'Prior misconduct at security gate',
            'is_active' => true,
            'added_by'  => $this->admin->id,
        ]);

        $payload = [
            'visitor_name'  => 'Suspicious Person',
            'category'      => 'other',
            'visitor_phone' => '9999900000',
            'purpose'       => 'Visiting campus',
            'action_type'   => 'direct_checkin',
        ];

        $response = $this->actingAs($this->admin)->post(route('gate.store'), $payload);
        $response->assertSessionHasErrors('visitor_phone');

        $this->assertDatabaseMissing('visitors', [
            'visitor_phone' => '9999900000',
        ]);
    }

    public function test_approval_workflow_pending_request_can_be_approved_by_host()
    {
        $visitor = Visitor::create([
            'visitor_name'     => 'Mahesh Interview Candidate',
            'category'         => 'interview',
            'visitor_phone'    => '9876511111',
            'purpose'          => 'Interview for PGT Physics Teacher',
            'job_role_applied' => 'PGT Physics',
            'host_employee_id' => $this->employee->id,
            'whom_to_meet'     => $this->employee->full_name,
            'status'           => Visitor::STATUS_PENDING,
            'visit_date'       => today(),
            'logged_by'        => $this->admin->id,
        ]);

        $this->assertEquals(Visitor::STATUS_PENDING, $visitor->status);

        // Host approves the visitor
        $response = $this->actingAs($this->hostTeacher)->post(route('gate.approve', $visitor->id), [
            'notes' => 'Candidate verified. Sent to Conference Room B.',
        ]);

        $response->assertRedirect();
        $visitor->refresh();

        $this->assertEquals(Visitor::STATUS_CHECKED_IN, $visitor->status);
        $this->assertNotNull($visitor->approved_at);
        $this->assertEquals($this->hostTeacher->id, $visitor->approved_by);
        $this->assertNotNull($visitor->in_time);
    }

    public function test_approval_workflow_pending_request_can_be_rejected_with_reason()
    {
        $visitor = Visitor::create([
            'visitor_name'     => 'Unscheduled Guest',
            'category'         => 'other',
            'visitor_phone'    => '9876522222',
            'purpose'          => 'Unscheduled meeting with Principal',
            'host_employee_id' => $this->employee->id,
            'status'           => Visitor::STATUS_PENDING,
            'visit_date'       => today(),
            'logged_by'        => $this->admin->id,
        ]);

        // Host rejects with reason
        $response = $this->actingAs($this->hostTeacher)->post(route('gate.reject', $visitor->id), [
            'rejection_reason' => 'Host is currently taking an exam class. Please book an appointment.',
        ]);

        $response->assertRedirect();
        $visitor->refresh();

        $this->assertEquals(Visitor::STATUS_REJECTED, $visitor->status);
        $this->assertEquals('Host is currently taking an exam class. Please book an appointment.', $visitor->rejection_reason);
        $this->assertEquals($this->hostTeacher->id, $visitor->rejected_by);
    }

    public function test_fast_qr_scan_checkout_stamps_exit_time_and_calculates_duration()
    {
        $visitor = Visitor::create([
            'visitor_name'  => 'Priya Mohan',
            'category'      => 'parent',
            'visitor_phone' => '9876533333',
            'purpose'       => 'Fee receipt collection',
            'status'        => Visitor::STATUS_CHECKED_IN,
            'visit_date'    => today(),
            'in_time'       => now()->subMinutes(45),
            'logged_by'     => $this->admin->id,
        ]);

        $this->assertNull($visitor->out_time);

        // Security gatekeeper scans pass_token
        $response = $this->actingAs($this->admin)->postJson(route('gate.scan-checkout'), [
            'code' => $visitor->pass_token,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'     => true,
            'checked_out' => true,
        ]);

        $visitor->refresh();
        $this->assertNotNull($visitor->out_time);
        $this->assertEquals(Visitor::STATUS_CHECKED_OUT, $visitor->status);

        // Second scan returns already checked out notice
        $secondResponse = $this->actingAs($this->admin)->postJson(route('gate.scan-checkout'), [
            'code' => $visitor->pass_number,
        ]);

        $secondResponse->assertStatus(200);
        $secondResponse->assertJson([
            'already_checked_out' => true,
        ]);
    }

    public function test_public_verify_pass_endpoint_returns_json_details()
    {
        $visitor = Visitor::create([
            'visitor_name'  => 'Official Inspector',
            'category'      => 'guest',
            'visitor_phone' => '9876544444',
            'purpose'       => 'Annual Board Affiliation Review',
            'status'        => Visitor::STATUS_CHECKED_IN,
            'visit_date'    => today(),
            'in_time'       => now(),
            'logged_by'     => $this->admin->id,
        ]);

        $response = $this->getJson(route('gate.verify', $visitor->pass_token));
        $response->assertStatus(200);
        $response->assertJson([
            'valid'       => true,
            'pass_number' => $visitor->pass_number,
            'name'        => 'Official Inspector',
            'status'      => 'checked_in',
            'is_inside'   => true,
        ]);
    }

    public function test_autocomplete_lookup_apis_return_matching_records()
    {
        // Student autocomplete
        $studentRes = $this->actingAs($this->admin)->getJson(route('gate.lookup.students', ['q' => substr($this->student->first_name, 0, 3)]));
        $studentRes->assertStatus(200);
        $studentRes->assertJsonFragment(['id' => $this->student->id]);

        // Host autocomplete
        $hostRes = $this->actingAs($this->admin)->getJson(route('gate.lookup.hosts', ['q' => substr($this->employee->first_name, 0, 3)]));
        $hostRes->assertStatus(200);
        $hostRes->assertJsonFragment(['id' => $this->employee->id]);
    }

    public function test_report_page_loads_and_exports_csv()
    {
        $response = $this->actingAs($this->admin)->get(route('gate.report', [
            'from'   => today()->subDays(1)->toDateString(),
            'to'     => today()->toDateString(),
            'export' => 'csv',
        ]));

        $response->assertStatus(200);
        $this->assertTrue(str_contains(strtolower($response->headers->get('content-type')), 'text/csv'));
    }
}
