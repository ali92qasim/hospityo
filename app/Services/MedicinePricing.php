<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Medicine;

class MedicinePricing
{
    public static function hasSellingPrice(Medicine $medicine): bool
    {
        return ! is_null($medicine->selling_price);
    }

    public static function sellingPricePerBaseUnit(Medicine $medicine): float
    {
        if (is_null($medicine->selling_price)) {
            return 0.0;
        }

        return (float) $medicine->selling_price;
    }

    public static function batchCostPerBaseUnit(InventoryTransaction $batch): float
    {
        return (float) $batch->unit_cost;
    }

    public static function snapshotLinePrice(Medicine $medicine): float
    {
        return self::sellingPricePerBaseUnit($medicine);
    }
}
