<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabOrderRequest;
use App\Http\Requests\UpdateLabOrderRequest;
use App\Http\Requests\CollectSampleRequest;
use App\Models\LabOrder;
use App\Models\Investigation;
use App\Models\LabSample;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;

class LabOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = LabOrder::with(['patient', 'doctor', 'investigation', 'sample', 'result']);
        
        if ($request->status) {
            $query->where('status', '=', $request->status);
        }
        
        if ($request->priority) {
            $query->where('priority', '=', $request->priority);
        }
        
        $orders = $query->latest()->paginate(15);
        return view('admin.lab.orders.index', compact('orders'));
    }

    public function create()
    {
        $patients = Patient::all();
        $doctors = Doctor::where('status', 'active')->get();
        $investigations = Investigation::active()->get();
        return view('admin.lab.orders.create', compact('patients', 'doctors', 'investigations'));
    }

    public function store(StoreLabOrderRequest $request)
    {
        $validated = $request->validated();
        $validated['ordered_at'] = now();
        $validated['status'] = 'ordered';

        LabOrder::create($validated);
        return redirect()->route('lab-orders.index')->with('success', 'Lab order created successfully.');
    }

    public function show(LabOrder $labOrder)
    {
        $labOrder->load(['patient', 'doctor', 'investigation', 'sample', 'result']);
        return view('admin.lab.orders.show', compact('labOrder'));
    }

    public function collectSample(CollectSampleRequest $request, LabOrder $labOrder)
    {
        $validated = $request->validated();

        LabSample::create([
            'investigation_order_id' => $labOrder->id,
            'sample_type' => $labOrder->investigation->sample_type,
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
        $investigations = Investigation::where('is_active', true)->get();
        return view('admin.lab.orders.edit', compact('labOrder', 'patients', 'doctors', 'investigations'));
    }

    public function update(UpdateLabOrderRequest $request, LabOrder $labOrder)
    {
        $labOrder->update($request->validated());
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