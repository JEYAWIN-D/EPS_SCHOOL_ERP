<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use Spatie\Permission\Models\Permission;

echo "--- 1. Checking Permissions Referenced in Routes ---\n";
$routeCollection = Route::getRoutes();
$usedPerms = [];

foreach ($routeCollection as $route) {
    foreach ($route->middleware() as $mw) {
        if (str_starts_with($mw, 'permission:')) {
            $pName = substr($mw, strlen('permission:'));
            $usedPerms[$pName] = true;
        }
    }
}

$dbPerms = Permission::pluck('name')->toArray();
$missingInDb = [];

foreach (array_keys($usedPerms) as $p) {
    if (!in_array($p, $dbPerms, true)) {
        $missingInDb[] = $p;
    }
}

echo "Permissions used in Routes: " . count($usedPerms) . "\n";
echo "Permissions defined in DB: " . count($dbPerms) . "\n";
echo "Permissions used in Routes BUT MISSING in DB: " . count($missingInDb) . "\n";
foreach ($missingInDb as $m) {
    echo "  ✗ MISSING: '{$m}'\n";
}

echo "\n--- 2. Checking Permissions Checked in Blade Views (@can) ---\n";
$bladeFiles = glob(resource_path('views/**/*.blade.php'));
$viewPerms = [];

// recursive scan
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $content = file_get_contents($file->getPathname());
        if (preg_match_all("/@can\(['\"]([^'\"]+)['\"]\)/", $content, $matches)) {
            foreach ($matches[1] as $pm) {
                $viewPerms[$pm] = true;
            }
        }
    }
}

$missingFromViews = [];
foreach (array_keys($viewPerms) as $vp) {
    if (!in_array($vp, $dbPerms, true)) {
        $missingFromViews[] = $vp;
    }
}

echo "Permissions checked in Blade (@can): " . count($viewPerms) . "\n";
echo "Permissions in Blade BUT MISSING in DB: " . count($missingFromViews) . "\n";
foreach ($missingFromViews as $mv) {
    echo "  ✗ MISSING FROM DB: '{$mv}'\n";
}

