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
        if (Schema::hasTable('book_note_checklists')) {
            Schema::table('book_note_checklists', function (Blueprint $table) {
                if (!Schema::hasColumn('book_note_checklists', 'sku')) {
                    $table->string('sku', 100)->nullable()->after('item_type');
                    $table->index(['sku'], 'bnc_sku_idx');
                }
            });
        }

        if (Schema::hasTable('admission_book_note_items')) {
            Schema::table('admission_book_note_items', function (Blueprint $table) {
                if (!Schema::hasColumn('admission_book_note_items', 'sku')) {
                    $table->string('sku', 100)->nullable()->after('item_type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('book_note_checklists') && Schema::hasColumn('book_note_checklists', 'sku')) {
            Schema::table('book_note_checklists', function (Blueprint $table) {
                $table->dropColumn('sku');
            });
        }

        if (Schema::hasTable('admission_book_note_items') && Schema::hasColumn('admission_book_note_items', 'sku')) {
            Schema::table('admission_book_note_items', function (Blueprint $table) {
                $table->dropColumn('sku');
            });
        }
    }
};
