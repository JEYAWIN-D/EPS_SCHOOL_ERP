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
        Schema::table('fee_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_payments', 'amount_adjusted')) {
                $table->decimal('amount_adjusted', 10, 2)->default(0)->after('amount_paid');
            }
            if (!Schema::hasColumn('fee_payments', 'excess_amount')) {
                $table->decimal('excess_amount', 10, 2)->default(0)->after('amount_adjusted');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'excess_amount')) {
                $table->decimal('excess_amount', 10, 2)->default(0)->after('admission_pending_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            if (Schema::hasColumn('fee_payments', 'excess_amount')) {
                $table->dropColumn('excess_amount');
            }
            if (Schema::hasColumn('fee_payments', 'amount_adjusted')) {
                $table->dropColumn('amount_adjusted');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'excess_amount')) {
                $table->dropColumn('excess_amount');
            }
        });
    }
};
