<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'timetables' ORDER BY ordinal_position");
echo "Timetable columns:\n";
foreach ($cols as $c) {
    echo "  {$c->column_name} ({$c->data_type})\n";
}

$sample = DB::table('timetables')->first();
echo "\nSample record:\n" . json_encode($sample, JSON_PRETTY_PRINT) . "\n";
