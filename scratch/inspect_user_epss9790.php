<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

$emp = Employee::find(278);
echo "Employee 278:\n";
echo "  Name: " . ($emp ? $emp->full_name : 'NOT FOUND') . "\n";
echo "  Code: " . ($emp ? $emp->employee_code : 'NOT FOUND') . "\n";
echo "  DOB: " . ($emp ? $emp->dob : 'NOT FOUND') . "\n";
echo "  User ID: " . ($emp ? ($emp->user_id ?? 'NULL') : 'NOT FOUND') . "\n";

$user = User::where('username', 'EPSS9790')
    ->orWhere('email', 'djeyaw@gmail.com')
    ->orWhere('employee_id', 278)
    ->first();

echo "\nUser for EPSS9790:\n";
if ($user) {
    echo "  ID: {$user->id}\n";
    echo "  Name: {$user->name}\n";
    echo "  Username: {$user->username}\n";
    echo "  Email: {$user->email}\n";
    echo "  Mobile: {$user->mobile}\n";
    echo "  Employee ID: {$user->employee_id}\n";
    echo "  Is Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "  Must Change Password: " . ($user->must_change_password ? 'Yes' : 'No') . "\n";
    echo "  Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
    echo "  Permissions Count: " . $user->getAllPermissions()->count() . "\n";
    echo "  Direct Permissions: " . $user->permissions->pluck('name')->implode(', ') . "\n";

    if ($emp && $emp->dob) {
        $dobDDMMYYYY = \Carbon\Carbon::parse($emp->dob)->format('dmY');
        $dobYYYYMMDD = \Carbon\Carbon::parse($emp->dob)->format('Y-m-d');
        $dobD_M_Y = \Carbon\Carbon::parse($emp->dob)->format('d-m-Y');
        echo "\nTesting Passwords against hash:\n";
        echo "  Check DDMMYYYY ({$dobDDMMYYYY}): " . (Hash::check($dobDDMMYYYY, $user->password) ? 'MATCH!' : 'NO') . "\n";
        echo "  Check YYYY-MM-DD ({$dobYYYYMMDD}): " . (Hash::check($dobYYYYMMDD, $user->password) ? 'MATCH!' : 'NO') . "\n";
        echo "  Check d-m-Y ({$dobD_M_Y}): " . (Hash::check($dobD_M_Y, $user->password) ? 'MATCH!' : 'NO') . "\n";
        echo "  Check 'password': " . (Hash::check('password', $user->password) ? 'MATCH!' : 'NO') . "\n";
        echo "  Check '12345678': " . (Hash::check('12345678', $user->password) ? 'MATCH!' : 'NO') . "\n";
        echo "  Check 'admin123': " . (Hash::check('admin123', $user->password) ? 'MATCH!' : 'NO') . "\n";
    }
} else {
    echo "  NO USER RECORD FOUND!\n";
}
