<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create admission_drafts table
        if (!Schema::hasTable('admission_drafts')) {
            Schema::create('admission_drafts', function (Blueprint $table) {
                $table->id();
                $table->string('draft_number', 50)->unique();
                $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->string('student_name', 150)->nullable();
                $table->string('parent_name', 150)->nullable();
                $table->string('parent_mobile', 20)->nullable();
                $table->json('form_data')->nullable();
                $table->unsignedSmallInteger('completion_percentage')->default(0);
                $table->unsignedSmallInteger('current_step')->default(1);
                $table->string('status', 40)->default('in_progress'); // in_progress, pending_documents, ready_for_submission, submission_failed, submitted
                $table->timestamp('last_saved_at')->nullable();
                $table->timestamp('submission_attempted_at')->nullable();
                $table->text('submission_error')->nullable();
                $table->string('idempotency_token', 100)->nullable()->index();
                $table->foreignId('converted_student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['created_by', 'status']);
                $table->index(['class_id', 'status']);
            });
        }

        // 2. Create admission_draft_documents table
        if (!Schema::hasTable('admission_draft_documents')) {
            Schema::create('admission_draft_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('draft_id')->constrained('admission_drafts')->cascadeOnDelete();
                $table->string('document_type', 60); // photo, birth_certificate, aadhaar, tc, marksheet, caste, pan, father_photo, mother_photo
                $table->string('original_filename', 255);
                $table->string('stored_path', 255);
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('upload_status', 30)->default('uploaded'); // uploaded, pending, failed
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamps();

                $table->index(['draft_id', 'document_type']);
            });
        }

        // 3. Create enquiry_status_history table
        if (!Schema::hasTable('enquiry_status_history')) {
            Schema::create('enquiry_status_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enquiry_id')->constrained('enquiries')->cascadeOnDelete();
                $table->string('previous_status', 50)->nullable();
                $table->string('new_status', 50);
                $table->string('reason', 150)->nullable();
                $table->text('remarks')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('changed_at')->useCurrent();
                $table->timestamps();

                $table->index(['enquiry_id', 'created_at']);
            });
        }

        // 4. Enhance enquiry_follow_ups table
        if (Schema::hasTable('enquiry_follow_ups')) {
            Schema::table('enquiry_follow_ups', function (Blueprint $table) {
                if (!Schema::hasColumn('enquiry_follow_ups', 'contact_person')) {
                    $table->string('contact_person', 150)->nullable();
                }
                if (!Schema::hasColumn('enquiry_follow_ups', 'contact_method')) {
                    $table->string('contact_method', 50)->default('phone'); // phone, email, whatsapp, visit, in_person, other
                }
                if (!Schema::hasColumn('enquiry_follow_ups', 'discussion')) {
                    $table->text('discussion')->nullable();
                }
                if (!Schema::hasColumn('enquiry_follow_ups', 'parent_response')) {
                    $table->string('parent_response', 80)->nullable();
                }
                if (!Schema::hasColumn('enquiry_follow_ups', 'assigned_staff_id')) {
                    $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        // 5. Enhance enquiries table
        if (Schema::hasTable('enquiries')) {
            Schema::table('enquiries', function (Blueprint $table) {
                if (!Schema::hasColumn('enquiries', 'converted_student_id')) {
                    $table->foreignId('converted_student_id')->nullable()->constrained('students')->nullOnDelete();
                }
                if (!Schema::hasColumn('enquiries', 'converted_at')) {
                    $table->timestamp('converted_at')->nullable();
                }
                if (!Schema::hasColumn('enquiries', 'converted_by')) {
                    $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('enquiries', 'non_conversion_reason')) {
                    $table->string('non_conversion_reason', 150)->nullable();
                }
                if (!Schema::hasColumn('enquiries', 'non_conversion_remarks')) {
                    $table->text('non_conversion_remarks')->nullable();
                }
                if (!Schema::hasColumn('enquiries', 'closed_by')) {
                    $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('enquiries', 'closed_at')) {
                    $table->timestamp('closed_at')->nullable();
                }
            });
        }

        // 6. Enhance students table
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'enquiry_id')) {
                    $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->nullOnDelete();
                }
                if (!Schema::hasColumn('students', 'admission_draft_id')) {
                    $table->foreignId('admission_draft_id')->nullable()->constrained('admission_drafts')->nullOnDelete();
                }
            });
        }

        // 7. Update PostgreSQL check constraints
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE enquiries DROP CONSTRAINT IF EXISTS enquiries_status_check");
            DB::statement("ALTER TABLE enquiries ADD CONSTRAINT enquiries_status_check CHECK (status::text = ANY (ARRAY[
                'new'::character varying,
                'contacted'::character varying,
                'follow_up'::character varying,
                'follow_up_required'::character varying,
                'interested'::character varying,
                'visit_scheduled'::character varying,
                'admission_pending'::character varying,
                'converted'::character varying,
                'not_interested'::character varying,
                'lost'::character varying,
                'on_hold'::character varying,
                'campus_visit'::character varying,
                'application'::character varying,
                'assessment'::character varying,
                'entrance_test'::character varying,
                'interview'::character varying,
                'document_verification'::character varying,
                'waitlisted'::character varying,
                'offered'::character varying,
                'confirmed'::character varying,
                'enrolled'::character varying,
                'pending_principal'::character varying,
                'pending_principal_approval'::character varying,
                'principal_approved'::character varying,
                'rejected'::character varying
            ]::text[]))");

            DB::statement("ALTER TABLE enquiry_follow_ups DROP CONSTRAINT IF EXISTS enquiry_follow_ups_status_check");
            DB::statement("ALTER TABLE enquiry_follow_ups ADD CONSTRAINT enquiry_follow_ups_status_check CHECK (status::text = ANY (ARRAY[
                'new'::character varying,
                'contacted'::character varying,
                'follow_up'::character varying,
                'follow_up_required'::character varying,
                'interested'::character varying,
                'visit_scheduled'::character varying,
                'admission_pending'::character varying,
                'converted'::character varying,
                'not_interested'::character varying,
                'lost'::character varying,
                'on_hold'::character varying,
                'campus_visit'::character varying,
                'application'::character varying,
                'assessment'::character varying,
                'entrance_test'::character varying,
                'interview'::character varying,
                'document_verification'::character varying,
                'waitlisted'::character varying,
                'offered'::character varying,
                'confirmed'::character varying,
                'enrolled'::character varying,
                'pending_principal'::character varying,
                'pending_principal_approval'::character varying,
                'principal_approved'::character varying,
                'rejected'::character varying
            ]::text[]))");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'admission_draft_id')) {
                    $table->dropForeign(['admission_draft_id']);
                    $table->dropColumn('admission_draft_id');
                }
                if (Schema::hasColumn('students', 'enquiry_id')) {
                    $table->dropForeign(['enquiry_id']);
                    $table->dropColumn('enquiry_id');
                }
            });
        }

        if (Schema::hasTable('enquiries')) {
            Schema::table('enquiries', function (Blueprint $table) {
                if (Schema::hasColumn('enquiries', 'converted_student_id')) {
                    $table->dropForeign(['converted_student_id']);
                    $table->dropColumn('converted_student_id');
                }
                if (Schema::hasColumn('enquiries', 'converted_by')) {
                    $table->dropForeign(['converted_by']);
                    $table->dropColumn('converted_by');
                }
                if (Schema::hasColumn('enquiries', 'closed_by')) {
                    $table->dropForeign(['closed_by']);
                    $table->dropColumn('closed_by');
                }
                $table->dropColumn([
                    'converted_at',
                    'non_conversion_reason',
                    'non_conversion_remarks',
                    'closed_at'
                ]);
            });
        }

        if (Schema::hasTable('enquiry_follow_ups')) {
            Schema::table('enquiry_follow_ups', function (Blueprint $table) {
                if (Schema::hasColumn('enquiry_follow_ups', 'assigned_staff_id')) {
                    $table->dropForeign(['assigned_staff_id']);
                    $table->dropColumn('assigned_staff_id');
                }
                $table->dropColumn([
                    'contact_person',
                    'contact_method',
                    'discussion',
                    'parent_response'
                ]);
            });
        }

        Schema::dropIfExists('enquiry_status_history');
        Schema::dropIfExists('admission_draft_documents');
        Schema::dropIfExists('admission_drafts');
    }
};

