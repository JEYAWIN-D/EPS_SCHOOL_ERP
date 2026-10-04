<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'category')) {
                $table->string('category', 60)->nullable()->after('event_type');
            }
            if (!Schema::hasColumn('events', 'status')) {
                $table->string('status', 30)->default('published')->after('category');
            }
            if (!Schema::hasColumn('events', 'organizer_id')) {
                $table->foreignId('organizer_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('events', 'budget_estimated')) {
                $table->decimal('budget_estimated', 12, 2)->default(0)->after('organizer_id');
            }
            if (!Schema::hasColumn('events', 'budget_actual')) {
                $table->decimal('budget_actual', 12, 2)->default(0)->after('budget_estimated');
            }
            if (!Schema::hasColumn('events', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('budget_actual');
            }
            if (!Schema::hasColumn('events', 'post_event_report')) {
                $table->text('post_event_report')->nullable()->after('cancellation_reason');
            }
            if (!Schema::hasColumn('events', 'recurrence_rule')) {
                $table->string('recurrence_rule', 100)->nullable()->after('post_event_report');
            }
            if (!Schema::hasColumn('events', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->index()->after('recurrence_rule');
            }
        });

        if (!Schema::hasTable('event_staff')) {
            Schema::create('event_staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('role', 30)->default('support'); // in_charge, coordinator, support
                $table->text('duties')->nullable();
                $table->timestamps();

                $table->unique(['event_id', 'employee_id', 'role']);
            });
        }

        if (!Schema::hasTable('event_participants')) {
            Schema::create('event_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->string('participant_type', 30)->default('user'); // user, student, employee
                $table->unsignedBigInteger('participant_id');
                $table->string('status', 30)->default('invited'); // invited, confirmed, attended, absent
                $table->timestamp('check_in_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['event_id', 'participant_type', 'participant_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('event_staff');

        Schema::table('events', function (Blueprint $table) {
            $cols = ['category', 'status', 'organizer_id', 'budget_estimated', 'budget_actual', 'cancellation_reason', 'post_event_report', 'recurrence_rule', 'school_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('events', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
