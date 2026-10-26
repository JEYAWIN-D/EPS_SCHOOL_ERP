<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$withRoll = App\Models\Student::whereNotNull('roll_number')->where('roll_number', '!=', '')->count();
$total = App\Models\Student::count();
$withAddress = App\Models\Student::where(function($q) {
    $q->whereNotNull('residential_address')->where('residential_address', '!=', '')
      ->orWhereNotNull('permanent_address')->where('permanent_address', '!=', '')
      ->orWhereNotNull('city')->where('city', '!=', '');
})->count();
$withPhone = App\Models\Student::where(function($q) {
    $q->whereNotNull('mobile')->where('mobile', '!=', '')
      ->orWhereNotNull('father_mobile')->where('father_mobile', '!=', '')
      ->orWhereNotNull('mother_mobile')->where('mother_mobile', '!=', '');
})->count();

echo "Total Students: {$total}\n";
echo "With Roll Number: {$withRoll}\n";
echo "With Phone: {$withPhone}\n";
echo "With Address: {$withAddress}\n";

$sample = App\Models\Student::whereNotNull('roll_number')->where('roll_number', '!=', '')->first();
if ($sample) {
    echo "Sample with Roll: {$sample->full_name} | Roll: {$sample->roll_number} | Adm: {$sample->admission_no}\n";
} else {
    echo "No student currently has roll_number set directly. We must handle fallback gracefully (e.g., student ID or sequence or formatted roll number like R-01 if null)!\n";
}
