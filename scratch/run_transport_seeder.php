<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

require_once __DIR__ . '/../database/seeders/TransportDataSeeder.php';

$seeder = new \Database\Seeders\TransportDataSeeder();
$seeder->run();

echo "Seeded successfully!\n";
echo "Vehicles count: " . \App\Models\Vehicle::count() . "\n";
echo "Routes count: " . \App\Models\TransportRoute::count() . "\n";
echo "Stops count: " . \App\Models\TransportStop::count() . "\n";
echo "Attendants count: " . \App\Models\TransportAttendant::count() . "\n";

foreach (\App\Models\TransportRoute::with('stops', 'vehicle')->get() as $r) {
    echo "- Route #{$r->id}: {$r->route_name} (Vehicle: {$r->vehicle?->vehicle_number}) - {$r->stops->count()} stops\n";
}
