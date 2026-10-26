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
    if (preg_match('/staff|employee|teacher|payroll|salary|leave|attendance/i', $tname)) {
        $c = DB::table($tname)->count();
        echo "{$tname} -> {$c} rows" . PHP_EOL;
    }
}
