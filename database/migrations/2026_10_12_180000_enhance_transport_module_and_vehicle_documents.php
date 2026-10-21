<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Create vehicle_documents table
        if (!Schema::hasTable('vehicle_documents')) {
            Schema::create('vehicle_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
                $table->string('title');
                $table->string('document_type', 50)->default('other'); // rc, insurance, fitness, permit, puc, tax, driver_license, attendant_id, other
                $table->string('file_path');
                $table->string('file_name')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
            });
        }

        // 2. Enhance vehicles table with extra fields
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'driver_address')) {
                $table->text('driver_address')->nullable()->after('driver_license');
            }
            if (!Schema::hasColumn('vehicles', 'attendant_name')) {
                $table->string('attendant_name', 100)->nullable()->after('driver_address');
            }
            if (!Schema::hasColumn('vehicles', 'attendant_mobile')) {
                $table->string('attendant_mobile', 20)->nullable()->after('attendant_name');
            }
            if (!Schema::hasColumn('vehicles', 'attendant_address')) {
                $table->text('attendant_address')->nullable()->after('attendant_mobile');
            }
            if (!Schema::hasColumn('vehicles', 'attendant_aadhaar')) {
                $table->string('attendant_aadhaar', 20)->nullable()->after('attendant_address');
            }
        });

        // 3. Drop shadowing stops column from transport_routes if present
        if (Schema::hasColumn('transport_routes', 'stops')) {
            Schema::table('transport_routes', function (Blueprint $table) {
                $table->dropColumn('stops');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_documents');
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['driver_address', 'attendant_name', 'attendant_mobile', 'attendant_address', 'attendant_aadhaar']);
        });
        if (!Schema::hasColumn('transport_routes', 'stops')) {
            Schema::table('transport_routes', function (Blueprint $table) {
                $table->text('stops')->nullable();
            });
        }
    }
};
