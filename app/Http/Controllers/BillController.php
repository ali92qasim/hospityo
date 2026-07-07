<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Http\Requests\AddBillPaymentRequest;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Visit;
use App\Services\BillItemCategoryResolver;
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
                $discountType       = $request->discount_type ?? 'fixed';
                $discountPercentage = $request->discount_percentage ?? 0;

                // Compute subtotal from submitted items so we can derive the
                // monetary discount server-side (prevents client-side manipulation).
                $subtotal = collect($request->items)->sum(
                    fn($item) => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0)
                );

                $discountAmount = $discountType === 'percentage'
                    ? round(($discountPercentage / 100) * $subtotal, 2)
                    : ($request->discount_amount ?? 0);

                // Auto-resolve visit from the patient's most recent visit matching bill type.
                // This provides the doctor link for doctor share calculation.
                $visitId = $request->visit_id;
                if (!$visitId) {
                    $visitQuery = Visit::where('patient_id', $request->patient_id);

                    // bill_type maps directly to visit_type for opd/ipd/emergency
                    if (in_array($request->bill_type, ['opd', 'ipd', 'emergency'])) {
                        $visitQuery->where('visit_type', $request->bill_type);
                    }

                    $visitId = $visitQuery->latest('visit_datetime')->value('id');
                }

                $bill = Bill::create([
                    'patient_id'          => $request->patient_id,
                    'visit_id'            => $visitId,
                    'bill_number'         => $this->generateBillNumber(),
                    'bill_date'           => $request->bill_date,
                    'bill_type'           => $request->bill_type,
                    'tax_amount'          => $request->tax_amount ?? 0,
                    'discount_type'       => $discountType,
                    'discount_percentage' => $discountPercentage,
                    'discount_amount'     => $discountAmount,
                    'total_amount'        => 0,
                    'due_amount'          => 0,
                    'notes'               => $request->notes,
                    'created_by'          => auth()->id(),
                ]);

                foreach ($request->items as $item) {
                    $bill->billItems()->create(
                        $this->buildBillItemAttributes($item, $request->bill_type)
                    );
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

            if ($request->has('save_and_add_another')) {
                return redirect()->route('bills.create')->with('success', 'Bill created successfully.');
            }

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

        // All bill writes + accounting reconciliation in a single transaction
        DB::connection('tenant')->transaction(function () use ($request, $bill) {
            $discountType       = $request->discount_type ?? 'fixed';
            $discountPercentage = $request->discount_percentage ?? 0;

            // Recompute monetary discount server-side when type is percentage.
            $subtotal = collect($request->items)->sum(
                fn($item) => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0)
            );

            $discountAmount = $discountType === 'percentage'
                ? round(($discountPercentage / 100) * $subtotal, 2)
                : ($request->discount_amount ?? 0);

            $bill->update([
                'patient_id'          => $request->patient_id,
                'visit_id'            => $request->visit_id,
                'bill_date'           => $request->bill_date,
                'bill_type'           => $request->bill_type,
                'tax_amount'          => $request->tax_amount ?? 0,
                'discount_type'       => $discountType,
                'discount_percentage' => $discountPercentage,
                'discount_amount'     => $discountAmount,
                'notes'               => $request->notes,
            ]);

            $bill->billItems()->delete();

            foreach ($request->items as $item) {
                $bill->billItems()->create(
                    $this->buildBillItemAttributes($item, $request->bill_type)
                );
            }

            $bill->calculateTotals();

            // --- Accounting Reconciliation: Reverse & Repost ---
            // Reverse old bill journal entry and post a fresh one with updated amounts.
            // This keeps full audit trail — old entries are never modified/deleted.
            \App\Services\AccountingService::reverseAndRepostBillEntry($bill, 'bill_updated');

            // --- Overpayment Detection & Handling ---
            // If paid > new total after discount, post an adjustment entry to move
            // the excess to "Advance from Patients" (liability account 2300).
            $overpayment = round((float) $bill->paid_amount - (float) $bill->total_amount, 2);

            if ($overpayment > 0) {
                // Cap due_amount at 0 (no negative due)
                $bill->due_amount = 0;
                $bill->status = 'paid';
                $bill->save();

                \App\Services\AccountingService::postOverpaymentAdjustment($bill, $overpayment);
            } else {
                // Re-evaluate bill status after total change
                $bill->status = $this->evaluateBillStatus($bill);
                $bill->save();
            }
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

        // Reverse all journal entries for this bill before deletion.
        // If any reversal fails, abort the deletion to prevent orphaned entries.
        $billEntries = \App\Models\JournalEntry::where('reference_type', 'Bill')
            ->where('reference_id', $bill->id)
            ->where('entry_type', 'original')
            ->with(['lines', 'subLedgerEntries'])
            ->get();

        foreach ($billEntries as $entry) {
            $reversal = \App\Services\AccountingService::reverseEntry($entry, 'bill_deleted');
            if (!$reversal) {
                return redirect()->back()->withErrors([
                    'error' => "Failed to reverse journal entry #{$entry->entry_number}. Bill was not deleted to protect financial integrity.",
                ]);
            }
        }

        // Reverse payment journal entries linked to this bill's payments
        foreach ($bill->payments as $payment) {
            $paymentEntries = \App\Models\JournalEntry::where('reference_type', 'Payment')
                ->where('reference_id', $payment->id)
                ->where('entry_type', 'original')
                ->with(['lines', 'subLedgerEntries'])
                ->get();

            foreach ($paymentEntries as $entry) {
                $reversal = \App\Services\AccountingService::reverseEntry($entry, 'bill_deleted');
                if (!$reversal) {
                    return redirect()->back()->withErrors([
                        'error' => "Failed to reverse payment journal entry #{$entry->entry_number}. Bill was not deleted to protect financial integrity.",
                    ]);
                }
            }
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
        $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
        $bill->status = $bill->paid_amount >= $bill->total_amount ? 'paid' : 'partial';
        $bill->save();

        // Auto-post accounting journal entry
        \App\Services\AccountingService::postPaymentEntry($payment);

        // Handle overpayment — if paid exceeds total, post adjustment to Patient Advance (2300)
        $overpayment = round((float) $bill->paid_amount - (float) $bill->total_amount, 2);
        if ($overpayment > 0) {
            \App\Services\AccountingService::postOverpaymentAdjustment($bill, $overpayment);
        }

        // Append immutable allocation events to the share ledger
        \App\Services\DoctorShareService::recordPaymentAllocations($payment);

        return redirect()->route('bills.show', $bill)->with('success', 'Payment added successfully');
    }

    public function removePayment(Bill $bill, Payment $payment)
    {
        if ($response = $this->ensurePaymentBelongsToBill($bill, $payment)) {
            return $response;
        }

        // Reverse the payment's journal entry
        $paymentEntries = \App\Models\JournalEntry::where('reference_type', 'Payment')
            ->where('reference_id', $payment->id)
            ->where('entry_type', 'original')
            ->with(['lines', 'subLedgerEntries'])
            ->get();

        foreach ($paymentEntries as $entry) {
            $reversal = \App\Services\AccountingService::reverseEntry($entry, 'payment_deleted');
            if (!$reversal) {
                return redirect()->back()->withErrors([
                    'error' => 'Failed to reverse payment journal entry. Payment was not deleted.',
                ]);
            }
        }

        // Update bill amounts
        $bill->paid_amount -= $payment->amount;
        $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
        $bill->status = $this->evaluateBillStatus($bill);
        $bill->save();

        // Delete the payment record
        $payment->delete();

        return redirect()->route('bills.show', $bill)->with('success', 'Payment removed and journal entry reversed.');
    }

    public function updatePayment(\Illuminate\Http\Request $request, Bill $bill, Payment $payment)
    {
        if ($response = $this->ensurePaymentBelongsToBill($bill, $payment)) {
            return $response;
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,upi,bank_transfer,cheque,insurance',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldAmount = (float) $payment->amount;
        $newAmount = (float) $request->amount;

        DB::connection('tenant')->transaction(function () use ($request, $bill, $payment, $oldAmount, $newAmount) {
            // 1. Reverse the old payment journal entry
            $paymentEntries = \App\Models\JournalEntry::where('reference_type', 'Payment')
                ->where('reference_id', $payment->id)
                ->where('entry_type', 'original')
                ->with(['lines', 'subLedgerEntries'])
                ->get();

            foreach ($paymentEntries as $entry) {
                $reversal = \App\Services\AccountingService::reverseEntry($entry, 'payment_edited');
                if (!$reversal) {
                    throw new \RuntimeException('Failed to reverse payment journal entry.');
                }
            }

            // 2. Reverse ALL existing overpayment adjustments for this bill
            $overpaymentEntries = \App\Models\JournalEntry::where('reference_type', 'Bill')
                ->where('reference_id', $bill->id)
                ->where('entry_type', 'adjustment')
                ->where('description', 'like', 'Overpayment%')
                ->with(['lines', 'subLedgerEntries'])
                ->get();

            foreach ($overpaymentEntries as $entry) {
                \App\Services\AccountingService::reverseEntry($entry, 'payment_edited_recalc');
            }

            // 3. Update the payment record
            $payment->update([
                'amount' => $newAmount,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
            ]);

            // 4. Recalculate bill amounts
            $bill->paid_amount = $bill->paid_amount - $oldAmount + $newAmount;
            $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
            $bill->status = $this->evaluateBillStatus($bill);
            $bill->save();

            // 5. Post new payment journal entry
            \App\Services\AccountingService::postPaymentEntry($payment);

            // 6. Recompute overpayment — if paid still exceeds total, post fresh adjustment
            $overpayment = round((float) $bill->paid_amount - (float) $bill->total_amount, 2);
            if ($overpayment > 0) {
                \App\Services\AccountingService::postOverpaymentAdjustment($bill, $overpayment);
            }
        });

        return redirect()->route('bills.show', $bill)->with('success', 'Payment updated successfully.');
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

    /**
     * Verify the payment is linked to the bill via the relationship query so
     * MySQL string/int ID mismatches from strict comparison cannot false-fail.
     */
    private function ensurePaymentBelongsToBill(Bill $bill, Payment $payment): ?\Illuminate\Http\RedirectResponse
    {
        if (!$bill->payments()->whereKey($payment->getKey())->exists()) {
            return redirect()->back()->withErrors(['error' => 'Payment does not belong to this bill.']);
        }

        return null;
    }

    /**
     * Evaluate the correct bill status based on paid vs total amounts.
     */
    private function evaluateBillStatus(Bill $bill): string
    {
        $paid  = (float) $bill->paid_amount;
        $total = (float) $bill->total_amount;

        if ($paid >= $total) return 'paid';
        if ($paid > 0) return 'partial';
        return 'pending';
    }

    /**
     * Generate a unique bill number using the highest existing sequence number.
     * Uses MAX on the numeric suffix rather than COUNT to avoid collisions
     * caused by deleted bills or concurrent requests.
     */
    private function generateBillNumber(): string
    {
        $prefix = 'BILL-' . date('Y') . '-';

        // Extract the highest numeric suffix for this year's bills
        $lastNumber = Bill::where('bill_number', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(bill_number, ?) AS UNSIGNED)) as max_num", [strlen($prefix) + 1])
            ->value('max_num');

        $nextNumber = ($lastNumber ?? 0) + 1;

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function buildBillItemAttributes(array $item, string $billType): array
    {
        return [
            'service_id'       => $item['service_id'] ?? null,
            'investigation_id' => $item['investigation_id'] ?? null,
            'item_category'    => BillItemCategoryResolver::resolve($item, $billType),
            'description'      => $item['description'],
            'quantity'         => $item['quantity'],
            'unit_price'       => $item['unit_price'],
        ];
    }
}