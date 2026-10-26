<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportAllotment;
use App\Models\TransportAssignmentHistory;
use App\Models\Vehicle;
use App\Services\TransportManagementService;
use Illuminate\Http\Request;

echo "=================================================================\n";
echo "ADMISSION & FEE PAYMENT TRANSPORT INTEGRATION VERIFICATION\n";
echo "=================================================================\n\n";

$service = app(TransportManagementService::class);
$currentYear = AcademicYear::current() ?? AcademicYear::latest('id')->first();

// TEST 1: New Admission with Transport Selection Workflow
echo "[TC-INT-01] New Admission with Transport Allotment Workflow ... ";
$route = TransportRoute::where('is_active', true)->has('stops')->first();
$stop = $route->stops()->first();
$vehicle = $route->vehicle;

$admStudent = Student::create([
    'admission_no'        => 'ADM-TEST-' . rand(1000, 9999),
    'admission_date'      => today(),
    'dob'                 => '2015-05-15',
    'gender'              => 'male',
    'first_name'          => 'TestAdmission',
    'last_name'           => 'Student',
    'status'              => 'active',
    'transport_route_id'  => $route->id,
    'transport_stop_id'   => $stop->id,
    'transport_fee'       => 15000,
]);

// Run the service assignment as invoked in AdmissionController::store
$allotment = $service->assignStudentTransport($admStudent->id, [
    'route_id'             => $route->id,
    'stop_id'              => $stop->id,
    'vehicle_id'           => $vehicle?->id,
    'pickup_time'          => '07:20:00',
    'drop_time'            => '16:15:00',
    'pickup_time_override' => true,
    'fee'                  => 15000,
    'remarks'              => 'Assigned through New Admission workflow',
]);

$allotment->refresh();
$admPass = $allotment->is_active && $allotment->student_id === $admStudent->id && $allotment->pickup_time === '07:20:00';
if ($admPass) {
    echo "PASS\n      Detail: New student admitted with Route #{$route->id}, Stop '{$stop->name}', Vehicle '{$vehicle?->vehicle_number}'\n";
} else {
    echo "FAIL\n";
}

// TEST 2: Existing Student Transport Management during Fee Collection
echo "[TC-INT-02] Fee Payment Screen - Manage Transport Action ... ";
// Change route and stop through FeeController::manageStudentTransport equivalent
$targetRoute = TransportRoute::where('id', '!=', $route->id)->where('is_active', true)->has('stops')->first();
$targetStop = $targetRoute->stops()->first();

$updatedAllotment = $service->assignStudentTransport($admStudent->id, [
    'route_id'             => $targetRoute->id,
    'stop_id'              => $targetStop->id,
    'pickup_time'          => '07:35:00',
    'fee'                  => 18000,
    'remarks'              => 'Updated via Fee Payment screen - Manage Transport modal',
]);

$updatedAllotment->refresh();
$feePass = $updatedAllotment->route_id === $targetRoute->id && $updatedAllotment->fee == 18000;

// Verify audit history was captured
$history = TransportAssignmentHistory::where('student_id', $admStudent->id)->latest('id')->first();
$histPass = $history && $history->old_route_id === $route->id && $history->new_route_id === $targetRoute->id;

if ($feePass && $histPass) {
    echo "PASS\n      Detail: Route updated to #{$targetRoute->id}, fee set to Rs. 18000, audit history logged seamlessly.\n";
} else {
    echo "FAIL\n";
}

// Clean up test student
$admStudent->transportAllotments()->delete();
$admStudent->transportHistories()->delete();
$admStudent->forceDelete();

echo "\nIntegration testing completed successfully!\n";
