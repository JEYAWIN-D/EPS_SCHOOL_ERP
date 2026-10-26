<?php

require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'C:\\Users\\jeyaw\\Downloads\\TRANSPORT FULL DETAILS (1).xlsx';
if (!file_exists($file)) {
    $file = 'C:\\Users\\jeyaw\\Downloads\\TRANSPORT FULL DETAILS.xlsx';
}

echo "Loading {$file}...\n";
$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($file);

foreach ($spreadsheet->getSheetNames() as $name) {
    $sheet = $spreadsheet->getSheetByName($name);
    echo "Sheet: '{$name}', HighestRow: " . $sheet->getHighestRow() . ", HighestCol: " . $sheet->getHighestColumn() . "\n";
    for ($r = 1; $r <= 4; $r++) {
        $rowVals = [];
        foreach (range('A', 'G') as $c) {
            $rowVals[] = (string)$sheet->getCell($c . $r)->getValue();
        }
        echo "  Row $r: " . implode(' | ', $rowVals) . "\n";
    }
}
