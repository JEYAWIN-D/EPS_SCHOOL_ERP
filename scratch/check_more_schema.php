<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = [
    'transport_allotments',
    'transport_assignment_histories',
    'transport_import_logs',
    'transport_stops',
    'transport_routes',
    'vehicles',
];

foreach ($tables as $t) {
    if (Schema::hasTable($t)) {
        echo "Table $t exists. Columns: " . implode(', ', Schema::getColumnListing($t)) . "\n";
    } else {
        echo "Table $t does NOT exist.\n";
    }
}
