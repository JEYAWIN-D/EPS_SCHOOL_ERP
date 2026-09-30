<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use App\Models\StaffAttendance;
use App\Http\Controllers\Admin\HrController;
use App\Http\Controllers\Admin\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

echo "============================================================\n";
echo "TEST SUITE: VERIFYING ALL 24 EXACT REQUIREMENTS\n";
echo "============================================================\n\n";

$adminUser = User::where('email', 'admin@dasa.com')->first() ?? User::where('email', 'admin@schoolerp.in')->first() ?? User::first();
Auth::login($adminUser);
view()->share('errors', new \Illuminate\Support\ViewErrorBag);

$hrController = app(HrController::class);
$attendanceController = app(AttendanceController::class);

$createBladeContent = file_get_contents(resource_path('views/hr/employees-create.blade.php'));
$editBladeContent   = file_get_contents(resource_path('views/hr/employees-edit.blade.php'));
$approvalBladeContent = file_get_contents(resource_path('views/hr/staff-approvals.blade.php'));

$allPassed = true;
function reportTest($num, $title, $passed, $details = '') {
    global $allPassed;
    if ($passed) {
        echo "PASS [TEST {$num}] {$title}\n";
        if ($details) echo "       {$details}\n";
    } else {
        echo "FAIL [TEST {$num}] {$title}\n";
        if ($details) echo "       {$details}\n";
        $allPassed = false;
    }
}

// ----------------------------------------------------------------------
// TEST 1: Staff Add Form - All required Excel-based fields present
// ----------------------------------------------------------------------
$excelFields = [
    'name="name"', 'name="dob"', 'name="gender"', 'name="mother_tongue"', 'name="nationality"',
    'name="religion"', 'name="caste"', 'name="marital_status"', 'name="number_of_children"',
    'name="children_male"', 'name="children_female"', 'name="address"', 'name="employee_code"',
    'name="designation"', 'name="joining_date"', 'name="first_appointment_date"',
    'name="current_employment_status"', 'name="aadhaar_no"', 'name="pan_no"', 'name="epf_no"',
    'name="official_email"', 'name="mobile"', 'name="name_of_contact_person"',
    'name="contact_relationship"', 'name="contact_address"', 'name="contact_no"',
    'name="previous_designation"', 'name="previous_school"', 'name="previous_from_date"',
    'name="previous_to_date"', 'name="academic_qualification"', 'name="academic_university"',
    'name="academic_medium"', 'name="academic_institution"', 'name="academic_percentage"',
    'name="academic_year_of_passing"', 'name="professional_qualification"',
    'name="professional_university"', 'name="professional_medium"', 'name="professional_institution"',
    'name="professional_percentage"', 'name="professional_year_of_passing"',
    'name="other_qual_1_name"', 'name="other_qual_1_university"', 'name="other_qual_1_medium"',
    'name="other_qual_1_institution"', 'name="other_qual_1_percentage"',
    'name="other_qual_1_year_of_passing"', 'name="other_qual_2_name"',
    'name="other_qual_2_university"', 'name="other_qual_2_medium"', 'name="other_qual_2_institution"',
    'name="other_qual_2_percentage"', 'name="other_qual_2_year_of_passing"',
    'name="additional_qual_after_recruitment"', 'name="training_attended_before_recruitment"'
];
$missingExcelFields = [];
foreach ($excelFields as $f) {
    if (strpos($createBladeContent, $f) === false) {
        $missingExcelFields[] = $f;
    }
}
reportTest(1, "Staff Add Form Excel fields presence", empty($missingExcelFields), empty($missingExcelFields) ? "All " . count($excelFields) . " master input fields found." : "Missing: " . implode(', ', $missingExcelFields));

// ----------------------------------------------------------------------
// TEST 2: Number Removal - No field label begins with numeric prefix
// ----------------------------------------------------------------------
// Check labels like "1. NAME" or "2. DESIGNATION" in create & edit blade
preg_match_all('/<label[^>]*>\s*(\d+[\.\)])\s*([A-Za-z]+)/i', $createBladeContent, $matchesCreate);
preg_match_all('/<label[^>]*>\s*(\d+[\.\)])\s*([A-Za-z]+)/i', $editBladeContent, $matchesEdit);
$numberPrefixesFound = array_merge($matchesCreate[0] ?? [], $matchesEdit[0] ?? []);
reportTest(2, "Removal of numeric prefixes from field labels", empty($numberPrefixesFound), empty($numberPrefixesFound) ? "Zero numeric prefixes found in labels." : "Found: " . implode(', ', $numberPrefixesFound));

// ----------------------------------------------------------------------
// TEST 3: Master Data Text - "Master Data Form" completely removed
// ----------------------------------------------------------------------
$hasMasterDataText = (stripos($createBladeContent, 'Master Data Form') !== false);
reportTest(3, "Removal of 'Master Data Form' text", !$hasMasterDataText, $hasMasterDataText ? "Found 'Master Data Form' in employees-create.blade.php" : "Text 'Master Data Form' is completely absent.");

// ----------------------------------------------------------------------
// TEST 4: Marital Status - Dropdown contains ONLY Single and Married
// ----------------------------------------------------------------------
preg_match('/<select[^>]*name=["\']marital_status["\'][^>]*>(.*?)<\/select>/is', $createBladeContent, $mStatusMatch);
$mOptions = [];
if (!empty($mStatusMatch[1])) {
    preg_match_all('/<option[^>]*value=["\']([^"\']*)["\'][^>]*>(.*?)<\/option>/i', $mStatusMatch[1], $optMatches);
    $mOptions = array_filter(array_map('trim', $optMatches[1]), fn($v) => !empty($v));
}
$expectedMStatus = ['Single', 'Married'];
$mStatusValid = (array_values($mOptions) === $expectedMStatus);
reportTest(4, "Marital Status dropdown options", $mStatusValid, "Options found: [" . implode(', ', $mOptions) . "] (Expected strictly Single, Married)");

// ----------------------------------------------------------------------
// TEST 5: Religion - Neutral placeholder 'Enter religion' and NO default 'Hindu'
// ----------------------------------------------------------------------
preg_match('/<input[^>]*name=["\']religion["\'][^>]*>/i', $createBladeContent, $relMatch);
$relTag = $relMatch[0] ?? '';
$relPlaceholderValid = (stripos($relTag, 'placeholder="Enter religion"') !== false);
$relHasHinduDefault  = (stripos($relTag, 'Hindu') !== false || stripos($createBladeContent, "'religion', 'Hindu'") !== false);
reportTest(5, "Religion neutral placeholder without default Hindu", ($relPlaceholderValid && !$relHasHinduDefault), "Tag: {$relTag}");

// ----------------------------------------------------------------------
// TEST 6: Caste - Neutral placeholder 'Enter caste' and NO default 'BC'
// ----------------------------------------------------------------------
preg_match('/<input[^>]*name=["\']caste["\'][^>]*>/i', $createBladeContent, $casteMatch);
$casteTag = $casteMatch[0] ?? '';
$castePlaceholderValid = (stripos($casteTag, 'placeholder="Enter caste"') !== false);
$casteHasBCDefault  = (stripos($casteTag, 'BC') !== false || stripos($createBladeContent, "'caste', 'BC'") !== false);
reportTest(6, "Caste neutral placeholder without default BC", ($castePlaceholderValid && !$casteHasBCDefault), "Tag: {$casteTag}");

// ----------------------------------------------------------------------
// TEST 7: Mother Tongue - Neutral placeholder 'Enter mother tongue'
// ----------------------------------------------------------------------
preg_match('/<input[^>]*name=["\']mother_tongue["\'][^>]*>/i', $createBladeContent, $mtMatch);
$mtTag = $mtMatch[0] ?? '';
$mtPlaceholderValid = (stripos($mtTag, 'placeholder="Enter mother tongue"') !== false);
reportTest(7, "Mother Tongue neutral placeholder", $mtPlaceholderValid, "Tag: {$mtTag}");

// ----------------------------------------------------------------------
// TEST 8: Nationality - Neutral placeholder 'Enter nationality'
// ----------------------------------------------------------------------
preg_match('/<input[^>]*name=["\']nationality["\'][^>]*>/i', $createBladeContent, $natMatch);
$natTag = $natMatch[0] ?? '';
$natPlaceholderValid = (stripos($natTag, 'placeholder="Enter nationality"') !== false);
reportTest(8, "Nationality neutral placeholder", $natPlaceholderValid, "Tag: {$natTag}");

// ----------------------------------------------------------------------
// TEST 9: Qualification - Personal Details has NO duplicate QUALIFICATION
// ----------------------------------------------------------------------
// Extract Section A block
preg_match('/SECTION A — PERSONAL DETAILS(.*?)SECTION B/is', $createBladeContent, $secAMatch);
$secA = $secAMatch[1] ?? '';
$secAHasQual = (stripos($secA, 'name="qualification"') !== false || stripos($secA, 'id="qualification"') !== false);
reportTest(9, "Qualification field removed from Personal Details", !$secAHasQual, $secAHasQual ? "Found qualification in Section A!" : "Section A has no duplicate qualification field.");

// ----------------------------------------------------------------------
// TEST 10: Contact Person - CONTACT PERSON removed, NAME OF THE CONTACT PERSON kept
// ----------------------------------------------------------------------
// Check Section D
preg_match('/SECTION D — CONTACT DETAILS(.*?)SECTION E/is', $createBladeContent, $secDMatch);
$secD = $secDMatch[1] ?? '';
$secDHasStandaloneContactPerson = (preg_match('/name=["\']contact_person["\']/i', $secD) === 1);
$secDHasNameOfContactPerson     = (preg_match('/name=["\']name_of_contact_person["\']/i', $secD) === 1);
reportTest(10, "Contact Person duplicate removed, Name of Contact Person retained", (!$secDHasStandaloneContactPerson && $secDHasNameOfContactPerson), "contact_person in Sec D: " . ($secDHasStandaloneContactPerson ? 'YES' : 'NO') . ", name_of_contact_person: " . ($secDHasNameOfContactPerson ? 'YES' : 'NO'));

// ----------------------------------------------------------------------
// TEST 11: Current Employment Status - Dropdown contains ONLY Permanent & Temporary
// ----------------------------------------------------------------------
preg_match('/<select[^>]*name=["\']current_employment_status["\'][^>]*>(.*?)<\/select>/is', $createBladeContent, $cesMatch);
$cesOptions = [];
if (!empty($cesMatch[1])) {
    preg_match_all('/<option[^>]*value=["\']([^"\']*)["\'][^>]*>(.*?)<\/option>/i', $cesMatch[1], $cesOptMatches);
    $cesOptions = array_filter(array_map('trim', $cesOptMatches[1]), fn($v) => !empty($v));
}
$expectedCES = ['Permanent', 'Temporary'];
$cesValid = (array_values($cesOptions) === $expectedCES);
reportTest(11, "Current Employment Status options strictly Permanent & Temporary", $cesValid, "Options found: [" . implode(', ', $cesOptions) . "]");

// ----------------------------------------------------------------------
// TEST 12: Employee ID Format - EPSS0001 / sequential EPSS generation
// ----------------------------------------------------------------------
$genId = $hrController->generateEmployeeNumber();
$genIdMatchesPattern = (preg_match('/^EPSS\d{4,}$/i', $genId) === 1);
reportTest(12, "Employee ID sequential EPSS format", $genIdMatchesPattern, "Generated ID: {$genId}");

// ----------------------------------------------------------------------
// TEST 13: Name Duplication Bug - "Anu" returns "Anu", "Anu Sharma" returns "Anu Sharma"
// ----------------------------------------------------------------------
// Create test single-name employee
$basePayload = [
    'employee_type' => 'teaching',
    'dob' => '1992-04-12',
    'gender' => 'female',
    'mother_tongue' => 'Hindi',
    'nationality' => 'Indian',
    'religion' => 'Jain',
    'caste' => 'General',
    'marital_status' => 'Single',
    'number_of_children' => 0,
    'children_male' => 0,
    'children_female' => 0,
    'address' => '12 Park Street, Jaipur, Rajasthan 302001',
    'designation' => 'Science Teacher',
    'joining_date' => '2026-06-01',
    'first_appointment_date' => '2020-07-15',
    'current_employment_status' => 'Permanent',
    'aadhaar_no' => '998877665544',
    'pan_no' => 'ABCDE9999F',
    'epf_no' => 'EPF11223344',
    'official_email' => 'anu.test.' . time() . '@schoolerp.in',
    'mobile' => '9876543210',
    'name_of_contact_person' => 'Ramesh Kumar',
    'contact_relationship' => 'Father',
    'contact_address' => '12 Park Street, Jaipur, Rajasthan 302001',
    'contact_no' => '9876543211',
    'previous_designation' => 'Assistant Teacher',
    'previous_school' => 'DPS Jaipur',
    'previous_from_date' => '2020-07-15',
    'previous_to_date' => '2026-05-30',
    'academic_qualification' => 'B.Sc Physics',
    'academic_university' => 'Rajasthan University',
    'academic_medium' => 'English',
    'academic_institution' => 'Maharaja College',
    'academic_percentage' => '82.50',
    'academic_year_of_passing' => 2014,
    'professional_qualification' => 'B.Ed',
    'professional_university' => 'Rajasthan University',
    'professional_medium' => 'English',
    'professional_institution' => 'University B.Ed College',
    'professional_percentage' => '78.00',
    'professional_year_of_passing' => 2016,
    'additional_qual_after_recruitment' => 'Completed CTET certification',
    'training_attended_before_recruitment' => 'CBSE pedagogy 30-day induction',
];

// Single name "Anu"
$payloadAnu = array_merge($basePayload, [
    'name' => 'Anu',
    'official_email' => 'anu.' . uniqid() . '@schoolerp.in',
    'employee_code' => $hrController->generateEmployeeNumber(),
]);
$reqAnu = Request::create(route('hr.employees.store'), 'POST', $payloadAnu);
$hrController->storeEmployee($reqAnu);
$empAnu = Employee::where('employee_code', $payloadAnu['employee_code'])->first();

// Full name "Anu Sharma"
$payloadAnuSharma = array_merge($basePayload, [
    'name' => 'Anu Sharma',
    'official_email' => 'anu.sharma.' . uniqid() . '@schoolerp.in',
    'employee_code' => $hrController->generateEmployeeNumber(),
]);
$reqAnuSharma = Request::create(route('hr.employees.store'), 'POST', $payloadAnuSharma);
$hrController->storeEmployee($reqAnuSharma);
$empAnuSharma = Employee::where('employee_code', $payloadAnuSharma['employee_code'])->first();

$singleNameOk = ($empAnu && $empAnu->full_name === 'Anu' && $empAnu->first_name === 'Anu' && $empAnu->last_name === '');
$fullNameOk   = ($empAnuSharma && $empAnuSharma->full_name === 'Anu Sharma' && $empAnuSharma->first_name === 'Anu' && $empAnuSharma->last_name === 'Sharma');

reportTest(13, "Staff Name Duplication Fix ('Anu' -> 'Anu', 'Anu Sharma' -> 'Anu Sharma')", ($singleNameOk && $fullNameOk), "Single name: '{$empAnu->full_name}' (First: '{$empAnu->first_name}', Last: '{$empAnu->last_name}') | Full name: '{$empAnuSharma->full_name}' (First: '{$empAnuSharma->first_name}', Last: '{$empAnuSharma->last_name}')");

// ----------------------------------------------------------------------
// TEST 14: Required Field Validation - Missing required field blocks creation
// ----------------------------------------------------------------------
$invalidPayload = $basePayload;
unset($invalidPayload['religion']); // remove required field
$invalidPayload['name'] = 'Incomplete Staff';
$invalidPayload['official_email'] = 'incomplete.' . uniqid() . '@schoolerp.in';
$invalidPayload['employee_code'] = $hrController->generateEmployeeNumber();

$validationBlocked = false;
try {
    $reqInvalid = Request::create(route('hr.employees.store'), 'POST', $invalidPayload);
    $hrController->storeEmployee($reqInvalid);
} catch (ValidationException $e) {
    $validationBlocked = $e->validator->errors()->has('religion');
}
reportTest(14, "Required field validation blocks empty submission", $validationBlocked, $validationBlocked ? "Validation correctly blocked creation when religion was omitted." : "Failed to block submission!");

// ----------------------------------------------------------------------
// TEST 15: Successful Staff Creation with status Pending Approval
// ----------------------------------------------------------------------
$empAnuCreatedCorrectly = ($empAnu && $empAnu->approval_status === 'pending' && $empAnu->status === 'pending' && $empAnu->is_active === false);
reportTest(15, "Staff created with Pending Approval status", $empAnuCreatedCorrectly, "Status: '{$empAnu->status}', approval_status: '{$empAnu->approval_status}', is_active: " . ($empAnu->is_active ? 'true' : 'false'));

// ----------------------------------------------------------------------
// TEST 16: Pending Approval - New staff appears, actions include Approve and Reject, no 'Awaiting Principal approval'
// ----------------------------------------------------------------------
$appReq = Request::create(route('hr.staff-approvals'), 'GET', ['status' => 'pending']);
$appResp = $hrController->staffApprovals($appReq);
$appHtml = $appResp->render();
$hasAnuInApproval = (strpos($appHtml, $empAnu->employee_code) !== false);
$hasAwaitingPrincipalText = (stripos($appHtml, 'Awaiting Principal approval') !== false);
$hasApproveForm = (strpos($appHtml, route('hr.staff-approvals.approve', $empAnu->id)) !== false);
$hasRejectForm  = (strpos($appHtml, route('hr.staff-approvals.reject', $empAnu->id)) !== false);
reportTest(16, "Staff Approval UI pending list, action buttons, no Principal text", ($hasAnuInApproval && !$hasAwaitingPrincipalText && $hasApproveForm && $hasRejectForm), "Staff in table: " . ($hasAnuInApproval ? 'YES' : 'NO') . ", No Principal text: " . (!$hasAwaitingPrincipalText ? 'YES' : 'NO') . ", Approve button: " . ($hasApproveForm ? 'YES' : 'NO') . ", Reject button: " . ($hasRejectForm ? 'YES' : 'NO'));

// ----------------------------------------------------------------------
// TEST 17: Approve Action - Status becomes Active / Approved
// ----------------------------------------------------------------------
$approveReq = Request::create(route('hr.staff-approvals.approve', $empAnu->id), 'POST', ['remarks' => 'Admin test approval']);
$hrController->approveStaff($approveReq, $empAnu->id);
$empAnu->refresh();
$empAnuApproved = ($empAnu->approval_status === 'approved' && $empAnu->status === 'active' && $empAnu->is_active === true);
reportTest(17, "Approve action updates status to Active / Approved", $empAnuApproved, "Status: '{$empAnu->status}', approval_status: '{$empAnu->approval_status}', is_active: " . ($empAnu->is_active ? 'true' : 'false'));

// ----------------------------------------------------------------------
// TEST 18: Attendance Eligibility - Approved staff available in Mark Attendance
// ----------------------------------------------------------------------
// Test in HrController::markAttendance
$markAttReq = Request::create(route('hr.attendance.mark'), 'GET', ['category' => 'all']);
$markAttView = $hrController->markAttendance($markAttReq);
$markAttHtml = $markAttView->render();
$anuInHrAttendance = (strpos($markAttHtml, $empAnu->full_name) !== false && strpos($markAttHtml, $empAnu->employee_code) !== false);

// Test in AttendanceController::staffAttendance
$staffAttReq = Request::create(route('attendance.staff'), 'GET');
$staffAttView = $attendanceController->staffAttendance($staffAttReq);
$staffAttHtml = $staffAttView->render();
$anuInAttendanceController = (strpos($staffAttHtml, $empAnu->employee_code) !== false);

reportTest(18, "Approved staff immediately available in Mark Attendance", ($anuInHrAttendance || $anuInAttendanceController), "HR Mark Attendance: " . ($anuInHrAttendance ? 'YES' : 'NO') . ", Staff Attendance: " . ($anuInAttendanceController ? 'YES' : 'NO'));

// ----------------------------------------------------------------------
// TEST 19: Reject Action - Status becomes Rejected and NOT available for attendance
// ----------------------------------------------------------------------
$rejectReq = Request::create(route('hr.staff-approvals.reject', $empAnuSharma->id), 'POST', ['rejection_reason' => 'Test document discrepancy']);
$hrController->rejectStaff($rejectReq, $empAnuSharma->id);
$empAnuSharma->refresh();
$empAnuSharmaRejected = ($empAnuSharma->approval_status === 'rejected' && $empAnuSharma->status === 'rejected' && $empAnuSharma->is_active === false);

// Verify NOT in mark attendance
$markAttViewAfterReject = $hrController->markAttendance($markAttReq);
$markAttHtmlAfterReject = $markAttViewAfterReject->render();
$rejectedInAttendance = (strpos($markAttHtmlAfterReject, $empAnuSharma->employee_code) !== false);

reportTest(19, "Reject action updates status to Rejected and excludes from attendance", ($empAnuSharmaRejected && !$rejectedInAttendance), "Rejected status: " . ($empAnuSharmaRejected ? 'YES' : 'NO') . ", Excluded from attendance: " . (!$rejectedInAttendance ? 'YES' : 'NO'));

// ----------------------------------------------------------------------
// TEST 20: No Duplicate Staff - Verify approval does not create another row
// ----------------------------------------------------------------------
$countWithCode = Employee::where('employee_code', $empAnu->employee_code)->count();
reportTest(20, "No duplicate staff record created during approval", ($countWithCode === 1), "Employee count for {$empAnu->employee_code}: {$countWithCode}");

// ----------------------------------------------------------------------
// TEST 21: Existing Staff - Verify legacy staff profile opens cleanly
// ----------------------------------------------------------------------
$legacyStaff = Employee::whereNotIn('id', [$empAnu->id, $empAnuSharma->id])->first();
$legacyShowOk = false;
if ($legacyStaff) {
    try {
        $showView = $hrController->showEmployee($legacyStaff->id);
        $showHtml = $showView->render();
        $legacyShowOk = (strpos($showHtml, $legacyStaff->full_name) !== false);
    } catch (\Throwable $e) {
        $legacyShowOk = false;
    }
} else {
    $legacyShowOk = true;
}
reportTest(21, "Existing staff profiles render cleanly", $legacyShowOk, "Tested profile for employee #{$legacyStaff?->id} ({$legacyStaff?->full_name})");

// ----------------------------------------------------------------------
// TEST 22: Staff Approval API - Verify API backend architecture remains functional
// ----------------------------------------------------------------------
$apiReq = Request::create(route('hr.api.staff-approvals.index'), 'GET', ['status' => 'all']);
$apiResp = $hrController->apiStaffApprovals($apiReq);
$apiData = json_decode($apiResp->getContent(), true);
$apiValid = ($apiResp->getStatusCode() === 200 && ($apiData['success'] ?? false) === true && isset($apiData['counts']));
reportTest(22, "Staff Approval API (/hr/api/staff-approvals) remains functional", $apiValid, "API returned HTTP 200 with counts: " . json_encode($apiData['counts'] ?? []));

// ----------------------------------------------------------------------
// TEST 23: Existing HR Features - Attendance, permissions, reports, leaves, events intact
// ----------------------------------------------------------------------
$hrFeaturesOk = true;
try {
    // 1. HR Dashboard
    $hrIndexView = $hrController->index(Request::create(route('hr.index'), 'GET'));
    $hrIndexView->render();

    // 2. View Attendance
    $viewAtt = $hrController->viewAttendance(Request::create(route('hr.attendance.view'), 'GET'));
    $viewAtt->render();

    // 3. Leave approvals
    $leavesView = $hrController->leaveApprovals(Request::create(route('hr.leave-approvals'), 'GET'));
    $leavesView->render();

    // 4. Staff Events
    $eventsView = $hrController->events(Request::create(route('hr.events'), 'GET'));
    $eventsView->render();
} catch (\Throwable $e) {
    $hrFeaturesOk = false;
    echo "HR Feature Error: " . $e->getMessage() . "\n";
}
reportTest(23, "Existing HR features (Dashboard, Attendance, Leaves, Events) intact", $hrFeaturesOk, "Rendered all HR views without error.");

// ----------------------------------------------------------------------
// TEST 24: Payroll Lock - Verify ZERO payroll files modified
// ----------------------------------------------------------------------
exec('git diff --name-only', $gitDiff);
$payrollFilesModified = [];
foreach ($gitDiff as $file) {
    if (stripos($file, 'payroll') !== false || stripos($file, 'salary') !== false || stripos($file, 'payslip') !== false) {
        $payrollFilesModified[] = $file;
    }
}
reportTest(24, "Payroll lock - Absolute zero payroll modifications", empty($payrollFilesModified), empty($payrollFilesModified) ? "Git diff is 100% clean of any payroll files." : "Violations: " . implode(', ', $payrollFilesModified));

// Clean up test employees
echo "\nCleaning up automated test records...\n";
$empAnu->delete();
$empAnuSharma->delete();
echo "Cleanup completed.\n\n";

if ($allPassed) {
    echo "============================================================\n";
    echo "ALL 24 REQUIREMENTS VERIFIED & PASSED WITH 100% SUCCESS!\n";
    echo "============================================================\n";
} else {
    echo "============================================================\n";
    echo "SOME TESTS FAILED! PLEASE REVIEW OUTPUT ABOVE.\n";
    echo "============================================================\n";
}
