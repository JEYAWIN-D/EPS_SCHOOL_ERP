<?php

require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('C:/Users/jeyaw/Downloads/TRANSPORT DETAILS (2).xlsx');
$sheet = $spreadsheet->getActiveSheet();
$maxRow = $sheet->getHighestRow();
echo "Highest row: $maxRow\n";

for ($r = 1; $r <= $maxRow; $r++) {
    $vals = [];
    for ($col = 'A'; $col <= 'D'; $col++) {
        $val = trim((string)$sheet->getCell($col . $r)->getValue());
        $vals[] = $val;
    }
    if (array_filter($vals)) {
        echo "Row $r: " . implode(' | ', $vals) . "\n";
    }
}
