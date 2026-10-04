<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Academic Groups Master Table (for XI, XII, and senior secondary streams)
        if (!Schema::hasTable('academic_groups')) {
            Schema::create('academic_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('code', 50)->nullable()->unique();
                $table->text('description')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'display_order']);
            });
        }

        // 2. Book & Notebook Master Checklists Table (Academic Year scoped)
        if (!Schema::hasTable('book_note_checklists')) {
            Schema::create('book_note_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('group_id')->nullable()->constrained('academic_groups')->nullOnDelete();
                $table->string('item_type', 20); // 'BOOK' or 'NOTE'
                $table->string('item_name', 255);
                $table->integer('quantity')->default(1);
                $table->integer('display_order')->default(0);
                $table->string('status', 20)->default('active'); // 'active' or 'inactive'
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                // Performance Indexes
                $table->index(['academic_year_id', 'class_id', 'status'], 'bnc_year_class_status_idx');
                $table->index(['group_id'], 'bnc_group_idx');
                $table->index(['item_type'], 'bnc_item_type_idx');
            });

            // PostgreSQL Duplicate Prevention Partial Indexes
            // Index 1: When group_id IS NULL (Classes Pre-KG to X)
            DB::statement('CREATE UNIQUE INDEX bnc_unique_without_group ON book_note_checklists (academic_year_id, class_id, item_type, LOWER(TRIM(item_name))) WHERE group_id IS NULL');
            // Index 2: When group_id IS NOT NULL (Classes XI and XII)
            DB::statement('CREATE UNIQUE INDEX bnc_unique_with_group ON book_note_checklists (academic_year_id, class_id, group_id, item_type, LOWER(TRIM(item_name))) WHERE group_id IS NOT NULL');
        }

        // 3. Admission Book & Note Items Snapshot Table
        if (!Schema::hasTable('admission_book_note_items')) {
            Schema::create('admission_book_note_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admission_id');
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->nullOnDelete();
                $table->foreignId('source_checklist_id')->nullable()->constrained('book_note_checklists')->nullOnDelete();
                $table->string('item_type', 20); // 'BOOK' or 'NOTE'
                $table->string('item_name', 255);
                $table->integer('quantity')->default(1);
                $table->timestamps();

                $table->index('admission_id');
                $table->index('student_id');
                $table->index('enquiry_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_book_note_items');
        Schema::dropIfExists('book_note_checklists');
        Schema::dropIfExists('academic_groups');
    }
};
