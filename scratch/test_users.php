<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$roles = Spatie\Permission\Models\Role::pluck('name')->toArray();
echo "Roles: " . implode(', ', $roles) . "\n";

foreach ($roles as $r) {
    $u = App\Models\User::role($r)->first(['id', 'name', 'email']);
    if ($u) {
        echo "Role: {$r} -> User: {$u->name} ({$u->email})\n";
    }
}
