<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\Medicine;
use App\Models\Visit;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Prescription::with(['patient', 'doctor', 'visit']);
        
        if ($request->status) {
            $query->where('status', $request->status);
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
        
        $medicines = Medicine::where('status', 'active')->where('stock_quantity', '>', 0)->get();
        return view('admin.prescriptions.create', compact('visit', 'medicines'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'notes' => 'nullable|string',
            'medicines' => 'required|array|min:1',
            'medicines.*.medicine_id' => 'required|exists:medicines,id',
            'medicines.*.quantity' => 'required|integer|min:1',
            'medicines.*.dosage' => 'required|string',
            'medicines.*.frequency' => 'required|string',
            'medicines.*.duration' => 'required|string',
            'medicines.*.instructions' => 'nullable|string'
        ]);

        $visit = Visit::findOrFail($validated['visit_id']);
        
        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'doctor_id' => $visit->doctor_id,
            'prescribed_date' => now(),
            'notes' => $validated['notes']
        ]);

        $totalAmount = 0;
        
        foreach ($validated['medicines'] as $medicineData) {
            $medicine = Medicine::findOrFail($medicineData['medicine_id']);
            $totalPrice = $medicine->unit_price * $medicineData['quantity'];
            
            $prescription->items()->create([
                'medicine_id' => $medicine->id,
                'quantity' => $medicineData['quantity'],
                'dosage' => $medicineData['dosage'],
                'frequency' => $medicineData['frequency'],
                'duration' => $medicineData['duration'],
                'instructions' => $medicineData['instructions'],
                'unit_price' => $medicine->unit_price,
                'total_price' => $totalPrice
            ]);
            
            $totalAmount += $totalPrice;
        }
        
        $prescription->update(['total_amount' => $totalAmount]);

        return redirect()->route('prescriptions.index')->with('success', 'Prescription created successfully.');
    }

    public function show(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'visit', 'items.medicine']);
        return view('admin.prescriptions.show', compact('prescription'));
    }

    public function dispense(Prescription $prescription)
    {
        foreach ($prescription->items as $item) {
            $medicine = $item->medicine;
            if ($medicine->stock_quantity < $item->quantity) {
                return back()->with('error', "Insufficient stock for {$medicine->name}");
            }
            
            $medicine->decrement('stock_quantity', $item->quantity);
        }
        
        $prescription->update([
            'status' => 'dispensed',
            'dispensed_date' => now()
        ]);

        return back()->with('success', 'Prescription dispensed successfully.');
    }
}