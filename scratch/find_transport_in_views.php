<?php
$lines = file('resources/views/admissions/create.blade.php');
foreach ($lines as $idx => $line) {
    if (stripos($line, 'transport') !== false || stripos($line, 'bus') !== false || stripos($line, 'van') !== false) {
        echo ($idx + 1) . ': ' . trim(substr($line, 0, 100)) . "\n";
    }
}
