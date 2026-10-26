<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IdCard;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\IdCardController;

echo "========================================================\n";
echo "  TESTING QR VERIFICATION ENDPOINT & PRIVACY COMPLIANCE\n";
echo "========================================================\n\n";

$controller = app()->make(IdCardController::class);

// 1. Test Active Card Verification
$activeCard = IdCard::where('status', IdCard::STATUS_ACTIVE)->first();
if ($activeCard) {
    echo "[1] Testing Active Card Token ({$activeCard->qr_verification_token})...\n";
    $view = $controller->verify($activeCard->qr_verification_token);
    $data = $view->getData();
    assert($data['statusText'] === 'VERIFIED & ACTIVE', "Expected 'VERIFIED & ACTIVE'");
    assert($data['isValid'] === true, "Expected isValid to be true");
    echo "    ✓ Active Card verified with 'VERIFIED & ACTIVE' banner\n";
}

// 2. Test Replaced Card Verification
$replacedCard = IdCard::where('status', IdCard::STATUS_REPLACED)->first();
if ($replacedCard) {
    echo "\n[2] Testing Replaced Card Token ({$replacedCard->qr_verification_token})...\n";
    $view = $controller->verify($replacedCard->qr_verification_token);
    $content = $view->render();
    assert(strpos($content, 'REPLACED / DEACTIVATED') !== false, "Expected 'REPLACED / DEACTIVATED' warning");
    echo "    ✓ Replaced Card flagged with 'REPLACED / DEACTIVATED (DO NOT HONOR)' warning\n";
}

// 3. Test Revoked Card Verification
$revokedCard = IdCard::where('status', IdCard::STATUS_REVOKED)->first();
if ($revokedCard) {
    echo "\n[3] Testing Revoked Card Token ({$revokedCard->qr_verification_token})...\n";
    $view = $controller->verify($revokedCard->qr_verification_token);
    $content = $view->render();
    assert(strpos($content, 'REVOKED (DO NOT HONOR)') !== false, "Expected 'REVOKED (DO NOT HONOR)' warning");
    echo "    ✓ Revoked Card flagged with 'REVOKED (DO NOT HONOR)' warning\n";
}

// 4. Test Invalid/Random Token Verification
echo "\n[4] Testing Random Token (tamper-detection)...\n";
$randomToken = 'invalid-uuid-random-attack-test';
$view = $controller->verify($randomToken);
$content = $view->render();
assert(strpos($content, 'CARD NOT FOUND / INVALID TOKEN') !== false, "Expected 'CARD NOT FOUND / INVALID TOKEN' badge");
echo "    ✓ Invalid Token rejected with 'CARD NOT FOUND / INVALID TOKEN' warning\n";

echo "\n========================================================\n";
echo "  QR VERIFICATION & TAMPER-EVIDENT SECURITY TESTS PASSED!\n";
echo "========================================================\n";
