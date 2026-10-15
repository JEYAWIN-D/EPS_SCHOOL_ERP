<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Create teaching_levels table
        if (!Schema::hasTable('teaching_levels')) {
            Schema::create('teaching_levels', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 50)->unique();
                $table->text('description')->nullable();
                $table->integer('min_class_numeric')->default(0);
                $table->integer('max_class_numeric')->default(12);
                $table->string('schedule_type', 50)->default('period_based'); // activity_based, period_based, group_period_based
                $table->integer('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Add teaching_level_id to classes table
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (!Schema::hasColumn('classes', 'teaching_level_id')) {
                    $table->unsignedBigInteger('teaching_level_id')->nullable()->after('numeric_value');
                    $table->foreign('teaching_level_id')->references('id')->on('teaching_levels')->onDelete('set null');
                }
            });
        }

        // 3. Create teacher_level_eligibilities table
        if (!Schema::hasTable('teacher_level_eligibilities')) {
            Schema::create('teacher_level_eligibilities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('teaching_level_id');
                $table->boolean('is_approved')->default(true);
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('teaching_level_id')->references('id')->on('teaching_levels')->onDelete('cascade');
                $table->unique(['employee_id', 'teaching_level_id']);
            });
        }

        // 4. Enhance employees table with teaching level & experience fields
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->index()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('employees', 'experience_months')) {
                    $table->integer('experience_months')->default(0)->after('experience_years');
                }
                if (!Schema::hasColumn('employees', 'kg_experience_years')) {
                    $table->integer('kg_experience_years')->default(0)->after('experience_months');
                }
                if (!Schema::hasColumn('employees', 'qualified_subjects')) {
                    $table->text('qualified_subjects')->nullable()->after('kg_experience_years');
                }
                if (!Schema::hasColumn('employees', 'approved_teaching_levels')) {
                    $table->text('approved_teaching_levels')->nullable()->after('qualified_subjects');
                }
                if (!Schema::hasColumn('employees', 'secondary_subject_expertise')) {
                    $table->text('secondary_subject_expertise')->nullable()->after('approved_teaching_levels');
                }
                if (!Schema::hasColumn('employees', 'higher_sec_subject_expertise')) {
                    $table->text('higher_sec_subject_expertise')->nullable()->after('secondary_subject_expertise');
                }
                if (!Schema::hasColumn('employees', 'max_weekly_periods')) {
                    $table->integer('max_weekly_periods')->default(30)->after('higher_sec_subject_expertise');
                }
            });
        }

        // 5. Enhance staff_class_teacher_assignments table
        if (Schema::hasTable('staff_class_teacher_assignments')) {
            Schema::table('staff_class_teacher_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('staff_class_teacher_assignments', 'section_id')) {
                    $table->unsignedBigInteger('section_id')->nullable()->after('class_id');
                }
                if (!Schema::hasColumn('staff_class_teacher_assignments', 'academic_year_id')) {
                    $table->unsignedBigInteger('academic_year_id')->nullable()->after('section_id');
                }
                if (!Schema::hasColumn('staff_class_teacher_assignments', 'approval_status')) {
                    $table->string('approval_status', 50)->default('approved')->after('is_current');
                }
                if (!Schema::hasColumn('staff_class_teacher_assignments', 'notes')) {
                    $table->string('notes', 255)->nullable()->after('approval_status');
                }
            });
        }

        // 6. Enhance teacher_subject_allocations table
        if (Schema::hasTable('teacher_subject_allocations')) {
            Schema::table('teacher_subject_allocations', function (Blueprint $table) {
                if (!Schema::hasColumn('teacher_subject_allocations', 'group_id')) {
                    $table->unsignedBigInteger('group_id')->nullable()->after('academic_year_id');
                }
                if (!Schema::hasColumn('teacher_subject_allocations', 'start_date')) {
                    $table->date('start_date')->nullable()->after('group_id');
                }
                if (!Schema::hasColumn('teacher_subject_allocations', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('teacher_subject_allocations', 'max_periods_per_week')) {
                    $table->integer('max_periods_per_week')->nullable()->after('end_date');
                }
                if (!Schema::hasColumn('teacher_subject_allocations', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('max_periods_per_week');
                }
            });
        }

        // 7. Create teaching_level_activities table (for Kindergarten activity schedules)
        if (!Schema::hasTable('teaching_level_activities')) {
            Schema::create('teaching_level_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('section_id')->nullable();
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->string('activity_name', 150);
                $table->string('activity_type', 50)->default('learning'); // learning, play, music, snack, nap, storytelling, creative
                $table->time('start_time');
                $table->time('end_time');
                $table->integer('day_of_week')->default(1); // 1=Mon .. 6=Sat
                $table->unsignedBigInteger('teacher_id')->nullable();
                $table->unsignedBigInteger('assistant_teacher_id')->nullable();
                $table->text('learning_outcome')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('teacher_id')->references('id')->on('employees')->onDelete('set null');
                $table->foreign('assistant_teacher_id')->references('id')->on('employees')->onDelete('set null');
                $table->index(['class_id', 'day_of_week']);
            });
        }

        // 8. Create attendance_corrections table
        if (!Schema::hasTable('attendance_corrections')) {
            Schema::create('attendance_corrections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('attendance_record_id')->nullable();
                $table->unsignedBigInteger('staff_attendance_id')->nullable();
                $table->string('correction_type', 50)->default('student'); // student, staff
                $table->unsignedBigInteger('requested_by'); // user id
                $table->string('original_status', 50);
                $table->string('requested_status', 50);
                $table->text('reason');
                $table->string('approval_status', 50)->default('pending'); // pending, approved, rejected
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();

                $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
                $table->index(['approval_status', 'correction_type']);
            });
        }

        // 9. Seed standard Teaching Levels
        $levels = [
            [
                'code' => 'kg',
                'name' => 'Kindergarten (KG)',
                'description' => 'Pre-KG, LKG, UKG early childhood education and activity-based learning.',
                'min_class_numeric' => 0,
                'max_class_numeric' => 0,
                'schedule_type' => 'activity_based',
                'display_order' => 1,
            ],
            [
                'code' => 'primary',
                'name' => 'Foundation / Primary Level',
                'description' => 'Classes 1–5 foundational literacy, numeracy, and environmental studies.',
                'min_class_numeric' => 1,
                'max_class_numeric' => 5,
                'schedule_type' => 'period_based',
                'display_order' => 2,
            ],
            [
                'code' => 'secondary',
                'name' => 'Secondary Level',
                'description' => 'Classes 6–10 subject-specialist curriculum, laboratory work, and board prep.',
                'min_class_numeric' => 6,
                'max_class_numeric' => 10,
                'schedule_type' => 'period_based',
                'display_order' => 3,
            ],
            [
                'code' => 'higher_secondary',
                'name' => 'Higher Secondary Level',
                'description' => 'Classes 11–12 stream/group-specific academic disciplines (Science, Commerce, Arts).',
                'min_class_numeric' => 11,
                'max_class_numeric' => 12,
                'schedule_type' => 'group_period_based',
                'display_order' => 4,
            ],
        ];

        foreach ($levels as $lvl) {
            DB::table('teaching_levels')->updateOrInsert(
                ['code' => $lvl['code']],
                array_merge($lvl, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // 10. Map existing classes to their teaching level
        $kgId = DB::table('teaching_levels')->where('code', 'kg')->value('id');
        $primaryId = DB::table('teaching_levels')->where('code', 'primary')->value('id');
        $secondaryId = DB::table('teaching_levels')->where('code', 'secondary')->value('id');
        $higherSecId = DB::table('teaching_levels')->where('code', 'higher_secondary')->value('id');

        if ($kgId) {
            DB::table('classes')->whereIn('name', ['Pre-KG', 'LKG', 'UKG'])->update(['teaching_level_id' => $kgId]);
        }
        if ($primaryId) {
            DB::table('classes')->whereIn('name', ['I', 'II', 'III', 'IV', 'V'])->update(['teaching_level_id' => $primaryId]);
            DB::table('classes')->where('numeric_value', '>=', 1)->where('numeric_value', '<=', 5)->whereNull('teaching_level_id')->update(['teaching_level_id' => $primaryId]);
        }
        if ($secondaryId) {
            DB::table('classes')->whereIn('name', ['VI', 'VII', 'VIII', 'IX', 'X'])->update(['teaching_level_id' => $secondaryId]);
            DB::table('classes')->where('numeric_value', '>=', 6)->where('numeric_value', '<=', 10)->whereNull('teaching_level_id')->update(['teaching_level_id' => $secondaryId]);
        }
        if ($higherSecId) {
            DB::table('classes')->whereIn('name', ['XI', 'XII'])->update(['teaching_level_id' => $higherSecId]);
            DB::table('classes')->where('numeric_value', '>=', 11)->where('numeric_value', '<=', 12)->whereNull('teaching_level_id')->update(['teaching_level_id' => $higherSecId]);
        }

        // 11. Sync existing employee -> user linkages where employee_id is on users
        $usersWithEmp = DB::table('users')->whereNotNull('employee_id')->get(['id', 'employee_id']);
        foreach ($usersWithEmp as $u) {
            DB::table('employees')->where('id', $u->employee_id)->whereNull('user_id')->update(['user_id' => $u->id]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('teaching_level_activities');
        Schema::dropIfExists('teacher_level_eligibilities');

        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (Schema::hasColumn('classes', 'teaching_level_id')) {
                    $table->dropForeign(['teaching_level_id']);
                    $table->dropColumn('teaching_level_id');
                }
            });
        }

        Schema::dropIfExists('teaching_levels');

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (Schema::hasColumn('employees', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
                $cols = [
                    'experience_months', 'kg_experience_years', 'qualified_subjects',
                    'approved_teaching_levels', 'secondary_subject_expertise',
                    'higher_sec_subject_expertise', 'max_weekly_periods'
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('employees', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
