<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Timetable;

$samples = Timetable::take(5)->get(['id', 'day_of_week', 'period_number', 'start_time', 'end_time', 'teacher_id']);
foreach ($samples as $s) {
    echo "ID: {$s->id} | Day: " . var_export($s->day_of_week, true) . " | Period: {$s->period_number} | Teacher: {$s->teacher_id}\n";
}
