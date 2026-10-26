<?php

require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$files = glob(__DIR__ . '/../storage/framework/cache/laravel-excel/*.xlsx');
foreach ($files as $f) {
    echo "=== FILE: " . basename($f) . PHP_EOL;
    try {
        $spreadsheet = IOFactory::load($f);
        foreach ($spreadsheet->getSheetNames() as $name) {
            echo "  Sheet: " . $name . PHP_EOL;
            $sheet = $spreadsheet->getSheetByName($name);
            $maxRow = min(15, $sheet->getHighestRow());
            $maxCol = $sheet->getHighestColumn();
            for ($row = 1; $row <= $maxRow; $row++) {
                $rowVals = [];
                for ($col = 'A'; $col <= min('H', $maxCol); $col++) {
                    $rowVals[] = (string)$sheet->getCell($col . $row)->getValue();
                }
                echo "    Row $row: " . implode(' | ', $rowVals) . PHP_EOL;
            }
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }
}
