<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Delete test classes (Grade 9 Test, Grade 10 Test) and their orphaned relations
        $testClasses = DB::table('classes')
            ->whereIn('name', ['Grade 9 Test', 'Grade 10 Test'])
            ->orWhereIn('id', [81, 82])
            ->get();

        foreach ($testClasses as $tc) {
            DB::table('teacher_subject_allocations')->where('class_id', $tc->id)->delete();
            DB::table('sections')->where('class_id', $tc->id)->delete();
            DB::table('classes')->where('id', $tc->id)->delete();
        }

        // 2. Standardize sequence from Pre-KG to 12th
        $standardOrder = [
            'Pre-KG'          => 1,
            'LKG'             => 2,
            'UKG'             => 3,
            'I'               => 4,
            'II'              => 5,
            'III'             => 6,
            'IV'              => 7,
            'V'               => 8,
            'VI'              => 9,
            'VII'             => 10,
            'VIII'            => 11,
            'IX'              => 12,
            'X'               => 13,
            'XI'              => 14,
            'XI (Integrated)' => 15,
            'XII'             => 16,
        ];

        foreach ($standardOrder as $name => $order) {
            DB::table('classes')->where('name', $name)->update(['sort_order' => $order]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed for cleanup of test data
    }
};
