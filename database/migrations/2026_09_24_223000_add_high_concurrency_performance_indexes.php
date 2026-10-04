<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for high-concurrency composite indexes.
     */
    public function up(): void
    {
        // 1. Attendance Records optimization
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->index(['class_id', 'section_id', 'date'], 'idx_att_class_sec_date');
            $table->index(['academic_year_id', 'student_id'], 'idx_att_year_student');
        });

        // 2. Fee Payments optimization
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->index(['payment_date', 'is_cancelled'], 'idx_fee_date_cancelled');
            $table->index(['academic_year_id', 'is_cancelled', 'payment_date'], 'idx_fee_year_cancelled_date');
        });

        // 3. Timetables optimization
        Schema::table('timetables', function (Blueprint $table) {
            $table->index(['class_id', 'section_id', 'day_of_week'], 'idx_tt_class_sec_day');
        });

        // 4. Student Enrollments optimization
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->index(['academic_year_id', 'class_id', 'section_id', 'status'], 'idx_enr_year_cls_sec_stat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('idx_att_class_sec_date');
            $table->dropIndex('idx_att_year_student');
        });

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropIndex('idx_fee_date_cancelled');
            $table->dropIndex('idx_fee_year_cancelled_date');
        });

        Schema::table('timetables', function (Blueprint $table) {
            $table->dropIndex('idx_tt_class_sec_day');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enr_year_cls_sec_stat');
        });
    }
};
