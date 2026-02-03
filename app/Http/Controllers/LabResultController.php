<?php

namespace App\Http\Controllers;

use App\Models\LabResult;
use App\Models\LabOrder;
use Illuminate\Http\Request;

class LabResultController extends Controller
{
    public function index(Request $request)
    {
        $query = LabResult::with(['labOrder.patient', 'labOrder.labTest', 'technician']);
        
        if ($request->status) {
            $query->byStatus($request->status);
        }
        
        $results = $query->latest()->paginate(15);
        
        // Get lab orders without results for "Add Result" functionality
        $pendingOrders = LabOrder::with(['patient', 'labTest'])
            ->whereIn('status', ['testing', 'verified'])
            ->whereDoesntHave('result')
            ->latest()
            ->take(10)
            ->get();
        
        return view('admin.lab.results.index', compact('results', 'pendingOrders'));
    }

    public function create(LabOrder $labOrder)
    {
        if ($labOrder->result) {
            return redirect()->route('lab-results.edit', $labOrder->result);
        }
        
        $labOrder->load(['patient', 'labTest']);
        
        return view('admin.lab.results.create', compact('labOrder'));
    }

    public function store(Request $request, LabOrder $labOrder)
    {
        $validated = $request->validate([
            'results' => 'required|array',
            'interpretation' => 'nullable|string',
            'comments' => 'nullable|string'
        ]);

        $result = LabResult::create([
            'lab_order_id' => $labOrder->id,
            'results' => $validated['results'],
            'interpretation' => $validated['interpretation'],
            'comments' => $validated['comments'],
            'status' => 'preliminary',
            'technician_id' => auth()->id(),
            'tested_at' => now()
        ]);

        $labOrder->update(['status' => 'verified', 'completed_at' => now()]);
        
        return redirect()->route('lab-results.index')->with('success', 'Results entered successfully.');
    }

    public function show(LabResult $labResult)
    {
        $labResult->load(['labOrder.patient', 'labOrder.labTest', 'technician', 'pathologist']);
        return view('admin.lab.results.show', compact('labResult'));
    }

    public function edit(LabResult $labResult)
    {
        return view('admin.lab.results.edit', compact('labResult'));
    }

    public function update(Request $request, LabResult $labResult)
    {
        $validated = $request->validate([
            'results' => 'required|array',
            'interpretation' => 'nullable|string',
            'comments' => 'nullable|string'
        ]);

        $labResult->update($validated);
        return redirect()->route('lab-results.show', $labResult)->with('success', 'Results updated successfully.');
    }

    public function verify(LabResult $labResult)
    {
        $labResult->update([
            'status' => 'final',
            'pathologist_id' => auth()->id(),
            'verified_at' => now(),
            'reported_at' => now()
        ]);

        return back()->with('success', 'Results verified and finalized.');
    }

    public function report(LabResult $labResult)
    {
        $labResult->load(['labOrder.patient', 'labOrder.doctor', 'labOrder.labTest', 'technician', 'pathologist']);
        return view('admin.lab.results.report', compact('labResult'));
    }
}