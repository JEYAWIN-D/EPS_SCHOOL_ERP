<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('approval_requests')) {
            Schema::create('approval_requests', function (Blueprint $table) {
                $table->id();
                $table->string('approvable_type');
                $table->unsignedBigInteger('approvable_id');
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->text('decision_notes')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index(['approvable_type', 'approvable_id']);
                $table->index(['status', 'approver_id']);
            });
        }

        if (!Schema::hasTable('activity_attachments')) {
            Schema::create('activity_attachments', function (Blueprint $table) {
                $table->id();
                $table->string('attachable_type');
                $table->unsignedBigInteger('attachable_id');
                $table->string('file_name');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('mime_type', 100)->nullable();
                $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['attachable_type', 'attachable_id']);
            });
        }

        if (!Schema::hasTable('activity_audit_logs')) {
            Schema::create('activity_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 50); // created, updated, published, approved, rejected, cancelled, completed, reassigned, acknowledged
                $table->string('entity_type', 60); // notice, event, meeting, task
                $table->unsignedBigInteger('entity_id');
                $table->jsonb('payload')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['entity_type', 'entity_id']);
                $table->index(['user_id', 'action']);
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('reminder_jobs')) {
            Schema::create('reminder_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 60);
                $table->unsignedBigInteger('entity_id');
                $table->string('reminder_type', 40);
                $table->dateTime('scheduled_at');
                $table->string('status', 30)->default('pending'); // pending, sent, failed
                $table->jsonb('payload')->nullable();
                $table->timestamps();

                $table->index(['scheduled_at', 'status']);
            });
        }

        if (!Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('in_app')->default(true);
                $table->boolean('email')->default(true);
                $table->boolean('sms')->default(false);
                $table->boolean('whatsapp')->default(false);
                $table->jsonb('preferences')->nullable();
                $table->timestamps();

                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('reminder_jobs');
        Schema::dropIfExists('activity_audit_logs');
        Schema::dropIfExists('activity_attachments');
        Schema::dropIfExists('approval_requests');
    }
};
