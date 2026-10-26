<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransportAllotment;
use App\Models\Student;
use Illuminate\Support\Facades\Schema;

echo "Allotments count: " . TransportAllotment::count() . PHP_EOL;
echo "Active allotments count: " . TransportAllotment::where('is_active', true)->count() . PHP_EOL;

$cols = Schema::getColumnListing('students');
$transportCols = array_filter($cols, fn($c) => str_contains($c, 'transport') || str_contains($c, 'route') || str_contains($c, 'stop') || str_contains($c, 'van') || str_contains($c, 'vehicle'));
echo "Student transport columns: " . implode(', ', $transportCols) . PHP_EOL;

foreach ($transportCols as $c) {
    echo "Students with non-null {$c}: " . Student::whereNotNull($c)->count() . PHP_EOL;
}

if (Schema::hasTable('transport_roster_passengers')) {
    echo "transport_roster_passengers count: " . DB::table('transport_roster_passengers')->count() . PHP_EOL;
}
if (Schema::hasTable('transport_assignment_histories')) {
    echo "transport_assignment_histories count: " . DB::table('transport_assignment_histories')->count() . PHP_EOL;
}

echo "Distinct transport_fee values: " . json_encode(Student::distinct()->pluck('transport_fee')) . PHP_EOL;

foreach (\App\Models\TransportRoute::all() as $r) {
    echo "{$r->route_name}: {$r->allotments()->where('is_active', true)->count()} active allotments, {$r->stops()->count()} stops" . PHP_EOL;
}

