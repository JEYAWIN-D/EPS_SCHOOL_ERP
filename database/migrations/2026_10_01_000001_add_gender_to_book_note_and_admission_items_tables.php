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
                if (!Schema::hasColumn('book_note_checklists', 'gender')) {
                    $table->string('gender', 20)->nullable()->after('item_type'); // 'BOYS', 'GIRLS', 'ALL'
                    $table->index(['gender'], 'bnc_gender_idx');
                }
            });
        }

        if (Schema::hasTable('admission_book_note_items')) {
            Schema::table('admission_book_note_items', function (Blueprint $table) {
                if (!Schema::hasColumn('admission_book_note_items', 'gender')) {
                    $table->string('gender', 20)->nullable()->after('item_type'); // 'BOYS', 'GIRLS', 'ALL'
                    $table->index(['gender'], 'abni_gender_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('book_note_checklists')) {
            Schema::table('book_note_checklists', function (Blueprint $table) {
                if (Schema::hasColumn('book_note_checklists', 'gender')) {
                    $table->dropColumn('gender');
                }
            });
        }

        if (Schema::hasTable('admission_book_note_items')) {
            Schema::table('admission_book_note_items', function (Blueprint $table) {
                if (Schema::hasColumn('admission_book_note_items', 'gender')) {
                    $table->dropColumn('gender');
                }
            });
        }
    }
};
