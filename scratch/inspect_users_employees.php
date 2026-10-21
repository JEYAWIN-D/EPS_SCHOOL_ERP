<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Employee;

echo "Total Users: " . User::count() . "\n";
echo "Total Employees: " . Employee::count() . "\n";
$usersWithEmp = User::whereNotNull('employee_id')->count();
echo "Users with employee_id: " . $usersWithEmp . "\n";

echo "Sample 5 users:\n";
foreach (User::take(5)->get() as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Username: {$u->username} | EmpId: {$u->employee_id}\n";
}

echo "\nSample 5 employees:\n";
foreach (Employee::take(5)->get() as $e) {
    echo "ID: {$e->id} | Code: {$e->employee_code} | Name: {$e->full_name} | Type: {$e->employee_type} | Desig: {$e->designation}\n";
}
