<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('book_note_checklists')) {
            // Drop existing unique indexes if they exist
            DB::statement('DROP INDEX IF EXISTS bnc_unique_without_group');
            DB::statement('DROP INDEX IF EXISTS bnc_unique_with_group');

            // Recreate unique indexes including COALESCE(gender, 'ALL')
            DB::statement("CREATE UNIQUE INDEX bnc_unique_without_group ON book_note_checklists (academic_year_id, class_id, item_type, COALESCE(gender, 'ALL'), LOWER(TRIM(item_name))) WHERE group_id IS NULL");
            DB::statement("CREATE UNIQUE INDEX bnc_unique_with_group ON book_note_checklists (academic_year_id, class_id, group_id, item_type, COALESCE(gender, 'ALL'), LOWER(TRIM(item_name))) WHERE group_id IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('book_note_checklists')) {
            DB::statement('DROP INDEX IF EXISTS bnc_unique_without_group');
            DB::statement('DROP INDEX IF EXISTS bnc_unique_with_group');

            DB::statement('CREATE UNIQUE INDEX bnc_unique_without_group ON book_note_checklists (academic_year_id, class_id, item_type, LOWER(TRIM(item_name))) WHERE group_id IS NULL');
            DB::statement('CREATE UNIQUE INDEX bnc_unique_with_group ON book_note_checklists (academic_year_id, class_id, group_id, item_type, LOWER(TRIM(item_name))) WHERE group_id IS NOT NULL');
        }
    }
};
