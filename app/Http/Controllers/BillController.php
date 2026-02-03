<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Http\Requests\AddBillPaymentRequest;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Visit;

class BillController extends Controller
{
    public function index()
    {
        $bills = Bill::with(['patient', 'visit'])->latest()->paginate(10);
        return view('admin.bills.index', compact('bills'));
    }

    public function create()
    {
        $patients = Patient::all();
        $services = Service::active()->get();
        $visits = Visit::with('patient')->latest()->take(50)->get();
        return view('admin.bills.create', compact('patients', 'services', 'visits'));
    }

    public function store(StoreBillRequest $request)
    {
        try {
            $bill = Bill::create([
                'patient_id' => $request->patient_id,
                'visit_id' => $request->visit_id,
                'bill_number' => 'BILL-' . date('Y') . '-' . str_pad(Bill::count() + 1, 6, '0', STR_PAD_LEFT),
                'bill_date' => $request->bill_date,
                'bill_type' => $request->bill_type,
                'tax_amount' => $request->tax_amount ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'total_amount' => 0,
                'due_amount' => 0,
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            foreach ($request->items as $item) {
                $bill->billItems()->create([
                    'service_id' => $item['service_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price']
                ]);
            }

            $bill->calculateTotals();

            return redirect()->route('bills.show', $bill)->with('success', 'Bill created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to create bill. Please try again.']);
        }
    }

    public function show(Bill $bill)
    {
        $bill->load(['patient', 'visit', 'billItems.service', 'payments.receivedBy']);
        return view('admin.bills.show', compact('bill'));
    }

    public function edit(Bill $bill)
    {
        $patients = Patient::all();
        $services = Service::active()->get();
        $visits = Visit::with('patient')->latest()->take(50)->get();
        $bill->load('billItems');
        return view('admin.bills.edit', compact('bill', 'patients', 'services', 'visits'));
    }

    public function update(UpdateBillRequest $request, Bill $bill)
    {
        $bill->update([
            'patient_id' => $request->patient_id,
            'visit_id' => $request->visit_id,
            'bill_date' => $request->bill_date,
            'bill_type' => $request->bill_type,
            'tax_amount' => $request->tax_amount ?? 0,
            'discount_amount' => $request->discount_amount ?? 0,
            'notes' => $request->notes
        ]);

        $bill->billItems()->delete();

        foreach ($request->items as $item) {
            $bill->billItems()->create([
                'service_id' => $item['service_id'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price']
            ]);
        }

        $bill->calculateTotals();

        return redirect()->route('bills.show', $bill)->with('success', 'Bill updated successfully');
    }

    public function destroy(Bill $bill)
    {
        $bill->delete();
        return redirect()->route('bills.index')->with('success', 'Bill deleted successfully');
    }

    public function addPayment(AddBillPaymentRequest $request, Bill $bill)
    {
        $bill->payments()->create([
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'received_by' => auth()->id()
        ]);

        $bill->paid_amount += $request->amount;
        $bill->due_amount = $bill->total_amount - $bill->paid_amount;
        $bill->status = $bill->due_amount <= 0 ? 'paid' : 'partial';
        $bill->save();

        return redirect()->route('bills.show', $bill)->with('success', 'Payment added successfully');
    }
}