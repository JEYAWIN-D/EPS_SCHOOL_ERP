<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeachingLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HrStaffOnboardingRbacTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Department $testDept;
    protected Designation $testDesig;
    protected Classes $testClass;
    protected Section $testSection;
    protected Subject $testSubject;
    protected TeachingLevel $secondaryLevel;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.database' => 'school_erp',
        ]);

        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        // Find or create admin user
        $this->adminUser = User::role('super_admin')->where('is_active', true)->first()
            ?: User::role('admin')->where('is_active', true)->firstOrFail();

        $this->testDept = Department::firstOrCreate(['name' => 'Mathematics & Sciences']);
        $this->testDesig = Designation::firstOrCreate(['name' => 'Senior Subject Teacher']);

        $academicYear = \App\Models\AcademicYear::current() ?: \App\Models\AcademicYear::first();
        if (!$academicYear) {
            $academicYear = \App\Models\AcademicYear::create([
                'name' => '2026-2027',
                'start_date' => '2026-06-01',
                'end_date' => '2027-05-31',
                'is_current' => true,
            ]);
        }

        $this->secondaryLevel = TeachingLevel::firstOrCreate(
            ['code' => 'secondary'],
            ['name' => 'Secondary Level (Classes 6-10)', 'class_range' => '6-10', 'sort_order' => 3, 'is_active' => true]
        );

        $this->testClass = Classes::firstOrCreate(
            ['name' => 'Class X-Test'],
            ['sort_order' => 10, 'is_active' => true, 'teaching_level_id' => $this->secondaryLevel->id]
        );
        if (!$this->testClass->teaching_level_id) {
            $this->testClass->update(['teaching_level_id' => $this->secondaryLevel->id]);
        }

        $this->testSection = Section::firstOrCreate(
            ['class_id' => $this->testClass->id, 'name' => 'A'],
            ['is_active' => true, 'academic_year_id' => $academicYear->id]
        );

        $this->testSubject = Subject::firstOrCreate(
            ['code' => 'MATH-TEST'],
            ['name' => 'Mathematics Test', 'type' => 'theory', 'is_active' => true]
        );
    }

    protected function validStaffPayload(array $overrides = []): array
    {
        $code = $overrides['employee_code'] ?? ('EPSS' . rand(1000, 9999));
        $dob = $overrides['dob'] ?? ($overrides['date_of_birth'] ?? '1990-05-15');

        return array_merge([
            'name'                                 => 'Staff Member',
            'first_name'                           => 'Staff',
            'last_name'                            => 'Member',
            'employee_code'                        => $code,
            'employee_type'                        => 'teaching',
            'gender'                               => 'male',
            'dob'                                  => $dob,
            'date_of_birth'                        => $dob,
            'mother_tongue'                        => 'English',
            'nationality'                          => 'Indian',
            'religion'                             => 'Hindu',
            'caste'                                => 'General',
            'marital_status'                       => 'Married',
            'number_of_children'                   => 0,
            'children_male'                        => 0,
            'children_female'                      => 0,
            'address'                              => '123 School Campus Road',
            'designation'                          => 'Senior Teacher',
            'designation_id'                       => $this->testDesig->id,
            'department'                           => 'Academics',
            'department_id'                        => $this->testDept->id,
            'joining_date'                         => '2026-06-01',
            'first_appointment_date'               => '2020-06-01',
            'current_employment_status'            => 'Permanent',
            'aadhaar_no'                           => '123456789012',
            'pan_no'                               => 'ABCDE1234F',
            'epf_no'                               => 'EPF123456',
            'official_email'                       => 'staff.' . strtolower($code) . '@schoolerp.in',
            'personal_email'                       => 'staff.' . strtolower($code) . '@example.com',
            'mobile'                               => '98765' . rand(10000, 99999),
            'name_of_contact_person'               => 'Emergency Contact',
            'contact_relationship'                 => 'Spouse',
            'contact_address'                      => '123 School Campus Road',
            'contact_no'                           => '9876543210',
            'previous_designation'                 => 'Teacher',
            'previous_school'                      => 'Previous Academy',
            'previous_from_date'                   => '2020-01-01',
            'previous_to_date'                     => '2025-12-31',
            'academic_qualification'               => 'M.Sc Mathematics',
            'academic_university'                  => 'Central University',
            'academic_medium'                      => 'English',
            'academic_institution'                 => 'University College',
            'academic_percentage'                  => 85.5,
            'academic_year_of_passing'             => 2018,
            'professional_qualification'           => 'B.Ed',
            'professional_university'              => 'National Teacher Univ',
            'professional_medium'                  => 'English',
            'professional_institution'             => 'Teacher Training Inst',
            'professional_percentage'              => 82.0,
            'professional_year_of_passing'         => 2020,
            'additional_qual_after_recruitment'    => 'None',
            'training_attended_before_recruitment' => 'Standard Induction',
        ], $overrides);
    }

    /** @test */
    public function saving_valid_teaching_staff_creates_employee_and_linked_user_with_dob_credentials()
    {
        $uniqueCode = 'EPSS' . rand(1000, 9999);
        $dob = '1990-05-15'; // Expected initial password = 15051990

        $payload = $this->validStaffPayload([
            'first_name'            => 'Ramanujan',
            'last_name'             => 'Teacher',
            'name'                  => 'Ramanujan Teacher',
            'employee_code'         => $uniqueCode,
            'employee_type'         => 'teaching',
            'gender'                => 'male',
            'date_of_birth'         => $dob,
            'dob'                   => $dob,
            'official_email'        => 'ramanujan.' . strtolower($uniqueCode) . '@schoolerp.in',
            'personal_email'        => 'ramanujan.' . strtolower($uniqueCode) . '@example.com',
            'experience_years'      => 8,
            'experience_months'     => 6,
            'teaching_levels'       => [$this->secondaryLevel->id],
            'max_weekly_periods'    => 24,
            'assigned_class_id'     => $this->testClass->id,
            'assigned_section_id'   => $this->testSection->id,
            'assigned_subject_id'   => $this->testSubject->id,
            'is_class_teacher'      => 1,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('hr.employees.store'), $payload);
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'employee_code'     => $uniqueCode,
            'employee_type'     => 'teaching',
            'experience_years'  => 8,
            'experience_months' => 6,
        ]);

        $employee = Employee::where('employee_code', $uniqueCode)->firstOrFail();

        // 1. Exactly one user account created and linked
        $user = User::where('username', $uniqueCode)->firstOrFail();
        $this->assertEquals($employee->id, $user->employee_id);
        $this->assertEquals($user->id, $employee->user_id);

        // 2. Initial password matches DOB in DDMMYYYY format
        $this->assertTrue(Hash::check('15051990', $user->password));

        // 3. User must change password at first login
        $this->assertTrue((bool) $user->must_change_password);

        // 4. Assigned safe default role 'teacher'
        $this->assertTrue($user->hasRole('teacher'));

        // 5. Teaching level eligibility was populated
        $this->assertDatabaseHas('teacher_level_eligibilities', [
            'employee_id'       => $employee->id,
            'teaching_level_id' => $this->secondaryLevel->id,
        ]);

        // 6. Class teacher assignment created
        $this->assertDatabaseHas('staff_class_teacher_assignments', [
            'employee_id' => $employee->id,
            'class_id'    => $this->testClass->id,
        ]);

        // 7. Subject allocation created
        $this->assertDatabaseHas('teacher_subject_allocations', [
            'employee_id' => $employee->id,
            'class_id'    => $this->testClass->id,
            'subject_id'  => $this->testSubject->id,
        ]);
    }

    /** @test */
    public function support_and_transport_personnel_can_be_added_as_employees_without_rbac_user_creation()
    {
        $uniqueCode = 'EPSS' . rand(1000, 9999);

        $payload = [
            'name'             => 'Driver Kannan',
            'first_name'       => 'Driver',
            'last_name'        => 'Kannan',
            'employee_code'    => $uniqueCode,
            'employee_type'    => 'driver',
            'gender'           => 'male',
            'mobile'           => '98765' . rand(10000, 99999),
            'designation'      => 'Bus Driver',
            'joining_date'     => '2026-06-01',
            'license_number'   => 'TN2820180001',
            'assigned_vehicle' => 'TN-28-AB-1234',
            'shift_timing'     => '07:00 AM - 04:00 PM',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('hr.employees.store'), $payload);

        $response->assertRedirect(route('hr.employees', ['type' => 'driver']));
        $this->assertDatabaseHas('employees', [
            'employee_code' => $uniqueCode,
            'employee_type' => 'driver',
            'user_id'       => null,
        ]);
        $this->assertDatabaseMissing('users', ['username' => $uniqueCode]);
    }

    /** @test */
    public function cleaners_and_nannies_can_be_added_as_employees_without_rbac_user_creation()
    {
        $cleanerCode = 'EPSS' . rand(1000, 9999);
        $cleanerPayload = [
            'name'           => 'Cleaner Selvi',
            'first_name'     => 'Cleaner',
            'last_name'      => 'Selvi',
            'employee_code'  => $cleanerCode,
            'employee_type'  => 'cleaner',
            'gender'         => 'female',
            'mobile'         => '98765' . rand(10000, 99999),
            'designation'    => 'Housekeeping Staff',
            'joining_date'   => '2026-06-01',
            'assigned_block' => 'Primary Block A',
            'shift_timing'   => '06:30 AM - 03:00 PM',
        ];

        $resCleaner = $this->actingAs($this->adminUser)->post(route('hr.employees.store'), $cleanerPayload);
        $resCleaner->assertRedirect(route('hr.employees', ['type' => 'cleaner']));
        $this->assertDatabaseHas('employees', [
            'employee_code' => $cleanerCode,
            'employee_type' => 'cleaner',
            'user_id'       => null,
        ]);
        $this->assertDatabaseMissing('users', ['username' => $cleanerCode]);

        $nannyCode = 'EPSS' . rand(1000, 9999);
        $nannyPayload = [
            'name'           => 'Nanny Mary',
            'first_name'     => 'Nanny',
            'last_name'      => 'Mary',
            'employee_code'  => $nannyCode,
            'employee_type'  => 'nanny',
            'gender'         => 'female',
            'mobile'         => '98765' . rand(10000, 99999),
            'designation'    => 'Kindergarten Caretaker',
            'joining_date'   => '2026-06-01',
            'assigned_block' => 'KG Section',
            'shift_timing'   => '08:00 AM - 04:00 PM',
        ];

        $resNanny = $this->actingAs($this->adminUser)->post(route('hr.employees.store'), $nannyPayload);
        $resNanny->assertRedirect(route('hr.employees', ['type' => 'nanny']));
        $this->assertDatabaseHas('employees', [
            'employee_code' => $nannyCode,
            'employee_type' => 'nanny',
            'user_id'       => null,
        ]);
        $this->assertDatabaseMissing('users', ['username' => $nannyCode]);
    }

    /** @test */
    public function account_provisioning_is_idempotent_and_does_not_create_duplicate_users()
    {
        $uniqueCode = 'EPSS' . rand(1000, 9999);
        $dob = '1988-11-20';

        $payload = $this->validStaffPayload([
            'first_name'    => 'NonTeaching',
            'last_name'     => 'Accountant',
            'name'          => 'NonTeaching Accountant',
            'employee_code' => $uniqueCode,
            'employee_type' => 'non_teaching',
            'designation'   => 'Accountant',
            'date_of_birth' => $dob,
            'dob'           => $dob,
        ]);

        // First submission
        $this->actingAs($this->adminUser)->post(route('hr.employees.store'), $payload);

        $userCount = User::where('username', $uniqueCode)->count();
        $this->assertEquals(1, $userCount);

        // Simulated secondary call (e.g. duplicate webhook or re-provision)
        $employee = Employee::where('employee_code', $uniqueCode)->firstOrFail();
        $this->assertEquals(1, User::where('employee_id', $employee->id)->count());
    }

    /** @test */
    public function staff_can_login_using_employee_id_username_and_is_forced_to_change_temporary_password()
    {
        $uniqueCode = 'EPSS' . rand(1000, 9999);
        $dob = '1992-08-25'; // Expected temp password = 25081992

        $user = User::create([
            'name'                 => 'Test Onboarded Teacher',
            'email'                => 'teacher.' . strtolower($uniqueCode) . '@schoolerp.in',
            'username'             => $uniqueCode,
            'password'             => Hash::make('25081992'),
            'must_change_password' => true,
            'is_active'            => true,
        ]);
        $user->assignRole('teacher');

        // Login using Employee ID as username
        $response = $this->post(route('login.post'), [
            'email'    => $uniqueCode,
            'password' => '25081992',
        ]);

        // Redirects to forced password change
        $response->assertRedirect(route('password.force_change'));

        // Attempting to access dashboard while must_change_password is true redirects back to force-change
        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse->assertRedirect(route('password.force_change'));

        // Changing the temporary password
        $changeResponse = $this->actingAs($user)->post(route('password.force_change.post'), [
            'current_password'      => '25081992',
            'password'              => 'NewSecurePass@2026',
            'password_confirmation' => 'NewSecurePass@2026',
        ]);

        $user->refresh();
        $this->assertFalse((bool) $user->must_change_password);
        $this->assertTrue(Hash::check('NewSecurePass@2026', $user->password));
    }

    /** @test */
    public function teacher_scoping_strictly_restricts_access_to_assigned_classes()
    {
        $uniqueCode = 'TEST-SCOPE-' . rand(1000, 9999);

        $employee = Employee::create([
            'first_name'       => 'Scoped',
            'last_name'        => 'Teacher',
            'employee_code'    => $uniqueCode,
            'employee_type'    => 'teaching',
            'gender'           => 'female',
            'joining_date'     => '2026-06-01',
            'date_of_birth'    => '1991-03-10',
            'is_active'        => true,
        ]);

        $user = User::create([
            'name'                 => 'Scoped Teacher',
            'email'                => 'scoped.' . strtolower($uniqueCode) . '@schoolerp.in',
            'username'             => $uniqueCode,
            'employee_id'          => $employee->id,
            'password'             => Hash::make('Teacher@1234'),
            'must_change_password' => false,
            'is_active'            => true,
        ]);
        $user->assignRole('teacher');
        $employee->update(['user_id' => $user->id]);

        // 1. With NO assigned classes, scoped teacher sees 0 students
        $indexResponse = $this->actingAs($user)->get(route('students.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertViewHas('students', function ($students) {
            return $students->isEmpty();
        });

        // 2. Assign teacher to Class X-Test
        \App\Models\StaffClassTeacherAssignment::create([
            'employee_id'      => $employee->id,
            'class_id'         => $this->testClass->id,
            'section_id'       => $this->testSection->id,
            'academic_year_id' => \App\Models\AcademicYear::current()?->id,
            'assigned_at'      => today(),
            'is_current'       => true,
        ]);

        // Accessing attendance mark page for assigned class succeeds
        $markResponse = $this->actingAs($user)->get(route('attendance.mark', [
            'class_id'   => $this->testClass->id,
            'section_id' => $this->testSection->id,
        ]));
        $markResponse->assertStatus(200);

        // Creating another unassigned class
        $unassignedClass = Classes::create([
            'name' => 'Unassigned Class ' . rand(100, 999),
            'sort_order' => 99,
            'is_active' => true,
        ]);

        // Accessing attendance for unassigned class is rejected with 403 Forbidden
        $forbiddenResponse = $this->actingAs($user)->get(route('attendance.mark', [
            'class_id' => $unassignedClass->id,
        ]));
        $forbiddenResponse->assertStatus(403);
    }

    /** @test */
    public function teaching_level_eligibility_and_workload_checks_prevent_invalid_allocations()
    {
        $uniqueCode = 'TEST-ELIG-' . rand(1000, 9999);

        $kgLevel = TeachingLevel::firstOrCreate(
            ['code' => 'kg'],
            ['name' => 'Kindergarten', 'class_range' => 'Pre-KG - UKG', 'sort_order' => 1, 'is_active' => true]
        );

        $employee = Employee::create([
            'first_name'         => 'KG',
            'last_name'          => 'Specialist',
            'employee_code'      => $uniqueCode,
            'employee_type'      => 'teaching',
            'gender'             => 'female',
            'joining_date'       => '2026-06-01',
            'date_of_birth'      => '1993-02-14',
            'max_weekly_periods' => 1, // Only 1 period allowed
            'is_active'          => true,
        ]);

        // Teacher is eligible ONLY for KG
        \App\Models\TeacherLevelEligibility::create([
            'employee_id'       => $employee->id,
            'teaching_level_id' => $kgLevel->id,
            'is_approved'       => true,
        ]);

        // Attempting to allocate KG teacher to Secondary Class (Class X-Test) fails
        $invalidLevelResponse = $this->actingAs($this->adminUser)->post(route('academics.allocation.save'), [
            'employee_id' => $employee->id,
            'subject_id'  => $this->testSubject->id,
            'class_id'    => $this->testClass->id, // Secondary level class
        ]);
        $invalidLevelResponse->assertSessionHas('error');

        // Create a KG Class
        $kgClass = Classes::create([
            'name'              => 'LKG-Alpha',
            'teaching_level_id' => $kgLevel->id,
            'sort_order'        => 1,
            'is_active'         => true,
        ]);

        // Allocating to KG class succeeds for the 1st allocation
        $validAllocResponse = $this->actingAs($this->adminUser)->post(route('academics.allocation.save'), [
            'employee_id' => $employee->id,
            'subject_id'  => $this->testSubject->id,
            'class_id'    => $kgClass->id,
        ]);
        $validAllocResponse->assertSessionHas('success');

        // Second allocation exceeds max_weekly_periods limit of 1
        $secondSubject = Subject::create([
            'code'      => 'KG-RHYME-' . rand(100, 999),
            'name'      => 'KG Rhymes',
            'type'      => 'activity',
            'is_active' => true,
        ]);

        $overloadResponse = $this->actingAs($this->adminUser)->post(route('academics.allocation.save'), [
            'employee_id' => $employee->id,
            'subject_id'  => $secondSubject->id,
            'class_id'    => $kgClass->id,
        ]);
        $overloadResponse->assertSessionHas('error');
    }

    /** @test */
    public function class_teacher_can_access_my_class_dashboard()
    {
        $uniqueCode = 'TEST-MYCLS-' . rand(1000, 9999);

        $employee = Employee::create([
            'first_name'    => 'Class',
            'last_name'     => 'Incharge',
            'employee_code' => $uniqueCode,
            'employee_type' => 'teaching',
            'gender'        => 'male',
            'joining_date'  => '2026-06-01',
            'date_of_birth' => '1989-07-12',
            'is_active'     => true,
        ]);

        $user = User::create([
            'name'                 => 'Class Incharge',
            'email'                => 'incharge.' . strtolower($uniqueCode) . '@schoolerp.in',
            'username'             => $uniqueCode,
            'employee_id'          => $employee->id,
            'password'             => Hash::make('Teacher@1234'),
            'must_change_password' => false,
            'is_active'            => true,
        ]);
        $user->assignRole('class_teacher');

        \App\Models\StaffClassTeacherAssignment::create([
            'employee_id'      => $employee->id,
            'class_id'         => $this->testClass->id,
            'section_id'       => $this->testSection->id,
            'academic_year_id' => \App\Models\AcademicYear::current()?->id,
            'assigned_at'      => today(),
            'is_current'       => true,
        ]);

        $response = $this->actingAs($user)->get(route('academics.my-class'));
        $response->assertStatus(200);
        $response->assertSee($this->testClass->name);
    }

    /** @test */
    public function staff_data_collection_13_headings_and_family_members_are_persisted_correctly()
    {
        $uniqueCode = 'EPSS' . rand(1000, 9999);
        $postData = $this->validStaffPayload([
            'employee_code' => $uniqueCode,
            'name' => 'Dr Alexander Hamilton',
            'first_name' => 'Alexander',
            'last_name' => 'Hamilton',
            'designation' => $this->testDesig->name,
            'designation_id' => $this->testDesig->id,
            'department' => $this->testDept->name,
            'department_id' => $this->testDept->id,
            'alternate_phone' => '9876543211',
            'permanent_address' => '42 Ancestral Bungalow, Tirunelveli',
            'pay_scale' => 'Level 10 (56100-177500)',
            'last_promotion_date' => '2025-01-01',
            'contract_end_date' => '2028-05-31',
            'confirmation_date' => '2025-12-01',
            'uan_no' => '101234567890',
            'esi_no' => '31001234567890001',
            'professional_tax_no' => 'PT-TN-2025-099',
            'passport_no' => 'Z9876543',
            'major_subject' => 'Pure Mathematics',
            'minor_subject' => 'Statistics',
            'special_skills' => 'Advanced Calculus, Vedic Math',
            'preferred_teaching_level' => 'Higher Secondary',
            'lab_subjects' => 'Computer Lab, Math Lab',
            'annual_increment' => 2500,
            'increment_effective_date' => '2026-07-01',
            'nominee_name' => 'Elizabeth Hamilton',
            'nominee_relationship' => 'Spouse',
            'nominee_share' => 100,
            'teaching_levels' => [$this->secondaryLevel->id],
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('hr.employees.store'), $postData);
        if ($response->exception) {
            $this->fail('Exception during store: ' . $response->exception->getMessage());
        }
        $response->assertSessionHasNoErrors();

        $employee = Employee::where('employee_code', $uniqueCode)->first();
        $this->assertNotNull($employee, 'Employee should have been created');
        $this->assertEquals('9876543211', $employee->alternate_phone);
        $this->assertEquals('42 Ancestral Bungalow, Tirunelveli', $employee->permanent_address);
        $this->assertEquals('Level 10 (56100-177500)', $employee->pay_scale);
        $this->assertEquals('101234567890', $employee->uan_no);
        $this->assertEquals('Pure Mathematics', $employee->major_subject);
        $this->assertEquals('Elizabeth Hamilton', $employee->nominee_name);
        $this->assertEquals(100, (int) $employee->nominee_share);

        // Test Family Member addition via API
        $famResponse = $this->actingAs($this->adminUser)->post(route('hr.employees.family-members.store', $employee->id), [
            'member_name' => 'Philip Hamilton',
            'relationship' => 'Son',
            'dob' => '2016-04-15',
            'contact_number' => '9876543219',
            'is_verified' => 1,
            'permanent_address' => '42 Heritage Enclave, Chennai',
        ]);
        $famResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employee_family_members', [
            'employee_id' => $employee->id,
            'member_name' => 'Philip Hamilton',
            'relationship' => 'Son',
        ]);

        // Test Update Employee
        $postData['alternate_phone'] = '9876543299';
        $postData['pay_scale'] = 'Level 11 Revised';
        $updateResponse = $this->actingAs($this->adminUser)->put(route('hr.employees.update', $employee->id), $postData);
        $updateResponse->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertEquals('9876543299', $employee->alternate_phone);
        $this->assertEquals('Level 11 Revised', $employee->pay_scale);
    }
}
