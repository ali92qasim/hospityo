<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockOutRequest;
use App\Models\Medicine;
use App\Models\InventoryTransaction;
use App\Models\Unit;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['medicine', 'user']);

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

        $medicine = Medicine::findOrFail($validated['medicine_id']);

        if (!$medicine->manage_stock) {
            return back()->withErrors(['medicine_id' => 'Stock management is not enabled for this medicine.']);
        }

        $unit = Unit::findOrFail($validated['unit_id']);

        // Convert to base unit for storage
        $baseQuantity = $unit->convertToBaseUnit($validated['quantity']);
        $baseUnitCost = $validated['unit_cost'] / $unit->conversion_factor;
        $totalCost    = $validated['quantity'] * $validated['unit_cost'];

        try {
            InventoryTransaction::create([
                'medicine_id'        => $validated['medicine_id'],
                'type'               => 'stock_in',
                'quantity'           => $baseQuantity,
                'remaining_quantity' => $baseQuantity, // FIFO: starts fully available
                'unit_cost'          => $baseUnitCost,
                'total_cost'         => $totalCost,
                'supplier'           => $validated['supplier'],
                'batch_no'           => $validated['batch_no'] ?? null,
                'expiry_date'        => $validated['expiry_date'] ?? null,
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