<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;
use App\Models\TransportRosterPassenger;
use App\Models\TransportAllotment;
use App\Services\TransportManagementService;
use PhpOffice\PhpSpreadsheet\IOFactory;

ob_implicit_flush(true);

$service = app(TransportManagementService::class);
$excelPath = 'C:\\Users\\jeyaw\\Downloads\\TRANSPORT FULL DETAILS (1).xlsx';
if (!file_exists($excelPath)) {
    $excelPath = 'C:\\Users\\jeyaw\\Downloads\\TRANSPORT FULL DETAILS.xlsx';
}

echo "Step 1: Backup transport data: " . date('H:i:s') . "\n";
$bStart = microtime(true);
$service->backupTransportData();
echo "  Backup took " . round(microtime(true) - $bStart, 2) . "s\n";

echo "Step 2: Load Excel: " . date('H:i:s') . "\n";
$lStart = microtime(true);
$reader = IOFactory::createReaderForFile($excelPath);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($excelPath);
echo "  Excel loaded in " . round(microtime(true) - $lStart, 2) . "s\n";

echo "Step 3: Preload students: " . date('H:i:s') . "\n";
$sStart = microtime(true);
$students = Student::select('id', 'first_name', 'last_name', 'admission_no', 'status')->get();
echo "  Loaded {$students->count()} students in " . round(microtime(true) - $sStart, 2) . "s\n";

echo "Step 4: Check sheets: " . date('H:i:s') . "\n";
$vanVehicleMap = [
    'VAN I'   => 'TN-33-AX-1001',
    'VAN II'  => 'TN-33-AX-1002',
    'VAN III' => 'TN-33-AX-1003',
    'VAN IV'  => 'TN-33-AX-1004',
    'VAN V'   => 'TN-33-AX-1005',
];

$vehicles = Vehicle::whereIn('vehicle_number', array_values($vanVehicleMap))->get()->keyBy('vehicle_number');
$routes   = TransportRoute::whereIn('vehicle_id', $vehicles->pluck('id')->filter())->with('stops')->get()->keyBy('vehicle_id');

$stopCache = [];
foreach ($routes as $rObj) {
    foreach ($rObj->stops as $sObj) {
        $stopCache[$rObj->id . '_' . strtoupper(trim($sObj->name))] = $sObj;
    }
}

$sheet = $spreadsheet->getSheetByName('VAN II');
$maxRow = min($sheet->getHighestRow(), 35);
echo "Sheet VAN II: maxRow = {$maxRow}\n";

for ($r = 3; $r <= $maxRow; $r++) {
    $rowStart = microtime(true);
    $name = trim((string)$sheet->getCell('B' . $r)->getValue());
    if (empty($name)) continue;

    $area = strtoupper(trim((string)$sheet->getCell('D' . $r)->getValue()));
    $amount = (float) preg_replace('/[^0-9.]/', '', (string)$sheet->getCell('E' . $r)->getValue());
    $morningTime = trim((string)$sheet->getCell('F' . $r)->getValue());
    $eveningTime = trim((string)$sheet->getCell('G' . $r)->getValue());

    $vehicle = $vehicles['TN-33-AX-1002'] ?? null;
    $route = $vehicle ? ($routes[$vehicle->id] ?? null) : null;

    $stop = null;
    if (!empty($area) && $route) {
        $stopKey = $route->id . '_' . $area;
        $stop = $stopCache[$stopKey] ?? null;
    }

    $cleanName = strtoupper(trim($name));
    $matched = false;
    foreach ($students as $st) {
        if (strtoupper(trim($st->first_name . ' ' . ($st->last_name ?? ''))) === $cleanName) {
            $matched = true;
            break;
        }
    }

    $ms = round((microtime(true) - $rowStart) * 1000, 1);
    echo "  Row {$r}: '{$name}' (Area: {$area}, Stop: " . ($stop ? $stop->name : 'NONE') . ", Matched: " . ($matched ? 'YES' : 'NO') . ") - took {$ms}ms\n";
    
    // Test assigning for the first matched student
    if ($r === 3 && $matched && $stop && $route) {
        $st = $students->firstWhere('first_name', 'DEVARASU');
        $tAssign = microtime(true);
        $allot = $service->assignStudentTransport($st->id, [
            'route_id'    => $route->id,
            'stop_id'     => $stop->id,
            'pickup_time' => '07:15:00',
            'fee'         => $amount,
        ]);
        echo "  --> assignStudentTransport took " . round((microtime(true) - $tAssign) * 1000, 1) . "ms\n";
    }
}
