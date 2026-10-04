<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('meetings')) {
            Schema::create('meetings', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('agenda')->nullable();
                $table->string('meeting_type', 40)->default('staff'); // staff, hod, management, ptm, departmental, general
                $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('chairperson_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->dateTime('start_time');
                $table->dateTime('end_time');
                $table->string('venue')->nullable();
                $table->string('meeting_link')->nullable();
                $table->string('status', 30)->default('scheduled'); // scheduled, in_progress, completed, cancelled
                $table->text('cancellation_reason')->nullable();
                $table->foreignId('follow_up_meeting_id')->nullable()->constrained('meetings')->nullOnDelete();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->timestamps();

                $table->index(['meeting_type', 'status']);
                $table->index(['start_time', 'end_time']);
            });
        }

        if (!Schema::hasTable('meeting_attendees')) {
            Schema::create('meeting_attendees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('is_optional')->default(false);
                $table->string('rsvp_status', 30)->default('pending'); // pending, accepted, declined, tentative
                $table->text('rsvp_note')->nullable();
                $table->string('attendance_status', 30)->default('pending'); // pending, present, absent, excused
                $table->timestamp('attended_at')->nullable();
                $table->timestamps();

                $table->unique(['meeting_id', 'user_id']);
                $table->index(['user_id', 'rsvp_status']);
            });
        }

        if (!Schema::hasTable('meeting_minutes')) {
            Schema::create('meeting_minutes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
                $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
                $table->text('summary');
                $table->text('key_decisions')->nullable();
                $table->unsignedInteger('action_items_count')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
        Schema::dropIfExists('meeting_attendees');
        Schema::dropIfExists('meetings');
    }
};
