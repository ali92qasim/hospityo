<?php

namespace App\Http\Controllers;

use App\Http\Requests\PharmacyPosCheckoutRequest;
use App\Models\Bill;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Services\AccountingService;
use App\Services\PharmacyStockDispenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PharmacyPosController extends Controller
{
    public function __construct(
        private readonly PharmacyStockDispenseService $stockDispenseService,
    ) {
    }

    public function index(): View
    {
        $pendingPrescriptions = Prescription::inHousePending()
            ->with(['patient', 'doctor', 'items.medicine'])
            ->latest('prescribed_date')
            ->get();

        $patients = Patient::orderBy('name')->get(['id', 'name', 'phone', 'patient_no']);

        return view('admin.pharmacy.pos.index', compact('pendingPrescriptions', 'patients'));
    }

    public function loadPrescription(Prescription $prescription): JsonResponse
    {
        if ($prescription->fulfillment_type !== 'in_house' || $prescription->status !== 'pending') {
            abort(404);
        }

        $prescription->load(['patient', 'items.medicine.baseUnit']);

        return response()->json([
            'id' => $prescription->id,
            'prescription_no' => $prescription->prescription_no,
            'patient_id' => $prescription->patient_id,
            'patient_name' => $prescription->patient->name,
            'total_amount' => (float) $prescription->total_amount,
            'items' => $prescription->items->map(fn ($item) => [
                'medicine_id' => $item->medicine_id,
                'name' => $item->medicine->name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'unit' => $item->medicine->baseUnit?->abbreviation ?? 'unit',
            ])->values(),
        ]);
    }

    public function searchMedicines(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $medicines = Medicine::query()
            ->where('status', 'active')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('generic_name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
            })
            ->with(['baseUnit', 'dispensingUnit'])
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->filter(fn (Medicine $medicine) => $medicine->getSellingPrice() > 0)
            ->values()
            ->map(fn (Medicine $medicine) => [
                'id' => $medicine->id,
                'text' => trim($medicine->name . ($medicine->strength ? " ({$medicine->strength})" : '')),
                'selling_price' => $medicine->getSellingPrice(),
                'available_stock' => $medicine->getTotalAvailableStock(),
                'manage_stock' => $medicine->manage_stock,
                'unit' => $medicine->dispensingUnit?->abbreviation
                    ?? $medicine->baseUnit?->abbreviation
                    ?? 'unit',
            ]);

        return response()->json(['results' => $medicines]);
    }

    public function checkout(PharmacyPosCheckoutRequest $request): RedirectResponse
    {
        try {
            $bill = DB::connection('tenant')->transaction(function () use ($request) {
                $prescription = null;
                $visitId = null;
                $billItemsPayload = [];
                $dispenseLines = [];

                if ($request->mode === 'prescription') {
                    $prescription = Prescription::with('items.medicine')
                        ->findOrFail($request->prescription_id);

                    if ($prescription->fulfillment_type !== 'in_house' || $prescription->status !== 'pending') {
                        throw new \RuntimeException('This prescription cannot be fulfilled at the pharmacy counter.');
                    }

                    $visitId = $prescription->visit_id;

                    foreach ($prescription->items as $item) {
                        $billItemsPayload[] = [
                            'medicine_id' => $item->medicine_id,
                            'description' => $item->medicine->name,
                            'quantity' => (int) $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                        ];

                        $dispenseLines[] = [
                            'medicine' => $item->medicine,
                            'quantity' => (int) $item->quantity,
                            'reference' => $prescription->prescription_no,
                            'notes' => 'Dispensed via POS for prescription ' . $prescription->prescription_no,
                        ];
                    }
                } else {
                    foreach ($request->items as $itemData) {
                        $medicine = Medicine::findOrFail($itemData['medicine_id']);

                        $billItemsPayload[] = [
                            'medicine_id' => $medicine->id,
                            'description' => $medicine->name,
                            'quantity' => (int) $itemData['quantity'],
                            'unit_price' => (float) $itemData['unit_price'],
                        ];

                        $dispenseLines[] = [
                            'medicine' => $medicine,
                            'quantity' => (int) $itemData['quantity'],
                            'reference' => 'POS-WALKIN',
                            'notes' => 'Walk-in pharmacy counter sale',
                        ];
                    }
                }

                $this->stockDispenseService->assertStockAvailable($dispenseLines);

                $bill = Bill::create([
                    'patient_id' => $request->patient_id,
                    'visit_id' => $visitId,
                    'prescription_id' => $prescription?->id,
                    'bill_number' => $this->generateBillNumber(),
                    'bill_date' => now()->toDateString(),
                    'bill_type' => 'pharmacy',
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);

                foreach ($billItemsPayload as $item) {
                    $bill->billItems()->create([
                        'medicine_id' => $item['medicine_id'],
                        'item_category' => 'pharmacy',
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                    ]);
                }

                $bill->calculateTotals();

                foreach ($dispenseLines as &$line) {
                    $line['reference'] = $bill->bill_number;
                }
                unset($line);

                $this->stockDispenseService->dispenseLines($dispenseLines, (int) auth()->id());

                AccountingService::postBillEntry($bill);

                $paymentAmount = round((float) $request->payment_amount, 2);
                if ($paymentAmount > 0) {
                    $payment = $bill->payments()->create([
                        'payment_date' => now()->toDateString(),
                        'amount' => $paymentAmount,
                        'payment_method' => $request->payment_method,
                        'received_by' => auth()->id(),
                    ]);

                    $bill->paid_amount = $paymentAmount;
                    $bill->due_amount = max(0, (float) $bill->total_amount - $paymentAmount);
                    $bill->status = $bill->paid_amount >= $bill->total_amount ? 'paid' : 'partial';
                    $bill->save();

                    AccountingService::postPaymentEntry($payment);

                    $overpayment = round((float) $bill->paid_amount - (float) $bill->total_amount, 2);
                    if ($overpayment > 0) {
                        AccountingService::postOverpaymentAdjustment($bill, $overpayment);
                    }
                }

                if ($prescription) {
                    $prescription->update([
                        'status' => 'dispensed',
                        'dispensed_date' => now(),
                    ]);
                }

                return $bill->fresh(['billItems', 'patient']);
            });
        } catch (\App\Exceptions\InsufficientPharmacyStockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[PharmacyPOS] Checkout failed', [
                'error' => $e->getMessage(),
                'mode' => $request->mode,
            ]);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('bills.show', $bill)
            ->with('success', 'Pharmacy sale completed successfully.');
    }

    private function generateBillNumber(): string
    {
        $prefix = 'BILL-' . date('Y') . '-';

        $lastNumber = Bill::where('bill_number', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(bill_number, ?) AS UNSIGNED)) as max_num', [strlen($prefix) + 1])
            ->value('max_num');

        return $prefix . str_pad((string) (($lastNumber ?? 0) + 1), 6, '0', STR_PAD_LEFT);
    }
}
