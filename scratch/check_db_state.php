<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== DATABASE CONNECTION & TABLES ===\n";
$tables = [
    'vehicles',
    'transport_routes',
    'transport_stops',
    'transport_allotments',
    'transport_roster_passengers',
    'transport_attendants',
    'students',
    'fee_types',
    'fee_structures',
    'student_fees',
    'fee_invoices',
    'fee_payments',
];

foreach ($tables as $t) {
    if (Schema::hasTable($t)) {
        $count = DB::table($t)->count();
        echo "Table: $t -> $count records\n";
    } else {
        echo "Table: $t -> NOT FOUND\n";
    }
}

echo "\n=== ROUTES IN DB ===\n";
$routes = DB::table('transport_routes')->get();
foreach ($routes as $r) {
    $stopsCount = DB::table('transport_stops')->where('route_id', $r->id)->count();
    $allotmentsCount = DB::table('transport_allotments')->where('route_id', $r->id)->count();
    $passengersCount = Schema::hasTable('transport_roster_passengers') 
        ? DB::table('transport_roster_passengers')->where('route_id', $r->id)->count() 
        : 0;
    echo "ID: {$r->id} | {$r->route_name} | Num: {$r->route_number} | Vehicle ID: {$r->vehicle_id} | Active: {$r->is_active} | Stops: $stopsCount | Allotments: $allotmentsCount | Passengers: $passengersCount\n";
}

echo "\n=== ALLOTMENTS COUNT BY ROUTE & VEHICLE ===\n";
$allotments = DB::table('transport_allotments')
    ->select('route_id', 'vehicle_id', 'stop_id', 'status', DB::raw('count(*) as c'))
    ->groupBy('route_id', 'vehicle_id', 'stop_id', 'status')
    ->get();
foreach ($allotments as $a) {
    echo "Route: {$a->route_id} | Vehicle: {$a->vehicle_id} | Stop: {$a->stop_id} | Status: {$a->status} | Count: {$a->c}\n";
}

echo "\n=== ROSTER PASSENGERS COUNT BY VAN ===\n";
if (Schema::hasTable('transport_roster_passengers')) {
    $roster = DB::table('transport_roster_passengers')
        ->select('van_number', 'is_active', DB::raw('count(*) as c'))
        ->groupBy('van_number', 'is_active')
        ->get();
    foreach ($roster as $row) {
        echo "Van: {$row->van_number} | Active: {$row->is_active} | Count: {$row->c}\n";
    }
}

