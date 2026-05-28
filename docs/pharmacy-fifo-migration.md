# Pharmacy Module — FIFO Stock Consumption Migration

## Overview

The current pharmacy module does not implement FIFO (First In, First Out) stock management. This document describes what needs to change, why, and exactly how to implement it while keeping all frontend views untouched.

---

## Current State (What Is Wrong)

### 1. Dispense uses a flat counter, not batch transactions

`PrescriptionController::dispense()` does this:

```php
$medicine->decrement('stock_quantity', $item->quantity);
```

This decrements a single integer column `stock_quantity` on the `medicines` table. It has no awareness of which batch the stock came from, no ordering by date or expiry, and does not create any `inventory_transactions` record for the outgoing stock. The `inventory_transactions` table is only written to during manual stock-in and stock-out operations — dispensing bypasses it entirely.

### 2. `stock_quantity` on `medicines` is a ghost column

The `medicines` table has a `stock_quantity` integer column. The `InventoryTransaction` model computes current stock via `getCurrentStock()` by summing `inventory_transactions`. These two sources of truth are not in sync. Dispensing decrements `stock_quantity` but does not write a `stock_out` transaction, so `getCurrentStock()` never reflects dispensed quantities.

### 3. `batch_number` on `medicines` vs `batch_no` on `inventory_transactions`

The original `medicines` table migration added `batch_number` as a single string column — treating batch as a static property of the medicine itself. This is architecturally wrong. A medicine (e.g. Paracetamol 500mg) is a product definition. Each purchase of that medicine arrives as a separate batch with its own `batch_no` and `expiry_date`. The `inventory_transactions` table correctly models this — each `stock_in` row has its own `batch_no` and `expiry_date`. The `batch_number` column on `medicines` is a leftover from the original naive design and should be removed.

### 4. No FIFO ordering on dispense

Even if `inventory_transactions` were used for dispensing, there is currently no logic to consume the oldest batch first. FIFO requires consuming `stock_in` transactions ordered by `created_at ASC` (or `expiry_date ASC` for pharmacy, which is actually FEFO — First Expired, First Out — the correct standard for medicines).

---

## What FIFO/FEFO Means for This System

When a prescription is dispensed:

1. Find all `stock_in` transactions for the medicine that still have remaining quantity (i.e. not fully consumed by previous dispenses).
2. Order them by `expiry_date ASC` (soonest expiry first — FEFO), with `created_at ASC` as a tiebreaker.
3. Consume quantity from the first batch. If that batch doesn't have enough, move to the next batch, and so on, until the full dispensed quantity is fulfilled.
4. For each batch consumed, write a `stock_out` transaction to `inventory_transactions` referencing the batch.

This requires knowing how much of each `stock_in` transaction has already been consumed. The cleanest way is to track `remaining_quantity` on each `stock_in` transaction row, updated as stock is consumed.

---

## Required Changes

### ⚠️ Constraint: Do not touch any frontend views. All changes are backend only.

---

### 1. Migration — Add `remaining_quantity` to `inventory_transactions`

Create a new migration:

```
database/migrations/tenant/YYYY_MM_DD_000001_add_remaining_quantity_to_inventory_transactions.php
```

```php
Schema::table('inventory_transactions', function (Blueprint $table) {
    $table->integer('remaining_quantity')->nullable()->after('quantity');
});
```

On `up()`, also backfill existing `stock_in` rows:

```php
DB::table('inventory_transactions')
    ->where('type', 'stock_in')
    ->update(['remaining_quantity' => DB::raw('quantity')]);
```

`stock_out` rows leave `remaining_quantity` as null — it only applies to `stock_in`.

Add proper validation: `remaining_quantity` must never go below 0 and must never exceed the original `quantity` of that transaction.

---

### 2. Migration — Remove `batch_number` and `stock_quantity` from `medicines`

Create a new migration:

```
database/migrations/tenant/YYYY_MM_DD_000002_remove_legacy_columns_from_medicines.php
```

Remove:
- `batch_number` — replaced by `batch_no` on `inventory_transactions`
- `stock_quantity` — replaced by computed stock via `getCurrentStock()` on the `Medicine` model
- `expiry_date` — also a per-batch concern, already on `inventory_transactions`
- `unit_price` — if it exists as a static column, it should come from the latest `stock_in` unit_cost instead

Add proper validation: before dropping `stock_quantity`, verify that `getCurrentStock()` returns consistent values for all medicines. If there is a discrepancy, log it and halt the migration.

---

### 3. `Medicine` model — Remove references to dropped columns

Remove `stock_quantity`, `batch_number`, `expiry_date`, `unit_price` from `$fillable` if present.

The `getCurrentStock()` method already correctly computes stock from `inventory_transactions` — keep it as-is. It becomes the single source of truth.

Add a new method `getAvailableBatches()`:

```php
public function getAvailableBatches()
{
    return $this->inventoryTransactions()
        ->where('type', 'stock_in')
        ->where('remaining_quantity', '>', 0)
        ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
        ->orderBy('expiry_date', 'asc')
        ->orderBy('created_at', 'asc')
        ->get();
}
```

Add a method `getTotalAvailableStock()`:

```php
public function getTotalAvailableStock(): int
{
    return $this->inventoryTransactions()
        ->where('type', 'stock_in')
        ->sum('remaining_quantity') ?? 0;
}
```

Add proper validation: `isLowStock()` should use `getTotalAvailableStock()` instead of `getCurrentStock()` once `remaining_quantity` is in place.

---

### 4. `InventoryController::processStockIn()` — Set `remaining_quantity`

When creating a `stock_in` transaction, set `remaining_quantity` equal to the converted base quantity:

```php
InventoryTransaction::create([
    // ... existing fields ...
    'remaining_quantity' => $baseQuantity,
]);
```

Add proper validation: reject stock-in if `expiry_date` is in the past. Warn (but allow) if `expiry_date` is within 30 days.

---

### 5. `PrescriptionController::dispense()` — Implement FIFO consumption

This is the core change. Replace the current flat decrement with a FIFO loop wrapped in a database transaction.

```php
public function dispense(Prescription $prescription)
{
    // Pre-flight: check all items have sufficient stock before touching anything
    foreach ($prescription->items as $item) {
        $available = $item->medicine->getTotalAvailableStock();
        if ($available < $item->quantity) {
            return back()->with('error',
                "Insufficient stock for {$item->medicine->name}. " .
                "Available: {$available}, Required: {$item->quantity}."
            );
        }
    }

    DB::transaction(function () use ($prescription) {
        foreach ($prescription->items as $item) {
            $remaining = $item->quantity;
            $batches = $item->medicine->getAvailableBatches();

            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                $consume = min($batch->remaining_quantity, $remaining);

                // Deduct from this batch
                $batch->decrement('remaining_quantity', $consume);

                // Record a stock_out transaction for traceability
                InventoryTransaction::create([
                    'medicine_id'  => $item->medicine_id,
                    'type'         => 'stock_out',
                    'quantity'     => $consume,
                    'unit_cost'    => $batch->unit_cost,
                    'total_cost'   => $consume * $batch->unit_cost,
                    'batch_no'     => $batch->batch_no,
                    'reference_no' => $prescription->prescription_no,
                    'notes'        => 'Dispensed via prescription ' . $prescription->prescription_no,
                    'created_by'   => auth()->id(),
                ]);

                $remaining -= $consume;
            }

            // This should never happen due to pre-flight check, but guard anyway
            if ($remaining > 0) {
                throw new \RuntimeException(
                    "Stock exhausted mid-dispense for {$item->medicine->name}. Transaction rolled back."
                );
            }
        }

        $prescription->update([
            'status'         => 'dispensed',
            'dispensed_date' => now(),
        ]);
    });

    return back()->with('success', 'Prescription dispensed successfully.');
}
```

Add proper validation:
- Wrap everything in `DB::transaction()` so a partial failure rolls back all batch deductions.
- Pre-flight stock check must run before the transaction opens — fail fast with a user-friendly message.
- Catch `\Throwable` around the transaction and log the error before returning a generic error response to the user.
- A prescription that is already `dispensed` must not be dispensed again — check `$prescription->status` at the top of the method.

---

### 6. `InventoryController::processStockOut()` — Respect FIFO on manual stock-out

Manual stock-out (expired, damaged, adjustment) should also consume from the oldest batches first, using the same FIFO loop. The `StockOutRequest` does not need to change — the batch selection happens automatically in the backend.

Add proper validation: if `reason = 'expired'`, only allow consuming from batches whose `expiry_date` is in the past or within the next 7 days. Reject if the user tries to mark non-expired stock as expired.

---

### 7. `StockInRequest` — Strengthen validation

```php
'batch_no'    => 'required|string|max:100',   // make required, not nullable
'expiry_date' => 'required|date|after:today',  // make required for medicines
```

Batch number and expiry date should be required on every stock-in. A medicine without a batch number or expiry date cannot be properly tracked.

---

## Files to Change

| File | Change |
|------|--------|
| `app/Models/Medicine.php` | Remove legacy fillable columns; add `getAvailableBatches()`, `getTotalAvailableStock()` |
| `app/Models/InventoryTransaction.php` | Add `remaining_quantity` to `$fillable` and `$casts` |
| `app/Http/Controllers/PrescriptionController.php` | Replace flat decrement with FIFO transaction loop |
| `app/Http/Controllers/InventoryController.php` | Set `remaining_quantity` on stock-in; apply FIFO on manual stock-out |
| `app/Http/Requests/StockInRequest.php` | Make `batch_no` and `expiry_date` required |
| New migration: `add_remaining_quantity_to_inventory_transactions` | Add column, backfill existing rows |
| New migration: `remove_legacy_columns_from_medicines` | Drop `batch_number`, `stock_quantity`, `expiry_date` |

## Files NOT to Change

- All blade views under `resources/views/admin/inventory/`
- All blade views under `resources/views/admin/prescriptions/`
- All routes in `routes/web.php`
- Any frontend JS

---

## Error Handling Requirements

- All dispense and stock operations must be wrapped in `DB::transaction()`.
- Pre-flight stock checks must happen before the transaction opens.
- Catch `\Throwable` at the controller level, log with `Log::error()`, and return a user-friendly error message — never expose raw exception messages to the UI.
- If `remaining_quantity` would go below 0 at any point, throw an exception inside the transaction to trigger a rollback.
- Log every FIFO consumption with medicine name, batch number, quantity consumed, and prescription reference for audit purposes.

---

## Validation Requirements

- `batch_no` must be required on every `stock_in` — no anonymous batches.
- `expiry_date` must be required on every `stock_in` and must be a future date.
- Dispensing a prescription that is already `dispensed` or `cancelled` must be rejected with a 422.
- Manual stock-out quantity must not exceed total available stock across all batches.
- `remaining_quantity` must be validated to never exceed the original `quantity` of its `stock_in` row.
- Stock-in for a medicine with `manage_stock = false` must be rejected.

---

## Notes

- The `PrescriptionController::create()` method references `stock_quantity` directly in its query (`->where('stock_quantity', '>', 0)`). After removing that column, replace this with a filter using `getTotalAvailableStock() > 0` or a subquery on `inventory_transactions`.
- The `PrescriptionController::store()` references `$medicine->unit_price` for pricing. Once `unit_price` is removed from `medicines`, pricing should come from the most recent `stock_in` unit_cost for that medicine, or from a separate `selling_price` column if the clinic sets a fixed selling price independent of purchase cost.
- FEFO (First Expired, First Out) is the correct standard for pharmacy — ordering by `expiry_date ASC` is preferred over `created_at ASC`. Batches with no expiry date should be consumed last.
