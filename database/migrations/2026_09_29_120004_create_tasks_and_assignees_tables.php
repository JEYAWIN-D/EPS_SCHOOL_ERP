<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
                $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable();
                $table->string('status', 35)->default('pending'); // pending, accepted, in_progress, blocked, submitted_for_review, completed, reopened, cancelled
                $table->string('source_type', 40)->default('independent'); // independent, meeting, event, notice
                $table->unsignedBigInteger('source_id')->nullable();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->decimal('estimated_hours', 5, 2)->nullable();
                $table->text('completion_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->timestamps();

                $table->index(['status', 'priority']);
                $table->index(['due_date']);
                $table->index(['source_type', 'source_id']);
            });
        }

        if (!Schema::hasTable('task_assignees')) {
            Schema::create('task_assignees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 30)->default('primary'); // primary, collaborator
                $table->timestamps();

                $table->unique(['task_id', 'user_id']);
                $table->index(['user_id', 'role']);
            });
        }

        if (!Schema::hasTable('task_updates')) {
            Schema::create('task_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('status_from', 35)->nullable();
                $table->string('status_to', 35)->nullable();
                $table->text('comment')->nullable();
                $table->unsignedTinyInteger('progress_percentage')->nullable();
                $table->string('evidence_path')->nullable();
                $table->timestamps();

                $table->index(['task_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_updates');
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
    }
};
