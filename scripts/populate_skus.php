<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicGroup;
use App\Models\BookNoteChecklist;
use App\Models\Classes;

$classes = Classes::all()->keyBy('id');
$groups = AcademicGroup::all()->keyBy('id');

$count = 0;
foreach (BookNoteChecklist::all() as $item) {
    $c = $classes->get($item->class_id);
    $rawName = strtoupper(trim($c?->name ?? 'CL'));
    $classCode = match($rawName) {
        'PRE-KG', 'PRE KG' => 'PKG',
        'LKG', 'JUNIOR KG' => 'LKG',
        'UKG', 'SENIOR KG' => 'UKG',
        default => preg_replace('/[^A-Z0-9]/', '', $rawName) ?: 'CL'
    };
    $grpCode = '';
    if ($item->group_id && isset($groups[$item->group_id])) {
        $g = $groups[$item->group_id];
        $grpCode = match(strtoupper(trim($g->code ?? ''))) {
            'BIO'      => '-BIO',
            'CS'       => '-CS',
            'BIO_CS'   => '-BIO',
            'ACCOUNTS' => '-ACC',
            default    => '-' . substr(preg_replace('/[^A-Z0-9]/', '', $g->code), 0, 3)
        };
    }
    $typeCode = $item->item_type === 'BOOK' ? 'BK' : 'NT';
    $num = str_pad($item->display_order ?: 1, 3, '0', STR_PAD_LEFT);
    $sku = "EPS-{$classCode}{$grpCode}-{$typeCode}-{$num}";
    $item->update(['sku' => $sku]);
    $count++;
}

echo "Successfully populated SKU for {$count} Book & Note items.\n";
