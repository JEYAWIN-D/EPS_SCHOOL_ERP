<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

foreach (Route::getRoutes() as $r) {
    if (str_starts_with($r->getName() ?? '', 'transport')) {
        echo sprintf("%-35s %-40s %s\n", $r->getName(), $r->uri(), $r->getActionMethod());
    }
}
