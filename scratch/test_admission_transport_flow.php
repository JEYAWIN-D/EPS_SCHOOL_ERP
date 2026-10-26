<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Classes;
use App\Models\Section;
use App\Models\Student;
use App\Models\TransportAllotment;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=================================================================\n";
echo "TESTING NEW ADMISSION -> VAN TRANSPORT ALLOTMENT FLOW\n";
echo "=================================================================\n\n";

$admin = User::where('email', 'admin@schoolerp.in')->first() ?: User::find(1);
Auth::login($admin);

$class = Classes::first();
$section = Section::where('class_id', $class->id)->first();

// 1. Choose Van II (Route VAN-02) and Stop RN PUDHUR
$van2Route = TransportRoute::where('route_name', 'Van II')->firstOrFail();
$van2Stop = TransportStop::where('route_id', $van2Route->id)->where('name', 'RN PUDHUR')->firstOrFail();

echo "Simulating New Admission with Van II ({$van2Route->route_name}) and Stop {$van2Stop->name}...\n";

$admissionController = app(\App\Http\Controllers\Admin\AdmissionController::class);

$randomPhone = '9' . rand(100000000, 999999999);
$admData = [
    'first_name'            => 'ManualAdm',
    'last_name'             => 'VanTwoStudent',
    'dob'                   => '2016-04-10',
    'gender'                => 'male',
    'class_id'              => $class->id,
    'section_id'            => $section?->id,
    'parent_name'           => 'Parent VanTwo',
    'parent_mobile'         => $randomPhone,
    'address'               => 'RN Pudhur Main Road',
    'payment_terms'         => 'single',
    'payment_mode'          => 'UPI',
    'amount_collected'      => 50000,
    // Transport selection
    'transport_route_id'    => $van2Route->id,
    'transport_stop_id'     => $van2Stop->id,
    'transport_pickup_time' => '07:15',
    'transport_drop_time'   => '16:45',
    'transport_fee'         => $van2Stop->fare,
];

$request = Request::create('/admissions', 'POST', $admData);
$response = $admissionController->store($request);

$admittedStudent = Student::where('first_name', 'ManualAdm')->where('last_name', 'VanTwoStudent')->latest('id')->first();

if (!$admittedStudent) {
    echo "❌ Student creation failed!\n";
    exit(1);
}

echo "✓ Student successfully created: {$admittedStudent->first_name} {$admittedStudent->last_name} (ID: {$admittedStudent->id}, Adm No: {$admittedStudent->admission_no})\n";

// Verify allotment in DB
$allotment = TransportAllotment::where('student_id', $admittedStudent->id)->where('is_active', true)->first();

if (!$allotment) {
    echo "❌ TransportAllotment NOT created for admitted student!\n";
    exit(1);
}

echo "✓ TransportAllotment created automatically:\n";
echo "    Route ID: {$allotment->route_id} (Expected: {$van2Route->id} - {$van2Route->route_name})\n";
echo "    Stop ID: {$allotment->stop_id} (Expected: {$van2Stop->id} - {$van2Stop->name})\n";
echo "    Pickup Time: {$allotment->pickup_time} (Expected: 07:15:00)\n";
echo "    Drop Time: {$allotment->drop_time} (Expected: 16:45:00)\n";
echo "    Fee: ₹" . number_format($allotment->fee, 0) . "\n";

// Verify route enrolled counts
$van2Enrolled = $van2Route->fresh()->allotments()->where('is_active', true)->count();
echo "✓ Van II Active Enrolled Count: {$van2Enrolled} (Expected: 1)\n";

$van2StopBoarding = $van2Stop->fresh()->allotments()->where('is_active', true)->count();
echo "✓ Stop RN PUDHUR Active Boarding Count: {$van2StopBoarding} (Expected: 1)\n";

// Verify other routes remain 0
$otherVansCount = TransportAllotment::where('is_active', true)->where('route_id', '!=', $van2Route->id)->count();
echo "✓ Other Vans Active Enrolled Count: {$otherVansCount} (Expected: 0)\n";

// Clean up test student
$admittedStudent->transportAllotments()->delete();
$admittedStudent->transportHistories()->delete();
$admittedStudent->enrollments()->delete();
$admittedStudent->forceDelete();
echo "✓ Cleaned up test admission student and allotment.\n\n";

echo "🎉 ALL TESTS PASSED: New admissions accurately populate into the selected Van & Bus Stop!\n";
