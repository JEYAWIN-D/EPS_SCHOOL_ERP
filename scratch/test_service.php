<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = new \App\Services\TransportManagementService();
$bk = $svc->backupTransportData();
echo "Backup created at: $bk\n";
echo "File size: " . filesize($bk) . " bytes\n";
