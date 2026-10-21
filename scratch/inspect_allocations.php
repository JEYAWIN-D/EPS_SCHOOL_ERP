<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'teacher_subject_allocations' ORDER BY ordinal_position");
echo "Teacher Subject Allocation columns:\n";
foreach ($cols as $c) {
    echo "  {$c->column_name} ({$c->data_type})\n";
}

$count = DB::table('teacher_subject_allocations')->count();
echo "Total allocations: {$count}\n";
$sample = DB::table('teacher_subject_allocations')->first();
echo "Sample record:\n" . json_encode($sample, JSON_PRETTY_PRINT) . "\n";
