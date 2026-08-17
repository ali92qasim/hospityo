<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectSampleRequest;
use App\Http\Requests\StoreLabOrderRequest;
use App\Http\Requests\UpdateLabOrderRequest;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabSample;
use App\Models\LabTest;
use App\Models\Patient;
use App\Services\InvestigationOrderBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestigationOrderController extends Controller
{
    public function index(Request $request)
    {
        return redirect()
            ->route('lab.orders.index')
            ->with('info', 'Investigation orders have moved to Lab Orders and Imaging Orders.');
    }

    public function indexLab(Request $request)
    {
        $query = LabOrder::with(['patient', 'doctor', 'items.labTest', 'items.result']);

        if ($request->filled('status')) {
            $query->whereHas('items', fn ($q) => $q->where('status', $request->status));
        }

        if ($request->filled('priority')) {
            $query->whereHas('items', fn ($q) => $q->where('priority', $request->priority));
        }

        $orders = $query->latest()->paginate(15);

        return view('admin.lab.orders.index', compact('orders'));
    }

    public function create()
    {
        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::where('status', 'active')->orderBy('name')->get();
        $labTests = LabTest::active()->orderBy('name')->get();

        return view('admin.lab.orders.create', compact('patients', 'doctors', 'labTests'));
    }

    public function store(StoreLabOrderRequest $request)
    {
        $order = DB::connection('tenant')->transaction(function () use ($request) {
            $order = LabOrder::create([
                'patient_id'           => $request->patient_id,
                'doctor_id'            => $request->doctor_id,
                'visit_id'             => $request->visit_id,
                'priority'             => collect($request->items)->pluck('priority')->contains('stat')
                                            ? 'stat'
                                            : (collect($request->items)->pluck('priority')->contains('urgent') ? 'urgent' : 'routine'),
                'status'               => 'ordered',
                'ordered_at'           => now(),
                'clinical_notes'       => $request->clinical_notes,
                'special_instructions' => $request->special_instructions,
            ]);

            foreach ($request->items as $item) {
                $order->items()->create([
                    'lab_test_id'    => $item['lab_test_id'],
                    'quantity'       => $item['quantity'] ?? 1,
                    'priority'       => $item['priority'],
                    'clinical_notes' => $item['clinical_notes'] ?? null,
                    'test_location'  => $item['test_location'],
                    'status'         => 'ordered',
                ]);
            }

            return $order;
        });

        InvestigationOrderBillingService::syncOrderToBill($order->fresh(['visit', 'items.labTest']));

        return redirect()->route('lab.orders.index')
            ->with('success', 'Lab order created successfully.');
    }

    public function show(LabOrder $investigationOrder)
    {
        $investigationOrder->load([
            'patient', 'doctor', 'visit',
            'items.labTest',
            'items.result',
            'items.sample',
        ]);

        return view('admin.lab.orders.show', compact('investigationOrder'));
    }

    public function edit(LabOrder $investigationOrder)
    {
        if ($investigationOrder->status !== 'ordered') {
            return redirect()->route('investigation-orders.show', $investigationOrder)
                ->with('error', 'Only orders in "Ordered" status can be edited.');
        }

        $investigationOrder->load('items.labTest');

        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::where('status', 'active')->orderBy('name')->get();
        $labTests = LabTest::active()->orderBy('name')->get();

        return view('admin.lab.orders.edit', compact(
            'investigationOrder', 'patients', 'doctors', 'labTests'
        ));
    }

    public function update(UpdateLabOrderRequest $request, LabOrder $investigationOrder)
    {
        if ($investigationOrder->status !== 'ordered') {
            return redirect()->route('investigation-orders.show', $investigationOrder)
                ->with('error', 'Only orders in "Ordered" status can be edited.');
        }

        DB::connection('tenant')->transaction(function () use ($request, $investigationOrder) {
            $investigationOrder->update([
                'patient_id'           => $request->patient_id,
                'doctor_id'            => $request->doctor_id,
                'visit_id'             => $request->visit_id,
                'priority'             => collect($request->items)->pluck('priority')->contains('stat')
                                            ? 'stat'
                                            : (collect($request->items)->pluck('priority')->contains('urgent') ? 'urgent' : 'routine'),
                'clinical_notes'       => $request->clinical_notes,
                'special_instructions' => $request->special_instructions,
            ]);

            $investigationOrder->items()->delete();

            foreach ($request->items as $item) {
                $investigationOrder->items()->create([
                    'lab_test_id'    => $item['lab_test_id'],
                    'quantity'       => $item['quantity'] ?? 1,
                    'priority'       => $item['priority'],
                    'clinical_notes' => $item['clinical_notes'] ?? null,
                    'test_location'  => $item['test_location'],
                    'status'         => 'ordered',
                ]);
            }
        });

        return redirect()->route('investigation-orders.show', $investigationOrder)
            ->with('success', 'Lab order updated successfully.');
    }

    public function destroy(LabOrder $investigationOrder)
    {
        if ($investigationOrder->status !== 'ordered') {
            return redirect()->route('lab.orders.index')
                ->with('error', 'Only orders in "Ordered" status can be deleted. This order has already been processed.');
        }

        try {
            DB::connection('tenant')->transaction(function () use ($investigationOrder) {
                $investigationOrder->items()->delete();
                $investigationOrder->delete();
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[LabOrder] Delete failed', [
                'order_id' => $investigationOrder->id,
                'error'    => $e->getMessage(),
            ]);

            return redirect()->route('lab.orders.index')
                ->with('error', 'Failed to delete order. Please try again.');
        }

        return redirect()->route('lab.orders.index')
            ->with('success', 'Lab order deleted successfully.');
    }

    public function collectSample(CollectSampleRequest $request, LabOrder $investigationOrder)
    {
        $validated = $request->validated();

        LabSample::create([
            'lab_order_id'     => $investigationOrder->id,
            'sample_type'      => $investigationOrder->items->first()?->labTest?->sample_type,
            'status'           => 'collected',
            'collected_at'     => now(),
            'collected_by'     => auth()->id(),
            'collection_notes' => $validated['collection_notes'] ?? null,
        ]);

        $investigationOrder->update([
            'status'              => 'collected',
            'sample_collected_at' => now(),
        ]);

        $investigationOrder->items()->where('status', 'ordered')->update(['status' => 'collected']);

        return back()->with('success', 'Sample collected successfully.');
    }

    public function receiveSample(Request $request, LabOrder $investigationOrder)
    {
        $sample = $investigationOrder->sample;
        $sample->update([
            'status'      => 'received',
            'received_at' => now(),
            'received_by' => auth()->id(),
        ]);

        $investigationOrder->update(['status' => 'testing']);
        $investigationOrder->items()->where('status', 'collected')->update(['status' => 'testing']);

        return back()->with('success', 'Sample received in laboratory.');
    }
}
