<?php

namespace App\Services;

use App\Exceptions\InsufficientPharmacyStockException;
use App\Models\InventoryTransaction;
use App\Models\Medicine;

class PharmacyStockDispenseService
{
    /**
     * @param  array<int, array{medicine: Medicine, quantity: int, reference: string, notes?: string|null}>  $lines
     */
    public function assertStockAvailable(array $lines): void
    {
        foreach ($lines as $line) {
            $medicine = $line['medicine'];

            if (! $medicine->manage_stock) {
                continue;
            }

            $available = $medicine->getTotalAvailableStock();
            $required = (int) $line['quantity'];

            if ($available < $required) {
                throw new InsufficientPharmacyStockException($medicine->name, $available, $required);
            }
        }
    }

    /**
     * @param  array<int, array{medicine: Medicine, quantity: int, reference: string, notes?: string|null}>  $lines
     */
    public function dispenseLines(array $lines, int $userId): void
    {
        $this->assertStockAvailable($lines);

        foreach ($lines as $line) {
            $medicine = $line['medicine'];

            if (! $medicine->manage_stock) {
                continue;
            }

            $remaining = (int) $line['quantity'];
            $reference = $line['reference'];
            $notes = $line['notes'] ?? 'Dispensed via ' . $reference;
            $batches = $medicine->getAvailableBatches();

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $consume = min($batch->remaining_quantity, $remaining);

                $batch->decrement('remaining_quantity', $consume);

                InventoryTransaction::create([
                    'medicine_id' => $medicine->id,
                    'type' => 'stock_out',
                    'quantity' => $consume,
                    'unit_cost' => $batch->unit_cost,
                    'total_cost' => $consume * $batch->unit_cost,
                    'batch_no' => $batch->batch_no,
                    'reference_no' => $reference,
                    'notes' => $notes,
                    'created_by' => $userId,
                ]);

                $remaining -= $consume;
            }

            if ($remaining > 0) {
                throw new \RuntimeException(
                    "Stock exhausted mid-dispense for {$medicine->name}. Transaction rolled back."
                );
            }
        }
    }
}
