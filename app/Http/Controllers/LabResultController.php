<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabResultRequest;
use App\Http\Requests\UpdateLabResultRequest;
use App\Models\LabResult;
use App\Models\LabOrder;
use App\Models\InvestigationOrder;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabResultController extends Controller
{
    public function index(Request $request)
    {
        // Group pending investigation orders by patient and visit for batch result entry
        $pendingOrdersQuery = InvestigationOrder::with(['patient', 'visit', 'investigation.parameters'])
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
        $completedResults = LabResult::with(['investigationOrder.patient', 'investigationOrder.investigation', 'technician'])
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
        
        $labOrders = InvestigationOrder::with(['patient', 'visit', 'investigation.parameters'])
            ->where('patient_id', $patientId)
            ->where('visit_id', $visitId)
            ->whereIn('status', ['ordered', 'collected', 'testing'])
            ->whereDoesntHave('result')
            ->get();
        
        return view('admin.lab.results.create-batch', compact('labOrders'));
    }

    public function create(InvestigationOrder $labOrder)
    {
        $labOrder->load(['patient', 'visit', 'investigation.parameters']);
        
        // Route radiology/cardiology tests to radiology result form
        if ($labOrder->isRadiology() || $labOrder->investigation->type === 'cardiology') {
            return redirect()->route('radiology-results.create', $labOrder);
        }
        
        // Validate that this is a pathology investigation
        if (!$labOrder->isPathology()) {
            return redirect()->route('lab-results.index')
                ->withErrors(['error' => 'Invalid investigation type for pathology result entry.']);
        }
        
        return view('admin.lab.results.create', compact('labOrder'));
    }

    public function store(Request $request, InvestigationOrder $labOrder)
    {
        // Validate that this is a pathology investigation
        if (!$labOrder->isPathology()) {
            return back()->withErrors(['error' => 'Cannot create pathology result for non-pathology investigation: ' . $labOrder->investigation->name]);
        }

        $validated = $request->validate([
            'test_location' => 'required|in:indoor,outdoor',
            'result_text' => 'nullable|string',
            'parameters' => 'nullable|array',
            'parameters.*.parameter_id' => 'nullable|integer',
            'parameters.*.value' => 'required_with:parameters.*.parameter_id|string',
            'parameters.*.unit' => 'nullable|string',
            'interpretation' => 'nullable|string',
            'comments' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $labOrder) {
            // Create lab result
            $result = LabResult::create([
                'investigation_order_id' => $labOrder->id,
                'results' => [],
                'interpretation' => $validated['interpretation'] ?? null,
                'comments' => $validated['comments'] ?? null,
                'status' => 'preliminary',
                'technician_id' => auth()->id(),
                'tested_at' => now()
            ]);
            
            // Create parameter results if provided
            if (!empty($validated['parameters'])) {
                foreach ($validated['parameters'] as $paramData) {
                    if (empty($paramData['parameter_id'])) {
                        continue;
                    }
                    
                    // Get the parameter to calculate flag
                    $parameter = \App\Models\LabTestParameter::find($paramData['parameter_id']);
                    $flag = 'N'; // Default to normal
                    
                    if ($parameter) {
                        $flag = $parameter->calculateFlag(
                            $paramData['value'],
                            $labOrder->patient->age ?? null,
                            $labOrder->patient->gender ?? null
                        );
                    }
                    
                    $result->resultItems()->create([
                        'lab_test_parameter_id' => $paramData['parameter_id'],
                        'value' => $paramData['value'],
                        'unit' => $paramData['unit'] ?? null,
                        'flag' => $flag,
                        'entered_by' => auth()->id(),
                        'entered_at' => now()
                    ]);
                }
            }
            
            // Update investigation order
            $labOrder->update([
                'status' => 'reported',
                'test_location' => $validated['test_location'],
                'completed_at' => now()
            ]);
        });

        return redirect()->route('lab-results.index')
            ->with('success', 'Investigation result entered successfully.');
    }

    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.lab_order_id' => 'required|integer',
            'orders.*.investigation_order_id' => 'nullable|integer',
            'orders.*.test_location' => 'required|in:indoor,outdoor',
            'orders.*.result_text' => 'nullable|string',
            'orders.*.parameters' => 'nullable|array',
            'orders.*.parameters.*.parameter_id' => 'nullable|integer',
            'orders.*.parameters.*.value' => 'required_with:orders.*.parameters.*.parameter_id|string',
            'orders.*.parameters.*.unit' => 'nullable|string',
            'orders.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['orders'] as $orderData) {
                // Support both old and new field names
                $orderId = $orderData['investigation_order_id'] ?? $orderData['lab_order_id'];
                $investigationOrder = InvestigationOrder::find($orderId);
                
                if (!$investigationOrder) {
                    continue; // Skip invalid orders
                }
                
                // Validate that this is a pathology investigation
                if (!$investigationOrder->isPathology()) {
                    throw new \Exception('Cannot create pathology result for non-pathology investigation: ' . $investigationOrder->investigation->name);
                }
                
                // Create lab result
                $result = LabResult::create([
                    'investigation_order_id' => $investigationOrder->id,
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
                        
                        // Get the parameter to calculate flag
                        $parameter = \App\Models\LabTestParameter::find($paramData['parameter_id']);
                        $flag = 'N'; // Default to normal
                        
                        if ($parameter) {
                            $flag = $parameter->calculateFlag(
                                $paramData['value'],
                                $investigationOrder->patient->age ?? null,
                                $investigationOrder->patient->gender ?? null
                            );
                        }
                        
                        $result->resultItems()->create([
                            'lab_test_parameter_id' => $paramData['parameter_id'],
                            'value' => $paramData['value'],
                            'unit' => $paramData['unit'] ?? null,
                            'flag' => $flag,
                            'entered_by' => auth()->id(),
                            'entered_at' => now()
                        ]);
                    }
                }
                
                // Update investigation order
                $investigationOrder->update([
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
            'investigationOrder.patient', 
            'investigationOrder.investigation', 
            'investigationOrder.visit',
            'investigationOrder.doctor',
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
            'investigationOrder.patient', 
            'investigationOrder.doctor', 
            'investigationOrder.investigation', 
            'investigationOrder.visit',
            'technician', 
            'pathologist',
            'resultItems.parameter'
        ]);
        return view('admin.lab.results.report', compact('labResult'));
    }
}