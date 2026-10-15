<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$tables = [
    'users',
    'employees',
    'roles',
    'permissions',
    'timetables',
    'teacher_subject_allocations',
    'staff_class_teacher_assignments',
    'classes',
    'sections',
    'academic_years',
    'academic_groups',
    'staff_attendance',
    'attendance_records',
    'user_data_scopes',
    'account_target_configs',
];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "=== TABLE: {$table} ===\n";
        echo implode(', ', Schema::getColumnListing($table)) . "\n\n";
    } else {
        echo "=== TABLE: {$table} (NOT FOUND) ===\n\n";
    }
}
