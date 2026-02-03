<?php

namespace App\Http\Controllers;

use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\LabSample;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;

class LabOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = LabOrder::with(['patient', 'doctor', 'labTest', 'sample', 'result']);
        
        if ($request->status) {
            $query->byStatus($request->status);
        }
        
        if ($request->priority) {
            $query->byPriority($request->priority);
        }
        
        $orders = $query->latest()->paginate(15);
        return view('admin.lab.orders.index', compact('orders'));
    }

    public function create()
    {
        $patients = Patient::all();
        $doctors = Doctor::where('status', 'active')->get();
        $labTests = LabTest::active()->get();
        return view('admin.lab.orders.create', compact('patients', 'doctors', 'labTests'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'lab_test_id' => 'required|exists:lab_tests,id',
            'priority' => 'required|in:routine,urgent,stat',
            'clinical_notes' => 'nullable|string',
            'special_instructions' => 'nullable|string'
        ]);

        $validated['ordered_at'] = now();
        $validated['status'] = 'ordered';

        LabOrder::create($validated);
        return redirect()->route('lab-orders.index')->with('success', 'Lab order created successfully.');
    }

    public function show(LabOrder $labOrder)
    {
        $labOrder->load(['patient', 'doctor', 'labTest', 'sample', 'result']);
        return view('admin.lab.orders.show', compact('labOrder'));
    }

    public function collectSample(Request $request, LabOrder $labOrder)
    {
        $validated = $request->validate([
            'collection_notes' => 'nullable|string'
        ]);

        LabSample::create([
            'lab_order_id' => $labOrder->id,
            'sample_type' => $labOrder->labTest->sample_type,
            'status' => 'collected',
            'collected_at' => now(),
            'collected_by' => auth()->id(),
            'collection_notes' => $validated['collection_notes'] ?? null
        ]);

        $labOrder->update([
            'status' => 'collected',
            'sample_collected_at' => now()
        ]);

        return back()->with('success', 'Sample collected successfully.');
    }

    public function edit(LabOrder $labOrder)
    {
        $patients = Patient::all();
        $doctors = Doctor::where('status', 'active')->get();
        $labTests = LabTest::where('is_active', true)->get();
        return view('admin.lab.orders.edit', compact('labOrder', 'patients', 'doctors', 'labTests'));
    }

    public function update(Request $request, LabOrder $labOrder)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'lab_test_id' => 'required|exists:lab_tests,id',
            'priority' => 'required|in:routine,urgent,stat',
            'clinical_notes' => 'nullable|string',
            'special_instructions' => 'nullable|string'
        ]);

        $labOrder->update($validated);
        return redirect()->route('lab-orders.show', $labOrder)->with('success', 'Lab order updated successfully.');
    }

    public function receiveSample(Request $request, LabOrder $labOrder)
    {
        $sample = $labOrder->sample;
        $sample->update([
            'status' => 'received',
            'received_at' => now(),
            'received_by' => auth()->id()
        ]);

        $labOrder->update(['status' => 'testing']);
        return back()->with('success', 'Sample received in laboratory.');
    }
}