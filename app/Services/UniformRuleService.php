<?php

namespace App\Services;

use App\Models\Classes;

class UniformRuleService
{
    public const TIER_PRE_KG_TO_SR_KG = 'PRE_KG_TO_SR_KG';
    public const TIER_GRADE_I_TO_III = 'GRADE_I_TO_III';
    public const TIER_GRADE_IV_TO_XI = 'GRADE_IV_TO_XI';

    /**
     * Resolve Tier from Class model, ID or name string.
     */
    public function resolveTier(int|Classes|string $class): string
    {
        $classModel = null;
        if ($class instanceof Classes) {
            $classModel = $class;
        } elseif (is_int($class) || ctype_digit((string)$class)) {
            $classModel = Classes::find((int)$class);
        }

        $rawName = strtoupper(trim($classModel?->name ?? (is_string($class) ? $class : '')));
        $numVal = $classModel ? (int)$classModel->numeric_value : null;

        // Check kindergarten first (Pre-KG, LKG / Jr.KG, UKG / Sr.KG)
        if (
            str_contains($rawName, 'KG') || 
            str_contains($rawName, 'PRE') || 
            str_contains($rawName, 'MONTESSORI') ||
            $rawName === 'LKG' || $rawName === 'UKG' ||
            ($numVal === 0 && !str_contains($rawName, 'X'))
        ) {
            return self::TIER_PRE_KG_TO_SR_KG;
        }

        // Check numeric value
        if ($numVal !== null && $numVal > 0) {
            if ($numVal >= 1 && $numVal <= 3) {
                return self::TIER_GRADE_I_TO_III;
            }
            if ($numVal >= 4) {
                return self::TIER_GRADE_IV_TO_XI;
            }
        }

        // Roman numerals check
        if (in_array($rawName, ['I', 'II', 'III', 'CLASS I', 'CLASS II', 'CLASS III', 'GRADE I', 'GRADE II', 'GRADE III', '1', '2', '3'])) {
            return self::TIER_GRADE_I_TO_III;
        }

        return self::TIER_GRADE_IV_TO_XI;
    }

    /**
     * Normalize gender input.
     * Returns 'BOYS' or 'GIRLS'.
     */
    public function normalizeGender(?string $gender): string
    {
        $g = strtolower(trim($gender ?? ''));
        if (in_array($g, ['female', 'girl', 'girls', 'f', 'woman'])) {
            return 'GIRLS';
        }
        return 'BOYS';
    }

    /**
     * Get uniform item specifications for a specific Tier and Gender.
     * 
     * Rules defined:
     * - Pre-KG to Sr.KG BOYS: Green T-Shirt (2), White T-Shirt (1), Shorts (3)
     * - Pre-KG to Sr.KG GIRLS: Green T-Shirt (2), White T-Shirt (1), Skirt (3)
     * - Grade I to III BOYS: White T-Shirt (2), Shorts (3), White Shirt (1), Sports T-Shirt (1)
     * - Grade I to III GIRLS: White T-Shirt (2), Skirt (3), White Shirt (1), Sports T-Shirt (1)
     * - Grade IV to XI BOYS: White T-Shirt (2), White Shirt (1), Pant (3), Sports T-Shirt (1)
     * - Grade IV to XI GIRLS: White T-Shirt (2), Skirt (3), White Shirt (1), Sports T-Shirt (1)
     */
    public function getRulesByTierAndGender(string $tier, string $gender): array
    {
        $gender = $this->normalizeGender($gender);

        if ($tier === self::TIER_PRE_KG_TO_SR_KG) {
            if ($gender === 'BOYS') {
                return [
                    ['name' => 'Green T-Shirt', 'quantity' => 2, 'display_order' => 1, 'category' => 'Top', 'gender' => 'BOYS'],
                    ['name' => 'White T-Shirt', 'quantity' => 1, 'display_order' => 2, 'category' => 'Top', 'gender' => 'BOYS'],
                    ['name' => 'Shorts',        'quantity' => 3, 'display_order' => 3, 'category' => 'Bottom', 'gender' => 'BOYS'],
                ];
            } else {
                return [
                    ['name' => 'Green T-Shirt', 'quantity' => 2, 'display_order' => 1, 'category' => 'Top', 'gender' => 'GIRLS'],
                    ['name' => 'White T-Shirt', 'quantity' => 1, 'display_order' => 2, 'category' => 'Top', 'gender' => 'GIRLS'],
                    ['name' => 'Skirt',         'quantity' => 3, 'display_order' => 3, 'category' => 'Bottom', 'gender' => 'GIRLS'],
                ];
            }
        }

        if ($tier === self::TIER_GRADE_I_TO_III) {
            if ($gender === 'BOYS') {
                return [
                    ['name' => 'White T-Shirt',  'quantity' => 2, 'display_order' => 1, 'category' => 'Top', 'gender' => 'BOYS'],
                    ['name' => 'Shorts',         'quantity' => 3, 'display_order' => 2, 'category' => 'Bottom', 'gender' => 'BOYS'],
                    ['name' => 'White Shirt',    'quantity' => 1, 'display_order' => 3, 'category' => 'Top', 'gender' => 'BOYS'],
                    ['name' => 'Sports T-Shirt', 'quantity' => 1, 'display_order' => 4, 'category' => 'Sports', 'gender' => 'BOYS'],
                ];
            } else {
                return [
                    ['name' => 'White T-Shirt',  'quantity' => 2, 'display_order' => 1, 'category' => 'Top', 'gender' => 'GIRLS'],
                    ['name' => 'Skirt',          'quantity' => 3, 'display_order' => 2, 'category' => 'Bottom', 'gender' => 'GIRLS'],
                    ['name' => 'White Shirt',    'quantity' => 1, 'display_order' => 3, 'category' => 'Top', 'gender' => 'GIRLS'],
                    ['name' => 'Sports T-Shirt', 'quantity' => 1, 'display_order' => 4, 'category' => 'Sports', 'gender' => 'GIRLS'],
                ];
            }
        }

        // Grade IV to XI
        if ($gender === 'BOYS') {
            return [
                ['name' => 'White T-Shirt',  'quantity' => 2, 'display_order' => 1, 'category' => 'Top', 'gender' => 'BOYS'],
                ['name' => 'White Shirt',    'quantity' => 1, 'display_order' => 2, 'category' => 'Top', 'gender' => 'BOYS'],
                ['name' => 'Pant',           'quantity' => 3, 'display_order' => 3, 'category' => 'Bottom', 'gender' => 'BOYS'],
                ['name' => 'Sports T-Shirt', 'quantity' => 1, 'display_order' => 4, 'category' => 'Sports', 'gender' => 'BOYS'],
            ];
        } else {
            return [
                ['name' => 'White T-Shirt',  'quantity' => 2, 'display_order' => 1, 'category' => 'Top', 'gender' => 'GIRLS'],
                ['name' => 'Skirt',          'quantity' => 3, 'display_order' => 2, 'category' => 'Bottom', 'gender' => 'GIRLS'],
                ['name' => 'White Shirt',    'quantity' => 1, 'display_order' => 3, 'category' => 'Top', 'gender' => 'GIRLS'],
                ['name' => 'Sports T-Shirt', 'quantity' => 1, 'display_order' => 4, 'category' => 'Sports', 'gender' => 'GIRLS'],
            ];
        }
    }

    /**
     * Generate standard-wise uniform SKU.
     * Format: EPS-{CLASS_CODE}-{B/G}-UNF-{001}
     */
    public function generateSku(int|Classes|string $class, string $gender, int $displayOrder = 1): string
    {
        $classModel = $class instanceof Classes ? $class : (is_int($class) || ctype_digit((string)$class) ? Classes::find((int)$class) : null);
        $rawClassName = strtoupper(trim($classModel?->name ?? (is_string($class) ? $class : 'CL')));

        $classCode = match($rawClassName) {
            'PRE-KG', 'PRE KG' => 'PKG',
            'LKG', 'JUNIOR KG' => 'LKG',
            'UKG', 'SENIOR KG' => 'UKG',
            default            => preg_replace('/[^A-Z0-9]/', '', $rawClassName) ?: 'CL'
        };

        $genLetter = $this->normalizeGender($gender) === 'BOYS' ? 'B' : 'G';
        $num = str_pad($displayOrder, 3, '0', STR_PAD_LEFT);

        return "EPS-{$classCode}-{$genLetter}-UNF-{$num}";
    }

    /**
     * Get uniform bundle for a specific class and gender.
     */
    public function getUniformsForClass(int|Classes $class, ?string $gender = null): array
    {
        $classModel = $class instanceof Classes ? $class : Classes::find($class);
        if (!$classModel) {
            return ['boys' => [], 'girls' => [], 'active' => []];
        }

        $tier = $this->resolveTier($classModel);

        $boysRules = $this->getRulesByTierAndGender($tier, 'BOYS');
        $girlsRules = $this->getRulesByTierAndGender($tier, 'GIRLS');

        $boysItems = [];
        foreach ($boysRules as $r) {
            $boysItems[] = [
                'name'         => $r['name'],
                'item_name'    => $r['name'],
                'quantity'     => $r['quantity'],
                'item_type'    => 'UNIFORM',
                'itemType'     => 'UNIFORM',
                'gender'       => 'BOYS',
                'category'     => $r['category'],
                'display_order'=> $r['display_order'],
                'displayOrder' => $r['display_order'],
                'sku'          => $this->generateSku($classModel, 'BOYS', $r['display_order']),
            ];
        }

        $girlsItems = [];
        foreach ($girlsRules as $r) {
            $girlsItems[] = [
                'name'         => $r['name'],
                'item_name'    => $r['name'],
                'quantity'     => $r['quantity'],
                'item_type'    => 'UNIFORM',
                'itemType'     => 'UNIFORM',
                'gender'       => 'GIRLS',
                'category'     => $r['category'],
                'display_order'=> $r['display_order'],
                'displayOrder' => $r['display_order'],
                'sku'          => $this->generateSku($classModel, 'GIRLS', $r['display_order']),
            ];
        }

        $activeItems = [];
        if ($gender) {
            $norm = $this->normalizeGender($gender);
            $activeItems = $norm === 'BOYS' ? $boysItems : $girlsItems;
        }

        return [
            'tier'         => $tier,
            'boys'         => $boysItems,
            'girls'        => $girlsItems,
            'boys_qty'     => array_sum(array_column($boysItems, 'quantity')),
            'girls_qty'    => array_sum(array_column($girlsItems, 'quantity')),
            'active'       => $activeItems,
        ];
    }
}
