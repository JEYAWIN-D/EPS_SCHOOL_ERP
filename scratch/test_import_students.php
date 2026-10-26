<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TransportManagementService;

$service = app(TransportManagementService::class);
$excelPath = 'C:\\Users\\jeyaw\\Downloads\\TRANSPORT FULL DETAILS (1).xlsx';
if (!file_exists($excelPath)) {
    $excelPath = 'C:\\Users\\jeyaw\\Downloads\\TRANSPORT FULL DETAILS.xlsx';
}

echo "[" . date('H:i:s') . "] Starting importStudentAllocationsFromExcel...\n";
$start = microtime(true);
$summary = $service->importStudentAllocationsFromExcel($excelPath, 1, basename($excelPath));
$elapsed = round(microtime(true) - $start, 2);

echo "[" . date('H:i:s') . "] Finished in {$elapsed}s!\n";
echo "Total rows: " . ($summary['total_rows'] ?? 0) . "\n";
echo "Success: " . ($summary['success_count'] ?? 0) . "\n";
echo "Duplicate/Roster: " . ($summary['duplicate_count'] ?? 0) . "\n";
echo "Failed: " . ($summary['failed_count'] ?? 0) . "\n";
if (!empty($summary['errors'])) {
    echo "Errors:\n";
    foreach (array_slice($summary['errors'], 0, 5) as $err) {
        echo " - {$err}\n";
    }
}
