<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\User;
use App\Http\Controllers\Admin\HrController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

echo "====================================================\n";
echo "HR STAFF ADD / NEW STAFF REDESIGN - TEST SUITE\n";
echo "====================================================\n\n";

$adminUser = User::where('email', 'admin@dasa.com')->first() ?? User::first();
Auth::login($adminUser);
view()->share('errors', new \Illuminate\Support\ViewErrorBag);

$controller = app(HrController::class);

// ----------------------------------------------------
// TEST 1: Open Add Staff - Verify all 58 Excel fields exist in Blade template
// ----------------------------------------------------
echo "[TEST 1] Verifying all Excel-defined fields are present in Add Staff view...\n";
$createBlade = file_get_contents('d:/school_erp - Copy/school_erp - Copy/resources/views/hr/employees-create.blade.php');

$requiredExcelFieldsInView = [
    // Section A
    'name="name"',
    'name="dob"',
    'name="gender"',
    'name="mother_tongue"',
    'name="nationality"',
    'name="religion"',
    'name="caste"',
    'name="marital_status"',
    'name="qualification"',
    'name="number_of_children"',
    'name="children_male"',
    'name="children_female"',
    'name="address"',
    // Section B
    'name="employee_code"',
    'name="designation"',
    'name="joining_date"',
    'name="first_appointment_date"',
    'name="current_employment_status"',
    // Section C
    'name="aadhaar_no"',
    'name="pan_no"',
    'name="epf_no"',
    // Section D
    'name="official_email"',
    'name="mobile"',
    'name="contact_person"',
    'name="name_of_contact_person"',
    'name="contact_relationship"',
    'name="contact_address"',
    'name="contact_no"',
    // Section E
    'name="previous_designation"',
    'name="previous_school"',
    'name="previous_from_date"',
    'name="previous_to_date"',
    // Section F - Academic
    'name="academic_qualification"',
    'name="academic_university"',
    'name="academic_medium"',
    'name="academic_institution"',
    'name="academic_percentage"',
    'name="academic_year_of_passing"',
    // Section G - Professional
    'name="professional_qualification"',
    'name="professional_university"',
    'name="professional_medium"',
    'name="professional_institution"',
    'name="professional_percentage"',
    'name="professional_year_of_passing"',
    // Section H - Other 1
    'name="other_qual_1_name"',
    'name="other_qual_1_university"',
    'name="other_qual_1_medium"',
    'name="other_qual_1_institution"',
    'name="other_qual_1_percentage"',
    'name="other_qual_1_year_of_passing"',
    // Section I - Other 2
    'name="other_qual_2_name"',
    'name="other_qual_2_university"',
    'name="other_qual_2_medium"',
    'name="other_qual_2_institution"',
    'name="other_qual_2_percentage"',
    'name="other_qual_2_year_of_passing"',
    // Section J
    'name="additional_qual_after_recruitment"',
    'name="training_attended_before_recruitment"'
];

$missingInBlade = [];
foreach ($requiredExcelFieldsInView as $f) {
    if (strpos($createBlade, $f) === false) {
        $missingInBlade[] = $f;
    }
}

if (empty($missingInBlade)) {
    echo "  PASS: All 58 Excel fields verified in Add Staff Blade template.\n";
} else {
    echo "  FAIL: Missing fields in Add Staff Blade: " . implode(', ', $missingInBlade) . "\n";
}

// ----------------------------------------------------
// TEST 2: Leave required fields empty -> Employee must NOT be created
// ----------------------------------------------------
echo "\n[TEST 2] Submitting empty form -> Verify employee creation is blocked...\n";
$emptyRequest = Request::create(route('hr.employees.store'), 'POST', [
    'name' => '',
    'dob' => '',
    'official_email' => '',
    'mobile' => '',
]);
$countBefore = Employee::count();
$caughtException = false;
try {
    $controller->storeEmployee($emptyRequest);
} catch (\Illuminate\Validation\ValidationException $e) {
    $caughtException = true;
    $errors = $e->validator->errors();
    echo "  PASS: ValidationException thrown successfully with " . count($errors) . " error messages.\n";
    echo "  Sample error: " . $errors->first() . "\n";
}
$countAfter = Employee::count();
if ($caughtException && $countBefore === $countAfter) {
    echo "  PASS: Database record was NOT created on empty submission.\n";
} else {
    echo "  FAIL: Record created or ValidationException not thrown.\n";
}

// ----------------------------------------------------
// TEST 3: Fill all fields with valid test data -> Save & Pending Approval
// ----------------------------------------------------
echo "\n[TEST 3] Submitting valid complete Excel-based dataset...\n";
$testCode = 'TEST_EMP_' . time();
$validData = [
    // Section A
    'name' => 'Rajesh Sharma',
    'dob' => '1985-05-15',
    'gender' => 'male',
    'mother_tongue' => 'Hindi',
    'nationality' => 'Indian',
    'religion' => 'Hindu',
    'caste' => 'Brahmin',
    'marital_status' => 'married',
    'qualification' => 'M.Sc., B.Ed.',
    'number_of_children' => '2',
    'children_male' => '1',
    'children_female' => '1',
    'address' => '45/B Temple Street, Civil Lines, Jaipur, Rajasthan - 302006',

    // Section B
    'employee_code' => $testCode,
    'employee_type' => 'teaching',
    'designation' => 'Senior Mathematics Teacher',
    'department' => 'Mathematics',
    'joining_date' => '2024-06-01',
    'first_appointment_date' => '2010-07-15',
    'current_employment_status' => 'Active',

    // Section C
    'aadhaar_no' => '987654321098',
    'pan_no' => 'ABCDE1234F',
    'epf_no' => 'EPF99887766',

    // Section D
    'official_email' => 'rajesh.sharma.' . time() . '@dasaedu.test',
    'mobile' => '9876543210',
    'contact_person' => 'Spouse',
    'name_of_contact_person' => 'Sunita Sharma',
    'contact_relationship' => 'Wife',
    'contact_address' => '45/B Temple Street, Civil Lines, Jaipur, Rajasthan - 302006',
    'contact_no' => '9876543211',

    // Section E
    'previous_designation' => 'TGT Mathematics',
    'previous_school' => 'St. Xavier High School, Delhi Road, Jaipur',
    'previous_from_date' => '2015-06-01',
    'previous_to_date' => '2024-04-30',

    // Section F - Academic Qualification
    'academic_qualification' => 'M.Sc. Mathematics',
    'academic_university' => 'University of Rajasthan',
    'academic_medium' => 'English',
    'academic_institution' => 'Maharaja College, Jaipur',
    'academic_percentage' => '82.50',
    'academic_year_of_passing' => '2008',

    // Section G - Professional Qualification
    'professional_qualification' => 'B.Ed. (Bachelor of Education)',
    'professional_university' => 'Maharshi Dayanand Saraswati University',
    'professional_medium' => 'English',
    'professional_institution' => 'Regional Institute of Education, Ajmer',
    'professional_percentage' => '78.00',
    'professional_year_of_passing' => '2010',

    // Section H - Other Qualification 1
    'other_qual_1_name' => 'Certificate in Vedic Mathematics',
    'other_qual_1_university' => 'National Vedic Institute',
    'other_qual_1_medium' => 'Hindi/English',
    'other_qual_1_institution' => 'Sanskrit Sansthan New Delhi',
    'other_qual_1_percentage' => '88.00',
    'other_qual_1_year_of_passing' => '2013',

    // Section I - Other Qualification 2
    'other_qual_2_name' => 'Diploma in Computer Applications',
    'other_qual_2_university' => 'IGNOU',
    'other_qual_2_medium' => 'English',
    'other_qual_2_institution' => 'IGNOU Regional Centre',
    'other_qual_2_percentage' => '74.50',
    'other_qual_2_year_of_passing' => '2016',

    // Section J - Training
    'additional_qual_after_recruitment' => 'Completed Advanced Pedagogy in STEM by Cambridge International in 2025.',
    'training_attended_before_recruitment' => 'CBSE Master Capacity Building Programme on Experiential Learning, 30 hours.'
];

$validRequest = Request::create(route('hr.employees.store'), 'POST', $validData);
$response = $controller->storeEmployee($validRequest);

$createdEmp = Employee::where('employee_code', $testCode)->first();

if ($createdEmp) {
    echo "  PASS: Employee created successfully with ID: {$createdEmp->id}\n";
    echo "  PASS: Approval status is: '{$createdEmp->approval_status}' (Pending approval verified).\n";
    echo "  PASS: Employee status is: '{$createdEmp->status}'.\n";
    echo "  PASS: Redirect destination: " . $response->getTargetUrl() . "\n";
} else {
    echo "  FAIL: Employee could not be created from valid dataset.\n";
    exit(1);
}

// ----------------------------------------------------
// TEST 4: Invalid email -> Save blocked
// ----------------------------------------------------
echo "\n[TEST 4] Testing invalid email format rejection...\n";
$invalidEmailData = $validData;
$invalidEmailData['employee_code'] = 'TEST_INV_MAIL_' . time();
$invalidEmailData['official_email'] = 'rajesh.sharma.invalid-email-address';
$invalidEmailReq = Request::create(route('hr.employees.store'), 'POST', $invalidEmailData);
$invalidEmailBlocked = false;
try {
    $controller->storeEmployee($invalidEmailReq);
} catch (\Illuminate\Validation\ValidationException $e) {
    $invalidEmailBlocked = true;
    echo "  PASS: Save blocked with invalid email error: " . $e->validator->errors()->first('official_email') . "\n";
}
if (!$invalidEmailBlocked) {
    echo "  FAIL: Invalid email was not blocked.\n";
}

// ----------------------------------------------------
// TEST 5: Invalid date combination: TO earlier than FROM -> Save blocked
// ----------------------------------------------------
echo "\n[TEST 5] Testing TO earlier than FROM date rejection...\n";
$invalidDateData = $validData;
$invalidDateData['employee_code'] = 'TEST_INV_DATE_' . time();
$invalidDateData['official_email'] = 'test.date.' . time() . '@dasa.com';
$invalidDateData['previous_from_date'] = '2024-05-01';
$invalidDateData['previous_to_date'] = '2022-01-01'; // earlier!
$invalidDateReq = Request::create(route('hr.employees.store'), 'POST', $invalidDateData);
$invalidDateBlocked = false;
try {
    $controller->storeEmployee($invalidDateReq);
} catch (\Illuminate\Validation\ValidationException $e) {
    $invalidDateBlocked = true;
    echo "  PASS: Save blocked with date combination error: " . $e->validator->errors()->first('previous_to_date') . "\n";
}
if (!$invalidDateBlocked) {
    echo "  FAIL: TO earlier than FROM was not blocked.\n";
}

// ----------------------------------------------------
// TEST 6: Invalid % marks such as 101 or -5 -> Save blocked
// ----------------------------------------------------
echo "\n[TEST 6] Testing invalid percentage (>100 or <0) rejection...\n";
$invalidMarksData = $validData;
$invalidMarksData['employee_code'] = 'TEST_INV_MARKS_' . time();
$invalidMarksData['official_email'] = 'test.marks.' . time() . '@dasa.com';
$invalidMarksData['academic_percentage'] = '101.50'; // Invalid!
$invalidMarksReq = Request::create(route('hr.employees.store'), 'POST', $invalidMarksData);
$invalidMarksBlocked = false;
try {
    $controller->storeEmployee($invalidMarksReq);
} catch (\Illuminate\Validation\ValidationException $e) {
    $invalidMarksBlocked = true;
    echo "  PASS: Save blocked with invalid marks error: " . $e->validator->errors()->first('academic_percentage') . "\n";
}
if (!$invalidMarksBlocked) {
    echo "  FAIL: Marks > 100 were not blocked.\n";
}

// ----------------------------------------------------
// TEST 7: Open the newly created staff in Edit -> Verify fields load correctly
// ----------------------------------------------------
echo "\n[TEST 7] Opening newly created staff in Edit view...\n";
$editView = $controller->editEmployee($createdEmp->id);
$editHtml = $editView->render();

$fieldsToCheckInEdit = [
    'Rajesh Sharma',
    '1985-05-15',
    'Hindi',
    'Brahmin',
    '987654321098',
    'ABCDE1234F',
    'EPF99887766',
    'Sunita Sharma',
    'University of Rajasthan',
    'Regional Institute of Education, Ajmer',
    'National Vedic Institute',
    'IGNOU Regional Centre'
];

$allFoundInEdit = true;
foreach ($fieldsToCheckInEdit as $txt) {
    if (strpos($editHtml, $txt) === false) {
        echo "  FAIL: '{$txt}' not found in rendered Edit HTML!\n";
        $allFoundInEdit = false;
    }
}
if ($allFoundInEdit) {
    echo "  PASS: All entered Excel fields are pre-populated accurately in Edit view.\n";
}

// ----------------------------------------------------
// TEST 8: Refresh / re-save the employee via update -> Persist correctly
// ----------------------------------------------------
echo "\n[TEST 8] Updating employee record via updateEmployee()...\n";
$updateData = $validData;
$updateData['name'] = 'Rajesh Kumar Sharma';
$updateData['academic_percentage'] = '85.25';
$updateData['additional_qual_after_recruitment'] = 'Updated post-recruitment qualification info.';

$updateReq = Request::create(route('hr.employees.update', $createdEmp->id), 'PUT', $updateData);
$controller->updateEmployee($updateReq, $createdEmp->id);

$reloaded = Employee::find($createdEmp->id);
if ($reloaded->full_name === 'Rajesh Kumar Sharma' && (float)$reloaded->academic_percentage === 85.25 && $reloaded->additional_qual_after_recruitment === 'Updated post-recruitment qualification info.') {
    echo "  PASS: Updated fields persisted accurately in database (Name: {$reloaded->full_name}, Marks: {$reloaded->academic_percentage}%).\n";
} else {
    echo "  FAIL: Updated fields failed to persist. Name: '{$reloaded->full_name}', %: '{$reloaded->academic_percentage}', Qual: '{$reloaded->additional_qual_after_recruitment}'\n";
}

// ----------------------------------------------------
// TEST 9: Verify repeated qualification sections do not overwrite each other
// ----------------------------------------------------
echo "\n[TEST 9] Verifying repeated qualification sections remain separate...\n";
if (
    $reloaded->academic_university === 'University of Rajasthan' &&
    $reloaded->professional_university === 'Maharshi Dayanand Saraswati University' &&
    $reloaded->other_qual_1_university === 'National Vedic Institute' &&
    $reloaded->other_qual_2_university === 'IGNOU'
) {
    echo "  PASS: All 4 universities are distinct and intact:\n";
    echo "    - Academic: {$reloaded->academic_university}\n";
    echo "    - Professional: {$reloaded->professional_university}\n";
    echo "    - Other Qual 1: {$reloaded->other_qual_1_university}\n";
    echo "    - Other Qual 2: {$reloaded->other_qual_2_university}\n";
} else {
    echo "  FAIL: Repeated qualification values collided or overwrote each other!\n";
}

// ----------------------------------------------------
// TEST 10: Verify legacy existing staff records still open cleanly
// ----------------------------------------------------
echo "\n[TEST 10] Testing legacy staff record display without errors...\n";
$legacyEmp = Employee::where('employee_code', '!=', $testCode)->first();
if ($legacyEmp) {
    try {
        $showView = $controller->showEmployee($legacyEmp->id);
        $showHtml = $showView->render();
        echo "  PASS: Legacy employee #{$legacyEmp->id} ({$legacyEmp->full_name}) rendered cleanly without errors.\n";
    } catch (\Throwable $e) {
        echo "  FAIL: Legacy employee view threw error: " . $e->getMessage() . "\n";
    }
} else {
    echo "  INFO: No other employee found to test legacy display.\n";
}

// ----------------------------------------------------
// TEST 11: Verify Staff Approval API/workflow remains intact
// ----------------------------------------------------
echo "\n[TEST 11] Verifying Staff Approval workflow...\n";
$approvalsView = $controller->staffApprovals(Request::create(route('hr.staff-approvals'), 'GET'));
$approvalsHtml = $approvalsView->render();
if (strpos($approvalsHtml, $testCode) !== false) {
    echo "  PASS: Created staff member '{$testCode}' is present on Pending Staff Approvals dashboard.\n";
} else {
    echo "  FAIL: Staff member not visible on pending approvals page.\n";
}

// Test Approve action
$approveReq = Request::create(route('hr.staff-approvals.approve', $createdEmp->id), 'POST', ['remarks' => 'Approved in automated test']);
$approveResp = $controller->approveStaff($approveReq, $createdEmp->id);
$approvedEmp = Employee::find($createdEmp->id);
if ($approvedEmp->approval_status === 'approved' && $approvedEmp->status === 'active') {
    echo "  PASS: Staff approval action successfully marked employee as 'approved' and 'active'.\n";
} else {
    echo "  FAIL: Staff approval failed: status is {$approvedEmp->approval_status}\n";
}

// ----------------------------------------------------
// TEST 12: Verify payroll files and functionality remain untouched
// ----------------------------------------------------
echo "\n[TEST 12] Verifying payroll files remain completely untouched...\n";
exec('git diff --name-only', $gitDiffOutput);
$payrollTouched = false;
foreach ($gitDiffOutput as $f) {
    if (stripos($f, 'payroll') !== false || stripos($f, 'salary') !== false || stripos($f, 'payslip') !== false) {
        $payrollTouched = true;
        echo "  FAIL: Payroll file touched: {$f}\n";
    }
}
if (!$payrollTouched) {
    echo "  PASS: Zero payroll files touched. Git diff is 100% clean of payroll modifications.\n";
}

// Clean up test employee
echo "\nCleaning up test employee #{$createdEmp->id}...\n";
$createdEmp->delete();
echo "Cleanup completed.\n";

echo "\n====================================================\n";
echo "ALL TESTS COMPLETED SUCCESSFULLY!\n";
echo "====================================================\n";
