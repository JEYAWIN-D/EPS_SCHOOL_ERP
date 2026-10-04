<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Student;
use App\Models\AdmissionBookNoteItem;
use App\Services\UniformRuleService;
use App\Services\BookNoteService;
use Illuminate\Http\Request;

echo "========================================================\n";
echo "   UNIFORM, BOOK & NOTE DISTRIBUTION VERIFICATION RUNNER\n";
echo "========================================================\n\n";

$uniformService = app(UniformRuleService::class);
$bookNoteService = app(BookNoteService::class);
$year = AcademicYear::where('name', 'like', '%2026-2027%')->orWhere('name', 'like', '%2026-27%')->first() ?? AcademicYear::current();

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

// ---------------------------------------------------------
// SECTION 1: Uniform Rule Engine Test
// ---------------------------------------------------------
echo "\n--- 1. Testing Uniform Rule Engine Mapping ---\n";

// Tier 1: Pre-KG, LKG, UKG
$preKg = Classes::where('name', 'Pre-KG')->orWhere('name', 'PRE KG')->first();
$lkg   = Classes::where('name', 'LKG')->first();
$ukg   = Classes::where('name', 'UKG')->first();

foreach (['Pre-KG' => $preKg, 'LKG' => $lkg, 'UKG' => $ukg] as $name => $cls) {
    if (!$cls) continue;
    $boysRules = $uniformService->getUniformsForClass($cls, 'BOYS')['active'];
    $girlsRules = $uniformService->getUniformsForClass($cls, 'GIRLS')['active'];

    $boysSummary = collect($boysRules)->map(fn($r) => "{$r['name']} ({$r['quantity']})")->join(', ');
    $girlsSummary = collect($girlsRules)->map(fn($r) => "{$r['name']} ({$r['quantity']})")->join(', ');

    $boysExpected = "Green T-Shirt (2), White T-Shirt (1), Shorts (3)";
    $girlsExpected = "Green T-Shirt (2), White T-Shirt (1), Skirt (3)";

    assertCheck($boysSummary === $boysExpected, "{$name} BOYS: {$boysSummary}", $passes, $errors);
    assertCheck($girlsSummary === $girlsExpected, "{$name} GIRLS: {$girlsSummary}", $passes, $errors);
    assertCheck(collect($boysRules)->sum('quantity') === 6, "{$name} BOYS total units = 6", $passes, $errors);
    assertCheck(collect($girlsRules)->sum('quantity') === 6, "{$name} GIRLS total units = 6", $passes, $errors);
}

// Tier 2: Grade I to III
$gradeI = Classes::where('name', 'I')->orWhere('name', 'Grade I')->orWhere('name', 'Class I')->first();
$gradeIII = Classes::where('name', 'III')->orWhere('name', 'Grade III')->orWhere('name', 'Class III')->first();

foreach (['Grade I' => $gradeI, 'Grade III' => $gradeIII] as $name => $cls) {
    if (!$cls) continue;
    $boysRules = $uniformService->getUniformsForClass($cls, 'BOYS')['active'];
    $girlsRules = $uniformService->getUniformsForClass($cls, 'GIRLS')['active'];

    $boysSummary = collect($boysRules)->map(fn($r) => "{$r['name']} ({$r['quantity']})")->join(', ');
    $girlsSummary = collect($girlsRules)->map(fn($r) => "{$r['name']} ({$r['quantity']})")->join(', ');

    $boysExpected = "White T-Shirt (2), Shorts (3), White Shirt (1), Sports T-Shirt (1)";
    $girlsExpected = "White T-Shirt (2), Skirt (3), White Shirt (1), Sports T-Shirt (1)";

    assertCheck($boysSummary === $boysExpected, "{$name} BOYS: {$boysSummary}", $passes, $errors);
    assertCheck($girlsSummary === $girlsExpected, "{$name} GIRLS: {$girlsSummary}", $passes, $errors);
    assertCheck(collect($boysRules)->sum('quantity') === 7, "{$name} BOYS total units = 7", $passes, $errors);
    assertCheck(collect($girlsRules)->sum('quantity') === 7, "{$name} GIRLS total units = 7", $passes, $errors);
}

// Tier 3: Grade IV to XI
$gradeIV = Classes::where('name', 'IV')->orWhere('name', 'Grade IV')->orWhere('name', 'Class IV')->first();
$gradeVII = Classes::where('name', 'VII')->orWhere('name', 'Grade VII')->orWhere('name', 'Class VII')->first();
$gradeXI = Classes::where('name', 'XI')->orWhere('name', 'Grade XI')->orWhere('name', 'Class XI')->first();

foreach (['Grade IV' => $gradeIV, 'Grade VII' => $gradeVII, 'Grade XI' => $gradeXI] as $name => $cls) {
    if (!$cls) continue;
    $boysRules = $uniformService->getUniformsForClass($cls, 'BOYS')['active'];
    $girlsRules = $uniformService->getUniformsForClass($cls, 'GIRLS')['active'];

    $boysSummary = collect($boysRules)->map(fn($r) => "{$r['name']} ({$r['quantity']})")->join(', ');
    $girlsSummary = collect($girlsRules)->map(fn($r) => "{$r['name']} ({$r['quantity']})")->join(', ');

    $boysExpected = "White T-Shirt (2), White Shirt (1), Pant (3), Sports T-Shirt (1)";
    $girlsExpected = "White T-Shirt (2), Skirt (3), White Shirt (1), Sports T-Shirt (1)";

    assertCheck($boysSummary === $boysExpected, "{$name} BOYS: {$boysSummary}", $passes, $errors);
    assertCheck($girlsSummary === $girlsExpected, "{$name} GIRLS: {$girlsSummary}", $passes, $errors);
    assertCheck(collect($boysRules)->sum('quantity') === 7, "{$name} BOYS total units = 7", $passes, $errors);
    assertCheck(collect($girlsRules)->sum('quantity') === 7, "{$name} GIRLS total units = 7", $passes, $errors);
}

// ---------------------------------------------------------
// SECTION 2: API Checklist with Gender Filter
// ---------------------------------------------------------
echo "\n--- 2. Testing API Checklist with Gender Parameter ---\n";

$reqChecklistBoys = Request::create('/api/books-notes/checklist', 'GET', [
    'academic_year_id' => $year->id,
    'class_id'         => $gradeIV->id,
    'gender'           => 'male',
]);
$respBoys = app(\App\Http\Controllers\Admin\BooksNotesController::class)->apiChecklist($reqChecklistBoys);
$jsonBoys = json_decode($respBoys->getContent(), true);

assertCheck($respBoys->getStatusCode() === 200, "GET /api/books-notes/checklist?gender=male returns 200", $passes, $errors);
assertCheck(isset($jsonBoys['uniforms']), "Checklist response contains 'uniforms' array", $passes, $errors);
assertCheck(count($jsonBoys['uniforms']) === 4, "Grade IV Boys checklist has 4 uniform item lines", $passes, $errors);
$hasPant = collect($jsonBoys['uniforms'])->contains(fn($u) => $u['item_name'] === 'Pant' && $u['quantity'] === 3);
assertCheck($hasPant, "Grade IV Boys has Pant (3)", $passes, $errors);

$reqChecklistGirls = Request::create('/api/books-notes/checklist', 'GET', [
    'academic_year_id' => $year->id,
    'class_id'         => $gradeIV->id,
    'gender'           => 'female',
]);
$respGirls = app(\App\Http\Controllers\Admin\BooksNotesController::class)->apiChecklist($reqChecklistGirls);
$jsonGirls = json_decode($respGirls->getContent(), true);
$hasSkirt = collect($jsonGirls['uniforms'])->contains(fn($u) => $u['item_name'] === 'Skirt' && $u['quantity'] === 3);
assertCheck($hasSkirt, "Grade IV Girls has Skirt (3)", $passes, $errors);

// ---------------------------------------------------------
// SECTION 3: Class Cards with Uniform Aggregations
// ---------------------------------------------------------
echo "\n--- 3. Testing Class Cards Uniform Metrics ---\n";

$reqCards = Request::create('/api/books-notes/cards', 'GET', [
    'academic_year_id' => $year->id,
]);
$respCards = app(\App\Http\Controllers\Admin\BooksNotesController::class)->apiCards($reqCards);
$jsonCards = json_decode($respCards->getContent(), true);

assertCheck($respCards->getStatusCode() === 200, "GET /api/books-notes/cards returns 200", $passes, $errors);
assertCheck(count($jsonCards['cards']) > 0, "Cards list is populated", $passes, $errors);

$firstCard = $jsonCards['cards'][0];
assertCheck(isset($firstCard['uniforms_boys_qty']), "Card contains 'uniforms_boys_qty'", $passes, $errors);
assertCheck(isset($firstCard['uniforms_girls_qty']), "Card contains 'uniforms_girls_qty'", $passes, $errors);
assertCheck(isset($firstCard['uniform_tier']), "Card contains 'uniform_tier'", $passes, $errors);

// ---------------------------------------------------------
// SECTION 4: Distribution Summary & Students List
// ---------------------------------------------------------
echo "\n--- 4. Testing Distribution Summary & Students List ---\n";

$reqSummary = Request::create('/api/books-notes/distribution/summary', 'GET', [
    'academic_year_id' => $year->id,
]);
$respSummary = app(\App\Http\Controllers\Admin\BooksNotesController::class)->apiDistributionSummary($reqSummary);
$jsonSummary = json_decode($respSummary->getContent(), true);

assertCheck($respSummary->getStatusCode() === 200, "GET /api/books-notes/distribution/summary returns 200", $passes, $errors);
assertCheck(isset($jsonSummary['summary']['remaining_uniforms_units']), "Summary contains 'remaining_uniforms_units'", $passes, $errors);

$reqStudents = Request::create('/api/books-notes/distribution/students', 'GET', [
    'academic_year_id' => $year->id,
    'per_page'         => 10,
]);
$respStudents = app(\App\Http\Controllers\Admin\BooksNotesController::class)->apiDistributionStudents($reqStudents);
$jsonStudents = json_decode($respStudents->getContent(), true);

assertCheck($respStudents->getStatusCode() === 200, "GET /api/books-notes/distribution/students returns 200", $passes, $errors);
assertCheck(!empty($jsonStudents['students']), "Students list is returned", $passes, $errors);

$testStudent = $jsonStudents['students'][0];
assertCheck(isset($testStudent['gender']), "Student item contains 'gender'", $passes, $errors);
assertCheck(isset($testStudent['payment_status']), "Student item contains 'payment_status'", $passes, $errors);
assertCheck(isset($testStudent['payment_status_label']), "Student item contains 'payment_status_label'", $passes, $errors);
assertCheck(isset($testStudent['distribution_status']), "Student item contains 'distribution_status'", $passes, $errors);
assertCheck(isset($testStudent['uniforms_prescribed']), "Student item contains 'uniforms_prescribed'", $passes, $errors);
assertCheck(isset($testStudent['uniforms_issued']), "Student item contains 'uniforms_issued'", $passes, $errors);
assertCheck(isset($testStudent['uniforms_remaining']), "Student item contains 'uniforms_remaining'", $passes, $errors);

// ---------------------------------------------------------
// SECTION 5: Student Detailed Checklist & Handover
// ---------------------------------------------------------
echo "\n--- 5. Testing Student Checklist Details & Uniform Items ---\n";

$studentId = $testStudent['student_id'];
$reqDetail = Request::create("/api/books-notes/distribution/student/{$studentId}", 'GET', [
    'academic_year_id' => $year->id,
]);
$respDetail = app(\App\Http\Controllers\Admin\BooksNotesController::class)->apiStudentChecklist($studentId, $reqDetail);
$jsonDetail = json_decode($respDetail->getContent(), true);

assertCheck($respDetail->getStatusCode() === 200, "GET /api/books-notes/distribution/student/{id} returns 200", $passes, $errors);
assertCheck(isset($jsonDetail['data']['uniforms']), "Detail contains 'uniforms' array", $passes, $errors);
assertCheck(isset($jsonDetail['data']['student']['payment_status_label']), "Detail student contains 'payment_status_label'", $passes, $errors);

if (!empty($jsonDetail['data']['uniforms'])) {
    $firstUniformItem = $jsonDetail['data']['uniforms'][0];
    echo "  Testing live handover for Uniform Item: {$firstUniformItem['item_name']} (ID: {$firstUniformItem['id']})...\n";
    $origQty = $firstUniformItem['issued_quantity'];
    $targetQty = min($firstUniformItem['quantity'], $origQty + 1);

    $reqIssue = Request::create('/api/books-notes/distribution/issue-item', 'POST', [
        'item_id'         => $firstUniformItem['id'],
        'issued_quantity' => $targetQty,
    ]);
    $respIssue = app(\App\Http\Controllers\Admin\BooksNotesController::class)->apiIssueItem($reqIssue);
    $jsonIssue = json_decode($respIssue->getContent(), true);
    assertCheck($respIssue->getStatusCode() === 200 && ($jsonIssue['success'] ?? false) === true, "POST /api/books-notes/distribution/issue-item succeeded for uniform", $passes, $errors);

    // Revert back
    $reqRevert = Request::create('/api/books-notes/distribution/issue-item', 'POST', [
        'item_id'         => $firstUniformItem['id'],
        'issued_quantity' => $origQty,
    ]);
    app(\App\Http\Controllers\Admin\BooksNotesController::class)->apiIssueItem($reqRevert);
}

// ---------------------------------------------------------
// SECTION 6: Template / Renaming Check
// ---------------------------------------------------------
echo "\n--- 6. Verifying Module Renaming Across Files ---\n";

$sidebarApp = file_get_contents(resource_path('views/layouts/app.blade.php'));
$sidebarAdmin = file_get_contents(resource_path('views/layouts/admin.blade.php'));
$settingsIndex = file_get_contents(resource_path('views/settings/index.blade.php'));
$moduleIndex = file_get_contents(resource_path('views/settings/books-notes/index.blade.php'));

assertCheck(str_contains($sidebarApp, 'Book, Note & Uniform Distribution'), "app.blade.php contains 'Book, Note & Uniform Distribution'", $passes, $errors);
assertCheck(str_contains($sidebarAdmin, 'Book, Note & Uniform Distribution'), "admin.blade.php contains 'Book, Note & Uniform Distribution'", $passes, $errors);
assertCheck(str_contains($settingsIndex, 'Book, Note &amp; Uniform Distribution') || str_contains($settingsIndex, 'Book, Note & Uniform Distribution'), "settings/index.blade.php contains 'Book, Note & Uniform Distribution'", $passes, $errors);
assertCheck(str_contains($moduleIndex, 'Book, Note & Uniform Distribution'), "books-notes/index.blade.php contains 'Book, Note & Uniform Distribution'", $passes, $errors);

// Summary
echo "\n========================================================\n";
echo "TEST RESULTS: {$passes} Passed, " . count($errors) . " Failed.\n";
if (empty($errors)) {
    echo "SUCCESS: ALL TESTS PASSED!\n";
} else {
    echo "FAILURES:\n";
    foreach ($errors as $e) {
        echo " - {$e}\n";
    }
}
echo "========================================================\n";
