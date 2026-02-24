<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabOrderRequest;
use App\Http\Requests\UpdateLabOrderRequest;
use App\Http\Requests\CollectSampleRequest;
use App\Models\InvestigationOrder;
use App\Models\Investigation;
use App\Models\LabSample;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;

class InvestigationOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = InvestigationOrder::with(['patient', 'doctor', 'investigation', 'sample', 'result']);
        
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

        InvestigationOrder::create($validated);
        return redirect()->route('investigation-orders.index')->with('success', 'Investigation order created successfully.');
    }

    public function show(InvestigationOrder $investigationOrder)
    {
        $investigationOrder->load(['patient', 'doctor', 'investigation', 'sample', 'result']);
        return view('admin.lab.orders.show', compact('investigationOrder'));
    }

    public function collectSample(CollectSampleRequest $request, InvestigationOrder $investigationOrder)
    {
        $validated = $request->validated();

        LabSample::create([
            'investigation_order_id' => $investigationOrder->id,
            'sample_type' => $investigationOrder->investigation->sample_type,
            'status' => 'collected',
            'collected_at' => now(),
            'collected_by' => auth()->id(),
            'collection_notes' => $validated['collection_notes'] ?? null
        ]);

        $investigationOrder->update([
            'status' => 'collected',
            'sample_collected_at' => now()
        ]);

        return back()->with('success', 'Sample collected successfully.');
    }

    public function edit(InvestigationOrder $investigationOrder)
    {
        $patients = Patient::all();
        $doctors = Doctor::where('status', 'active')->get();
        $investigations = Investigation::where('is_active', true)->get();
        return view('admin.lab.orders.edit', compact('investigationOrder', 'patients', 'doctors', 'investigations'));
    }

    public function update(UpdateLabOrderRequest $request, InvestigationOrder $investigationOrder)
    {
        $investigationOrder->update($request->validated());
        return redirect()->route('investigation-orders.show', $investigationOrder)->with('success', 'Investigation order updated successfully.');
    }

    public function receiveSample(Request $request, InvestigationOrder $investigationOrder)
    {
        $sample = $investigationOrder->sample;
        $sample->update([
            'status' => 'received',
            'received_at' => now(),
            'received_by' => auth()->id()
        ]);

        $investigationOrder->update(['status' => 'testing']);
        return back()->with('success', 'Sample received in laboratory.');
    }
}
