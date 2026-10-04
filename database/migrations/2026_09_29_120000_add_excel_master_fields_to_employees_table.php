<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                // Section A: Personal Details
                if (!Schema::hasColumn('employees', 'mother_tongue')) {
                    $table->string('mother_tongue', 100)->nullable();
                }
                if (!Schema::hasColumn('employees', 'caste')) {
                    $table->string('caste', 100)->nullable();
                }
                if (!Schema::hasColumn('employees', 'marital_status')) {
                    $table->string('marital_status', 50)->nullable();
                }
                if (!Schema::hasColumn('employees', 'number_of_children')) {
                    $table->integer('number_of_children')->default(0)->nullable();
                }
                if (!Schema::hasColumn('employees', 'children_male')) {
                    $table->integer('children_male')->default(0)->nullable();
                }
                if (!Schema::hasColumn('employees', 'children_female')) {
                    $table->integer('children_female')->default(0)->nullable();
                }

                // Section B: Employment Details
                if (!Schema::hasColumn('employees', 'first_appointment_date')) {
                    $table->date('first_appointment_date')->nullable();
                }
                if (!Schema::hasColumn('employees', 'current_employment_status')) {
                    $table->string('current_employment_status', 100)->nullable();
                }

                // Section C: Identity / Government Details
                if (!Schema::hasColumn('employees', 'epf_no')) {
                    $table->string('epf_no', 50)->nullable();
                }

                // Section D: Contact Details
                if (!Schema::hasColumn('employees', 'contact_person')) {
                    $table->string('contact_person', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'name_of_contact_person')) {
                    $table->string('name_of_contact_person', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'contact_relationship')) {
                    $table->string('contact_relationship', 100)->nullable();
                }
                if (!Schema::hasColumn('employees', 'contact_address')) {
                    $table->text('contact_address')->nullable();
                }
                if (!Schema::hasColumn('employees', 'contact_no')) {
                    $table->string('contact_no', 20)->nullable();
                }

                // Section E: Previous Employment Details
                if (!Schema::hasColumn('employees', 'previous_from_date')) {
                    $table->date('previous_from_date')->nullable();
                }
                if (!Schema::hasColumn('employees', 'previous_to_date')) {
                    $table->date('previous_to_date')->nullable();
                }

                // Section F: Academic Qualification
                if (!Schema::hasColumn('employees', 'academic_qualification')) {
                    $table->string('academic_qualification', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'academic_university')) {
                    $table->string('academic_university', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'academic_medium')) {
                    $table->string('academic_medium', 100)->nullable();
                }
                if (!Schema::hasColumn('employees', 'academic_institution')) {
                    $table->string('academic_institution', 200)->nullable();
                }
                if (!Schema::hasColumn('employees', 'academic_percentage')) {
                    $table->decimal('academic_percentage', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('employees', 'academic_year_of_passing')) {
                    $table->integer('academic_year_of_passing')->nullable();
                }

                // Section G: Professional Qualification
                if (!Schema::hasColumn('employees', 'professional_qualification')) {
                    $table->string('professional_qualification', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'professional_university')) {
                    $table->string('professional_university', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'professional_medium')) {
                    $table->string('professional_medium', 100)->nullable();
                }
                if (!Schema::hasColumn('employees', 'professional_institution')) {
                    $table->string('professional_institution', 200)->nullable();
                }
                if (!Schema::hasColumn('employees', 'professional_percentage')) {
                    $table->decimal('professional_percentage', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('employees', 'professional_year_of_passing')) {
                    $table->integer('professional_year_of_passing')->nullable();
                }

                // Section H: Other Qualification 1
                if (!Schema::hasColumn('employees', 'other_qual_1_name')) {
                    $table->string('other_qual_1_name', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_1_university')) {
                    $table->string('other_qual_1_university', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_1_medium')) {
                    $table->string('other_qual_1_medium', 100)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_1_institution')) {
                    $table->string('other_qual_1_institution', 200)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_1_percentage')) {
                    $table->decimal('other_qual_1_percentage', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_1_year_of_passing')) {
                    $table->integer('other_qual_1_year_of_passing')->nullable();
                }

                // Section I: Other Qualification 2
                if (!Schema::hasColumn('employees', 'other_qual_2_name')) {
                    $table->string('other_qual_2_name', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_2_university')) {
                    $table->string('other_qual_2_university', 150)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_2_medium')) {
                    $table->string('other_qual_2_medium', 100)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_2_institution')) {
                    $table->string('other_qual_2_institution', 200)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_2_percentage')) {
                    $table->decimal('other_qual_2_percentage', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('employees', 'other_qual_2_year_of_passing')) {
                    $table->integer('other_qual_2_year_of_passing')->nullable();
                }

                // Section J: Additional Qualification / Training
                if (!Schema::hasColumn('employees', 'additional_qual_after_recruitment')) {
                    $table->text('additional_qual_after_recruitment')->nullable();
                }
                if (!Schema::hasColumn('employees', 'training_attended_before_recruitment')) {
                    $table->text('training_attended_before_recruitment')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                $columns = [
                    'mother_tongue', 'caste', 'marital_status', 'number_of_children', 'children_male', 'children_female',
                    'first_appointment_date', 'current_employment_status', 'epf_no',
                    'contact_person', 'name_of_contact_person', 'contact_relationship', 'contact_address', 'contact_no',
                    'previous_from_date', 'previous_to_date',
                    'academic_qualification', 'academic_university', 'academic_medium', 'academic_institution', 'academic_percentage', 'academic_year_of_passing',
                    'professional_qualification', 'professional_university', 'professional_medium', 'professional_institution', 'professional_percentage', 'professional_year_of_passing',
                    'other_qual_1_name', 'other_qual_1_university', 'other_qual_1_medium', 'other_qual_1_institution', 'other_qual_1_percentage', 'other_qual_1_year_of_passing',
                    'other_qual_2_name', 'other_qual_2_university', 'other_qual_2_medium', 'other_qual_2_institution', 'other_qual_2_percentage', 'other_qual_2_year_of_passing',
                    'additional_qual_after_recruitment', 'training_attended_before_recruitment'
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('employees', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
