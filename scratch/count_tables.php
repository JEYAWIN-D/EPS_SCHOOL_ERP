<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name");
echo "TOTAL_TABLES: " . count($tables) . PHP_EOL;

$summary = [];
foreach ($tables as $t) {
    $table = $t->table_name;
    try {
        $count = DB::table($table)->count();
        if ($count > 0) {
            echo "{$table}: {$count}\n";
        }
    } catch (\Throwable $e) {
        // skip views/errors
    }
}
