<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== COUNTS ===" . PHP_EOL;
echo "Students: " . Student::count() . PHP_EOL;
echo "Employees: " . Employee::count() . PHP_EOL;
echo "Users: " . User::count() . PHP_EOL;

echo PHP_EOL . "=== USERS BREAKDOWN ===" . PHP_EOL;
foreach (User::all() as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Role: " . ($u->role ?? 'N/A') . " | Roles: " . implode(', ', $u->roles->pluck('name')->toArray() ?? []) . PHP_EOL;
}

echo PHP_EOL . "=== EMPLOYEES SAMPLE & CATEGORIES ===" . PHP_EOL;
$deptCounts = Employee::select('department', DB::raw('count(*) as c'))->groupBy('department')->get();
foreach ($deptCounts as $d) {
    echo "Dept: " . ($d->department ?? 'None') . " -> " . $d->c . PHP_EOL;
}

echo PHP_EOL . "=== FOREIGN KEYS / RELATED TABLES TO STUDENTS ===" . PHP_EOL;
$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
foreach ($tables as $t) {
    $tname = $t->table_name;
    if (Schema::hasColumn($tname, 'student_id')) {
        $c = DB::table($tname)->count();
        echo "Table with student_id: {$tname} -> {$c} rows" . PHP_EOL;
    }
}

echo PHP_EOL . "=== FOREIGN KEYS / RELATED TABLES TO EMPLOYEES ===" . PHP_EOL;
foreach ($tables as $t) {
    $tname = $t->table_name;
    if (Schema::hasColumn($tname, 'employee_id') || Schema::hasColumn($tname, 'staff_id') || Schema::hasColumn($tname, 'teacher_id')) {
        $c = DB::table($tname)->count();
        echo "Table with employee/staff/teacher: {$tname} -> {$c} rows" . PHP_EOL;
    }
}
