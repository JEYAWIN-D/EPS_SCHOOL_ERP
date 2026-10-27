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
        // 1. Create houses table
        if (!Schema::hasTable('houses')) {
            Schema::create('houses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 20)->unique();
                $table->string('color', 20)->default('#8C2826');
                $table->string('logo')->nullable();
                $table->unsignedBigInteger('captain_student_id')->nullable();
                $table->unsignedBigInteger('vice_captain_student_id')->nullable();
                $table->unsignedBigInteger('teacher_id')->nullable();
                $table->integer('capacity')->nullable();
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->foreign('captain_student_id')->references('id')->on('students')->nullOnDelete();
                $table->foreign('vice_captain_student_id')->references('id')->on('students')->nullOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            });
        }

        // 2. Add house_id to student_enrollments if not present
        if (Schema::hasTable('student_enrollments')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                if (!Schema::hasColumn('student_enrollments', 'house_id')) {
                    $table->unsignedBigInteger('house_id')->nullable()->after('house');
                    $table->foreign('house_id')->references('id')->on('houses')->nullOnDelete();
                }
            });
        }

        // 3. Create student_allotment_histories table
        if (!Schema::hasTable('student_allotment_histories')) {
            Schema::create('student_allotment_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->string('allotment_type', 30); // 'house', 'section', 'roll_number'
                $table->string('from_value')->nullable();
                $table->string('to_value')->nullable();
                $table->string('reason')->nullable();
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
                $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['student_id', 'allotment_type']);
            });
        }

        // 4. Seed standard default houses if houses table is empty
        if (DB::table('houses')->count() === 0) {
            $currentYear = DB::table('academic_years')->where('is_current', true)->first();
            $yearId = $currentYear ? $currentYear->id : null;

            $now = now();
            $defaultHouses = [
                [
                    'name' => 'Red (Ruby)',
                    'code' => 'RED',
                    'color' => '#DC2626',
                    'academic_year_id' => $yearId,
                    'is_active' => true,
                    'description' => 'Ruby House (Red) symbolizes passion, courage, and determination.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Blue (Sapphire)',
                    'code' => 'BLUE',
                    'color' => '#2563EB',
                    'academic_year_id' => $yearId,
                    'is_active' => true,
                    'description' => 'Sapphire House (Blue) symbolizes wisdom, loyalty, and integrity.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Green (Emerald)',
                    'code' => 'GREEN',
                    'color' => '#059669',
                    'academic_year_id' => $yearId,
                    'is_active' => true,
                    'description' => 'Emerald House (Green) symbolizes growth, vitality, and perseverance.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Yellow (Topaz)',
                    'code' => 'YELLOW',
                    'color' => '#D97706',
                    'academic_year_id' => $yearId,
                    'is_active' => true,
                    'description' => 'Topaz House (Yellow) symbolizes friendship, hope, and optimism.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            foreach ($defaultHouses as $h) {
                DB::table('houses')->insert($h);
            }

            // Backfill house_id on student_enrollments
            $houseMap = DB::table('houses')->pluck('id', 'code')->toArray();
            foreach ($houseMap as $code => $id) {
                DB::table('student_enrollments')
                    ->whereRaw('UPPER(house) = ?', [$code])
                    ->update(['house_id' => $id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('student_enrollments', 'house_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->dropForeign(['house_id']);
                $table->dropColumn('house_id');
            });
        }
        Schema::dropIfExists('student_allotment_histories');
        Schema::dropIfExists('houses');
    }
};

