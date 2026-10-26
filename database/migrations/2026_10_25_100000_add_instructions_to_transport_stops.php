<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('transport_stops')) {
            Schema::table('transport_stops', function (Blueprint $table) {
                if (!Schema::hasColumn('transport_stops', 'pickup_instructions')) {
                    $table->text('pickup_instructions')->nullable()->after('landmark');
                }
                if (!Schema::hasColumn('transport_stops', 'drop_instructions')) {
                    $table->text('drop_instructions')->nullable()->after('pickup_instructions');
                }
                if (!Schema::hasColumn('transport_stops', 'remarks')) {
                    $table->text('remarks')->nullable()->after('drop_instructions');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transport_stops')) {
            Schema::table('transport_stops', function (Blueprint $table) {
                if (Schema::hasColumn('transport_stops', 'pickup_instructions')) {
                    $table->dropColumn('pickup_instructions');
                }
                if (Schema::hasColumn('transport_stops', 'drop_instructions')) {
                    $table->dropColumn('drop_instructions');
                }
                if (Schema::hasColumn('transport_stops', 'remarks')) {
                    $table->dropColumn('remarks');
                }
            });
        }
    }
};
