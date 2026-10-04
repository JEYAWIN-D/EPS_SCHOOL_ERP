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
        if (Schema::hasTable('admission_book_note_items')) {
            Schema::table('admission_book_note_items', function (Blueprint $table) {
                if (!Schema::hasColumn('admission_book_note_items', 'issued_quantity')) {
                    $table->integer('issued_quantity')->default(0)->after('quantity');
                }
                if (!Schema::hasColumn('admission_book_note_items', 'is_issued')) {
                    $table->boolean('is_issued')->default(false)->after('issued_quantity');
                }
                if (!Schema::hasColumn('admission_book_note_items', 'issued_at')) {
                    $table->timestamp('issued_at')->nullable()->after('is_issued');
                }
                if (!Schema::hasColumn('admission_book_note_items', 'issued_by')) {
                    $table->unsignedBigInteger('issued_by')->nullable()->after('issued_at');
                }
                if (!Schema::hasColumn('admission_book_note_items', 'remarks')) {
                    $table->string('remarks', 255)->nullable()->after('issued_by');
                }

                $table->index('is_issued');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admission_book_note_items')) {
            Schema::table('admission_book_note_items', function (Blueprint $table) {
                $columns = ['issued_quantity', 'is_issued', 'issued_at', 'issued_by', 'remarks'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('admission_book_note_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
