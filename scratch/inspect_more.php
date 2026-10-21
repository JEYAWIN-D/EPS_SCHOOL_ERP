<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "notifications columns: " . implode(', ', Schema::getColumnListing('notifications')) . "\n";
echo "audit_logs columns: " . implode(', ', Schema::getColumnListing('audit_logs')) . "\n";
echo "designations columns: " . implode(', ', Schema::getColumnListing('designations')) . "\n";
echo "departments columns: " . implode(', ', Schema::getColumnListing('departments')) . "\n";
echo "academic_groups columns: " . implode(', ', Schema::getColumnListing('academic_groups')) . "\n";
