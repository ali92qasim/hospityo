<?php

namespace App\Exceptions;

use Exception;

class MedicineException extends Exception
{
    /**
     * Duplicate medicine exception
     */
    public static function duplicateFound($medicine)
    {
        return new static(
            "A medicine with similar details already exists: {$medicine->name} " .
            "({$medicine->strength}, {$medicine->dosage_form}). " .
            "SKU: {$medicine->sku}"
        );
    }

    /**
     * Insufficient stock exception
     */
    public static function insufficientStock($medicine, $requested, $available)
    {
        return new static(
            "Insufficient stock for {$medicine->name}. " .
            "Requested: {$requested}, Available: {$available}"
        );
    }

    /**
     * Medicine not found exception
     */
    public static function notFound($id)
    {
        return new static("Medicine with ID {$id} not found.");
    }

    /**
     * Stock management disabled exception
     */
    public static function stockManagementDisabled($medicine)
    {
        return new static(
            "Stock management is disabled for {$medicine->name}. " .
            "Enable it to perform stock operations."
        );
    }
}
