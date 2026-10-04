<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$count = \App\Models\Student::count();
$enrollments = \App\Models\StudentEnrollment::count();
echo "Students count: {$count}\n";
echo "Enrollments count: {$enrollments}\n";

$sampleEnrollments = \App\Models\StudentEnrollment::with(['student', 'class'])->take(5)->get();
foreach ($sampleEnrollments as $e) {
    echo "- Student: " . ($e->student->full_name ?? 'N/A') . " | Adm No: " . ($e->student->admission_no ?? 'N/A') . " | Class: " . ($e->class->name ?? 'N/A') . " | Year: {$e->academic_year_id}\n";
}

$bookNoteItemsCount = \App\Models\AdmissionBookNoteItem::count();
echo "AdmissionBookNoteItems count: {$bookNoteItemsCount}\n";

