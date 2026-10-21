<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'substitutions' ORDER BY ordinal_position");
echo "Substitutions columns:\n";
foreach ($cols as $c) {
    echo "  {$c->column_name} ({$c->data_type})\n";
}

$count = DB::table('substitutions')->count();
echo "Total substitutions: {$count}\n";
