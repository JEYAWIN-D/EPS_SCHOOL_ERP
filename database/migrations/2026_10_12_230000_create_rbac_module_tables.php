<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        // 1. Add deleted_at and username to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 100)->nullable()->unique()->after('name');
            }
        });

        // 2. Create account_target_configs table
        if (!Schema::hasTable('account_target_configs')) {
            Schema::create('account_target_configs', function (Blueprint $table) {
                $table->id();
                $table->string('role_name', 100)->unique();
                $table->integer('target_count')->default(1);
                $table->integer('warning_threshold')->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // 3. Create user_data_scopes table
        if (!Schema::hasTable('user_data_scopes')) {
            Schema::create('user_data_scopes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('scope_type', 50); // all, academic_year, class_id, section_id, department, route_id, warehouse_id, assigned_students_only, own_records_only
                $table->text('scope_value')->nullable(); // JSON array or scalar ID/name
                $table->boolean('is_active')->default(true);
                $table->string('notes', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['user_id', 'scope_type']);
            });
        }

        // 4. Create field_permissions table
        if (!Schema::hasTable('field_permissions')) {
            Schema::create('field_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('entity', 100); // students, employees, fees, admissions
                $table->string('field_name', 100); // salary, father_mobile, bank_account_no, etc.
                $table->string('permission_type', 50)->default('masked'); // hidden, masked, readonly, editable
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['role_id', 'entity', 'field_name']);
                $table->index(['user_id', 'entity', 'field_name']);
            });
        }

        // 5. Seed missing required roles safely
        $requiredRoles = [
            'super_admin',
            'admin',
            'principal',
            'vice_principal',
            'teacher',
            'class_teacher',
            'librarian',
            'warehouse_manager',
        ];

        foreach ($requiredRoles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 6. Seed initial account target configurations
        $initialTargets = [
            ['role_name' => 'super_admin', 'target_count' => 2, 'notes' => 'Target 2 Super Admin accounts for failover redundancy'],
            ['role_name' => 'admin', 'target_count' => 2, 'notes' => 'Target 2 System Admin accounts'],
            ['role_name' => 'principal', 'target_count' => 1, 'notes' => 'Primary School Principal account'],
            ['role_name' => 'vice_principal', 'target_count' => 1, 'notes' => 'Vice-Principal / Visiting Officer account'],
            ['role_name' => 'teacher', 'target_count' => 20, 'notes' => 'Teaching Staff target'],
            ['role_name' => 'class_teacher', 'target_count' => 10, 'notes' => 'Section in-charge teachers'],
            ['role_name' => 'librarian', 'target_count' => 1, 'notes' => 'Library Management in-charge'],
            ['role_name' => 'warehouse_manager', 'target_count' => 1, 'notes' => 'Store & Inventory Supervisor'],
        ];

        foreach ($initialTargets as $target) {
            DB::table('account_target_configs')->updateOrInsert(
                ['role_name' => $target['role_name']],
                [
                    'target_count' => $target['target_count'],
                    'notes' => $target['notes'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('field_permissions');
        Schema::dropIfExists('user_data_scopes');
        Schema::dropIfExists('account_target_configs');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
