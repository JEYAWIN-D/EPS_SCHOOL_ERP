<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

$routes = Illuminate\Support\Facades\Route::getRoutes();
$usedPerms = [];

foreach ($routes as $route) {
    $middlewares = $route->gatherMiddleware();
    foreach ($middlewares as $mw) {
        if (is_string($mw) && str_starts_with($mw, 'permission:')) {
            $permString = substr($mw, strlen('permission:'));
            $perms = explode('|', $permString);
            foreach ($perms as $p) {
                $usedPerms[trim($p)][] = $route->uri();
            }
        }
    }
}

ksort($usedPerms);
echo "Total distinct permissions checked on routes: " . count($usedPerms) . "\n\n";

$existingPerms = \Spatie\Permission\Models\Permission::pluck('name')->toArray();
$missingFromDb = [];

foreach ($usedPerms as $perm => $uris) {
    $exists = in_array($perm, $existingPerms);
    $status = $exists ? "[EXISTS IN DB]" : "[MISSING IN DB!]";
    if (!$exists) {
        $missingFromDb[] = $perm;
    }
    echo sprintf("%-30s %-16s (Used on %d routes, e.g. %s)\n", $perm, $status, count($uris), $uris[0]);
}

echo "\n--- MISSING PERMISSIONS SUMMARY ---\n";
if (empty($missingFromDb)) {
    echo "All route permissions exist in DB!\n";
} else {
    echo "Found " . count($missingFromDb) . " missing permissions:\n";
    foreach ($missingFromDb as $mp) {
        echo " - " . $mp . "\n";
    }
}
