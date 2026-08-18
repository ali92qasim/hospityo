<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImagingOrderRequest;
use App\Http\Requests\UpdateImagingOrderRequest;
use App\Models\Doctor;
use App\Models\ImagingOrder;
use App\Models\ImagingStudy;
use App\Models\Patient;
use App\Services\InvestigationOrderBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImagingOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = ImagingOrder::with(['patient', 'doctor', 'items.imagingStudy', 'report']);

        if ($request->filled('status')) {
            $query->whereHas('items', fn ($q) => $q->where('status', $request->status));
        }

        if ($request->filled('priority')) {
            $query->whereHas('items', fn ($q) => $q->where('priority', $request->priority));
        }

        $orders = $query->latest()->paginate(15);

        return view('admin.imaging.orders.index', compact('orders'));
    }

    public function create()
    {
        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::where('status', 'active')->orderBy('name')->get();
        $imagingStudies = ImagingStudy::active()->orderBy('name')->get();

        return view('admin.imaging.orders.create', compact('patients', 'doctors', 'imagingStudies'));
    }

    public function store(StoreImagingOrderRequest $request)
    {
        $order = DB::connection('tenant')->transaction(function () use ($request) {
            $order = ImagingOrder::create([
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
                    'imaging_study_id' => $item['imaging_study_id'],
                    'quantity'         => $item['quantity'] ?? 1,
                    'priority'         => $item['priority'],
                    'clinical_notes'   => $item['clinical_notes'] ?? null,
                    'status'           => 'ordered',
                ]);
            }

            return $order;
        });

        InvestigationOrderBillingService::syncOrderToBill($order->fresh(['visit', 'items.imagingStudy']));

        return redirect()->route('imaging.orders.index')
            ->with('success', 'Imaging order created successfully.');
    }

    public function show(ImagingOrder $imagingOrder)
    {
        $imagingOrder->load([
            'patient', 'doctor', 'visit',
            'items.imagingStudy',
            'report',
        ]);

        return view('admin.imaging.orders.show', compact('imagingOrder'));
    }

    public function edit(ImagingOrder $imagingOrder)
    {
        if ($imagingOrder->status !== 'ordered') {
            return redirect()->route('imaging.orders.show', $imagingOrder)
                ->with('error', 'Only orders in "Ordered" status can be edited.');
        }

        $imagingOrder->load('items.imagingStudy');

        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::where('status', 'active')->orderBy('name')->get();
        $imagingStudies = ImagingStudy::active()->orderBy('name')->get();

        return view('admin.imaging.orders.edit', compact(
            'imagingOrder', 'patients', 'doctors', 'imagingStudies'
        ));
    }

    public function update(UpdateImagingOrderRequest $request, ImagingOrder $imagingOrder)
    {
        if ($imagingOrder->status !== 'ordered') {
            return redirect()->route('imaging.orders.show', $imagingOrder)
                ->with('error', 'Only orders in "Ordered" status can be edited.');
        }

        DB::connection('tenant')->transaction(function () use ($request, $imagingOrder) {
            $imagingOrder->update([
                'patient_id'           => $request->patient_id,
                'doctor_id'            => $request->doctor_id,
                'visit_id'             => $request->visit_id,
                'priority'             => collect($request->items)->pluck('priority')->contains('stat')
                                            ? 'stat'
                                            : (collect($request->items)->pluck('priority')->contains('urgent') ? 'urgent' : 'routine'),
                'clinical_notes'       => $request->clinical_notes,
                'special_instructions' => $request->special_instructions,
            ]);

            $imagingOrder->items()->delete();

            foreach ($request->items as $item) {
                $imagingOrder->items()->create([
                    'imaging_study_id' => $item['imaging_study_id'],
                    'quantity'         => $item['quantity'] ?? 1,
                    'priority'         => $item['priority'],
                    'clinical_notes'   => $item['clinical_notes'] ?? null,
                    'status'           => 'ordered',
                ]);
            }
        });

        return redirect()->route('imaging.orders.show', $imagingOrder)
            ->with('success', 'Imaging order updated successfully.');
    }

    public function destroy(ImagingOrder $imagingOrder)
    {
        if ($imagingOrder->status !== 'ordered') {
            return redirect()->route('imaging.orders.index')
                ->with('error', 'Only orders in "Ordered" status can be deleted. This order has already been processed.');
        }

        try {
            DB::connection('tenant')->transaction(function () use ($imagingOrder) {
                $imagingOrder->items()->delete();
                $imagingOrder->delete();
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[ImagingOrder] Delete failed', [
                'order_id' => $imagingOrder->id,
                'error'    => $e->getMessage(),
            ]);

            return redirect()->route('imaging.orders.index')
                ->with('error', 'Failed to delete order. Please try again.');
        }

        return redirect()->route('imaging.orders.index')
            ->with('success', 'Imaging order deleted successfully.');
    }
}
