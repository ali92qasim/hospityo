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
        
        // Check if stock management is enabled for this medicine
        if (!$medicine->manage_stock) {
            return back()->withErrors(['medicine_id' => 'Stock management is not enabled for this medicine.']);
        }
        
        $unit = Unit::findOrFail($validated['unit_id']);
        
        // Convert to base unit for storage
        $baseQuantity = $unit->convertToBaseUnit($validated['quantity']);
        $totalCost = $validated['quantity'] * $validated['unit_cost'];

        InventoryTransaction::create([
            'medicine_id' => $validated['medicine_id'],
            'type' => 'stock_in',
            'quantity' => $baseQuantity,
            'unit_cost' => $validated['unit_cost'] / $unit->conversion_factor,
            'total_cost' => $totalCost,
            'supplier' => $validated['supplier'],
            'batch_no' => $validated['batch_no'],
            'expiry_date' => $validated['expiry_date'],
            'reference_no' => $validated['reference_no'],
            'notes' => $validated['notes'],
            'created_by' => auth()->id()
        ]);

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
        
        // Check if stock management is enabled for this medicine
        if (!$medicine->manage_stock) {
            return back()->withErrors(['medicine_id' => 'Stock management is not enabled for this medicine.']);
        }
        
        $currentStock = $medicine->getCurrentStock();

        if ($currentStock < $validated['quantity']) {
            return back()->withErrors(['quantity' => 'Insufficient stock available.']);
        }

        InventoryTransaction::create([
            'medicine_id' => $validated['medicine_id'],
            'type' => 'stock_out',
            'quantity' => $validated['quantity'],
            'unit_cost' => 0,
            'total_cost' => 0,
            'supplier' => $validated['reason'],
            'reference_no' => $validated['reference_no'],
            'notes' => $validated['notes'],
            'created_by' => auth()->id()
        ]);

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
        $expiringStock = InventoryTransaction::with(['medicine'])
            ->where('type', 'stock_in')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addMonths(3))
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date')
            ->get();

        return view('admin.inventory.expiring', compact('expiringStock'));
    }
}