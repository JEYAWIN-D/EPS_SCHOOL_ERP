<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransportRoute;
use App\Models\TransportStop;

echo "=================================================================\n";
echo "VERIFYING 5 VANS & 57 STOPS DETAILED SPECIFICATION\n";
echo "=================================================================\n\n";

$expected = [
    'Van I' => [
        'count' => 14,
        'stops' => [
            [1, 'SOLAR', 11.0, 20000],
            [2, 'MULLAPARAPPU', 12.0, 20000],
            [3, 'MUTHAMPALAYAM', 10.0, 7500],
            [4, 'SASTHRI NAGAR', 7.0, 12000],
            [5, 'CHETTIPALAYAM', 7.0, 15000],
            [6, 'KOLLAMPALAYAM', 5.0, 12000],
            [7, 'KALLUKKADAIMEDU', 5.0, 7500],
            [8, 'PERIYAR NAGAR', 5.0, 10000],
            [9, 'NGGO COLONY', 4.0, 12000],
            [10, 'SURAMPATTI VALASU', 6.0, 10000],
            [11, 'SURAMPATTI', 5.0, 10000],
            [12, 'TEACHERS COLONY', 5.0, 12000],
            [13, 'KUMALANKUTTAI', 5.0, 15000],
            [14, 'MANICKAMPLAYAM HOUSING UNIT', 1.0, 10000],
        ]
    ],
    'Van II' => [
        'count' => 9,
        'stops' => [
            [1, 'RN PUDHUR', 8.0, 7500],
            [2, 'BHAVANI', 15.0, 7500],
            [3, 'TEXVALLY', 12.0, 7500],
            [4, 'NASIYANOOR', 10.0, 12000],
            [5, 'THINDAL', 8.0, 12000],
            [6, 'VEERAPPANPALAYAM', 8.0, 12000],
            [7, 'VEERAPPANPALAYAM PIRIVU', 6.0, 12000],
            [8, 'KAIKKATIVALASU', 3.5, 15000],
            [9, 'POSTAL NAGAR', 2.5, 15000],
        ]
    ],
    'Van III' => [
        'count' => 14,
        'stops' => [
            [1, 'SOOLAI', 3.0, 10000],
            [2, 'PERIYASEMUR', 5.0, 10000],
            [3, 'EPB NAGAR', 2.0, 10000],
            [4, 'THENDRAL NAGAR', 3.0, 9000],
            [5, 'RASAMPALAYAM', 3.0, 10000],
            [6, 'S BEND', 2.0, 10000],
            [7, 'PP GARDEN', 2.0, 10000],
            [8, 'NEW RAM NAGAR', 3.0, 10000],
            [9, 'PANDIYAN NAGAR', 1.0, 10000],
            [10, 'MOKKAIYAN PALAYAM', 7.0, 15000],
            [11, 'K KULAM', 5.0, 12000],
            [12, 'BHARATHI THEATER', 3.0, 10000],
            [13, 'EDAYANKATTUVALASU', 2.0, 10000],
            [14, 'D MART', 6.0, 10000],
        ]
    ],
    'Van IV' => [
        'count' => 7,
        'stops' => [
            [1, 'VAIRAPALAYAM', 10.0, 15000],
            [2, 'KARUNGALPALAYAM', 7.0, 12000],
            [3, 'THIRUNAGARCOLONY', 7.0, 12000],
            [4, 'CHENNAI SILKS', 7.0, 12000],
            [5, 'VALAIYAKKAR STREET', 6.0, 12000],
            [6, 'PERIYAVALASU', 1.5, 6000],
            [7, 'VEERAPPANCHATHRAM', 4.0, 10000],
        ]
    ],
    'Van V' => [
        'count' => 13,
        'stops' => [
            [1, 'MUTHAMPALAYAM', 10.0, 7500],
            [2, 'SURAMPATTI', 5.0, 12000],
            [3, 'KARUNGALPALAYAM', 7.0, 12000],
            [4, 'KUMALANKUTTAI', 5.0, 9000],
            [5, 'VEERAPPANCHATHRAM', 4.0, 12000],
            [6, 'PALAYAPALAYAM', 5.0, 12000],
            [7, 'SUDHANANDHA NAGAR', 7.0, 15000],
            [8, 'MANICKAMPALAYAM', 1.0, 6000],
            [9, 'BUS STAND', 5.0, 7500],
            [10, 'SOOLAI', 10.0, 10000],
            [11, 'PERIYAVALASU', 1.0, 6000],
            [12, 'MANICKAMPALAYAM', 1.0, 6000],
            [13, 'PERIYAR NAGAR', 5.0, 12000],
        ]
    ]
];

$allPassed = true;
$totalStopsFound = 0;

foreach ($expected as $vanName => $expData) {
    $route = TransportRoute::where('route_name', $vanName)->first();
    if (!$route) {
        echo "❌ Route {$vanName} NOT FOUND!\n";
        $allPassed = false;
        continue;
    }

    $stops = TransportStop::where('route_id', $route->id)->where('is_active', true)->orderBy('stop_order')->get();
    echo "Route: {$vanName} (ID: {$route->id}, Route No: {$route->route_number})\n";
    echo "  Stops Count: {$stops->count()} (Expected: {$expData['count']})\n";

    if ($stops->count() !== $expData['count']) {
        echo "  ❌ STOP COUNT MISMATCH! Expected {$expData['count']}, got {$stops->count()}\n";
        $allPassed = false;
    }

    $totalStopsFound += $stops->count();

    foreach ($expData['stops'] as $idx => [$seq, $name, $km, $fee]) {
        $stop = $stops->firstWhere('stop_order', $seq);
        if (!$stop) {
            echo "  ❌ Missing sequence {$seq} ({$name})\n";
            $allPassed = false;
            continue;
        }

        $kmOk = abs((float)$stop->distance_km - $km) < 0.01;
        $feeOk = (float)$stop->fare == $fee;
        $nameOk = strtoupper(trim($stop->name)) === strtoupper(trim($name));

        if ($kmOk && $feeOk && $nameOk) {
            echo "  ✓ [Seq {$seq}] {$stop->name} - {$stop->distance_km} KM - ₹" . number_format($stop->fare, 0) . " (ID: {$stop->id})\n";
        } else {
            echo "  ❌ MISMATCH [Seq {$seq}]: Got [{$stop->name}, {$stop->distance_km}km, ₹{$stop->fare}], Expected [{$name}, {$km}km, ₹{$fee}]\n";
            $allPassed = false;
        }
    }
    echo "\n";
}

echo "Total Fleet Active Stops: {$totalStopsFound} (Expected: 57)\n";
if ($totalStopsFound === 57 && $allPassed) {
    echo "🎉 ALL 5 VANS AND 57 STOPS VERIFIED WITH 100% ACCURACY!\n";
} else {
    echo "❌ VERIFICATION FAILED!\n";
    exit(1);
}
