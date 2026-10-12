<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportAttendant;
use Illuminate\Support\Facades\DB;

class TransportDataSeeder extends Seeder
{
    public function run(): void
    {
        $vansData = [
            [
                'van_key'         => 'VAN I',
                'vehicle_number'  => 'TN-33-AX-1001',
                'registration'    => 'TN33AX1001',
                'make'            => 'Force Motors',
                'model'           => 'Cruiser (24 Seater)',
                'vehicle_type'    => 'van',
                'capacity'        => 24,
                'driver_name'     => 'P. Shanmugam',
                'driver_mobile'   => '9842711001',
                'driver_license'  => 'TN-33-2015-0012341',
                'driver_address'  => 'Solar, Erode - 638002',
                'attendant_name'  => 'S. Lakshmi',
                'attendant_mobile'=> '9842712001',
                'attendant_address'=> 'Surampatti, Erode - 638009',
                'attendant_aadhaar'=> '784512349001',
                'route_name'      => 'Van I — Solar / Surampatti / Teachers Colony',
                'route_number'    => 'VAN-01',
                'from_location'   => 'Solar',
                'to_location'     => 'Manickampalayam Housing U',
                'distance_km'     => 12.0,
                'fee'             => 20000,
                'stops' => [
                    ['name' => 'SOLAR', 'km' => 11, 'fee' => 20000, 'pickup' => '07:25', 'drop' => '16:30'],
                    ['name' => 'MULLAPARAPPU', 'km' => 12, 'fee' => 20000, 'pickup' => '07:30', 'drop' => '16:25'],
                    ['name' => 'MUTHAMPALAYAM', 'km' => 10, 'fee' => 7500, 'pickup' => '07:35', 'drop' => '16:20'],
                    ['name' => 'SASTHRI NAGAR', 'km' => 7, 'fee' => 12000, 'pickup' => '07:42', 'drop' => '16:15'],
                    ['name' => 'CHETTIPALAYAM', 'km' => 7, 'fee' => 15000, 'pickup' => '07:48', 'drop' => '16:10'],
                    ['name' => 'KOLLAMPALAYAM', 'km' => 5, 'fee' => 12000, 'pickup' => '07:54', 'drop' => '16:05'],
                    ['name' => 'KALLUKKADAIMEDU', 'km' => 5, 'fee' => 7500, 'pickup' => '07:58', 'drop' => '16:00'],
                    ['name' => 'PERIYAR NAGAR', 'km' => 5, 'fee' => 10000, 'pickup' => '08:03', 'drop' => '15:55'],
                    ['name' => 'NGGO COLONY', 'km' => 4, 'fee' => 12000, 'pickup' => '08:08', 'drop' => '15:50'],
                    ['name' => 'SURAMPATTI VALASU', 'km' => 6, 'fee' => 10000, 'pickup' => '08:14', 'drop' => '15:45'],
                    ['name' => 'SURAMPATTI', 'km' => 5, 'fee' => 10000, 'pickup' => '08:18', 'drop' => '15:40'],
                    ['name' => 'TEACHERS COLONY', 'km' => 5, 'fee' => 12000, 'pickup' => '08:22', 'drop' => '15:35'],
                    ['name' => 'KUMALANKUTTAI', 'km' => 5, 'fee' => 15000, 'pickup' => '08:26', 'drop' => '15:30'],
                    ['name' => 'MANICKAMPALAYAM HOUSING U', 'km' => 1, 'fee' => 10000, 'pickup' => '08:32', 'drop' => '15:25'],
                ],
            ],
            [
                'van_key'         => 'VAN II',
                'vehicle_number'  => 'TN-33-AX-1002',
                'registration'    => 'TN33AX1002',
                'make'            => 'Force Motors',
                'model'           => 'Cruiser (24 Seater)',
                'vehicle_type'    => 'van',
                'capacity'        => 24,
                'driver_name'     => 'K. Raman',
                'driver_mobile'   => '9842711002',
                'driver_license'  => 'TN-33-2016-0012342',
                'driver_address'  => 'Bhavani, Erode - 638301',
                'attendant_name'  => 'M. Kalavathi',
                'attendant_mobile'=> '9842712002',
                'attendant_address'=> 'RN Pudhur, Erode - 638005',
                'attendant_aadhaar'=> '784512349002',
                'route_name'      => 'Van II — Bhavani / Texvalley / Thindal',
                'route_number'    => 'VAN-02',
                'from_location'   => 'Bhavani',
                'to_location'     => 'Postal Nagar',
                'distance_km'     => 15.0,
                'fee'             => 15000,
                'stops' => [
                    ['name' => 'RN PUDHUR', 'km' => 8, 'fee' => 7500, 'pickup' => '07:30', 'drop' => '16:25'],
                    ['name' => 'BHAVANI', 'km' => 15, 'fee' => 7500, 'pickup' => '07:38', 'drop' => '16:20'],
                    ['name' => 'TEXVALLY', 'km' => 12, 'fee' => 7500, 'pickup' => '07:46', 'drop' => '16:12'],
                    ['name' => 'NASIYANOOR', 'km' => 10, 'fee' => 12000, 'pickup' => '07:55', 'drop' => '16:05'],
                    ['name' => 'THINDAL', 'km' => 8, 'fee' => 12000, 'pickup' => '08:04', 'drop' => '15:55'],
                    ['name' => 'VEERAPPANPALAYAM', 'km' => 8, 'fee' => 12000, 'pickup' => '08:12', 'drop' => '15:48'],
                    ['name' => 'VEERAPPANPALAYAM PIRIVU', 'km' => 6, 'fee' => 12000, 'pickup' => '08:18', 'drop' => '15:42'],
                    ['name' => 'KAIKKATIVALASU', 'km' => 3.5, 'fee' => 15000, 'pickup' => '08:24', 'drop' => '15:35'],
                    ['name' => 'POSTAL NAGAR', 'km' => 2.5, 'fee' => 15000, 'pickup' => '08:30', 'drop' => '15:30'],
                ],
            ],
            [
                'van_key'         => 'VAN III',
                'vehicle_number'  => 'TN-33-AX-1003',
                'registration'    => 'TN33AX1003',
                'make'            => 'Force Motors',
                'model'           => 'Cruiser (24 Seater)',
                'vehicle_type'    => 'van',
                'capacity'        => 24,
                'driver_name'     => 'V. Murugesan',
                'driver_mobile'   => '9842711003',
                'driver_license'  => 'TN-33-2017-0012343',
                'driver_address'  => 'Soolai, Erode - 638004',
                'attendant_name'  => 'R. Saraswathi',
                'attendant_mobile'=> '9842712003',
                'attendant_address'=> 'Periyasemur, Erode - 638004',
                'attendant_aadhaar'=> '784512349003',
                'route_name'      => 'Van III — Soolai / Periyasemur / D Mart',
                'route_number'    => 'VAN-03',
                'from_location'   => 'Soolai',
                'to_location'     => 'D Mart',
                'distance_km'     => 7.0,
                'fee'             => 15000,
                'stops' => [
                    ['name' => 'SOOLAI', 'km' => 3, 'fee' => 10000, 'pickup' => '07:30', 'drop' => '16:25'],
                    ['name' => 'PERIYASEMUR', 'km' => 5, 'fee' => 10000, 'pickup' => '07:36', 'drop' => '16:20'],
                    ['name' => 'EPB NAGAR', 'km' => 2, 'fee' => 10000, 'pickup' => '07:42', 'drop' => '16:14'],
                    ['name' => 'THENDRAL NAGAR', 'km' => 3, 'fee' => 9000, 'pickup' => '07:47', 'drop' => '16:08'],
                    ['name' => 'RASAMPALAYAM', 'km' => 3, 'fee' => 10000, 'pickup' => '07:52', 'drop' => '16:03'],
                    ['name' => 'S BEND', 'km' => 2, 'fee' => 10000, 'pickup' => '07:57', 'drop' => '15:58'],
                    ['name' => 'PP GARDEN', 'km' => 2, 'fee' => 10000, 'pickup' => '08:02', 'drop' => '15:53'],
                    ['name' => 'NEW RAM NAGAR', 'km' => 3, 'fee' => 10000, 'pickup' => '08:07', 'drop' => '15:48'],
                    ['name' => 'PANDIYAN NAGAR', 'km' => 1, 'fee' => 10000, 'pickup' => '08:12', 'drop' => '15:43'],
                    ['name' => 'MOKKAIYAN PALAYAM', 'km' => 7, 'fee' => 15000, 'pickup' => '08:18', 'drop' => '15:37'],
                    ['name' => 'K KULAM', 'km' => 5, 'fee' => 12000, 'pickup' => '08:24', 'drop' => '15:32'],
                    ['name' => 'BHARATHI THEATER', 'km' => 3, 'fee' => 10000, 'pickup' => '08:29', 'drop' => '15:27'],
                    ['name' => 'EDAYANKATTUVALASU', 'km' => 2, 'fee' => 10000, 'pickup' => '08:33', 'drop' => '15:23'],
                    ['name' => 'D MART', 'km' => 6, 'fee' => 12000, 'pickup' => '08:38', 'drop' => '15:18'],
                ],
            ],
            [
                'van_key'         => 'VAN IV',
                'vehicle_number'  => 'TN-33-AX-1004',
                'registration'    => 'TN33AX1004',
                'make'            => 'Force Motors',
                'model'           => 'Cruiser (24 Seater)',
                'vehicle_type'    => 'van',
                'capacity'        => 24,
                'driver_name'     => 'M. Senthil Kumar',
                'driver_mobile'   => '9842711004',
                'driver_license'  => 'TN-33-2018-0012344',
                'driver_address'  => 'Vairapalayam, Erode - 638003',
                'attendant_name'  => 'P. Revathi',
                'attendant_mobile'=> '9842712004',
                'attendant_address'=> 'Karungalpalayam, Erode - 638003',
                'attendant_aadhaar'=> '784512349004',
                'route_name'      => 'Van IV — Vairapalayam / Karungalpalayam / Chennai Silks',
                'route_number'    => 'VAN-04',
                'from_location'   => 'Vairapalayam',
                'to_location'     => 'Veerappanchathram',
                'distance_km'     => 10.0,
                'fee'             => 15000,
                'stops' => [
                    ['name' => 'VAIRAPALAYAM', 'km' => 10, 'fee' => 15000, 'pickup' => '07:45', 'drop' => '16:15'],
                    ['name' => 'KARUNGALPALAYAM', 'km' => 7, 'fee' => 12000, 'pickup' => '07:54', 'drop' => '16:06'],
                    ['name' => 'THIRUNAGARCOLONY', 'km' => 7, 'fee' => 12000, 'pickup' => '08:02', 'drop' => '15:58'],
                    ['name' => 'CHENNAI SILKS', 'km' => 7, 'fee' => 12000, 'pickup' => '08:10', 'drop' => '15:50'],
                    ['name' => 'VALAIYAKKAR STREET', 'km' => 6, 'fee' => 12000, 'pickup' => '08:17', 'drop' => '15:43'],
                    ['name' => 'PERIYAVALASU', 'km' => 1.5, 'fee' => 6000, 'pickup' => '08:25', 'drop' => '15:35'],
                    ['name' => 'VEERAPPANCHATHRAM', 'km' => 4, 'fee' => 10000, 'pickup' => '08:32', 'drop' => '15:28'],
                ],
            ],
            [
                'van_key'         => 'VAN V',
                'vehicle_number'  => 'TN-33-AX-1005',
                'registration'    => 'TN33AX1005',
                'make'            => 'Force Motors',
                'model'           => 'Cruiser (24 Seater)',
                'vehicle_type'    => 'van',
                'capacity'        => 24,
                'driver_name'     => 'A. Thangavel',
                'driver_mobile'   => '9842711005',
                'driver_license'  => 'TN-33-2019-0012345',
                'driver_address'  => 'Palayapalayam, Erode - 638011',
                'attendant_name'  => 'D. Meena',
                'attendant_mobile'=> '9842712005',
                'attendant_address'=> 'Manickampalayam, Erode - 638004',
                'attendant_aadhaar'=> '784512349005',
                'route_name'      => 'Van V — Palayapalayam / Bus Stand / Periyar Nagar',
                'route_number'    => 'VAN-05',
                'from_location'   => 'Muthampalayam',
                'to_location'     => 'Periyar Nagar',
                'distance_km'     => 10.0,
                'fee'             => 15000,
                'stops' => [
                    ['name' => 'MUTHAMPALAYAM', 'km' => 10, 'fee' => 7500, 'pickup' => '07:30', 'drop' => '16:25'],
                    ['name' => 'SURAMPATTI', 'km' => 5, 'fee' => 12000, 'pickup' => '07:37', 'drop' => '16:18'],
                    ['name' => 'KARUNGALPALAYAM', 'km' => 7, 'fee' => 12000, 'pickup' => '07:43', 'drop' => '16:12'],
                    ['name' => 'KUMALANKUTTAI', 'km' => 5, 'fee' => 9000, 'pickup' => '07:49', 'drop' => '16:06'],
                    ['name' => 'VEERAPPANCHATHRAM', 'km' => 4, 'fee' => 12000, 'pickup' => '07:55', 'drop' => '16:00'],
                    ['name' => 'PALAYAPALAYAM', 'km' => 5, 'fee' => 12000, 'pickup' => '08:01', 'drop' => '15:54'],
                    ['name' => 'SUDHANANDHA NAGAR', 'km' => 7, 'fee' => 15000, 'pickup' => '08:07', 'drop' => '15:48'],
                    ['name' => 'MANICKAMPALAYAM', 'km' => 1, 'fee' => 6000, 'pickup' => '08:13', 'drop' => '15:42'],
                    ['name' => 'BUS STAND', 'km' => 5, 'fee' => 7500, 'pickup' => '08:19', 'drop' => '15:36'],
                    ['name' => 'SOOLAI', 'km' => 3, 'fee' => 10000, 'pickup' => '08:24', 'drop' => '15:31'],
                    ['name' => 'PERIYAVALASU', 'km' => 1, 'fee' => 6000, 'pickup' => '08:29', 'drop' => '15:26'],
                    ['name' => 'MANICKAMPALAYAM (Stop 2)', 'km' => 1, 'fee' => 6000, 'pickup' => '08:33', 'drop' => '15:22'],
                    ['name' => 'PERIYAR NAGAR', 'km' => 5, 'fee' => 12000, 'pickup' => '08:38', 'drop' => '15:17'],
                ],
            ],
        ];

        DB::transaction(function () use ($vansData) {
            foreach ($vansData as $vItem) {
                // 1. Create or update Vehicle
                $vehicle = Vehicle::updateOrCreate(
                    ['vehicle_number' => $vItem['vehicle_number']],
                    [
                        'registration_number'   => $vItem['registration'],
                        'make'                  => $vItem['make'],
                        'model'                 => $vItem['model'],
                        'vehicle_type'          => $vItem['vehicle_type'],
                        'seating_capacity'      => $vItem['capacity'],
                        'capacity'              => $vItem['capacity'],
                        'driver_name'           => $vItem['driver_name'],
                        'driver_mobile'         => $vItem['driver_mobile'],
                        'driver_contact'        => $vItem['driver_mobile'],
                        'driver_license'        => $vItem['driver_license'],
                        'driver_address'        => $vItem['driver_address'],
                        'attendant_name'        => $vItem['attendant_name'],
                        'attendant_mobile'      => $vItem['attendant_mobile'],
                        'attendant_address'     => $vItem['attendant_address'],
                        'attendant_aadhaar'     => $vItem['attendant_aadhaar'],
                        'status'                => 'active',
                        'is_active'             => true,
                        'gps_enabled'           => true,
                        'gps_status'            => 'online',
                        'fitness_expiry'        => now()->addMonths(10)->toDateString(),
                        'insurance_expiry'      => now()->addMonths(8)->toDateString(),
                        'permit_expiry'         => now()->addYear()->toDateString(),
                        'puc_expiry'            => now()->addMonths(5)->toDateString(),
                        'tax_expiry'            => now()->addMonths(6)->toDateString(),
                    ]
                );

                // 2. Create or update Route
                $route = TransportRoute::updateOrCreate(
                    ['route_number' => $vItem['route_number']],
                    [
                        'route_name'    => $vItem['route_name'],
                        'vehicle_id'    => $vehicle->id,
                        'from_location' => $vItem['from_location'],
                        'to_location'   => $vItem['to_location'],
                        'distance_km'   => $vItem['distance_km'],
                        'fee'           => $vItem['fee'],
                        'departure_time'=> '07:25:00',
                        'arrival_time'  => '08:45:00',
                        'is_active'     => true,
                    ]
                );

                // Link vehicle back to route
                $vehicle->update(['route_id' => $route->id]);

                // 3. Create or update Attendant
                if (!empty($vItem['attendant_name'])) {
                    TransportAttendant::updateOrCreate(
                        ['vehicle_id' => $vehicle->id],
                        [
                            'name'       => $vItem['attendant_name'],
                            'mobile'     => $vItem['attendant_mobile'],
                            'aadhaar'    => $vItem['attendant_aadhaar'],
                            'route_id'   => $route->id,
                            'is_active'  => true,
                        ]
                    );
                }

                // 4. Create or update stops for this route
                // Remove existing stops for this route so we have the exact sequence clean
                TransportStop::where('route_id', $route->id)->delete();

                foreach ($vItem['stops'] as $idx => $stopData) {
                    TransportStop::create([
                        'route_id'    => $route->id,
                        'vehicle_id'  => $vehicle->id,
                        'van_number'  => $vItem['van_key'],
                        'name'        => $stopData['name'],
                        'stop_order'  => $idx + 1,
                        'distance_km' => $stopData['km'],
                        'fare'        => $stopData['fee'],
                        'pickup_time' => $stopData['pickup'],
                        'drop_time'   => $stopData['drop'],
                        'landmark'    => $stopData['name'],
                    ]);
                }
            }
        });
    }
}
