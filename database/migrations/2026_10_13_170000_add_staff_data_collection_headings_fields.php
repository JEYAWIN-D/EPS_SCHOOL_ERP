<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to employees table matching the 13 Staff Data Collection Headings
        Schema::table('employees', function (Blueprint $table) {
            // Heading 1: Personal Information
            if (!Schema::hasColumn('employees', 'alternate_phone')) {
                $table->string('alternate_phone', 20)->nullable()->after('mobile');
            }
            if (!Schema::hasColumn('employees', 'permanent_address')) {
                $table->text('permanent_address')->nullable()->after('address');
            }

            // Heading 2: Employment Details
            if (!Schema::hasColumn('employees', 'pay_scale')) {
                $table->string('pay_scale', 100)->nullable()->after('basic_salary');
            }
            if (!Schema::hasColumn('employees', 'last_promotion_date')) {
                $table->date('last_promotion_date')->nullable()->after('joining_date');
            }
            if (!Schema::hasColumn('employees', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('joining_date');
            }
            if (!Schema::hasColumn('employees', 'confirmation_date')) {
                $table->date('confirmation_date')->nullable()->after('probation_end_date');
            }

            // Heading 3: Educational Qualifications & Specializations
            if (!Schema::hasColumn('employees', 'major_subject')) {
                $table->string('major_subject', 150)->nullable()->after('qualification');
            }
            if (!Schema::hasColumn('employees', 'minor_subject')) {
                $table->string('minor_subject', 150)->nullable()->after('major_subject');
            }
            if (!Schema::hasColumn('employees', 'special_skills')) {
                $table->text('special_skills')->nullable()->after('minor_subject');
            }

            // Heading 4: Teaching Assignment Details
            if (!Schema::hasColumn('employees', 'preferred_teaching_level')) {
                $table->string('preferred_teaching_level', 100)->nullable()->after('max_weekly_periods');
            }
            if (!Schema::hasColumn('employees', 'lab_subjects')) {
                $table->string('lab_subjects', 255)->nullable()->after('preferred_teaching_level');
            }

            // Heading 6: Government ID and Statutory Details
            if (!Schema::hasColumn('employees', 'uan_no')) {
                $table->string('uan_no', 50)->nullable()->after('epf_no');
            }
            if (!Schema::hasColumn('employees', 'professional_tax_no')) {
                $table->string('professional_tax_no', 50)->nullable()->after('esi_no');
            }
            if (!Schema::hasColumn('employees', 'passport_no')) {
                $table->string('passport_no', 50)->nullable()->after('pan_no');
            }

            // Heading 7: Salary and Employment Benefits
            if (!Schema::hasColumn('employees', 'annual_increment')) {
                $table->decimal('annual_increment', 10, 2)->nullable()->after('gross_salary');
            }
            if (!Schema::hasColumn('employees', 'increment_effective_date')) {
                $table->date('increment_effective_date')->nullable()->after('annual_increment');
            }
            if (!Schema::hasColumn('employees', 'nominee_name')) {
                $table->string('nominee_name', 150)->nullable()->after('increment_effective_date');
            }
            if (!Schema::hasColumn('employees', 'nominee_relationship')) {
                $table->string('nominee_relationship', 100)->nullable()->after('nominee_name');
            }
            if (!Schema::hasColumn('employees', 'nominee_share')) {
                $table->decimal('nominee_share', 5, 2)->nullable()->default(100.00)->after('nominee_relationship');
            }
        });

        // 2. Add duration and course fields to employee_certifications for Heading 8: Training & Professional Development
        Schema::table('employee_certifications', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_certifications', 'duration_days')) {
                $table->integer('duration_days')->nullable()->after('certificate_number');
            }
            if (!Schema::hasColumn('employee_certifications', 'course_name')) {
                $table->string('course_name', 200)->nullable()->after('certification_name');
            }
            if (!Schema::hasColumn('employee_certifications', 'remarks')) {
                $table->text('remarks')->nullable()->after('file_path');
            }
        });

        // 3. Create employee_family_members table for Heading 11: Family Details
        if (!Schema::hasTable('employee_family_members')) {
            Schema::create('employee_family_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
                $table->string('member_name', 150);
                $table->date('dob')->nullable();
                $table->string('relationship', 100);
                $table->string('contact_number', 25)->nullable();
                $table->boolean('is_verified')->default(false);
                $table->text('permanent_address')->nullable();
                $table->text('aadhaar_address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_family_members');

        Schema::table('employee_certifications', function (Blueprint $table) {
            $table->dropColumn(['duration_days', 'course_name', 'remarks']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'alternate_phone', 'permanent_address', 'pay_scale', 'last_promotion_date',
                'contract_end_date', 'confirmation_date', 'major_subject', 'minor_subject',
                'special_skills', 'preferred_teaching_level', 'lab_subjects', 'uan_no',
                'professional_tax_no', 'passport_no', 'annual_increment', 'increment_effective_date',
                'nominee_name', 'nominee_relationship', 'nominee_share'
            ]);
        });
    }
};
