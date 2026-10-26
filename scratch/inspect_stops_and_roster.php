<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransportStop;
use App\Models\TransportRoute;
use App\Models\TransportRosterPassenger;
use App\Models\TransportAllotment;
use Illuminate\Support\Facades\DB;

echo "=== ROSTER PASSENGERS BY AREA / STOP ===\n";
$rosterByVan = TransportRosterPassenger::all()->groupBy('van_number');
foreach ($rosterByVan as $van => $passengers) {
    echo "--- $van (" . $passengers->count() . " passengers) ---\n";
    $areas = $passengers->groupBy('area');
    foreach ($areas as $area => $list) {
        $times = $list->map(fn($p) => "{$p->morning_time}/{$p->evening_time} (₹{$p->amount})")->unique()->implode(', ');
        echo "  - $area: " . $list->count() . " students | Times: $times\n";
    }
}

echo "\n=== ALLOTMENTS PICKUP STOPS ===\n";
$allotmentStops = TransportAllotment::all()->groupBy('pickup_stop');
foreach ($allotmentStops as $stop => $list) {
    echo "  - $stop: " . $list->count() . " students\n";
}
