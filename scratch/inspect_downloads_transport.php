<?php

require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$files = [
    'TRANSPORT FULL DETAILS (1).xlsx' => 'C:/Users/jeyaw/Downloads/TRANSPORT FULL DETAILS (1).xlsx',
    'TRANSPORT DETAILS (2).xlsx'      => 'C:/Users/jeyaw/Downloads/TRANSPORT DETAILS (2).xlsx',
    'TRANSPORT DETAILS.xlsx'          => 'C:/Users/jeyaw/Downloads/TRANSPORT DETAILS.xlsx',
    'TRANSPORT FULL DETAILS.xlsx'     => 'C:/Users/jeyaw/Downloads/TRANSPORT FULL DETAILS.xlsx',
];

foreach ($files as $label => $path) {
    if (!file_exists($path)) {
        echo "File $path does not exist.\n";
        continue;
    }
    echo "====================================================\n";
    echo "=== FILE: $label (" . filesize($path) . " bytes)\n";
    echo "====================================================\n";
    $spreadsheet = IOFactory::load($path);
    foreach ($spreadsheet->getSheetNames() as $sheetName) {
        echo "\n--- Sheet: $sheetName ---\n";
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $maxRow = min(20, $sheet->getHighestRow());
        $maxCol = $sheet->getHighestColumn();
        echo "Total rows: " . $sheet->getHighestRow() . ", Cols: $maxCol\n";
        for ($r = 1; $r <= $maxRow; $r++) {
            $rowVals = [];
            for ($col = 'A'; $col <= min('K', $maxCol); $col++) {
                $val = (string)$sheet->getCell($col . $r)->getValue();
                $rowVals[] = trim($val);
            }
            echo "Row $r: " . implode(' | ', $rowVals) . "\n";
        }
    }
}
