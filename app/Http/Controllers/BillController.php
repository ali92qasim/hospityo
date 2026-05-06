<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Http\Requests\AddBillPaymentRequest;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

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
        $investigations = \App\Models\Investigation::active()->orderBy('category')->orderBy('name')->get();
        $visits = Visit::with('patient')->latest()->take(50)->get();
        return view('admin.bills.create', compact('patients', 'services', 'investigations', 'visits'));
    }

    public function store(StoreBillRequest $request)
    {
        try {
            $bill = DB::connection('tenant')->transaction(function () use ($request) {
                $bill = Bill::create([
                    'patient_id'      => $request->patient_id,
                    'visit_id'        => $request->visit_id,
                    'bill_number'     => 'BILL-' . date('Y') . '-' . str_pad(Bill::count() + 1, 6, '0', STR_PAD_LEFT),
                    'bill_date'       => $request->bill_date,
                    'bill_type'       => $request->bill_type,
                    'tax_amount'      => $request->tax_amount ?? 0,
                    'discount_amount' => $request->discount_amount ?? 0,
                    'total_amount'    => 0,
                    'due_amount'      => 0,
                    'notes'           => $request->notes,
                    'created_by'      => auth()->id(),
                ]);

                foreach ($request->items as $item) {
                    $bill->billItems()->create([
                        'service_id'       => $item['service_id'] ?? null,
                        'investigation_id' => $item['investigation_id'] ?? null,
                        'description'      => $item['description'],
                        'quantity'         => $item['quantity'],
                        'unit_price'       => $item['unit_price'],
                    ]);
                }

                $bill->calculateTotals();

                // Accounting entry — inside the transaction so a failure rolls
                // back the bill and items together, leaving no orphaned records
                \App\Services\AccountingService::postBillEntry($bill);

                return $bill;
            });

            // Doctor share runs OUTSIDE the billing transaction intentionally.
            // calculate() has its own internal try/catch — a missing share rule
            // must never roll back a completed bill. The share can be recalculated
            // manually if needed. The bill and accounting entry are already committed.
            \App\Services\DoctorShareService::calculate($bill);

            return redirect()->route('bills.show', $bill)->with('success', 'Bill created successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[BillController] store() failed', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to create bill. Please try again.']);
        }
    }

    public function show(Bill $bill)
    {
        $bill->load(['patient', 'visit', 'billItems.service', 'billItems.investigation', 'payments.receivedBy']);
        return view('admin.bills.show', compact('bill'));
    }

    public function edit(Bill $bill)
    {
        $patients = Patient::all();
        $services = Service::active()->get();
        $investigations = \App\Models\Investigation::active()->orderBy('category')->orderBy('name')->get();
        $visits = Visit::with('patient')->latest()->take(50)->get();
        $bill->load('billItems');
        return view('admin.bills.edit', compact('bill', 'patients', 'services', 'investigations', 'visits'));
    }

    public function update(UpdateBillRequest $request, Bill $bill)
    {
        // Void existing share items BEFORE bill_items are deleted.
        // If settled items exist, this throws and the update is aborted entirely —
        // no bill fields are changed, no items are deleted.
        try {
            \App\Services\DoctorShareService::voidForBill($bill, 'bill_updated');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        // All bill writes in a single transaction — no partial update state
        DB::connection('tenant')->transaction(function () use ($request, $bill) {
            $bill->update([
                'patient_id'      => $request->patient_id,
                'visit_id'        => $request->visit_id,
                'bill_date'       => $request->bill_date,
                'bill_type'       => $request->bill_type,
                'tax_amount'      => $request->tax_amount ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'notes'           => $request->notes,
            ]);

            $bill->billItems()->delete();

            foreach ($request->items as $item) {
                $bill->billItems()->create([
                    'service_id'       => $item['service_id'] ?? null,
                    'investigation_id' => $item['investigation_id'] ?? null,
                    'description'      => $item['description'],
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $item['unit_price'],
                ]);
            }

            $bill->calculateTotals();
        });

        // Share recalculation runs after the transaction commits — same reasoning
        // as store(): a share failure must not roll back a completed bill update
        \App\Services\DoctorShareService::calculate($bill);

        return redirect()->route('bills.show', $bill)->with('success', 'Bill updated successfully');
    }

    public function destroy(Bill $bill)
    {
        // Void share items before deleting the bill.
        // If settled items exist, the delete is blocked and the user is informed.
        try {
            \App\Services\DoctorShareService::voidForBill($bill, 'bill_cancelled');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }

        $bill->delete();
        return redirect()->route('bills.index')->with('success', 'Bill deleted successfully');
    }

    public function addPayment(AddBillPaymentRequest $request, Bill $bill)
    {
        $payment = $bill->payments()->create([
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

        // Auto-post accounting journal entry
        \App\Services\AccountingService::postPaymentEntry($payment);

        // Append immutable allocation events to the share ledger
        \App\Services\DoctorShareService::recordPaymentAllocations($payment);

        return redirect()->route('bills.show', $bill)->with('success', 'Payment added successfully');
    }

    public function print(Bill $bill)
    {
        $bill->load(['patient', 'visit', 'billItems.service', 'payments.receivedBy', 'createdBy']);
        $settings = [
            'hospital_name'    => setting('hospital_name', config('app.name', 'Hospital Management System')),
            'hospital_address' => setting('hospital_address', ''),
            'hospital_phone'   => setting('hospital_phone', ''),
            'hospital_email'   => setting('hospital_email', ''),
            'hospital_logo'    => setting('hospital_logo', ''),
        ];
        return view('admin.bills.print', compact('bill', 'settings'));
    }
}