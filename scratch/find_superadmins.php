<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$superAdmins = User::whereHas('roles', function($q) {
    $q->where('name', 'super_admin')->orWhere('name', 'superadmin')->orWhere('name', 'admin');
})->get();

foreach ($superAdmins as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Roles: " . implode(', ', $u->roles->pluck('name')->toArray()) . PHP_EOL;
}
