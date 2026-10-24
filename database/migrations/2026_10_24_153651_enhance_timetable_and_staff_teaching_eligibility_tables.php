<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance employees table with timetable teaching eligibility & availability
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'max_daily_periods')) {
                    $table->integer('max_daily_periods')->default(6)->after('max_weekly_periods');
                }
                if (!Schema::hasColumn('employees', 'preferred_days')) {
                    $table->json('preferred_days')->nullable()->after('max_daily_periods');
                }
                if (!Schema::hasColumn('employees', 'preferred_periods')) {
                    $table->json('preferred_periods')->nullable()->after('preferred_days');
                }
                if (!Schema::hasColumn('employees', 'unavailable_days')) {
                    $table->json('unavailable_days')->nullable()->after('preferred_periods');
                }
                if (!Schema::hasColumn('employees', 'unavailable_periods')) {
                    $table->json('unavailable_periods')->nullable()->after('unavailable_days');
                }
                if (!Schema::hasColumn('employees', 'eligible_classes')) {
                    $table->json('eligible_classes')->nullable()->after('unavailable_periods');
                }
                if (!Schema::hasColumn('employees', 'eligible_subjects')) {
                    $table->json('eligible_subjects')->nullable()->after('eligible_classes');
                }
            });
        }

        // 2. Enhance timetables table with status, conflict metadata, and performance indexes
        if (Schema::hasTable('timetables')) {
            Schema::table('timetables', function (Blueprint $table) {
                if (!Schema::hasColumn('timetables', 'status')) {
                    $table->string('status', 30)->default('published')->after('is_active'); // draft, published, archived
                }
                if (!Schema::hasColumn('timetables', 'conflict_note')) {
                    $table->string('conflict_note', 255)->nullable()->after('status');
                }
            });

            // Add index for fast conflict lookups if not already indexed
            try {
                Schema::table('timetables', function (Blueprint $table) {
                    $table->index(['academic_year_id', 'day_of_week', 'period_number', 'teacher_id'], 'idx_tt_ay_dow_pn_teacher');
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }

        // 3. Enhance teacher_subject_allocations table with audit tracking
        if (Schema::hasTable('teacher_subject_allocations')) {
            Schema::table('teacher_subject_allocations', function (Blueprint $table) {
                if (!Schema::hasColumn('teacher_subject_allocations', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
                }
                if (!Schema::hasColumn('teacher_subject_allocations', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('timetables')) {
            Schema::table('timetables', function (Blueprint $table) {
                if (Schema::hasColumn('timetables', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('timetables', 'conflict_note')) {
                    $table->dropColumn('conflict_note');
                }
                try {
                    $table->dropIndex('idx_tt_ay_dow_pn_teacher');
                } catch (\Throwable $e) {
                }
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                $cols = ['max_daily_periods', 'preferred_days', 'preferred_periods', 'unavailable_days', 'unavailable_periods', 'eligible_classes', 'eligible_subjects'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('employees', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('teacher_subject_allocations')) {
            Schema::table('teacher_subject_allocations', function (Blueprint $table) {
                if (Schema::hasColumn('teacher_subject_allocations', 'created_by')) {
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('teacher_subject_allocations', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
            });
        }
    }
};
