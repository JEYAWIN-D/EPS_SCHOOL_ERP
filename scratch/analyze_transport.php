<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;
use App\Models\TransportAllotment;
use App\Models\TransportRosterPassenger;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

echo "=== VEHICLES (" . Vehicle::count() . ") ===\n";
foreach (Vehicle::all() as $v) {
    $allotments = TransportAllotment::where('vehicle_id', $v->id)->count();
    $activeAllotments = TransportAllotment::where('vehicle_id', $v->id)->where('is_active', true)->count();
    $rosterCount = TransportRosterPassenger::where('vehicle_id', $v->id)->count();
    echo "ID: {$v->id} | {$v->vehicle_number} | {$v->model} | Cap: {$v->seating_capacity} (eff: {$v->capacity}) | Status: {$v->status} | Allotments: $allotments ($activeAllotments active) | Roster: $rosterCount\n";
}

echo "\n=== ROUTES (" . TransportRoute::count() . ") ===\n";
foreach (TransportRoute::with('stops', 'vehicle')->get() as $r) {
    $allotments = TransportAllotment::where('route_id', $r->id)->count();
    $activeAllotments = TransportAllotment::where('route_id', $r->id)->where('is_active', true)->count();
    $rosterCount = TransportRosterPassenger::where('route_id', $r->id)->count();
    echo "ID: {$r->id} | {$r->route_number}: {$r->route_name} | Vehicle: " . ($r->vehicle?->vehicle_number ?? 'None') . " | Active: {$r->is_active} | Stops: {$r->stops->count()} | Allotments: $allotments ($activeAllotments active) | Roster: $rosterCount\n";
}

echo "\n=== TRANSPORT ALLOTMENTS (" . TransportAllotment::count() . ") ===\n";
$allotmentStats = TransportAllotment::select('route_id', 'vehicle_id', 'is_active', DB::raw('count(*) as c'))
    ->groupBy('route_id', 'vehicle_id', 'is_active')
    ->get();
foreach ($allotmentStats as $stat) {
    echo "Route {$stat->route_id} | Vehicle {$stat->vehicle_id} | Active: {$stat->is_active} | Count: {$stat->c}\n";
}

echo "\n=== ROSTER PASSENGERS (" . TransportRosterPassenger::count() . ") ===\n";
$rosterStats = TransportRosterPassenger::select('van_number', 'route_id', 'vehicle_id', 'is_active', DB::raw('count(*) as c'))
    ->groupBy('van_number', 'route_id', 'vehicle_id', 'is_active')
    ->get();
foreach ($rosterStats as $stat) {
    echo "Van: {$stat->van_number} | Route {$stat->route_id} | Vehicle {$stat->vehicle_id} | Active: {$stat->is_active} | Count: {$stat->c}\n";
}

echo "\n=== STOPS SUMMARY PER ROUTE ===\n";
foreach (TransportRoute::all() as $r) {
    $stops = TransportStop::where('route_id', $r->id)->orderBy('stop_order')->get();
    echo "Route {$r->id} ({$r->route_name}) has " . $stops->count() . " stops:\n";
    $first3 = $stops->take(3)->map(fn($s) => "#{$s->stop_order} {$s->name} ({$s->pickup_time}-{$s->drop_time})")->implode(', ');
    $last1 = $stops->last() ? "#{$stops->last()->stop_order} {$stops->last()->name} ({$stops->last()->pickup_time}-{$stops->last()->drop_time})" : '';
    echo "  Stops: $first3 ... $last1\n";
}
