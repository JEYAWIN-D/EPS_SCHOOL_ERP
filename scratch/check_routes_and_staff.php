<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;

$u = User::role('staff')->where('is_active', true)->first();
echo "User #{$u->id} ({$u->email})\n";
echo "must_change_password: " . ($u->must_change_password ? 'true' : 'false') . "\n";
echo "roles: " . $u->roles->pluck('name')->implode(', ') . "\n";

echo "\n--- CHECKING ROUTE NAMES ---\n";
$routeNames = collect(Illuminate\Support\Facades\Route::getRoutes())->map(fn($r) => $r->getName())->filter()->values();

echo "Classes routes:\n";
foreach ($routeNames->filter(fn($n) => str_contains($n, 'classes')) as $r) {
    echo "  $r\n";
}

echo "Payroll routes:\n";
foreach ($routeNames->filter(fn($n) => str_contains($n, 'payroll')) as $r) {
    echo "  $r\n";
}
