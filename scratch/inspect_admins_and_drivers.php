<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\TransportVehicle;
use App\Models\Employee;

echo "=== VEHICLES DRIVER INFO ===" . PHP_EOL;
foreach (DB::table('vehicles')->get() as $v) {
    echo json_encode($v) . PHP_EOL;
}

echo PHP_EOL . "=== ADMIN USERS (Non-Teacher / Non-Student) ===" . PHP_EOL;
foreach (User::all() as $u) {
    $roles = $u->roles->pluck('name')->toArray();
    $isStaffOrStudent = in_array('teacher', $roles) || in_array('student', $roles) || in_array('parent', $roles);
    if (!$isStaffOrStudent || $u->id < 10) {
        echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Roles: " . implode(', ', $roles) . " | Employee ID: " . ($u->employee_id ?? 'null') . PHP_EOL;
    }
}
