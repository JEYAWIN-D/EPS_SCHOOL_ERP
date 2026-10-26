<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
foreach ($tables as $t) {
    $tname = $t->table_name;
    $cols = Schema::getColumnListing($tname);
    $empCols = array_filter($cols, fn($c) => in_array($c, ['employee_id', 'staff_id', 'teacher_id', 'driver_id', 'user_id']));
    if (!empty($empCols)) {
        $c = DB::table($tname)->count();
        if ($c > 0) {
            echo "{$tname} (" . implode(', ', $empCols) . ") -> {$c} rows" . PHP_EOL;
        }
    }
}
