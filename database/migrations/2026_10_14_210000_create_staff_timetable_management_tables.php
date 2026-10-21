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
        // 1. Extra Classes Table
        if (!Schema::hasTable('extra_classes')) {
            Schema::create('extra_classes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('section_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->date('date');
                $table->smallInteger('period_number')->nullable();
                $table->time('start_time');
                $table->time('end_time');
                $table->string('room', 100)->nullable();
                $table->text('reason')->nullable();
                $table->string('approval_status', 30)->default('approved'); // approved, pending, rejected, cancelled
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('completion_status', 30)->default('scheduled'); // scheduled, in_progress, completed, cancelled
                $table->text('completion_notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
                $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
                $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['teacher_id', 'date']);
                $table->index(['class_id', 'section_id', 'date']);
                $table->index(['approval_status']);
            });
        }

        // 2. Staff Duties Table (Exams, Meetings, Remedials, Lab Sessions, Administrative)
        if (!Schema::hasTable('staff_duties')) {
            Schema::create('staff_duties', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id');
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->string('duty_type', 50)->default('administrative'); // exam_duty, meeting, remedial, practical_lab, activity_period, leave_unavailable, administrative
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->date('date');
                $table->smallInteger('day_of_week')->nullable();
                $table->smallInteger('period_number')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('room', 100)->nullable();
                $table->string('status', 30)->default('scheduled'); // scheduled, completed, cancelled
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
                $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['teacher_id', 'date']);
                $table->index(['duty_type', 'date']);
            });
        }

        // 3. Staff Free Period Bookings / Tasks Table
        if (!Schema::hasTable('staff_free_period_tasks')) {
            Schema::create('staff_free_period_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id');
                $table->date('date');
                $table->smallInteger('period_number');
                $table->string('activity_type', 50); // lesson_preparation, assignment_correction, student_mentoring, remedial_planning, qp_preparation, teaching_report, academic_meeting, library_lab_prep, administrative_work, other
                $table->string('title', 255);
                $table->text('notes')->nullable();
                $table->string('status', 30)->default('reserved'); // available, reserved, occupied, completed, cancelled
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['teacher_id', 'date', 'period_number']);
            });
        }

        // 4. Staff Test & Exam Reminders Table
        if (!Schema::hasTable('staff_test_reminders')) {
            Schema::create('staff_test_reminders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('section_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->string('test_title', 255);
                $table->string('test_type', 50)->default('class_test'); // class_test, unit_test, model_exam, revision_test, term_assessment
                $table->date('test_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->text('syllabus_details')->nullable();
                $table->integer('max_marks')->default(25);
                $table->date('paper_submission_deadline')->nullable();
                $table->date('evaluation_deadline')->nullable();
                $table->date('result_publication_deadline')->nullable();
                $table->string('status', 30)->default('scheduled'); // scheduled, paper_submitted, conducted, evaluated, published, cancelled
                $table->text('instructions')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
                $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['teacher_id', 'test_date']);
                $table->index(['class_id', 'section_id', 'test_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_test_reminders');
        Schema::dropIfExists('staff_free_period_tasks');
        Schema::dropIfExists('staff_duties');
        Schema::dropIfExists('extra_classes');
    }
};
