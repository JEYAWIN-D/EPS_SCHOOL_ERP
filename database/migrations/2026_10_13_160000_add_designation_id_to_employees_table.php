<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'designation_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('designation_id')->nullable()->index()->after('department_id');
                if (Schema::hasTable('designations')) {
                    $table->foreign('designation_id')->references('id')->on('designations')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'designation_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['designation_id']);
                $table->dropColumn('designation_id');
            });
        }
    }
};
