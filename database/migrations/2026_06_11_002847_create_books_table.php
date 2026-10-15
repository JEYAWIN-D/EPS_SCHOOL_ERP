<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('book_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('accession_number')->unique();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->string('isbn', 20)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->year('publish_year')->nullable();
            $table->string('edition')->nullable();
            $table->string('language', 30)->nullable();
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->decimal('purchase_price', 8, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_available')->default(true);
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'author']);
            }
            $table->timestamps();
        });

        Schema::create('book_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->decimal('fine_amount', 6, 2)->default(0);
            $table->boolean('fine_paid')->default(false);
            $table->enum('status', ['issued','returned','lost','overdue'])->default('issued');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('returned_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('returned_to')->references('id')->on('users')->nullOnDelete();
            $table->index(['student_id', 'status']);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('book_issues');
        Schema::dropIfExists('books');
        Schema::dropIfExists('book_categories');
    }
};
