<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Vehicle;
use App\Models\TransportRoute;
use App\Models\TransportStop;

echo "=== VEHICLES (" . Vehicle::count() . ") ===\n";
foreach (Vehicle::all() as $v) {
    echo "ID: {$v->id} | {$v->vehicle_number} | Model: {$v->model} | Cap: {$v->seating_capacity} | Driver: {$v->driver_name} ({$v->driver_mobile})\n";
}

echo "\n=== ROUTES (" . TransportRoute::count() . ") ===\n";
foreach (TransportRoute::with('stops', 'vehicle')->get() as $r) {
    echo "ID: {$r->id} | {$r->route_name} | Vehicle: " . ($r->vehicle?->vehicle_number ?? 'None') . " | Stops: " . $r->stops->count() . "\n";
    foreach ($r->stops as $s) {
        echo "   - #{$s->stop_order} {$s->name} | {$s->distance_km} km | ₹{$s->fare} | Time: {$s->pickup_time}\n";
    }
}
