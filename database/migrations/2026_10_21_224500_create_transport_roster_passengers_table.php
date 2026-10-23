<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('transport_roster_passengers')) {
            Schema::create('transport_roster_passengers', function (Blueprint $table) {
                $table->id();
                $table->string('van_number', 30); // 'VAN I', 'VAN II', 'VAN III', 'VAN IV', 'VAN V'
                $table->string('van_label', 150)->nullable();
                $table->foreignId('route_id')->nullable()->constrained('transport_routes')->nullOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->integer('serial_no')->default(1);
                $table->string('student_name', 150);
                $table->string('class_name', 50);
                $table->string('area', 150);
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('morning_time', 50)->nullable();
                $table->string('evening_time', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['van_number', 'is_active']);
                $table->index('student_name');
                $table->index('area');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_roster_passengers');
    }
};
