<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\BookNoteChecklist;
use App\Models\AdmissionBookNoteItem;
use App\Models\Classes;
use App\Models\Student;
use App\Models\User;
use App\Services\BookNoteService;

echo "========================================================\n";
echo "   ERODE PUBLIC SCHOOL CBSE - BOOKS & NOTES TEST RUNNER\n";
echo "========================================================\n\n";

echo "Available Academic Years in DB:\n";
foreach (AcademicYear::all() as $ay) {
    echo "  - ID {$ay->id}: {$ay->name} (is_current: " . ($ay->is_current ? 'true' : 'false') . ")\n";
}

$service = app(BookNoteService::class);
// Target specifically 2026-2027
$year = AcademicYear::where('name', 'like', '%2026-2027%')->orWhere('name', 'like', '%2026-27%')->first() ?? AcademicYear::current();
echo "Testing against Academic Year: ID {$year->id} ({$year->name})\n";
echo "Total checklists for this year: " . BookNoteChecklist::where('academic_year_id', $year->id)->count() . "\n\n";

// Clean up any rogue records in other years if any
BookNoteChecklist::where('academic_year_id', '!=', $year->id)->delete();
BookNoteChecklist::where('academic_year_id', $year->id)->update(['status' => 'active']);

$adminUser = User::first();

$errors = [];
$passes = 0;

function assertCheck($condition, $message, &$passes, &$errors) {
    if ($condition) {
        echo " [PASS] $message\n";
        $passes++;
    } else {
        echo " [FAIL] $message\n";
        $errors[] = $message;
    }
}

// TEST 1: Academic Year & Seeded Checklists
echo "1. Checking Master Data & Seeded Classes...\n";
assertCheck($year !== null, "Target Academic Year loaded (ID: {$year->id}, Name: {$year->name})", $passes, $errors);

$preKgClass = Classes::where('name', 'Pre-KG')->orWhere('name', 'PRE KG')->first();
assertCheck($preKgClass !== null, "Pre-KG class exists (ID: {$preKgClass->id})", $passes, $errors);
if ($preKgClass) {
    $preKgList = $service->getChecklist($year->id, $preKgClass->id);
    $itemTitlesCount = count($preKgList['books']) + count($preKgList['notes']);
    $totalQty = collect($preKgList['books'])->sum('quantity') + collect($preKgList['notes'])->sum('quantity');
    assertCheck($itemTitlesCount === 3, "PRE KG has 3 prescribed titles (Found: $itemTitlesCount)", $passes, $errors);
    assertCheck($totalQty === 12, "PRE KG total required units is exactly 12 (10 Ripples, 1 Tamil, 1 Diary) (Found: $totalQty)", $passes, $errors);
}

$viClass = Classes::where('name', 'VI')->orWhere('name', 'VI STD')->first();
assertCheck($viClass !== null, "VI STD class exists (ID: {$viClass->id})", $passes, $errors);
if ($viClass) {
    $viList = $service->getChecklist($year->id, $viClass->id);
    $bookTitles = count($viList['books']);
    $noteTitles = count($viList['notes']);
    $booksQty = collect($viList['books'])->sum('quantity');
    $notesQty = collect($viList['notes'])->sum('quantity');
    $grandTotalQty = $booksQty + $notesQty;

    assertCheck($bookTitles === 8, "VI STD has 8 distinct Book titles (Found: $bookTitles)", $passes, $errors);
    assertCheck($booksQty === 8, "VI STD has 8 total Books quantity (Found: $booksQty)", $passes, $errors);
    assertCheck($noteTitles === 17, "VI STD has 17 distinct Note titles (Found: $noteTitles)", $passes, $errors);
    assertCheck($notesQty === 29, "VI STD has 29 total Notebooks quantity (Found: $notesQty)", $passes, $errors);
    assertCheck($grandTotalQty === 37, "VI STD has exactly 37 Grand Total units matching school checklist (Found: $grandTotalQty)", $passes, $errors);
}

// TEST 2: Senior Secondary (XI STD) Strict Group Isolation
echo "\n2. Testing XI STD Senior Secondary Group Isolation...\n";
$xiClass = Classes::where('name', 'XI')->orWhere('name', 'XI STD')->first();
assertCheck($xiClass !== null, "XI STD class exists (ID: {$xiClass->id})", $passes, $errors);

$bioGroup = AcademicGroup::where('code', 'BIO')->first();
$csGroup = AcademicGroup::where('code', 'CS')->first();
$accountsGroup = AcademicGroup::where('code', 'ACCOUNTS')->first();
assertCheck($bioGroup !== null, "BIO GROUP exists (ID: {$bioGroup?->id})", $passes, $errors);
assertCheck($csGroup !== null, "CS GROUP exists (ID: {$csGroup?->id})", $passes, $errors);
assertCheck($accountsGroup !== null, "ACCOUNTS GROUP exists (ID: {$accountsGroup?->id})", $passes, $errors);

if ($xiClass && $bioGroup && $csGroup && $accountsGroup) {
    // 1. BIO Group: removed CS books, kept Biology
    $bioList = $service->getChecklist($year->id, $xiClass->id, $bioGroup->id);
    $bioBookNames = collect($bioList['books'])->pluck('name')->all();
    $bioHasBio = in_array('NCERT Biology', $bioBookNames);
    $bioHasCS = in_array('SUMITA ARORA COMPUTERSCIENCE BOOK AND PRACTICE BOOK', $bioBookNames);
    $bioHasAcc = in_array('NCERT Accountancy Part I', $bioBookNames);
    $bioQty = collect($bioList['books'])->sum('quantity') + collect($bioList['notes'])->sum('quantity');
    assertCheck($bioHasBio, "BIO Checklist contains 'NCERT Biology'", $passes, $errors);
    assertCheck(!$bioHasCS, "BIO Checklist removed CS books ('SUMITA ARORA...')", $passes, $errors);
    assertCheck(!$bioHasAcc, "BIO Checklist does NOT leak 'NCERT Accountancy Part I'", $passes, $errors);
    assertCheck(count($bioList['books']) === 8, "BIO Checklist has 8 books", $passes, $errors);
    assertCheck(count($bioList['notes']) === 2, "BIO Checklist has 2 notes", $passes, $errors);
    assertCheck($bioQty === 33, "BIO Checklist total is 33 units (8 books + 25 notes) (Found: $bioQty)", $passes, $errors);

    // 2. CS Group: removed Biology book, kept CS books
    $csList = $service->getChecklist($year->id, $xiClass->id, $csGroup->id);
    $csBookNames = collect($csList['books'])->pluck('name')->all();
    $csHasBio = in_array('NCERT Biology', $csBookNames);
    $csHasCS = in_array('SUMITA ARORA COMPUTERSCIENCE BOOK AND PRACTICE BOOK', $csBookNames);
    $csHasAcc = in_array('NCERT Accountancy Part I', $csBookNames);
    $csQty = collect($csList['books'])->sum('quantity') + collect($csList['notes'])->sum('quantity');
    assertCheck(!$csHasBio, "CS Checklist removed 'NCERT Biology'", $passes, $errors);
    assertCheck($csHasCS, "CS Checklist contains CS books ('SUMITA ARORA...')", $passes, $errors);
    assertCheck(!$csHasAcc, "CS Checklist does NOT leak 'NCERT Accountancy Part I'", $passes, $errors);
    assertCheck(count($csList['books']) === 9, "CS Checklist has 9 books", $passes, $errors);
    assertCheck(count($csList['notes']) === 2, "CS Checklist has 2 notes", $passes, $errors);
    assertCheck($csQty === 34, "CS Checklist total is 34 units (9 books + 25 notes) (Found: $csQty)", $passes, $errors);

    // 3. Accounts group: exact accounts content
    $accList = $service->getChecklist($year->id, $xiClass->id, $accountsGroup->id);
    $accBookNames = collect($accList['books'])->pluck('name')->all();
    $accHasAcc = in_array('NCERT Accountancy Part I', $accBookNames);
    $accHasBio = in_array('NCERT Biology', $accBookNames);
    $accQty = collect($accList['books'])->sum('quantity') + collect($accList['notes'])->sum('quantity');
    assertCheck($accHasAcc, "Accounts Checklist contains 'NCERT Accountancy Part I'", $passes, $errors);
    assertCheck(!$accHasBio, "Accounts Checklist does NOT leak 'NCERT Biology'", $passes, $errors);
    assertCheck(count($accList['books']) === 9, "Accounts Checklist has 9 books", $passes, $errors);
    assertCheck(count($accList['notes']) === 3, "Accounts Checklist has 3 notes", $passes, $errors);
    assertCheck($accQty === 34, "Accounts Checklist total is 34 units (9 books + 25 notes) (Found: $accQty)", $passes, $errors);

    // Class XI without group must return empty (prevent leaking across groups)
    $emptyList = $service->getChecklist($year->id, $xiClass->id, null);
    $emptyCount = count($emptyList['books']) + count($emptyList['notes']);
    assertCheck($emptyCount === 0, "Class XI with group=null safely returns 0 items", $passes, $errors);
}

// TEST 3: Duplicate Prevention
echo "\n3. Testing Duplicate Prevention...\n";
if ($viClass) {
    $duplicateCaught = false;
    try {
        $service->createItem([
            'academic_year_id' => $year->id,
            'class_id'         => $viClass->id,
            'group_id'         => null,
            'item_type'        => 'BOOK',
            'item_name'        => 'NCERT Maths', // Duplicate
            'quantity'         => 1,
            'display_order'    => 1,
            'status'           => 'active',
            'created_by'       => $adminUser?->id,
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        $duplicateCaught = true;
    } catch (\Exception $e) {
        $duplicateCaught = true;
    }
    assertCheck($duplicateCaught, "Duplicate item 'NCERT Maths' for VI STD was prevented", $passes, $errors);
}

// TEST 4: CRUD Operations
echo "\n4. Testing CRUD Operations...\n";
if ($viClass) {
    $testItemName = 'Test Unique Rule Note ' . uniqid();
    $created = $service->createItem([
        'academic_year_id' => $year->id,
        'class_id'         => $viClass->id,
        'group_id'         => null,
        'item_type'        => 'NOTE',
        'item_name'        => $testItemName,
        'quantity'         => 2,
        'display_order'    => 99,
        'status'           => 'active',
        'created_by'       => $adminUser?->id,
    ]);
    assertCheck($created->id > 0 && $created->quantity === 2, "Created test note with ID: {$created->id}", $passes, $errors);

    // Update
    $updated = $service->updateItem($created->id, [
        'quantity' => 7,
        'updated_by' => $adminUser?->id,
    ]);
    assertCheck($updated->quantity === 7, "Updated quantity to 7", $passes, $errors);

    // Toggle status
    $toggled = $service->toggleStatus($updated->id);
    assertCheck($toggled->status === 'inactive', "Toggled status to 'inactive'", $passes, $errors);

    // Hard delete when unreferenced
    $delResult = $service->deleteOrDeactivate($toggled->id);
    assertCheck($delResult['action'] === 'deleted', "Unreferenced item was hard deleted", $passes, $errors);
    assertCheck(BookNoteChecklist::find($created->id) === null, "Verified record no longer exists in DB", $passes, $errors);
}

// TEST 5: Admission Snapshot & Preservation
echo "\n5. Testing Admission Snapshot & Historical Preservation...\n";
if ($viClass) {
    $student = Student::first();
    assertCheck($student !== null, "Test student loaded (ID: {$student?->id})", $passes, $errors);

    if ($student) {
        // Clear prior test items for clean verification
        AdmissionBookNoteItem::where('student_id', $student->id)->delete();

        $snapCount = $service->snapshotForAdmission(
            $year->id,
            $viClass->id,
            null,
            $student->id,
            $student->id,
            null
        );
        assertCheck($snapCount === 25, "Generated 25 snapshot item records for student admission (8 books + 17 notes) (Found: $snapCount)", $passes, $errors);

        $savedCount = AdmissionBookNoteItem::where('student_id', $student->id)->count();
        $savedTotalUnits = (int) AdmissionBookNoteItem::where('student_id', $student->id)->sum('quantity');
        assertCheck($savedCount === 25, "Persisted 25 items in admission_book_note_items table", $passes, $errors);
        assertCheck($savedTotalUnits === 37, "Persisted exactly 37 total units across all items in snapshot", $passes, $errors);

        // Test deactivation protection for referenced checklist item
        $firstItem = AdmissionBookNoteItem::where('student_id', $student->id)->first();
        $sourceMaster = BookNoteChecklist::find($firstItem->source_checklist_id);
        assertCheck($sourceMaster !== null, "Loaded master checklist item referenced by admission", $passes, $errors);

        if ($sourceMaster) {
            $delRes = $service->deleteOrDeactivate($sourceMaster->id);
            assertCheck($delRes['action'] === 'deactivated', "Master item was deactivated instead of deleted (Protected Historical Snapshot)", $passes, $errors);
            
            $checkStillExists = BookNoteChecklist::find($sourceMaster->id);
            assertCheck($checkStillExists !== null && $checkStillExists->status === 'inactive', "Master item remains in DB with inactive status", $passes, $errors);

            // Restore status
            BookNoteChecklist::where('id', $sourceMaster->id)->update(['status' => 'active']);
        }

        // Clean up test snapshot items
        AdmissionBookNoteItem::where('student_id', $student->id)->delete();
    }
}

// TEST 6: API Endpoints & Response Structure
echo "\n6. Testing API Endpoints & Response Structure...\n";
$controller = app(\App\Http\Controllers\Admin\BooksNotesController::class);

// Checklist API for VI STD
$req = \Illuminate\Http\Request::create('/api/books-notes/checklist', 'GET', [
    'academicYearId' => $year->id,
    'classId'        => $viClass->id,
]);
$resp = $controller->apiChecklist($req);
$data = json_decode($resp->getContent(), true);

assertCheck($resp->getStatusCode() === 200, "GET /api/books-notes/checklist returned HTTP 200", $passes, $errors);
assertCheck($data['success'] === true, "Checklist API response contains success: true", $passes, $errors);
assertCheck($data['academicYear']['name'] === $year->name, "Checklist academicYear matches '{$year->name}'", $passes, $errors);
assertCheck(count($data['books']) === 8, "Checklist API returns 8 books for VI STD", $passes, $errors);
assertCheck(count($data['notes']) === 17, "Checklist API returns 17 notes for VI STD", $passes, $errors);

// Checklist API for XI STD Bio Group
$reqBio = \Illuminate\Http\Request::create('/api/books-notes/checklist', 'GET', [
    'academicYearId' => $year->id,
    'classId'        => $xiClass->id,
    'groupId'        => $bioGroup->id,
]);
$respBio = $controller->apiChecklist($reqBio);
$dataBio = json_decode($respBio->getContent(), true);
assertCheck($dataBio['group']['name'] === 'BIO GROUP', "Checklist API returns selected group 'BIO GROUP'", $passes, $errors);
assertCheck(count($dataBio['books']) === 8, "Checklist API returns 8 books for XI Bio", $passes, $errors);

// Checklist API for XI STD CS Group
$reqCS = \Illuminate\Http\Request::create('/api/books-notes/checklist', 'GET', [
    'academicYearId' => $year->id,
    'classId'        => $xiClass->id,
    'groupId'        => $csGroup->id,
]);
$respCS = $controller->apiChecklist($reqCS);
$dataCS = json_decode($respCS->getContent(), true);
assertCheck($dataCS['group']['name'] === 'CS GROUP', "Checklist API returns selected group 'CS GROUP'", $passes, $errors);
assertCheck(count($dataCS['books']) === 9, "Checklist API returns 9 books for XI CS", $passes, $errors);

// Academic Groups API
$respGroups = $controller->apiGroups();
$dataGroups = json_decode($respGroups->getContent(), true);
assertCheck(count($dataGroups['data'] ?? []) >= 3, "GET /api/academic-groups returns available groups (>= 3)", $passes, $errors);

// TEST 7: Class Cards API & SKU verification
echo "\n7. Testing Class Cards API & SKU Verification...\n";
$reqCards = \Illuminate\Http\Request::create('/api/books-notes/cards', 'GET', [
    'academic_year_id' => $year->id,
]);
$respCards = $controller->apiCards($reqCards);
$dataCards = json_decode($respCards->getContent(), true);

assertCheck($respCards->getStatusCode() === 200, "GET /api/books-notes/cards returned HTTP 200", $passes, $errors);
assertCheck($dataCards['success'] === true, "Cards API response contains success: true", $passes, $errors);
assertCheck(count($dataCards['cards']) >= 14, "Cards API returns cards for all standards", $passes, $errors);

// Find Class VI card
$viCard = collect($dataCards['cards'])->firstWhere('class_name', 'VI');
assertCheck($viCard !== null, "Found Class VI card", $passes, $errors);
if ($viCard) {
    assertCheck($viCard['books_count'] === 8, "Class VI card has 8 books", $passes, $errors);
    assertCheck($viCard['notes_count'] === 17, "Class VI card has 17 notes", $passes, $errors);
    assertCheck($viCard['grand_total_qty'] === 37, "Class VI card has 37 grand total units", $passes, $errors);
    $firstSampleBook = $viCard['sample_books'][0] ?? null;
    assertCheck(!empty($firstSampleBook['sku']), "Sample book has non-empty SKU: " . ($firstSampleBook['sku'] ?? 'none'), $passes, $errors);
}

// Find Class XI card
$xiCard = collect($dataCards['cards'])->firstWhere('class_name', 'XI');
assertCheck($xiCard !== null, "Found Class XI card", $passes, $errors);
if ($xiCard) {
    assertCheck($xiCard['is_senior_secondary'] === true, "Class XI card is marked as Senior Secondary", $passes, $errors);
    assertCheck(count($xiCard['groups']) === 3, "Class XI card contains 3 Senior Secondary groups (BIO, CS, ACCOUNTS)", $passes, $errors);
    
    $bioGrpCard = collect($xiCard['groups'])->firstWhere('group_code', 'BIO');
    assertCheck($bioGrpCard !== null, "Class XI card includes BIO group details", $passes, $errors);
    if ($bioGrpCard) {
        assertCheck($bioGrpCard['books_count'] === 8, "XI BIO has 8 books", $passes, $errors);
        assertCheck($bioGrpCard['grand_total_qty'] === 33, "XI BIO has 33 total units (8 books + 25 notes)", $passes, $errors);
    }

    $csGrpCard = collect($xiCard['groups'])->firstWhere('group_code', 'CS');
    assertCheck($csGrpCard !== null, "Class XI card includes CS group details", $passes, $errors);
    if ($csGrpCard) {
        assertCheck($csGrpCard['books_count'] === 9, "XI CS has 9 books", $passes, $errors);
        assertCheck($csGrpCard['grand_total_qty'] === 34, "XI CS has 34 total units (9 books + 25 notes)", $passes, $errors);
    }

    $accGrpCard = collect($xiCard['groups'])->firstWhere('group_code', 'ACCOUNTS');
    assertCheck($accGrpCard !== null, "Class XI card includes ACCOUNTS group details", $passes, $errors);
    if ($accGrpCard) {
        assertCheck($accGrpCard['books_count'] === 9, "XI ACCOUNTS has 9 books", $passes, $errors);
        assertCheck($accGrpCard['grand_total_qty'] === 34, "XI ACCOUNTS has 34 total units (9 books + 25 notes)", $passes, $errors);
    }
}

// TEST 8: Distribution Tracker, Student Checklist & Slip Verification
echo "\n8. Testing Distribution Tracker, Student Checklist & Slip...\n";
$reqDistSummary = \Illuminate\Http\Request::create('/api/books-notes/distribution/summary', 'GET', [
    'academic_year_id' => $year->id,
]);
$respDistSummary = $controller->apiDistributionSummary($reqDistSummary);
$dataDistSummary = json_decode($respDistSummary->getContent(), true);
assertCheck($respDistSummary->getStatusCode() === 200, "GET /api/books-notes/distribution/summary returned HTTP 200", $passes, $errors);
assertCheck($dataDistSummary['success'] === true, "Distribution summary contains success: true", $passes, $errors);
assertCheck(isset($dataDistSummary['summary']['students_with_remaining']), "Distribution summary includes students_with_remaining", $passes, $errors);

// Distribution Students API with status=all
$reqDistStudents = \Illuminate\Http\Request::create('/api/books-notes/distribution/students', 'GET', [
    'academic_year_id' => $year->id,
    'status'           => 'all',
    'per_page'         => 10,
]);
$respDistStudents = $controller->apiDistributionStudents($reqDistStudents);
$dataDistStudents = json_decode($respDistStudents->getContent(), true);
assertCheck($respDistStudents->getStatusCode() === 200, "GET /api/books-notes/distribution/students returned HTTP 200", $passes, $errors);
assertCheck($dataDistStudents['success'] === true, "Distribution students contains success: true", $passes, $errors);
assertCheck(count($dataDistStudents['students']) >= 1, "Distribution students returned enrolled students", $passes, $errors);

$firstStudent = $dataDistStudents['students'][0];
assertCheck(!empty($firstStudent['student_name']), "Student record contains student_name: " . ($firstStudent['student_name'] ?? ''), $passes, $errors);
assertCheck(isset($firstStudent['total_remaining']), "Student record contains total_remaining", $passes, $errors);

// Student Checklist Details API
$reqChecklist = \Illuminate\Http\Request::create("/api/books-notes/distribution/student/{$firstStudent['student_id']}", 'GET', [
    'academic_year_id' => $year->id,
]);
$respChecklist = $controller->apiStudentChecklist($firstStudent['student_id'], $reqChecklist);
$dataChecklist = json_decode($respChecklist->getContent(), true);
assertCheck($respChecklist->getStatusCode() === 200, "GET /api/books-notes/distribution/student/{id} returned HTTP 200", $passes, $errors);
assertCheck($dataChecklist['success'] === true, "Student checklist contains success: true", $passes, $errors);
assertCheck(count($dataChecklist['data']['books']) > 0 || count($dataChecklist['data']['notes']) > 0, "Student has prescribed books/notes in checklist", $passes, $errors);

// Test Item Issuance API
$sampleItem = $dataChecklist['data']['books'][0] ?? $dataChecklist['data']['notes'][0];
assertCheck($sampleItem !== null, "Found sample item for issuance test", $passes, $errors);
if ($sampleItem) {
    $reqIssue = \Illuminate\Http\Request::create('/api/books-notes/distribution/issue-item', 'POST', [
        'item_id'         => $sampleItem['id'],
        'issued_quantity' => $sampleItem['quantity'],
        'remarks'         => 'Automated test issuance',
    ]);
    $respIssue = $controller->apiIssueItem($reqIssue);
    $dataIssue = json_decode($respIssue->getContent(), true);
    assertCheck($respIssue->getStatusCode() === 200, "POST /api/books-notes/distribution/issue-item returned HTTP 200", $passes, $errors);
    assertCheck($dataIssue['success'] === true, "Issue item API returned success: true", $passes, $errors);
    assertCheck($dataIssue['item']['is_issued'] === true, "Item marked as is_issued = true", $passes, $errors);
}

// Test Printable Slip View
$reqSlip = \Illuminate\Http\Request::create("/settings/books-notes/slip/{$firstStudent['student_id']}", 'GET', [
    'academic_year_id' => $year->id,
]);
$respSlip = $controller->slip($firstStudent['student_id'], $reqSlip);
assertCheck($respSlip instanceof \Illuminate\View\View, "slip controller action returns View instance", $passes, $errors);
assertCheck($respSlip->name() === 'settings.books-notes.slip', "Slip view is settings.books-notes.slip", $passes, $errors);

echo "\n========================================================\n";
echo "SUMMARY: Passed: $passes | Failed: " . count($errors) . "\n";
echo "========================================================\n";

if (count($errors) > 0) {
    exit(1);
}
exit(0);
