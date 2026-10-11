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
                if (!Schema::hasColumn('period_reports', 'day')) {
                    $table->string('day', 20)->nullable()->after('date');
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
                if (Schema::hasColumn('period_reports', 'day')) {
                    $table->dropColumn('day');
                }
            });
        }
    }
};
