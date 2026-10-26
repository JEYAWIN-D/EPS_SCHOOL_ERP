<?php

require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load(__DIR__ . '/../storage/framework/cache/laravel-excel/laravel-excel-JwCVdV4waYcwmuWCVjnxK7i83mFpSYGy.xlsx');
$sheet = $spreadsheet->getActiveSheet();
$maxRow = $sheet->getHighestRow();
echo "Highest row: " . $maxRow . PHP_EOL;

$vans = [];
$sampleByVan = [];
for ($r = 2; $r <= $maxRow; $r++) {
    $van = (string)$sheet->getCell('B' . $r)->getValue();
    $name = (string)$sheet->getCell('C' . $r)->getValue();
    $class = (string)$sheet->getCell('D' . $r)->getValue();
    $area = (string)$sheet->getCell('E' . $r)->getValue();
    $pickup = (string)$sheet->getCell('F' . $r)->getValue();
    $drop = (string)$sheet->getCell('G' . $r)->getValue();
    $fare = (string)$sheet->getCell('H' . $r)->getValue();
    
    $vans[$van] = ($vans[$van] ?? 0) + 1;
    if (!isset($sampleByVan[$van])) {
        $sampleByVan[$van] = [];
    }
    if (count($sampleByVan[$van]) < 5) {
        $sampleByVan[$van][] = [
            'name' => $name, 'class' => $class, 'area' => $area,
            'pickup' => $pickup, 'drop' => $drop, 'fare' => $fare
        ];
    }
}

echo "Total rows: " . ($maxRow - 1) . PHP_EOL;
print_r($vans);
echo "Samples:\n";
print_r($sampleByVan);
