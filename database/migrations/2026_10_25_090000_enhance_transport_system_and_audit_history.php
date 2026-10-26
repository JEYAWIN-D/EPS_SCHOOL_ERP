<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Enhance transport_stops
        if (Schema::hasTable('transport_stops')) {
            Schema::table('transport_stops', function (Blueprint $table) {
                if (!Schema::hasColumn('transport_stops', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('van_number');
                    $table->index(['route_id', 'is_active']);
                }
            });
        }

        // 2. Enhance transport_allotments
        if (Schema::hasTable('transport_allotments')) {
            Schema::table('transport_allotments', function (Blueprint $table) {
                if (!Schema::hasColumn('transport_allotments', 'drop_stop_id')) {
                    $table->foreignId('drop_stop_id')->nullable()->constrained('transport_stops')->nullOnDelete()->after('stop_id');
                }
                if (!Schema::hasColumn('transport_allotments', 'drop_stop')) {
                    $table->string('drop_stop', 150)->nullable()->after('drop_stop_id');
                }
                if (!Schema::hasColumn('transport_allotments', 'pickup_time_override')) {
                    $table->boolean('pickup_time_override')->default(false)->after('drop_time');
                }
                if (!Schema::hasColumn('transport_allotments', 'drop_time_override')) {
                    $table->boolean('drop_time_override')->default(false)->after('pickup_time_override');
                }
                if (!Schema::hasColumn('transport_allotments', 'status')) {
                    $table->string('status', 30)->default('active')->after('drop_time_override');
                    $table->index('status');
                }
                if (!Schema::hasColumn('transport_allotments', 'start_date')) {
                    $table->date('start_date')->nullable()->after('status');
                }
                if (!Schema::hasColumn('transport_allotments', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('transport_allotments', 'remarks')) {
                    $table->text('remarks')->nullable()->after('end_date');
                }
                if (!Schema::hasColumn('transport_allotments', 'discontinued_reason')) {
                    $table->string('discontinued_reason', 255)->nullable()->after('remarks');
                }
                if (!Schema::hasColumn('transport_allotments', 'discontinued_at')) {
                    $table->timestamp('discontinued_at')->nullable()->after('discontinued_reason');
                }
                if (!Schema::hasColumn('transport_allotments', 'discontinued_by')) {
                    $table->foreignId('discontinued_by')->nullable()->constrained('users')->nullOnDelete()->after('discontinued_at');
                }
            });
        }

        // 3. Create transport_assignment_histories
        if (!Schema::hasTable('transport_assignment_histories')) {
            Schema::create('transport_assignment_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->foreignId('old_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->foreignId('new_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->foreignId('old_route_id')->nullable()->constrained('transport_routes')->nullOnDelete();
                $table->foreignId('new_route_id')->nullable()->constrained('transport_routes')->nullOnDelete();
                $table->foreignId('old_stop_id')->nullable()->constrained('transport_stops')->nullOnDelete();
                $table->foreignId('new_stop_id')->nullable()->constrained('transport_stops')->nullOnDelete();
                $table->string('old_pickup_time', 30)->nullable();
                $table->string('new_pickup_time', 30)->nullable();
                $table->string('old_drop_time', 30)->nullable();
                $table->string('new_drop_time', 30)->nullable();
                $table->decimal('old_fee', 10, 2)->nullable();
                $table->decimal('new_fee', 10, 2)->nullable();
                $table->string('change_type', 40); // 'assigned', 'changed', 'discontinued', 'resumed', 'migrated'
                $table->date('effective_date')->nullable();
                $table->text('remarks')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['student_id', 'created_at']);
                $table->index('change_type');
            });
        }

        // 4. Create transport_import_logs
        if (!Schema::hasTable('transport_import_logs')) {
            Schema::create('transport_import_logs', function (Blueprint $table) {
                $table->id();
                $table->string('file_name');
                $table->string('import_type', 50); // 'stops_master', 'student_allocations', 'combined'
                $table->integer('total_rows')->default(0);
                $table->integer('success_count')->default(0);
                $table->integer('duplicate_count')->default(0);
                $table->integer('invalid_count')->default(0);
                $table->integer('failed_count')->default(0);
                $table->json('summary_json')->nullable();
                $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_import_logs');
        Schema::dropIfExists('transport_assignment_histories');
        if (Schema::hasTable('transport_allotments')) {
            Schema::table('transport_allotments', function (Blueprint $table) {
                $cols = [
                    'drop_stop_id', 'drop_stop', 'pickup_time_override', 'drop_time_override',
                    'status', 'start_date', 'end_date', 'remarks', 'discontinued_reason',
                    'discontinued_at', 'discontinued_by'
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('transport_allotments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
        if (Schema::hasTable('transport_stops')) {
            Schema::table('transport_stops', function (Blueprint $table) {
                if (Schema::hasColumn('transport_stops', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }
    }
};
