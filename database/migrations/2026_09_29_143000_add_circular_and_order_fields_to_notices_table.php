<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (!Schema::hasColumn('notices', 'reference_no')) {
                $table->string('reference_no', 80)->nullable()->index()->after('title');
            }
            if (!Schema::hasColumn('notices', 'issuing_authority')) {
                $table->string('issuing_authority', 100)->nullable()->after('reference_no');
            }
            if (!Schema::hasColumn('notices', 'signed_by_name')) {
                $table->string('signed_by_name', 100)->nullable()->after('issuing_authority');
            }
            if (!Schema::hasColumn('notices', 'signatory_designation')) {
                $table->string('signatory_designation', 100)->nullable()->after('signed_by_name');
            }
            if (!Schema::hasColumn('notices', 'order_category')) {
                $table->string('order_category', 60)->nullable()->after('notice_type');
            }
            if (!Schema::hasColumn('notices', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('is_published');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn([
                'reference_no',
                'issuing_authority',
                'signed_by_name',
                'signatory_designation',
                'order_category',
                'is_pinned'
            ]);
        });
    }
};
