<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. id_card_templates table
        if (!Schema::hasTable('id_card_templates')) {
            Schema::create('id_card_templates', function (Blueprint $table) {
                $table->id();
                $table->string('template_name', 150);
                $table->string('card_type', 30); // student, staff, visitor
                $table->string('orientation', 20)->default('landscape'); // landscape, portrait
                $table->decimal('physical_width', 6, 2)->default(85.60);
                $table->decimal('physical_height', 6, 2)->default(53.98);
                $table->json('configuration_json')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('version')->default(1);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['card_type', 'is_active']);
                $table->index(['card_type', 'orientation']);
            });
        }

        // 2. id_cards table
        if (!Schema::hasTable('id_cards')) {
            Schema::create('id_cards', function (Blueprint $table) {
                $table->id();
                $table->string('card_number', 50)->unique();
                $table->string('card_type', 30); // student, staff, visitor
                $table->string('linked_entity_type', 100);
                $table->unsignedBigInteger('linked_entity_id');
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->foreignId('template_id')->nullable()->constrained('id_card_templates')->nullOnDelete();
                $table->unsignedInteger('template_version')->default(1);
                $table->string('orientation', 20)->default('landscape');
                $table->string('side', 20)->default('both'); // both, front, back
                $table->date('issue_date');
                $table->date('expiry_date')->nullable();
                $table->string('status', 30)->default('active'); // draft, pending_verification, generated, issued, active, expired, lost, damaged, replaced, revoked
                $table->unsignedInteger('reprint_count')->default(0);
                $table->text('reprint_reason')->nullable();
                $table->string('qr_verification_token', 64)->unique();
                $table->string('barcode_value', 100)->nullable();
                $table->json('field_snapshot_json')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['linked_entity_type', 'linked_entity_id']);
                $table->index(['card_type', 'status']);
                $table->index('academic_year_id');
                $table->index('qr_verification_token');
            });
        }

        // 3. id_card_generation_jobs table
        if (!Schema::hasTable('id_card_generation_jobs')) {
            Schema::create('id_card_generation_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('job_type', 50); // student_bulk, student_class, staff_bulk, visitor_batch, single_card
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('filters_json')->nullable();
                $table->unsignedInteger('total_records')->default(0);
                $table->unsignedInteger('processed_records')->default(0);
                $table->unsignedInteger('failed_records')->default(0);
                $table->string('job_status', 30)->default('completed'); // queued, processing, completed, failed
                $table->string('output_file_reference')->nullable();
                $table->json('failed_details_json')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('completed_at')->nullable();

                $table->index('job_status');
                $table->index('requested_by');
            });
        }

        // 4. id_card_audit_logs table
        if (!Schema::hasTable('id_card_audit_logs')) {
            Schema::create('id_card_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('card_id')->constrained('id_cards')->cascadeOnDelete();
                $table->string('action', 50); // created, generated, issued, reprinted, status_changed, revoked, replaced
                $table->json('previous_values')->nullable();
                $table->json('new_values')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('timestamp')->useCurrent();

                $table->index(['card_id', 'action']);
                $table->index('timestamp');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('id_card_audit_logs');
        Schema::dropIfExists('id_card_generation_jobs');
        Schema::dropIfExists('id_cards');
        Schema::dropIfExists('id_card_templates');
    }
};
