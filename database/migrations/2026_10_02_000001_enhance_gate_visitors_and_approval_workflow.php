<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('visitors')) {
            Schema::create('visitors', function (Blueprint $table) {
                $table->id();
                $table->string('pass_number', 50)->unique();
                $table->string('pass_token', 64)->unique();
                $table->string('visitor_name', 150);
                $table->string('category', 50)->default('other'); // parent, admission_enquiry, vendor, interview, guest, other
                $table->string('visitor_phone', 20)->nullable();
                $table->string('visitor_email', 150)->nullable();
                $table->string('visitor_id_type', 50)->nullable(); // aadhaar, pan, dl, passport, voter_id, other
                $table->string('visitor_id_number', 50)->nullable();
                $table->string('purpose', 255);
                $table->string('department', 100)->nullable();
                $table->string('whom_to_meet', 150)->nullable();
                $table->foreignId('host_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                
                // Timing & Counts
                $table->date('visit_date');
                $table->dateTime('in_time')->nullable();
                $table->dateTime('expected_exit_time')->nullable();
                $table->dateTime('out_time')->nullable();
                $table->unsignedSmallInteger('visitor_count')->default(1);
                
                // Vehicle & Media
                $table->string('vehicle_type', 30)->nullable(); // none, two_wheeler, four_wheeler, etc.
                $table->string('vehicle_number', 30)->nullable();
                $table->string('visitor_photo', 255)->nullable();
                $table->string('id_proof_document', 255)->nullable();
                $table->text('items_carried')->nullable();
                
                // Workflow Status & Approval
                $table->string('status', 30)->default('checked_in'); // pending_approval, approved, checked_in, checked_out, rejected, cancelled
                $table->string('badge_color', 20)->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                
                // Dynamic ERP Category Links
                // 1. Parent / Guardian
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->string('relationship_to_student', 50)->nullable();
                
                // 2. Admission Enquiry
                $table->string('child_name', 150)->nullable();
                $table->string('grade_applying_for', 50)->nullable();
                $table->string('enquiry_source', 100)->nullable();
                $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->nullOnDelete();
                
                // 3. Vendor / Maintenance
                $table->string('company_name', 150)->nullable();
                $table->string('work_order_number', 100)->nullable();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                
                // 4. Staff Interview
                $table->string('candidate_ref_number', 100)->nullable();
                $table->string('job_role_applied', 100)->nullable();
                
                // General & Audit
                $table->text('remarks')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_blacklisted')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
