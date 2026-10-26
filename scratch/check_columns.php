<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = [
    'vehicles',
    'transport_routes',
    'transport_stops',
    'transport_allotments',
    'transport_roster_passengers',
    'transport_attendants',
    'students',
];

foreach ($tables as $t) {
    echo "=== COLUMNS FOR $t ===\n";
    $cols = Schema::getColumnListing($t);
    echo implode(', ', $cols) . "\n\n";
}

echo "=== SAMPLE 1 ALLOTMENT ===\n";
print_r(DB::table('transport_allotments')->first());

echo "\n=== SAMPLE 1 ROSTER PASSENGER ===\n";
print_r(DB::table('transport_roster_passengers')->first());

echo "\n=== SAMPLE 1 STOP ===\n";
print_r(DB::table('transport_stops')->first());
