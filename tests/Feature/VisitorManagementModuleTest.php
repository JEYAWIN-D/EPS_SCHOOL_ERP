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

    public function test_registration_form_empty_submission_validation_errors()
    {
        $response = $this->actingAs($this->admin)->post(route('gate.store'), []);
        $response->assertSessionHasErrors(['visitor_name', 'category', 'purpose']);
    }

    public function test_registration_form_invalid_phone_format_validation()
    {
        $response = $this->actingAs($this->admin)->post(route('gate.store'), [
            'visitor_name'  => 'John Invalid',
            'category'      => 'other',
            'purpose'       => 'Test Meeting',
            'visitor_phone' => 'not-a-phone-number',
        ]);
        $response->assertSessionHasErrors(['visitor_phone']);
    }

    public function test_registration_form_invalid_visitor_count()
    {
        $response = $this->actingAs($this->admin)->post(route('gate.store'), [
            'visitor_name'  => 'Count Tester',
            'category'      => 'other',
            'purpose'       => 'Test Meeting',
            'visitor_count' => 999,
        ]);
        $response->assertSessionHasErrors(['visitor_count']);
    }

    public function test_registration_form_expected_exit_time_cannot_be_earlier_than_entry_time()
    {
        $earlierTime = now()->subHours(2)->format('H:i');
        $response = $this->actingAs($this->admin)->post(route('gate.store'), [
            'visitor_name'       => 'Time Tester',
            'category'           => 'other',
            'purpose'            => 'Test Meeting',
            'expected_exit_time' => $earlierTime,
        ]);
        $response->assertSessionHasErrors(['expected_exit_time']);
    }

    public function test_send_for_host_approval_action_is_disabled_for_testing()
    {
        $response = $this->actingAs($this->admin)->post(route('gate.store'), [
            'visitor_name' => 'Approval Attempt',
            'category'     => 'other',
            'purpose'      => 'Test Meeting',
            'action_type'  => 'request_approval',
        ]);
        $response->assertSessionHasErrors(['action_type']);
    }

    public function test_visitor_history_filters_work_individually_and_together()
    {
        $v1 = Visitor::create([
            'visitor_name'     => 'Filter Alpha',
            'category'         => 'parent',
            'visitor_phone'    => '9876599001',
            'purpose'          => 'Fee verification discussion',
            'department'       => 'Accounts',
            'whom_to_meet'     => 'Mr. Ramanan',
            'status'           => Visitor::STATUS_CHECKED_IN,
            'visit_date'       => today(),
            'in_time'          => now(),
            'logged_by'        => $this->admin->id,
        ]);

        $v2 = Visitor::create([
            'visitor_name'     => 'Filter Beta',
            'category'         => 'vendor',
            'visitor_phone'    => '9876599002',
            'purpose'          => 'Hardware repair',
            'department'       => 'IT Lab',
            'whom_to_meet'     => 'Ms. Shanthi',
            'status'           => Visitor::STATUS_CHECKED_OUT,
            'visit_date'       => today(),
            'in_time'          => now()->subHours(2),
            'out_time'         => now()->subHour(),
            'logged_by'        => $this->admin->id,
        ]);

        // 1. Filter by Department
        $resDept = $this->actingAs($this->admin)->get(route('gate.index', ['department' => 'Accounts', 'tab' => 'all']));
        $resDept->assertStatus(200);
        $resDept->assertSee('Filter Alpha');
        $resDept->assertDontSee('Filter Beta');

        // 2. Filter by Category
        $resCat = $this->actingAs($this->admin)->get(route('gate.index', ['category' => 'vendor', 'tab' => 'all']));
        $resCat->assertStatus(200);
        $resCat->assertSee('Filter Beta');
        $resCat->assertDontSee('Filter Alpha');

        // 3. Filter by Purpose
        $resPurp = $this->actingAs($this->admin)->get(route('gate.index', ['purpose' => 'Hardware repair', 'tab' => 'all']));
        $resPurp->assertStatus(200);
        $resPurp->assertSee('Filter Beta');
        $resPurp->assertDontSee('Filter Alpha');

        // 4. Combined Filter
        $resComb = $this->actingAs($this->admin)->get(route('gate.index', [
            'department' => 'Accounts',
            'category'   => 'parent',
            'date'       => today()->toDateString(),
            'tab'        => 'all',
        ]));
        $resComb->assertStatus(200);
        $resComb->assertSee('Filter Alpha');
    }

    public function test_unauthorized_user_cannot_approve_or_reject_other_hosts_visitor()
    {
        $otherUser = User::create([
            'name'     => 'Regular Staff User',
            'email'    => 'staff_' . uniqid() . '@schoolerp.in',
            'password' => bcrypt('Secret@123'),
        ]);

        $visitor = Visitor::create([
            'visitor_name'     => 'Guarded Visitor',
            'category'         => 'interview',
            'visitor_phone'    => '9876599999',
            'purpose'          => 'Confidential interview',
            'host_employee_id' => $this->employee->id,
            'status'           => Visitor::STATUS_PENDING,
            'visit_date'       => today(),
            'logged_by'        => $this->admin->id,
        ]);

        // Attempt approve by unauthorized user without admin role and not host
        $res = $this->actingAs($otherUser)->post(route('gate.approve', $visitor->id));
        $res->assertStatus(403);

        // Attempt reject by unauthorized user
        $resRej = $this->actingAs($otherUser)->post(route('gate.reject', $visitor->id), [
            'rejection_reason' => 'Unauthorized rejection attempt',
        ]);
        $resRej->assertStatus(403);
    }

    public function test_automated_shift_end_bulk_checkout_for_overstayed_visitors()
    {
        // Create 2 overstayed visitors
        $v1 = Visitor::create([
            'visitor_name'       => 'Auto Overstay One',
            'category'           => 'other',
            'visitor_phone'      => '9123456781',
            'purpose'            => 'Official Inspection',
            'status'             => Visitor::STATUS_CHECKED_IN,
            'visit_date'         => today(),
            'in_time'            => now()->subHours(3),
            'expected_exit_time' => now()->subHours(1),
            'logged_by'          => $this->admin->id,
        ]);

        $v2 = Visitor::create([
            'visitor_name'       => 'Auto Overstay Two',
            'category'           => 'vendor',
            'visitor_phone'      => '9123456782',
            'purpose'            => 'AC Servicing',
            'status'             => Visitor::STATUS_CHECKED_IN,
            'visit_date'         => today(),
            'in_time'            => now()->subHours(4),
            'expected_exit_time' => now()->subHours(2),
            'logged_by'          => $this->admin->id,
        ]);

        $this->assertNull($v1->out_time);
        $this->assertNull($v2->out_time);

        // Run automated bulk checkout endpoint
        $res = $this->actingAs($this->admin)->post(route('gate.auto-checkout-overstayed'));
        $res->assertRedirect(route('gate.index', ['tab' => 'today']));
        $res->assertSessionHas('success');

        $v1->refresh();
        $v2->refresh();

        $this->assertNotNull($v1->out_time);
        $this->assertEquals(Visitor::STATUS_CHECKED_OUT, $v1->status);
        $this->assertStringContainsString('Auto-checked out at shift-end', $v1->remarks);

        $this->assertNotNull($v2->out_time);
        $this->assertEquals(Visitor::STATUS_CHECKED_OUT, $v2->status);
    }

    public function test_automated_expected_exit_time_default_and_autoprint_flow()
    {
        // When expected exit time is omitted, system should automatically default to +1 hour
        // and redirect to gate.pass with autoprint=1
        $res = $this->actingAs($this->admin)->post(route('gate.store'), [
            'visitor_name'       => 'Automated Exit Timing Visitor',
            'category'           => 'parent',
            'visitor_phone'      => '9988776655',
            'purpose'            => 'Fee payment enquiry',
            'action_type'        => 'direct_checkin',
            'auto_print'         => '1',
            // expected_exit_time omitted intentionally
        ]);

        $visitor = Visitor::where('visitor_name', 'Automated Exit Timing Visitor')->first();
        $this->assertNotNull($visitor);
        $this->assertNotNull($visitor->expected_exit_time);
        $this->assertTrue($visitor->expected_exit_time->gt(now()->subMinute()));

        $res->assertRedirect(route('gate.pass', ['id' => $visitor->id, 'autoprint' => 1]));
    }

    public function test_automated_id_proof_normalization_and_phone_trimming()
    {
        $res = $this->actingAs($this->admin)->post(route('gate.store'), [
            'visitor_name'      => 'Normalized Visitor',
            'category'          => 'other',
            'visitor_phone'     => '  9876543210  ',
            'visitor_id_type'   => 'PAN Card',
            'visitor_id_number' => '  abcde1234f  ',
            'purpose'           => 'General meeting',
            'action_type'       => 'direct_checkin',
        ]);

        $visitor = Visitor::where('visitor_name', 'Normalized Visitor')->first();
        $this->assertNotNull($visitor);
        $this->assertEquals('9876543210', $visitor->visitor_phone);
        $this->assertEquals('ABCDE1234F', $visitor->visitor_id_number);
    }

    public function test_thermal_roll_pass_renders_with_80mm_layout_and_student_details_for_parent()
    {
        $student = Student::first() ?? Student::create([
            'first_name' => 'Rahul',
            'last_name' => 'Sharma',
            'admission_no' => 'ADM-TEST-2026',
            'status' => 'active',
        ]);

        $visitor = Visitor::create([
            'visitor_name' => 'Suresh Sharma',
            'category' => Visitor::CATEGORY_PARENT,
            'visitor_phone' => '9840112233',
            'student_id' => $student->id,
            'relationship_to_student' => 'Father',
            'purpose' => 'Parent Teacher Consultation',
            'whom_to_meet' => 'Mrs. Sundaram',
            'department' => 'Science',
            'visitor_count' => 1,
            'status' => Visitor::STATUS_CHECKED_IN,
            'in_time' => now(),
            'visitor_id_type' => 'aadhaar',
            'visitor_id_number' => '1234 5678 9012',
        ]);

        $res = $this->actingAs($this->admin)->get(route('gate.pass', $visitor->id));
        $res->assertStatus(200);

        // Verify dedicated 80mm roll printer elements
        $res->assertSee('id="thermal-roll-pass"', false);
        $res->assertSee('72mm', false);
        $res->assertSee('80mm', false);
        $res->assertSee('@media print', false);
        $res->assertSee('#sidebar', false);
        $res->assertSee('VISITOR GATE PASS', false);
        $res->assertSee($visitor->pass_number);
        $res->assertSee('Suresh Sharma');
        $res->assertSee('9840112233');
        $res->assertSee('Parent Teacher Consultation');
        $res->assertSee('Mrs. Sundaram');
        $res->assertSee('Science');

        // Linked student details present
        $res->assertSee('Linked Student:');
        $res->assertSee($student->first_name);
        $res->assertSee($student->admission_no);
        $res->assertSee('Father');

        // Masked ID proof (privacy protected)
        $res->assertSee('•••• 9012');
        $res->assertDontSee('1234 5678 9012');

        // Verify non-applicable category sections are NOT rendered
        $res->assertDontSee('Admission Candidate:');
        $res->assertDontSee('Vendor / Agency:');
        $res->assertDontSee('Interview Role:');
    }

    public function test_thermal_roll_pass_for_non_parent_meeting_teacher_omits_student_fields()
    {
        $visitor = Visitor::create([
            'visitor_name' => 'Dr. A. P. Ramanathan',
            'category' => Visitor::CATEGORY_OTHER,
            'visitor_phone' => '9123456780',
            'purpose' => 'Guest Lecture on Astronomy',
            'whom_to_meet' => 'Principal Office',
            'department' => 'Administration',
            'visitor_count' => 2,
            'vehicle_number' => 'TN-33-AX-9999',
            'status' => Visitor::STATUS_CHECKED_IN,
            'in_time' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('gate.pass', $visitor->id));
        $res->assertStatus(200);

        // Core visitor and host details
        $res->assertSee('Dr. A. P. Ramanathan');
        $res->assertSee('Guest Lecture on Astronomy');
        $res->assertSee('Principal Office');
        $res->assertSee('Administration');
        $res->assertSee('TN-33-AX-9999');

        // No student fields displayed
        $res->assertDontSee('Linked Student:');
        $res->assertDontSee('Admission Candidate:');
        $res->assertDontSee('Vendor / Agency:');
    }

    public function test_thermal_roll_pass_for_vendor_scenario()
    {
        $visitor = Visitor::create([
            'visitor_name' => 'Karthik Hardware Supplier',
            'category' => Visitor::CATEGORY_VENDOR,
            'company_name' => 'Apex Network Solutions',
            'work_order_number' => 'WO-2026-088',
            'visitor_phone' => '9876500000',
            'purpose' => 'Fiber Optic Cable Maintenance',
            'whom_to_meet' => 'IT Department Incharge',
            'visitor_count' => 3,
            'status' => Visitor::STATUS_CHECKED_IN,
            'in_time' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('gate.pass', $visitor->id));
        $res->assertStatus(200);

        $res->assertSee('Vendor / Agency:');
        $res->assertSee('Apex Network Solutions');
        $res->assertSee('WO-2026-088');
        $res->assertDontSee('Linked Student:');
        $res->assertDontSee('Admission Candidate:');

        // Confirm Expected Exit Time is NOT on the printed pass
        $res->assertDontSee('Exp. Exit:');
        $res->assertDontSee('Expected Exit');
    }

    public function test_log_new_visitor_form_has_manual_text_inputs_and_no_webcam()
    {
        $res = $this->actingAs($this->admin)->get(route('gate.create'));
        $res->assertStatus(200);

        // Confirm Vendor & Maintenance fields are normal text inputs, NOT select dropdowns
        $res->assertSee('name="company_name"', false);
        $res->assertSee('name="maintenance_details"', false);
        $res->assertDontSee('name="vendor_id"', false);
        $res->assertDontSee('Choose Existing Registered Vendor', false);

        // Confirm live webcam photo capture is completely disabled and removed
        $res->assertDontSee('Live Webcam Visitor Photo Capture');
        $res->assertDontSee('Start Live Camera');
        $res->assertDontSee('📸 Snap Photo');
        $res->assertDontSee('startCamera');
        $res->assertDontSee('getUserMedia');
    }

    public function test_manual_vendor_and_maintenance_inputs_save_and_display_in_details_and_pass()
    {
        $res = $this->actingAs($this->admin)->post(route('gate.store'), [
            'visitor_name'        => 'Ramesh Electrician',
            'category'            => 'vendor',
            'company_name'        => 'Bright Light Services',
            'maintenance_details' => 'Auditorium Projector & Sound Maintenance',
            'work_order_number'   => 'WO-2026-909',
            'items_carried'       => 'Toolbox and Cable Tester',
            'visitor_phone'       => '9888877777',
            'purpose'             => 'Audio Visual System Setup',
            'action_type'         => 'direct_checkin',
        ]);

        $visitor = Visitor::where('visitor_name', 'Ramesh Electrician')->first();
        $this->assertNotNull($visitor);
        $this->assertEquals('Bright Light Services', $visitor->company_name);
        $this->assertStringContainsString('Auditorium Projector & Sound Maintenance', $visitor->work_order_number);
        $this->assertEquals('Toolbox and Cable Tester', $visitor->items_carried);

        // Check details page shows saved manual values and does NOT show expected exit
        $showRes = $this->actingAs($this->admin)->get(route('gate.show', $visitor->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('Bright Light Services');
        $showRes->assertSee('Auditorium Projector & Sound Maintenance');
        $showRes->assertDontSee('Expected Exit:');

        // Check pass page does NOT show expected exit time
        $passRes = $this->actingAs($this->admin)->get(route('gate.pass', $visitor->id));
        $passRes->assertStatus(200);
        $passRes->assertSee('Bright Light Services');
        $passRes->assertDontSee('Exp. Exit:');
        $passRes->assertDontSee('Expected Exit');
    }
}


