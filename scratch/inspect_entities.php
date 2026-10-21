<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Classes;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Designation;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

echo "=== CLASSES ===\n";
foreach (Classes::orderBy('sort_order')->get() as $c) {
    echo "ID: {$c->id} | Name: {$c->name} | Numeric: {$c->numeric_value}\n";
}

echo "\n=== ACADEMIC YEARS ===\n";
foreach (AcademicYear::all() as $ay) {
    echo "ID: {$ay->id} | Name: {$ay->name} | Current: {$ay->is_current}\n";
}

echo "\n=== DEPARTMENTS ===\n";
foreach (Department::all() as $d) {
    echo "ID: {$d->id} | Name: {$d->name}\n";
}

echo "\n=== DESIGNATIONS ===\n";
foreach (Designation::all() as $des) {
    echo "ID: {$des->id} | Name: {$des->name} | Role: {$des->spatie_role}\n";
}

echo "\n=== ROLES ===\n";
foreach (Role::all() as $r) {
    echo "ID: {$r->id} | Name: {$r->name}\n";
}
