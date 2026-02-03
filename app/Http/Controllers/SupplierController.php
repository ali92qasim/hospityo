<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->latest()->paginate(15)->withQueryString();
        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('admin.suppliers.create');
    }

    public function store(StoreSupplierRequest $request)
    {
        Supplier::create($request->validated());

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier)
    {
        $recentTransactions = InventoryTransaction::with(['medicine'])
            ->where('supplier', $supplier->name)
            ->latest()
            ->take(10)
            ->get();

        $totalTransactions = InventoryTransaction::where('supplier', $supplier->name)->count();
        $totalValue = InventoryTransaction::where('supplier', $supplier->name)
            ->where('type', 'stock_in')
            ->sum('total_cost');

        return view('admin.suppliers.show', compact('supplier', 'recentTransactions', 'totalTransactions', 'totalValue'));
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $hasTransactions = InventoryTransaction::where('supplier', $supplier->name)->exists();
        
        if ($hasTransactions) {
            return back()->withErrors(['error' => 'Cannot delete supplier with existing transactions.']);
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}