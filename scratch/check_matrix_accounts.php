<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

$rolesToTest = [
    'super_admin',
    'admin',
    'principal',
    'teacher',
    'class_teacher',
    'librarian',
    'staff', // Read-only / baseline staff
];

echo "=== CHECKING EXISTING ACCOUNTS FOR TEST MATRIX ===\n";
foreach ($rolesToTest as $r) {
    $user = User::role($r)->where('is_active', true)->first();
    if ($user) {
        echo sprintf("Role '%-15s': User #%-4d | %-25s | %-25s\n", $r, $user->id, $user->username, $user->email);
    } else {
        echo sprintf("Role '%-15s': NOT FOUND\n", $r);
    }
}
