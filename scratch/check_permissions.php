<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$perms = Permission::where('name', 'like', '%transport%')->get();
echo "=== TRANSPORT PERMISSIONS (" . $perms->count() . ") ===\n";
foreach ($perms as $p) {
    echo "ID: {$p->id} | {$p->name} | Guard: {$p->guard_name}\n";
}

echo "\n=== ROLES WITH TRANSPORT PERMISSIONS ===\n";
foreach (Role::all() as $r) {
    $rPerms = $r->permissions()->where('name', 'like', '%transport%')->pluck('name');
    if ($rPerms->isNotEmpty()) {
        echo "Role: {$r->name} -> " . $rPerms->implode(', ') . "\n";
    }
}
