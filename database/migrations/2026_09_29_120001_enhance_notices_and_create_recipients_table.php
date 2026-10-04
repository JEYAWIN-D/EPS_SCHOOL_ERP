<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (!Schema::hasColumn('notices', 'priority')) {
                $table->string('priority', 20)->default('normal')->after('notice_type');
            }
            if (!Schema::hasColumn('notices', 'status')) {
                $table->string('status', 30)->default('published')->after('priority');
            }
            if (!Schema::hasColumn('notices', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('status');
            }
            if (!Schema::hasColumn('notices', 'requires_acknowledgement')) {
                $table->boolean('requires_acknowledgement')->default(false)->after('requires_approval');
            }
            if (!Schema::hasColumn('notices', 'approval_status')) {
                $table->string('approval_status', 30)->default('not_required')->after('requires_acknowledgement');
            }
            if (!Schema::hasColumn('notices', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('publish_date');
            }
            if (!Schema::hasColumn('notices', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('expiry_date');
            }
            if (!Schema::hasColumn('notices', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('archived_at');
            }
            if (!Schema::hasColumn('notices', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->index()->after('created_by');
            }
        });

        if (!Schema::hasTable('notice_recipients')) {
            Schema::create('notice_recipients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notice_id')->constrained('notices')->cascadeOnDelete();
                $table->string('recipient_type', 40); // all, role, department, class, section, employee, student, parent
                $table->unsignedBigInteger('recipient_id')->nullable();
                $table->timestamps();

                $table->index(['notice_id', 'recipient_type', 'recipient_id']);
            });
        }

        if (!Schema::hasTable('notice_acknowledgements')) {
            Schema::create('notice_acknowledgements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notice_id')->constrained('notices')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('acknowledged_at');
                $table->string('ip_address', 45)->nullable();
                $table->text('feedback_note')->nullable();
                $table->timestamps();

                $table->unique(['notice_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_acknowledgements');
        Schema::dropIfExists('notice_recipients');

        Schema::table('notices', function (Blueprint $table) {
            $cols = ['priority', 'status', 'requires_approval', 'requires_acknowledgement', 'approval_status', 'scheduled_at', 'archived_at', 'rejection_reason', 'school_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('notices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
