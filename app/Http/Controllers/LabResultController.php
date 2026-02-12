<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabResultRequest;
use App\Http\Requests\UpdateLabResultRequest;
use App\Models\LabResult;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabResultController extends Controller
{
    public function index(Request $request)
    {
        // Group pending lab orders by patient and visit for batch result entry
        $pendingOrdersQuery = LabOrder::with(['patient', 'visit', 'labTest.parameters'])
            ->whereIn('status', ['ordered', 'collected', 'testing'])
            ->whereDoesntHave('result');
        
        if ($request->patient_search) {
            $pendingOrdersQuery->whereHas('patient', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->patient_search . '%')
                  ->orWhere('phone', 'like', '%' . $request->patient_search . '%');
            });
        }
        
        $pendingOrders = $pendingOrdersQuery->get()
            ->groupBy(function($order) {
                return $order->patient_id . '_' . $order->visit_id;
            });
        
        // Get completed results for display
        $completedResults = LabResult::with(['labOrder.patient', 'labOrder.labTest', 'technician'])
            ->latest()
            ->paginate(10);
        
        return view('admin.lab.results.index', compact('pendingOrders', 'completedResults'));
    }

    public function createBatch(Request $request)
    {
        $patientId = $request->patient_id;
        $visitId = $request->visit_id;
        
        if (!$patientId || !$visitId) {
            return redirect()->route('lab-results.index')
                ->with('error', 'Patient ID and Visit ID are required.');
        }
        
        $labOrders = LabOrder::with(['patient', 'visit', 'labTest.parameters'])
            ->where('patient_id', $patientId)
            ->where('visit_id', $visitId)
            ->whereIn('status', ['ordered', 'collected', 'testing'])
            ->whereDoesntHave('result')
            ->get();
        
        return view('admin.lab.results.create-batch', compact('labOrders'));
    }

    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.lab_order_id' => 'required|integer',
            'orders.*.test_location' => 'required|in:indoor,outdoor',
            'orders.*.result_text' => 'nullable|string',
            'orders.*.parameters' => 'nullable|array',
            'orders.*.parameters.*.parameter_id' => 'nullable|integer',
            'orders.*.parameters.*.value' => 'required_with:orders.*.parameters.*.parameter_id|string',
            'orders.*.parameters.*.unit' => 'nullable|string',
            'orders.*.parameters.*.flag' => 'nullable|in:N,H,L,HH,LL,A',
            'orders.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['orders'] as $orderData) {
                $labOrder = LabOrder::find($orderData['lab_order_id']);
                
                if (!$labOrder) {
                    continue; // Skip invalid lab orders
                }
                
                // Create lab result
                $result = LabResult::create([
                    'lab_order_id' => $labOrder->id,
                    'results' => [],
                    'comments' => $orderData['notes'] ?? null,
                    'status' => 'preliminary',
                    'technician_id' => auth()->id(),
                    'tested_at' => now()
                ]);
                
                // Create parameter results if provided
                if (!empty($orderData['parameters'])) {
                    foreach ($orderData['parameters'] as $paramData) {
                        // Skip if parameter_id is empty
                        if (empty($paramData['parameter_id'])) {
                            continue;
                        }
                        
                        $result->resultItems()->create([
                            'lab_test_parameter_id' => $paramData['parameter_id'],
                            'value' => $paramData['value'],
                            'unit' => $paramData['unit'] ?? null,
                            'flag' => $paramData['flag'] ?? 'N',
                            'entered_by' => auth()->id(),
                            'entered_at' => now()
                        ]);
                    }
                }
                
                // Update lab order
                $labOrder->update([
                    'status' => 'reported',
                    'test_location' => $orderData['test_location'],
                    'completed_at' => now()
                ]);
            }
        });
        
        return redirect()->route('lab-results.index')
            ->with('success', 'Results entered successfully for ' . count($validated['orders']) . ' tests.');
    }

    public function show(LabResult $labResult)
    {
        $labResult->load([
            'labOrder.patient', 
            'labOrder.labTest', 
            'labOrder.visit',
            'labOrder.doctor',
            'technician', 
            'pathologist',
            'resultItems.parameter'
        ]);
        return view('admin.lab.results.show', compact('labResult'));
    }

    public function edit(LabResult $labResult)
    {
        return view('admin.lab.results.edit', compact('labResult'));
    }

    public function update(UpdateLabResultRequest $request, LabResult $labResult)
    {
        $labResult->update($request->validated());
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
        $labResult->load([
            'labOrder.patient', 
            'labOrder.doctor', 
            'labOrder.labTest', 
            'labOrder.visit',
            'technician', 
            'pathologist',
            'resultItems.parameter'
        ]);
        return view('admin.lab.results.report', compact('labResult'));
    }
}