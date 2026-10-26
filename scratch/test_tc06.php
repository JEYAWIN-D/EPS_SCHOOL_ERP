<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportAllotment;
use App\Services\TransportManagementService;

$service = app(TransportManagementService::class);

$student = Student::where('status', 'active')->first();
$route = TransportRoute::where('is_active', true)->has('stops')->first();
$stop = $route->stops()->first();

echo "Student: {$student->id} ({$student->first_name})\n";
echo "Route: {$route->id} ({$route->route_name})\n";
echo "Stop: {$stop->id} ({$stop->name})\n";
echo "Stop pickup: '{$stop->pickup_time}', drop: '{$stop->drop_time}'\n";

$allotment = $service->assignStudentTransport($student->id, [
    'route_id'    => $route->id,
    'stop_id'     => $stop->id,
    'pickup_time' => '07:15 AM', // Override
    'drop_time'   => null,       // Inherit default
    'fee'         => 15000,
    'remarks'     => 'Timing test',
]);

$allotment->refresh();
echo "Allotment id: {$allotment->id}\n";
echo "pickup_time: '{$allotment->pickup_time}'\n";
echo "drop_time: '{$allotment->drop_time}'\n";
echo "pickup_time_override: " . var_export($allotment->pickup_time_override, true) . "\n";
echo "drop_time_override: " . var_export($allotment->drop_time_override, true) . "\n";
echo "effective_pickup_time: '{$allotment->effective_pickup_time}'\n";
echo "effective_drop_time: '{$allotment->effective_drop_time}'\n";
