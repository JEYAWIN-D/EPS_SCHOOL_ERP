<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;

$routes = TransportRoute::with(['stops' => fn($q) => $q->orderBy('stop_order'), 'vehicle'])->orderBy('id')->get();
echo "TOTAL ROUTES IN DB: " . $routes->count() . "\n\n";

foreach ($routes as $r) {
    $activeStudents = $r->allotments()->where('is_active', true)->count();
    $vehicleStr = $r->vehicle ? $r->vehicle->vehicle_number : 'Not assigned';
    echo "========================================================\n";
    echo "Route ID: {$r->id} | Number: {$r->route_number} | Name: {$r->route_name}\n";
    echo "Status: " . ($r->is_active ? 'Active' : 'Inactive') . " | Vehicle: {$vehicleStr} | Students: {$activeStudents} | Stops Count: " . $r->stops->count() . "\n";
    echo "--------------------------------------------------------\n";
    foreach ($r->stops as $s) {
        $pTime = $s->pickup_time ?: 'Not Set';
        $dTime = $s->drop_time ?: 'Not Set';
        echo "  [Seq {$s->stop_order}] {$s->name} | {$s->distance_km} KM | ₹" . number_format($s->fare, 0) . " | Pickup: {$pTime} | Drop: {$dTime}\n";
    }
}
