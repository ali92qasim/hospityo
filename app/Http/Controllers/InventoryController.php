<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockOutRequest;
use App\Models\Medicine;
use App\Models\InventoryTransaction;
use App\Models\Unit;
use App\Services\MedicineStockConversion;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['medicine.baseUnit', 'user']);

        if ($request->type) {
            $query->where('type', '=', $request->type);
        }

        if ($request->medicine_id) {
            $query->where('medicine_id', '=', $request->medicine_id);
        }

        $transactions = $query->latest()->paginate(15)->withQueryString();
        $medicines = Medicine::where('status', '=', 'active')->get();

        return view('admin.inventory.index', compact('transactions', 'medicines'));
    }

    public function stockIn()
    {
        $medicines = Medicine::where('status', 'active')
            ->where('manage_stock', true)
            ->with(['baseUnit', 'purchaseUnit'])
            ->get();
        $suppliers = \App\Models\Supplier::where('status', 'active')->get();
        $units = \App\Models\Unit::active()->get();
        return view('admin.inventory.stock-in', compact('medicines', 'suppliers', 'units'));
    }

    public function processStockIn(StockInRequest $request)
    {
        $validated = $request->validated();
        $mode = $validated['stock_in_mode'] ?? 'new';

        $medicine = Medicine::findOrFail($validated['medicine_id']);

        if (!$medicine->manage_stock) {
            return back()->withErrors(['medicine_id' => 'Stock management is not enabled for this medicine.']);
        }

        $unit = Unit::findOrFail($validated['unit_id']);

        if ($mode === 'existing') {
            $batch = InventoryTransaction::findOrFail($validated['existing_batch_id']);
            $purchaseUnitCost = (float) $batch->unit_cost * (float) $unit->conversion_factor;

            $converted = MedicineStockConversion::toBaseUnits(
                $unit,
                (int) $validated['quantity'],
                $purchaseUnitCost
            );

            $batchNo = $batch->batch_no;
            $expiryDate = $batch->expiry_date;
        } else {
            $converted = MedicineStockConversion::toBaseUnits(
                $unit,
                (int) $validated['quantity'],
                (float) $validated['unit_cost']
            );

            $batchNo = $validated['batch_no'];
            $expiryDate = $validated['expiry_date'];
        }

        try {
            InventoryTransaction::create([
                'medicine_id'        => $validated['medicine_id'],
                'type'               => 'stock_in',
                'quantity'           => $converted['base_quantity'],
                'remaining_quantity' => $converted['base_quantity'],
                'unit_cost'          => $converted['base_unit_cost'],
                'total_cost'         => $converted['total_cost'],
                'supplier'           => $validated['supplier'],
                'batch_no'           => $batchNo,
                'expiry_date'        => $expiryDate,
                'reference_no'       => $validated['reference_no'] ?? null,
                'notes'              => $validated['notes'] ?? null,
                'created_by'         => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('[Inventory] Stock-in failed', [
                'medicine_id' => $validated['medicine_id'],
                'error'       => $e->getMessage(),
            ]);
            return back()->withInput()->with('error', 'Failed to record stock. Please try again.');
        }

        return redirect()->route('inventory.index')
            ->with('success', 'Stock added successfully.');
    }

    public function stockOut()
    {
        $medicines = Medicine::where('status', 'active')
            ->where('manage_stock', true)
            ->with('baseUnit')
            ->get()
            ->filter(function($medicine) {
                return $medicine->getCurrentStock() > 0;
            });
            
        return view('admin.inventory.stock-out', compact('medicines'));
    }

    public function processStockOut(StockOutRequest $request)
    {
        $validated = $request->validated();

        $medicine = Medicine::findOrFail($validated['medicine_id']);

        if (!$medicine->manage_stock) {
            return back()->withErrors(['medicine_id' => 'Stock management is not enabled for this medicine.']);
        }

        $available = $medicine->getTotalAvailableStock();

        if ($available < $validated['quantity']) {
            return back()->withErrors([
                'quantity' => "Insufficient stock. Available: {$available}, requested: {$validated['quantity']}.",
            ]);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $medicine) {
                $remaining = $validated['quantity'];
                $batches   = $medicine->getAvailableBatches();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;

                    $consume = min($batch->remaining_quantity, $remaining);

                    $batch->decrement('remaining_quantity', $consume);

                    InventoryTransaction::create([
                        'medicine_id'  => $medicine->id,
                        'type'         => 'stock_out',
                        'quantity'     => $consume,
                        'unit_cost'    => $batch->unit_cost,
                        'total_cost'   => $consume * $batch->unit_cost,
                        'batch_no'     => $batch->batch_no,
                        'supplier'     => $validated['reason'],
                        'reference_no' => $validated['reference_no'] ?? null,
                        'notes'        => $validated['notes'] ?? null,
                        'created_by'   => auth()->id(),
                    ]);

                    $remaining -= $consume;
                }

                if ($remaining > 0) {
                    throw new \RuntimeException(
                        "Stock exhausted mid-operation for {$medicine->name}. Transaction rolled back."
                    );
                }
            });
        } catch (\Throwable $e) {
            \Log::error('[Inventory] Stock-out failed', [
                'medicine_id' => $medicine->id,
                'error'       => $e->getMessage(),
            ]);
            return back()->withInput()->with('error', 'Failed to remove stock. Please try again.');
        }

        return redirect()->route('inventory.index')
            ->with('success', 'Stock removed successfully.');
    }

    public function lowStock()
    {
        $lowStockMedicines = Medicine::where('status', 'active')
            ->where('manage_stock', true)
            ->with(['baseUnit', 'dispensingUnit'])
            ->get()
            ->filter(function($medicine) {
                return $medicine->isLowStock();
            });

        return view('admin.inventory.low-stock', compact('lowStockMedicines'));
    }

    public function expiring()
    {
        try {
            $expiringStock = InventoryTransaction::nearExpiry(6)->get();
        } catch (\Throwable $e) {
            \Log::error('[Inventory] Failed to load near-expiry stock', ['error' => $e->getMessage()]);
            $expiringStock = collect();
        }

        return view('admin.inventory.expiring', compact('expiringStock'));
    }
}