<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admins = User::role(['super_admin', 'admin'])->get();
$count = 0;
foreach ($admins as $admin) {
    if (empty($admin->admin_pin)) {
        // Model casts 'admin_pin' => 'hashed', so assigning '1122' automatically hashes with bcrypt
        $admin->admin_pin = '1122';
        $admin->save();
        $count++;
        echo "Updated PIN for: {$admin->email} (ID: {$admin->id})\n";
    }
}
echo "Total admin PINs hashed and saved: {$count}\n";
