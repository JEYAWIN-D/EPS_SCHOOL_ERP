<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = new \App\Services\TransportManagementService();
$res = $svc->importBusStopsFromExcel('C:/Users/jeyaw/Downloads/TRANSPORT DETAILS (2).xlsx');

echo "=== IMPORT BUS STOPS RESULT ===\n";
echo "Total rows: {$res['total_rows']}\n";
echo "Created: {$res['success_count']}\n";
echo "Updated/Duplicate: {$res['duplicate_count']}\n";
echo "Invalid: {$res['invalid_count']}\n";
echo "Failed: {$res['failed_count']}\n";
if (!empty($res['errors'])) {
    echo "Errors:\n" . implode("\n", $res['errors']) . "\n";
}
echo "Sample 5 imported stops:\n";
print_r(array_slice($res['imported_stops'], 0, 5));
