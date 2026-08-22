<?php

namespace App\Exceptions;

use Exception;

class InsufficientPharmacyStockException extends Exception
{
    public function __construct(
        public readonly string $medicineName,
        public readonly int $available,
        public readonly int $required,
    ) {
        parent::__construct(
            "Insufficient stock for {$medicineName}. Available: {$available}, required: {$required}."
        );
    }
}
