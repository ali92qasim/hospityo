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
        $pendingOrdersQuery = InvestigationOrder::with(['patient', 'visit', 'items.investigation.parameters'])
            ->whereIn('status', ['ordered', 'sample_collected', 'in_progress', 'collected', 'testing'])
            ->whereHas('items', fn($q) => $q->whereNotIn('status', ['reported', 'verified', 'cancelled']));

        if ($request->patient_search) {
            $pendingOrdersQuery->whereHas('patient', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->patient_search . '%')
                  ->orWhere('phone', 'like', '%' . $request->patient_search . '%');
            });
        }

        $pendingOrders = $pendingOrdersQuery->get()
            ->groupBy(fn($order) => $order->patient_id . '_' . $order->visit_id);

        $completedResults = LabResult::with(['investigationOrder.patient', 'investigationOrder.items.investigation', 'technician'])
            ->latest()
            ->paginate(10);

        return view('admin.lab.results.index', compact('pendingOrders', 'completedResults'));
    }

    public function createBatch(Request $request)
    {
        $patientId = $request->patient_id;
        $visitId   = $request->visit_id;

        if (!$patientId) {
            return redirect()->route('lab-results.index')->with('error', 'Patient ID is required.');
        }

        $query = InvestigationOrder::with(['patient', 'visit', 'items.investigation.parameters'])
            ->where('patient_id', $patientId)
            ->whereIn('status', ['ordered', 'sample_collected', 'in_progress', 'collected', 'testing'])
            ->whereHas('items', fn($q) => $q->whereNotIn('status', ['reported', 'verified', 'cancelled']));

        if ($visitId) {
            $query->where('visit_id', $visitId);
        }

        $labOrders = $query->get();

        return view('admin.lab.results.create-batch', compact('labOrders'));
    }

    public function create(InvestigationOrder $labOrder)
    {
        $labOrder->load(['patient', 'visit', 'items.investigation.parameters']);

        // Pass the first pending item's investigation for single-result entry
        $item = $labOrder->items->firstWhere('status', '!=', 'reported')
             ?? $labOrder->items->first();

        if (!$item) {
            return redirect()->route('lab-results.index')
                ->with('error', 'No investigations found on this order.');
        }

        return view('admin.lab.results.create', compact('labOrder', 'item'));
    }

    public function store(Request $request, InvestigationOrder $labOrder)
    {
        $validated = $request->validate([
            'test_location'                    => 'required|in:indoor,outdoor',
            'result_text'                      => 'nullable|string',
            'parameters'                       => 'nullable|array',
            'parameters.*.parameter_id'        => 'nullable|integer',
            'parameters.*.value'               => 'required_with:parameters.*.parameter_id|string',
            'parameters.*.unit'                => 'nullable|string',
            'interpretation'                   => 'nullable|string',
            'comments'                         => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $labOrder) {
            $result = LabResult::create([
                'investigation_order_id' => $labOrder->id,
                'results'                => [],
                'interpretation'         => $validated['interpretation'] ?? null,
                'comments'               => $validated['comments'] ?? null,
                'status'                 => 'preliminary',
                'technician_id'          => auth()->id(),
                'tested_at'              => now(),
            ]);

            if (!empty($validated['parameters'])) {
                foreach ($validated['parameters'] as $paramData) {
                    if (empty($paramData['parameter_id'])) continue;

                    $parameter = \App\Models\LabTestParameter::find($paramData['parameter_id']);
                    $flag = $parameter
                        ? $parameter->calculateFlag($paramData['value'], $labOrder->patient->age ?? null, $labOrder->patient->gender ?? null)
                        : 'N';

                    $result->resultItems()->create([
                        'lab_test_parameter_id' => $paramData['parameter_id'],
                        'value'                 => $paramData['value'],
                        'unit'                  => $paramData['unit'] ?? null,
                        'flag'                  => $flag,
                        'entered_by'            => auth()->id(),
                        'entered_at'            => now(),
                    ]);
                }
            }

            $labOrder->update(['status' => 'reported', 'completed_at' => now()]);
            $labOrder->items()->whereNotIn('status', ['cancelled'])->update(['status' => 'reported']);
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
                $orderId = $orderData['investigation_order_id'] ?? $orderData['lab_order_id'];
                $investigationOrder = InvestigationOrder::with('items.investigation.parameters', 'patient')->find($orderId);

                if (!$investigationOrder) continue;

                $result = LabResult::create([
                    'investigation_order_id' => $investigationOrder->id,
                    'results'                => [],
                    'comments'               => $orderData['notes'] ?? null,
                    'status'                 => 'preliminary',
                    'technician_id'          => auth()->id(),
                    'tested_at'              => now(),
                ]);

                if (!empty($orderData['parameters'])) {
                    foreach ($orderData['parameters'] as $paramData) {
                        if (empty($paramData['parameter_id'])) continue;

                        $parameter = \App\Models\LabTestParameter::find($paramData['parameter_id']);
                        $flag = $parameter
                            ? $parameter->calculateFlag($paramData['value'], $investigationOrder->patient->age ?? null, $investigationOrder->patient->gender ?? null)
                            : 'N';

                        $result->resultItems()->create([
                            'lab_test_parameter_id' => $paramData['parameter_id'],
                            'value'                 => $paramData['value'],
                            'unit'                  => $paramData['unit'] ?? null,
                            'flag'                  => $flag,
                            'entered_by'            => auth()->id(),
                            'entered_at'            => now(),
                        ]);
                    }
                }

                $investigationOrder->update(['status' => 'reported', 'completed_at' => now()]);
                $investigationOrder->items()->whereNotIn('status', ['cancelled'])->update(['status' => 'reported']);
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
