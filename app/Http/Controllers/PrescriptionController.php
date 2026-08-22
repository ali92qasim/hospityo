<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrescriptionRequest;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\Visit;
use App\Services\PharmacyStockDispenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionController extends Controller
{
    public function __construct(
        private readonly PharmacyStockDispenseService $stockDispenseService,
    ) {
    }
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
            ->with(['baseUnit', 'dispensingUnit'])
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

        $lines = $prescription->items
            ->map(fn ($item) => [
                'medicine' => $item->medicine,
                'quantity' => (int) $item->quantity,
                'reference' => $prescription->prescription_no,
                'notes' => 'Dispensed via prescription ' . $prescription->prescription_no,
            ])
            ->all();

        try {
            $this->stockDispenseService->assertStockAvailable($lines);
        } catch (\App\Exceptions\InsufficientPharmacyStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        try {
            DB::transaction(function () use ($prescription, $lines) {
                $this->stockDispenseService->dispenseLines($lines, (int) auth()->id());

                $prescription->update([
                    'status' => 'dispensed',
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
