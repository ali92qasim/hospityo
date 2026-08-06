<?php

namespace App\Services;

use App\Models\Unit;

class MedicineStockConversion
{
    /**
     * @return array{base_quantity: int, base_unit_cost: float, total_cost: float}
     */
    public static function toBaseUnits(Unit $unit, int $quantity, float $unitCost): array
    {
        $baseQuantity = (int) round($unit->convertToBaseUnit($quantity));
        $factor = (float) $unit->conversion_factor;
        $baseUnitCost = $factor > 0 ? $unitCost / $factor : 0.0;

        return [
            'base_quantity' => $baseQuantity,
            'base_unit_cost' => round($baseUnitCost, 4),
            'total_cost' => round($quantity * $unitCost, 2),
        ];
    }
}
