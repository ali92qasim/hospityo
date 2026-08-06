<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Medicine;
use App\Models\InventoryTransaction;
use App\Models\Unit;
use App\Services\MedicineStockConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'user']);

        if ($request->status) {
            $query->byStatus($request->status);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();
        $suppliers = Supplier::active()->get();

        return view('admin.purchases.index', compact('orders', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->get();
        $medicines = Medicine::where('status', 'active')->get();
        return view('admin.purchases.create', compact('suppliers', 'medicines'));
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $subtotal = 0;
            
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery' => $validated['expected_delivery'],
                'status' => 'pending',
                'notes' => $validated['notes'],
                'created_by' => auth()->id()
            ]);

            foreach ($validated['items'] as $item) {
                $totalPrice = $item['quantity'] * $item['unit_price'];
                $subtotal += $totalPrice;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'medicine_id' => $item['medicine_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice
                ]);
            }

            $taxAmount = $subtotal * 0.17; // 17% tax
            $totalAmount = $subtotal + $taxAmount;

            $purchaseOrder->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount
            ]);
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchase)
    {
        $purchase->load(['supplier', 'items.medicine', 'user']);
        return view('admin.purchases.show', compact('purchase'));
    }

    public function receive(PurchaseOrder $purchase)
    {
        if ($purchase->status !== 'approved') {
            return back()->withErrors(['error' => 'Only approved orders can be received.']);
        }

        try {
            DB::transaction(function () use ($purchase) {
                $purchase->load(['items', 'supplier']);

                foreach ($purchase->items as $item) {
                    if (!$item->unit_id) {
                        throw new \InvalidArgumentException('Cannot receive order: line items missing unit.');
                    }

                    $unit = Unit::findOrFail($item->unit_id);
                    $converted = MedicineStockConversion::toBaseUnits(
                        $unit,
                        (int) $item->quantity,
                        (float) $item->unit_price
                    );

                    InventoryTransaction::create([
                        'medicine_id'        => $item->medicine_id,
                        'type'               => 'stock_in',
                        'quantity'           => $converted['base_quantity'],
                        'remaining_quantity' => $converted['base_quantity'],
                        'unit_cost'          => $converted['base_unit_cost'],
                        'total_cost'         => $converted['total_cost'],
                        'supplier'           => $purchase->supplier->name,
                        'reference_no'       => $purchase->po_number,
                        'notes'              => 'Purchase order received',
                        'created_by'         => auth()->id(),
                    ]);
                }

                $purchase->update(['status' => 'received']);

                \App\Services\AccountingService::postPurchaseEntry($purchase);
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Purchase order received and stock updated.');
    }

    public function approve(PurchaseOrder $purchase)
    {
        if ($purchase->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending orders can be approved.']);
        }

        $purchase->update(['status' => 'approved']);

        return back()->with('success', 'Purchase order approved.');
    }

    public function cancel(PurchaseOrder $purchase)
    {
        if ($purchase->status === 'received') {
            return back()->withErrors(['error' => 'Cannot cancel received orders.']);
        }

        $purchase->update(['status' => 'cancelled']);

        return back()->with('success', 'Purchase order cancelled.');
    }
}