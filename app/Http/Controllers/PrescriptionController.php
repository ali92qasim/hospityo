<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrescriptionRequest;
use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Prescription::with(['patient', 'doctor', 'visit']);

        if ($request->status) {
            $query->where('status', '=', $request->status);
        }

        $prescriptions = $query->latest()->paginate(10);
        return view('admin.prescriptions.index', compact('prescriptions'));
    }

    public function create(Request $request)
    {
        $visit = null;
        if ($request->visit_id) {
            $visit = Visit::with(['patient', 'doctor'])->findOrFail($request->visit_id);
        }

        // Only show medicines that have available stock (FIFO-aware)
        $medicines = Medicine::where('status', 'active')
            ->where('manage_stock', true)
            ->get()
            ->filter(fn($m) => $m->getTotalAvailableStock() > 0)
            ->values();

        return view('admin.prescriptions.create', compact('visit', 'medicines'));
    }

    public function store(StorePrescriptionRequest $request)
    {
        $validated = $request->validated();

        $visit = Visit::findOrFail($validated['visit_id']);

        $prescription = Prescription::create([
            'visit_id'       => $visit->id,
            'patient_id'     => $visit->patient_id,
            'doctor_id'      => $visit->doctor_id,
            'prescribed_date' => now(),
            'notes'          => $validated['notes'] ?? null,
        ]);

        $totalAmount = 0;

        foreach ($validated['medicines'] as $medicineData) {
            $medicine   = Medicine::findOrFail($medicineData['medicine_id']);
            $unitPrice  = $medicine->getSellingPrice(); // FIFO-aware price
            $totalPrice = $unitPrice * $medicineData['quantity'];

            $prescription->items()->create([
                'medicine_id'  => $medicine->id,
                'quantity'     => $medicineData['quantity'],
                'dosage'       => $medicineData['dosage'],
                'frequency'    => $medicineData['frequency'],
                'duration'     => $medicineData['duration'],
                'instructions' => $medicineData['instructions'] ?? null,
                'unit_price'   => $unitPrice,
                'total_price'  => $totalPrice,
            ]);

            $totalAmount += $totalPrice;
        }

        $prescription->update(['total_amount' => $totalAmount]);

        return redirect()->route('prescriptions.index')
            ->with('success', 'Prescription created successfully.');
    }

    public function show(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'visit', 'items.medicine']);
        return view('admin.prescriptions.show', compact('prescription'));
    }

    public function dispense(Prescription $prescription)
    {
        // Guard: already dispensed
        if ($prescription->status === 'dispensed') {
            return back()->with('error', 'This prescription has already been dispensed.');
        }

        // Guard: cancelled
        if ($prescription->status === 'cancelled') {
            return back()->with('error', 'Cannot dispense a cancelled prescription.');
        }

        $prescription->load('items.medicine');

        // Pre-flight: verify all items have sufficient stock before opening a transaction
        foreach ($prescription->items as $item) {
            if (!$item->medicine->manage_stock) {
                continue; // unmanaged medicines are always "available"
            }

            $available = $item->medicine->getTotalAvailableStock();

            if ($available < $item->quantity) {
                return back()->with(
                    'error',
                    "Insufficient stock for {$item->medicine->name}. " .
                    "Available: {$available}, required: {$item->quantity}."
                );
            }
        }

        try {
            DB::transaction(function () use ($prescription) {
                foreach ($prescription->items as $item) {
                    if (!$item->medicine->manage_stock) {
                        continue;
                    }

                    $remaining = $item->quantity;
                    $batches   = $item->medicine->getAvailableBatches();

                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;

                        $consume = min($batch->remaining_quantity, $remaining);

                        // Deduct from this batch's remaining stock
                        $batch->decrement('remaining_quantity', $consume);

                        // Record a stock_out transaction for full traceability
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

                    // Should never happen due to pre-flight check, but guard anyway
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
        } catch (\Throwable $e) {
            Log::error('[Prescription] Dispense failed', [
                'prescription_id' => $prescription->id,
                'error'           => $e->getMessage(),
            ]);
            return back()->with('error', 'Dispense failed. Please try again or contact support.');
        }

        return back()->with('success', 'Prescription dispensed successfully.');
    }
}
