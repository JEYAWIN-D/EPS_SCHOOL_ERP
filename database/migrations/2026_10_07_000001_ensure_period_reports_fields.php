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
        if (Schema::hasTable('period_reports')) {
            Schema::table('period_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('period_reports', 'student_absent_count')) {
                    $table->integer('student_absent_count')->nullable()->default(0)->after('student_present_count');
                }
                if (!Schema::hasColumn('period_reports', 'description')) {
                    $table->text('description')->nullable()->after('student_absent_count');
                }
                if (!Schema::hasColumn('period_reports', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('description');
                    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('period_reports')) {
            Schema::table('period_reports', function (Blueprint $table) {
                if (Schema::hasColumn('period_reports', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('period_reports', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('period_reports', 'student_absent_count')) {
                    $table->dropColumn('student_absent_count');
                }
            });
        }
    }
};
