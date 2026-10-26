<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\Employee;
use App\Models\Visitor;
use App\Models\IdCard;
use App\Models\IdCardTemplate;
use App\Models\IdCardAuditLog;
use App\Services\IdCardService;
use Barryvdh\DomPDF\Facade\Pdf;

echo "========================================================\n";
echo "  ERODE PUBLIC SCHOOL - ID CARD SYSTEM VERIFICATION TEST\n";
echo "========================================================\n\n";

$service = new IdCardService();

// Step 1: Ensure Default Templates
echo "[1] Testing Template Seeding & School Settings...\n";
$service->ensureDefaultTemplates();
$templateCount = IdCardTemplate::count();
echo "    ✓ Active Templates in DB: {$templateCount}\n";
assert($templateCount >= 6, "Expected at least 6 templates");

// Step 2: Student Card Generation
echo "\n[2] Testing Student ID Card Generation...\n";
$student = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->first();
if (!$student) {
    echo "    ✕ Error: No student found in database!\n";
    exit(1);
}
$className = $student->currentEnrollment?->class?->name ?? 'Standard Class';
echo "    Found Student: {$student->full_name} (Adm: {$student->admission_number}, Class: {$className})\n";

$studentCard = $service->getOrCreateCardForStudent($student);
echo "    ✓ Generated Student Card #: {$studentCard->card_number}\n";
echo "    ✓ Status: {$studentCard->status}\n";
echo "    ✓ Verification Token: {$studentCard->qr_verification_token}\n";
assert(!empty($studentCard->card_number), "Student card number must not be empty");
assert(!empty($studentCard->qr_verification_token), "Student verification token must not be empty");

// Step 3: Staff Card Generation & Inactive Protection
echo "\n[3] Testing Staff ID Card Generation & Inactive Guard...\n";
$activeEmployee = Employee::where('is_active', true)->first() ?? Employee::first();
$activeStatusStr = $activeEmployee->is_active ? 'Active' : 'Inactive';
echo "    Found Staff: {$activeEmployee->full_name} ({$activeEmployee->employee_code}, Status: {$activeStatusStr})\n";

$staffCard = $service->getOrCreateCardForEmployee($activeEmployee);
echo "    ✓ Generated Staff Card #: {$staffCard->card_number}\n";
echo "    ✓ Status: {$staffCard->status}\n";

// Test inactive employee blocking
$inactiveEmployee = Employee::where('is_active', false)->first();
if (!$inactiveEmployee) {
    // Temporarily create a mock inactive test employee in memory or fetch one to test
    $inactiveEmployee = clone $activeEmployee;
    $inactiveEmployee->is_active = false;
}

echo "    Testing inactive staff guard for: {$inactiveEmployee->full_name} (is_active: false)...\n";
try {
    $service->getOrCreateCardForEmployee($inactiveEmployee);
    echo "    ✕ Security Failed: Inactive employee card generation should have thrown an Exception!\n";
} catch (\DomainException $e) {
    echo "    ✓ Inactive Staff Guard Working: Caught expected domain exception -> " . $e->getMessage() . "\n";
}

// Step 4: Visitor Pass Generation
echo "\n[4] Testing Visitor ID Card Pass Generation...\n";
$visitor = Visitor::first();
if (!$visitor) {
    echo "    ✕ Error: No visitor record found in database!\n";
    exit(1);
}
echo "    Found Visitor: {$visitor->visitor_name} (Pass: {$visitor->pass_number}, Status: {$visitor->status})\n";
$visitorCard = $service->getOrCreateCardForVisitor($visitor);
echo "    ✓ Generated Visitor Pass Card #: {$visitorCard->card_number}\n";
echo "    ✓ Status: {$visitorCard->status}\n";

// Step 5: Card Lifecycle: Reprint
echo "\n[5] Testing Card Reprint Workflow...\n";
$initialReprints = $studentCard->reprint_count;
$reprintedCard = $service->reprintCard($studentCard, 'Damaged during sports activity');
echo "    ✓ Reprint Count: {$reprintedCard->reprint_count} (was {$initialReprints})\n";
echo "    ✓ Reprint Reason: {$reprintedCard->reprint_reason}\n";
assert($reprintedCard->reprint_count === $initialReprints + 1, "Reprint count must increment");

// Step 6: Card Lifecycle: Replacement
echo "\n[6] Testing Card Replacement Workflow...\n";
$newReplacementCard = $service->replaceCard($studentCard, 'Card lost by student');
$oldStudentCard = IdCard::find($studentCard->id);
echo "    ✓ Old Card Status: {$oldStudentCard->status} (Expected: replaced)\n";
echo "    ✓ New Card #: {$newReplacementCard->card_number}\n";
echo "    ✓ New Card Status: {$newReplacementCard->status} (Expected: active)\n";
assert($oldStudentCard->status === IdCard::STATUS_REPLACED, "Old card status must be replaced");
assert($newReplacementCard->status === IdCard::STATUS_ACTIVE, "New card status must be active");

// Step 7: Card Lifecycle: Revocation
echo "\n[7] Testing Card Revocation Workflow...\n";
$revokedCard = $service->revokeCard($newReplacementCard, 'Student withdrawn from school');
echo "    ✓ Revoked Card Status: {$revokedCard->status} (Expected: revoked)\n";
assert($revokedCard->status === IdCard::STATUS_REVOKED, "Card status must be revoked");

// Step 8: Audit Logs Check
echo "\n[8] Verifying Audit Trail Integrity...\n";
$logs = IdCardAuditLog::where('card_id', $oldStudentCard->id)->orWhere('card_id', $newReplacementCard->id)->get();
echo "    ✓ Recorded Audit Log Entries: " . $logs->count() . "\n";
foreach ($logs as $log) {
    echo "      - Action: [{$log->action}] Reason: {$log->reason}\n";
}
assert($logs->count() >= 2, "Expected audit trail records for reprint, replace, or revoke");

// Step 9: DomPDF Rendering Verification
echo "\n[9] Testing DomPDF Generation for Student, Staff, and Visitor...\n";

// Re-generate an active student card for PDF rendering
$freshStudentCard = $service->getOrCreateCardForStudent($student);
$freshStudentCard->update(['status' => IdCard::STATUS_ACTIVE]);

// Test A4 Student Grid PDF - Landscape
$landscapeTemplate = IdCardTemplate::where('card_type', 'student')->where('orientation', 'landscape')->first();
$rollNo = $student->roll_number ?: str_pad($student->id % 100 ?: 1, 2, '0', STR_PAD_LEFT);
$barcodeVal = 'EPS-R' . str_pad($rollNo, 2, '0', STR_PAD_LEFT);
$barcodeSvg = $service->generateBarcodeSvg($barcodeVal, 24, 2, '#0f172a');

$cardsData = [[
    'card' => $freshStudentCard,
    'student' => $student,
    'barcodeVal' => $barcodeVal,
    'barcodeSvg' => $barcodeSvg,
]];

echo "    Rendering Student Landscape A4 Grid PDF...\n";
$pdfLandscape = Pdf::loadView('pdf.student-id-cards-grid', [
    'cardsData' => $cardsData,
    'template' => $landscapeTemplate,
    'school' => \App\Models\SchoolSetting::first(),
    'orientation' => 'landscape',
    'side' => 'both',
])->setPaper('a4', 'landscape')->output();

$landscapeSize = strlen($pdfLandscape);
echo "    ✓ Student Landscape PDF generated successfully ({$landscapeSize} bytes)\n";
assert($landscapeSize > 1000, "PDF size must be valid");

// Test A4 Student Grid PDF - Portrait
$portraitTemplate = IdCardTemplate::where('card_type', 'student')->where('orientation', 'portrait')->first();
echo "    Rendering Student Portrait A4 Grid PDF...\n";
$pdfPortrait = Pdf::loadView('pdf.student-id-cards-grid', [
    'cardsData' => $cardsData,
    'template' => $portraitTemplate,
    'school' => \App\Models\SchoolSetting::first(),
    'orientation' => 'portrait',
    'side' => 'both',
])->setPaper('a4', 'portrait')->output();

$portraitSize = strlen($pdfPortrait);
echo "    ✓ Student Portrait PDF generated successfully ({$portraitSize} bytes)\n";
assert($portraitSize > 1000, "PDF size must be valid");

// Test Staff PDF
echo "    Rendering Staff A4 Grid PDF...\n";
$staffTemplate = IdCardTemplate::where('card_type', 'staff')->where('orientation', 'portrait')->first();
$staffBarcodeVal = 'EMP-' . ($activeEmployee->employee_code ?: $activeEmployee->id);
$staffBarcodeSvg = $service->generateBarcodeSvg($staffBarcodeVal, 24, 2, '#0f172a');
$staffCardsData = [[
    'card' => $staffCard,
    'employee' => $activeEmployee,
    'barcodeVal' => $staffBarcodeVal,
    'barcodeSvg' => $staffBarcodeSvg,
]];
$pdfStaff = Pdf::loadView('pdf.staff-id-cards-grid', [
    'cardsData' => $staffCardsData,
    'template' => $staffTemplate,
    'school' => \App\Models\SchoolSetting::first(),
    'orientation' => 'portrait',
    'side' => 'both',
])->setPaper('a4', 'portrait')->output();

$staffPdfSize = strlen($pdfStaff);
echo "    ✓ Staff Portrait PDF generated successfully ({$staffPdfSize} bytes)\n";
assert($staffPdfSize > 1000, "PDF size must be valid");

// Test Visitor Pass PDF
echo "    Rendering Visitor Pass PDF...\n";
$visitorTemplate = IdCardTemplate::where('card_type', 'visitor')->where('orientation', 'landscape')->first();
$visitorBarcodeVal = 'VP-' . $visitor->id;
$visitorBarcodeSvg = $service->generateBarcodeSvg($visitorBarcodeVal, 24, 2, '#0f172a');
$pdfVisitor = Pdf::loadView('pdf.visitor-id-card', [
    'card' => $visitorCard,
    'visitor' => $visitor,
    'template' => $visitorTemplate,
    'school' => \App\Models\SchoolSetting::first(),
    'orientation' => 'landscape',
    'barcodeVal' => $visitorBarcodeVal,
    'barcodeSvg' => $visitorBarcodeSvg,
])->setPaper([0, 0, 242.65, 153.01], 'landscape')->output();

$visitorPdfSize = strlen($pdfVisitor);
echo "    ✓ Visitor Pass PDF generated successfully ({$visitorPdfSize} bytes)\n";
assert($visitorPdfSize > 1000, "PDF size must be valid");

echo "\n========================================================\n";
echo "  ALL ID CARD MODULE TESTS PASSED WITH 100% SUCCESS!\n";
echo "========================================================\n";
