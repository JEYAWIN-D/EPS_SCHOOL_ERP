<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('period_reports')) {
            Schema::create('period_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id')->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->unsignedBigInteger('timetable_id')->nullable();
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->date('date')->nullable();
                $table->string('day', 20)->nullable();
                $table->string('period_number', 50)->nullable();
                $table->integer('student_present_count')->default(0);
                $table->integer('student_absent_count')->default(0);
                $table->text('description')->nullable();
                $table->text('topic_covered')->nullable();
                $table->text('teaching_report')->nullable();
                $table->text('feedback')->nullable();
                $table->text('suggestion')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('employees')->nullOnDelete();
                $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
                $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
                $table->foreign('timetable_id')->references('id')->on('timetables')->nullOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['teacher_id', 'date']);
                $table->index(['timetable_id', 'date']);
                $table->index(['class_id', 'section_id', 'date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_reports');
    }
};
