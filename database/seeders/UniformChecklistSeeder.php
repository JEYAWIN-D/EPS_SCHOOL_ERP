<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\BookNoteChecklist;
use App\Models\Classes;
use App\Services\UniformRuleService;
use Illuminate\Database\Seeder;

class UniformChecklistSeeder extends Seeder
{
    /**
     * Seed prescribed uniform items into book_note_checklists.
     */
    public function run(): void
    {
        $uniformRuleService = app(UniformRuleService::class);
        $academicYears = AcademicYear::all();
        $classes = Classes::orderBy('numeric_value')->get();

        foreach ($academicYears as $year) {
            foreach ($classes as $class) {
                $tier = $uniformRuleService->resolveTier($class);
                $boysRules = $uniformRuleService->getRulesByTierAndGender($tier, 'BOYS');
                $girlsRules = $uniformRuleService->getRulesByTierAndGender($tier, 'GIRLS');

                // Seed Boys items
                $order = 1;
                foreach ($boysRules as $rule) {
                    $sku = $uniformRuleService->generateSku($class, 'BOYS', $order);
                    BookNoteChecklist::updateOrCreate(
                        [
                            'academic_year_id' => $year->id,
                            'class_id'         => $class->id,
                            'group_id'         => null,
                            'item_type'        => 'UNIFORM',
                            'gender'           => 'BOYS',
                            'item_name'        => $rule['name'],
                        ],
                        [
                            'sku'              => $sku,
                            'quantity'         => $rule['quantity'],
                            'display_order'    => $order++,
                            'status'           => 'active',
                        ]
                    );
                }

                // Seed Girls items
                $order = 1;
                foreach ($girlsRules as $rule) {
                    $sku = $uniformRuleService->generateSku($class, 'GIRLS', $order);
                    BookNoteChecklist::updateOrCreate(
                        [
                            'academic_year_id' => $year->id,
                            'class_id'         => $class->id,
                            'group_id'         => null,
                            'item_type'        => 'UNIFORM',
                            'gender'           => 'GIRLS',
                            'item_name'        => $rule['name'],
                        ],
                        [
                            'sku'              => $sku,
                            'quantity'         => $rule['quantity'],
                            'display_order'    => $order++,
                            'status'           => 'active',
                        ]
                    );
                }
            }
        }
    }
}
