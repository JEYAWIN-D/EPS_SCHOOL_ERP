<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportAllotment;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

echo "=================================================================\n";
echo "CONFIGURING 5 ROUTES AND 57 EXACT BUS STOPS FROM SPECIFICATION\n";
echo "=================================================================\n\n";

$dataset = [
    'Van I' => [
        'route_number' => 'VAN-01',
        'vehicle_reg'  => 'TN-33-AX-1001',
        'stops' => [
            ['seq' => 1,  'name' => 'SOLAR',                       'km' => 11.0, 'fee' => 20000],
            ['seq' => 2,  'name' => 'MULLAPARAPPU',                'km' => 12.0, 'fee' => 20000],
            ['seq' => 3,  'name' => 'MUTHAMPALAYAM',               'km' => 10.0, 'fee' => 7500],
            ['seq' => 4,  'name' => 'SASTHRI NAGAR',               'km' => 7.0,  'fee' => 12000],
            ['seq' => 5,  'name' => 'CHETTIPALAYAM',               'km' => 7.0,  'fee' => 15000],
            ['seq' => 6,  'name' => 'KOLLAMPALAYAM',               'km' => 5.0,  'fee' => 12000],
            ['seq' => 7,  'name' => 'KALLUKKADAIMEDU',             'km' => 5.0,  'fee' => 7500],
            ['seq' => 8,  'name' => 'PERIYAR NAGAR',               'km' => 5.0,  'fee' => 10000],
            ['seq' => 9,  'name' => 'NGGO COLONY',                 'km' => 4.0,  'fee' => 12000],
            ['seq' => 10, 'name' => 'SURAMPATTI VALASU',           'km' => 6.0,  'fee' => 10000],
            ['seq' => 11, 'name' => 'SURAMPATTI',                  'km' => 5.0,  'fee' => 10000],
            ['seq' => 12, 'name' => 'TEACHERS COLONY',             'km' => 5.0,  'fee' => 12000],
            ['seq' => 13, 'name' => 'KUMALANKUTTAI',               'km' => 5.0,  'fee' => 15000],
            ['seq' => 14, 'name' => 'MANICKAMPLAYAM HOUSING UNIT', 'km' => 1.0,  'fee' => 10000],
        ]
    ],
    'Van II' => [
        'route_number' => 'VAN-02',
        'vehicle_reg'  => 'TN-33-AX-1002',
        'stops' => [
            ['seq' => 1, 'name' => 'RN PUDHUR',                'km' => 8.0,  'fee' => 7500],
            ['seq' => 2, 'name' => 'BHAVANI',                  'km' => 15.0, 'fee' => 7500],
            ['seq' => 3, 'name' => 'TEXVALLY',                 'km' => 12.0, 'fee' => 7500],
            ['seq' => 4, 'name' => 'NASIYANOOR',               'km' => 10.0, 'fee' => 12000],
            ['seq' => 5, 'name' => 'THINDAL',                  'km' => 8.0,  'fee' => 12000],
            ['seq' => 6, 'name' => 'VEERAPPANPALAYAM',         'km' => 8.0,  'fee' => 12000],
            ['seq' => 7, 'name' => 'VEERAPPANPALAYAM PIRIVU',  'km' => 6.0,  'fee' => 12000],
            ['seq' => 8, 'name' => 'KAIKKATIVALASU',           'km' => 3.5,  'fee' => 15000],
            ['seq' => 9, 'name' => 'POSTAL NAGAR',             'km' => 2.5,  'fee' => 15000],
        ]
    ],
    'Van III' => [
        'route_number' => 'VAN-03',
        'vehicle_reg'  => 'TN-33-AX-1003',
        'stops' => [
            ['seq' => 1,  'name' => 'SOOLAI',             'km' => 3.0, 'fee' => 10000],
            ['seq' => 2,  'name' => 'PERIYASEMUR',        'km' => 5.0, 'fee' => 10000],
            ['seq' => 3,  'name' => 'EPB NAGAR',          'km' => 2.0, 'fee' => 10000],
            ['seq' => 4,  'name' => 'THENDRAL NAGAR',     'km' => 3.0, 'fee' => 9000],
            ['seq' => 5,  'name' => 'RASAMPALAYAM',       'km' => 3.0, 'fee' => 10000],
            ['seq' => 6,  'name' => 'S BEND',             'km' => 2.0, 'fee' => 10000],
            ['seq' => 7,  'name' => 'PP GARDEN',          'km' => 2.0, 'fee' => 10000],
            ['seq' => 8,  'name' => 'NEW RAM NAGAR',      'km' => 3.0, 'fee' => 10000],
            ['seq' => 9,  'name' => 'PANDIYAN NAGAR',     'km' => 1.0, 'fee' => 10000],
            ['seq' => 10, 'name' => 'MOKKAIYAN PALAYAM',  'km' => 7.0, 'fee' => 15000],
            ['seq' => 11, 'name' => 'K KULAM',            'km' => 5.0, 'fee' => 12000],
            ['seq' => 12, 'name' => 'BHARATHI THEATER',   'km' => 3.0, 'fee' => 10000],
            ['seq' => 13, 'name' => 'EDAYANKATTUVALASU',  'km' => 2.0, 'fee' => 10000],
            ['seq' => 14, 'name' => 'D MART',             'km' => 6.0, 'fee' => 10000],
        ]
    ],
    'Van IV' => [
        'route_number' => 'VAN-04',
        'vehicle_reg'  => 'TN-33-AX-1004',
        'stops' => [
            ['seq' => 1, 'name' => 'VAIRAPALAYAM',        'km' => 10.0, 'fee' => 15000],
            ['seq' => 2, 'name' => 'KARUNGALPALAYAM',     'km' => 7.0,  'fee' => 12000],
            ['seq' => 3, 'name' => 'THIRUNAGARCOLONY',    'km' => 7.0,  'fee' => 12000],
            ['seq' => 4, 'name' => 'CHENNAI SILKS',       'km' => 7.0,  'fee' => 12000],
            ['seq' => 5, 'name' => 'VALAIYAKKAR STREET',  'km' => 6.0,  'fee' => 12000],
            ['seq' => 6, 'name' => 'PERIYAVALASU',        'km' => 1.5,  'fee' => 6000],
            ['seq' => 7, 'name' => 'VEERAPPANCHATHRAM',   'km' => 4.0,  'fee' => 10000],
        ]
    ],
    'Van V' => [
        'route_number' => 'VAN-05',
        'vehicle_reg'  => 'TN-33-AX-1005',
        'stops' => [
            ['seq' => 1,  'name' => 'MUTHAMPALAYAM',     'km' => 10.0, 'fee' => 7500],
            ['seq' => 2,  'name' => 'SURAMPATTI',        'km' => 5.0,  'fee' => 12000],
            ['seq' => 3,  'name' => 'KARUNGALPALAYAM',   'km' => 7.0,  'fee' => 12000],
            ['seq' => 4,  'name' => 'KUMALANKUTTAI',     'km' => 5.0,  'fee' => 9000],
            ['seq' => 5,  'name' => 'VEERAPPANCHATHRAM', 'km' => 4.0,  'fee' => 12000],
            ['seq' => 6,  'name' => 'PALAYAPALAYAM',     'km' => 5.0,  'fee' => 12000],
            ['seq' => 7,  'name' => 'SUDHANANDHA NAGAR', 'km' => 7.0,  'fee' => 15000],
            ['seq' => 8,  'name' => 'MANICKAMPALAYAM',   'km' => 1.0,  'fee' => 6000],
            ['seq' => 9,  'name' => 'BUS STAND',         'km' => 5.0,  'fee' => 7500],
            ['seq' => 10, 'name' => 'SOOLAI',            'km' => 10.0, 'fee' => 10000],
            ['seq' => 11, 'name' => 'PERIYAVALASU',      'km' => 1.0,  'fee' => 6000],
            ['seq' => 12, 'name' => 'MANICKAMPALAYAM',   'km' => 1.0,  'fee' => 6000],
            ['seq' => 13, 'name' => 'PERIYAR NAGAR',     'km' => 5.0,  'fee' => 12000],
        ]
    ],
];

$totalConfiguredStops = 0;

DB::transaction(function() use ($dataset, &$totalConfiguredStops) {
    foreach ($dataset as $vanLabel => $data) {
        $routeNum = $data['route_number'];
        $vehicle = Vehicle::where('vehicle_number', $data['vehicle_reg'])->first();

        // Find or create route
        $route = TransportRoute::where('route_number', $routeNum)->first();
        if (!$route && $vehicle) {
            $route = TransportRoute::where('vehicle_id', $vehicle->id)->first();
        }

        if (!$route) {
            $route = TransportRoute::create([
                'route_name'    => $vanLabel,
                'route_number'  => $routeNum,
                'vehicle_id'    => $vehicle?->id,
                'from_location' => $data['stops'][0]['name'],
                'to_location'   => $data['stops'][count($data['stops']) - 1]['name'],
                'distance_km'   => array_sum(array_column($data['stops'], 'km')),
                'fee'           => $data['stops'][0]['fee'],
                'is_active'     => true,
            ]);
        } else {
            $route->update([
                'route_name'    => $vanLabel,
                'route_number'  => $routeNum,
                'vehicle_id'    => $vehicle?->id,
                'from_location' => $data['stops'][0]['name'],
                'to_location'   => $data['stops'][count($data['stops']) - 1]['name'],
                'distance_km'   => array_sum(array_column($data['stops'], 'km')),
                'is_active'     => true,
            ]);
        }

        echo "Configuring Route {$vanLabel} (ID: {$route->id}, Number: {$route->route_number})...\n";

        // Map existing stops by stop_order
        $existingStopsByOrder = TransportStop::where('route_id', $route->id)->get()->keyBy('stop_order');
        $officialStopIds = [];

        foreach ($data['stops'] as $stopSpec) {
            $seq  = $stopSpec['seq'];
            $name = $stopSpec['name'];
            $km   = $stopSpec['km'];
            $fee  = $stopSpec['fee'];

            // Match stop strictly on route and sequence order
            $stop = TransportStop::where('route_id', $route->id)
                ->where('stop_order', $seq)
                ->first();

            if ($stop) {
                $stop->update([
                    'stop_order'   => $seq,
                    'name'         => $name,
                    'distance_km'  => $km,
                    'fare'         => $fee,
                    'vehicle_id'   => $vehicle?->id,
                    'van_number'   => $vanLabel,
                    'is_active'    => true,
                    // If pickup/drop time is not manually configured, leave null or preserve
                    'pickup_time'  => $stop->pickup_time ?: null,
                    'drop_time'    => $stop->drop_time ?: null,
                ]);
            } else {
                $stop = TransportStop::create([
                    'route_id'     => $route->id,
                    'vehicle_id'   => $vehicle?->id,
                    'van_number'   => $vanLabel,
                    'name'         => $name,
                    'stop_order'   => $seq,
                    'distance_km'  => $km,
                    'fare'         => $fee,
                    'pickup_time'  => null,
                    'drop_time'    => null,
                    'landmark'     => $name,
                    'is_active'    => true,
                ]);
            }

            $officialStopIds[] = $stop->id;
            $totalConfiguredStops++;
            echo "  [Seq {$seq}] {$name} ({$km} KM, ₹" . number_format($fee, 0) . ") -> Stop ID: {$stop->id}\n";
        }

        // Handle any ad-hoc / extra stops that were created previously
        $extraStops = TransportStop::where('route_id', $route->id)
            ->whereNotIn('id', $officialStopIds)
            ->get();

        if ($extraStops->count() > 0) {
            echo "  Cleaning up {$extraStops->count()} ad-hoc extra stops on {$vanLabel}...\n";
            $firstOfficialStop = TransportStop::where('id', $officialStopIds[0])->first();
            $extraStopIds = $extraStops->pluck('id')->all();

            // Reassign any student allotments to first official stop in batch
            TransportAllotment::whereIn('stop_id', $extraStopIds)->update([
                'stop_id'     => $firstOfficialStop->id,
                'pickup_stop' => $firstOfficialStop->name,
            ]);
            TransportAllotment::whereIn('drop_stop_id', $extraStopIds)->update([
                'drop_stop_id' => $firstOfficialStop->id,
                'drop_stop'    => $firstOfficialStop->name,
            ]);

            // Safely delete extra stops
            TransportStop::whereIn('id', $extraStopIds)->delete();
        }
    }
});

echo "\n=================================================================\n";
echo "SUCCESS: {$totalConfiguredStops} official stops configured across 5 Vans!\n";
echo "=================================================================\n";
