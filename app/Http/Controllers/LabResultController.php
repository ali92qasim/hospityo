<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLabResultRequest;
use App\Models\LabResult;
use App\Models\InvestigationOrder;
use App\Models\InvestigationOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabResultController extends Controller
{
    public function index(Request $request)
    {
        // Query InvestigationOrders that have at least one pending item
        $pendingOrdersQuery = InvestigationOrder::with([
                'patient',
                'visit',
                'items.investigation.parameters',
            ])
            ->whereHas('items', function ($q) {
                $q->whereNotIn('status', ['reported', 'verified', 'cancelled']);
            });

        if ($request->patient_search) {
            $pendingOrdersQuery->whereHas('patient', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->patient_search . '%')
                  ->orWhere('phone', 'like', '%' . $request->patient_search . '%');
            });
        }

        // Group by patient + visit so the view can render one card per patient/visit
        $pendingOrders = $pendingOrdersQuery->get()
            ->groupBy(function ($order) {
                return $order->patient_id . '_' . $order->visit_id;
            });

        $completedResults = LabResult::with([
                'investigationOrder.patient',
                'investigationOrder.items.investigation',
                'technician',
            ])
            ->latest()
            ->paginate(10);

        return view('admin.lab.results.index', compact('pendingOrders', 'completedResults'));
    }

    public function createBatch(Request $request)
    {
        $patientId = $request->patient_id;
        $visitId   = $request->visit_id;

        if (!$patientId) {
            return redirect()->route('lab-results.index')
                ->with('error', 'Patient ID is required.');
        }

        // Load the InvestigationOrders (headers) for this patient/visit,
        // the create-batch view iterates order->items internally.
        $query = InvestigationOrder::with(['patient', 'visit', 'items.investigation.parameters'])
            ->where('patient_id', $patientId)
            ->whereHas('items', function ($q) {
                $q->whereNotIn('status', ['reported', 'verified', 'cancelled']);
            });

        if ($visitId) {
            $query->where('visit_id', $visitId);
        }

        $labOrders = $query->get();

        return view('admin.lab.results.create-batch', compact('labOrders'));
    }

    /**
     * Show the result entry form for a single investigation order item.
     * Route: GET lab-orders/{orderItem}/results/create
     */
    public function create(InvestigationOrderItem $orderItem)
    {
        $orderItem->load(['order.patient', 'order.visit', 'investigation.parameters']);

        if ($orderItem->isRadiology()) {
            // Radiology results are entered via the radiology controller
            return redirect()->route('radiology-results.create', $orderItem->order)
                ->with('info', 'This investigation requires a radiology result form.');
        }

        if (!$orderItem->isPathology()) {
            return redirect()->route('lab-results.index')
                ->withErrors(['error' => 'Invalid investigation type for pathology result entry.']);
        }

        // Pass as $labOrder for view compatibility
        $labOrder = $orderItem;
        return view('admin.lab.results.create', compact('labOrder'));
    }

    /**
     * Store a result for a single investigation order item.
     * Route: POST lab-orders/{orderItem}/results
     */
    public function store(Request $request, InvestigationOrderItem $orderItem)
    {
        if (!$orderItem->isPathology()) {
            return back()->withErrors(['error' => 'Cannot create pathology result for non-pathology investigation: ' . $orderItem->investigation->name]);
        }

        $validated = $request->validate([
            'test_location'             => 'required|in:indoor,outdoor',
            'result_text'               => 'nullable|string',
            'parameters'                => 'nullable|array',
            'parameters.*.parameter_id' => 'nullable|integer',
            'parameters.*.value'        => 'required_with:parameters.*.parameter_id|string',
            'parameters.*.unit'         => 'nullable|string',
            'interpretation'            => 'nullable|string',
            'comments'                  => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $orderItem) {
            $result = LabResult::create([
                'investigation_order_id' => $orderItem->investigation_order_id,
                'results'                => [],
                'interpretation'         => $validated['interpretation'] ?? null,
                'comments'               => $validated['comments'] ?? null,
                'status'                 => 'preliminary',
                'technician_id'          => auth()->id(),
                'tested_at'              => now(),
            ]);

            if (!empty($validated['parameters'])) {
                foreach ($validated['parameters'] as $paramData) {
                    if (empty($paramData['parameter_id'])) {
                        continue;
                    }

                    $parameter = \App\Models\LabTestParameter::find($paramData['parameter_id']);
                    $flag = 'N';

                    if ($parameter) {
                        $flag = $parameter->calculateFlag(
                            $paramData['value'],
                            $orderItem->order->patient->age ?? null,
                            $orderItem->order->patient->gender ?? null
                        );
                    }

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

            // Update the item status
            $orderItem->update([
                'status'        => 'reported',
                'test_location' => $validated['test_location'],
            ]);

            // Update the parent order status if all items are reported
            $order = $orderItem->order;
            $allReported = $order->items()->whereNotIn('status', ['reported', 'verified', 'cancelled'])->doesntExist();
            if ($allReported) {
                $order->update([
                    'status'       => 'reported',
                    'completed_at' => now(),
                ]);
            }
        });

        return redirect()->route('lab-results.index')
            ->with('success', 'Investigation result entered successfully.');
    }

    /**
     * Store results for multiple items at once (batch entry).
     * Route: POST lab-results/store-batch
     * The create-batch view submits orders[n][investigation_order_id] = InvestigationOrder id
     * and iterates items inside each order.
     */
    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'orders'                             => 'required|array',
            'orders.*.investigation_order_id'    => 'required|integer',
            'orders.*.item_id'                   => 'required|integer',
            'orders.*.test_location'             => 'required|in:indoor,outdoor',
            'orders.*.result_text'               => 'nullable|string',
            'orders.*.parameters'                => 'nullable|array',
            'orders.*.parameters.*.parameter_id' => 'nullable|integer',
            'orders.*.parameters.*.value'        => 'required_with:orders.*.parameters.*.parameter_id|string',
            'orders.*.parameters.*.unit'         => 'nullable|string',
            'orders.*.notes'                     => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['orders'] as $orderData) {
                // Look up the specific item submitted by the form.
                $item = InvestigationOrderItem::find($orderData['item_id']);

                if (!$item) {
                    continue;
                }

                $investigationOrder = $item->order;

                if (!$investigationOrder) {
                    continue;
                }

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
                        if (empty($paramData['parameter_id'])) {
                            continue;
                        }

                        $parameter = \App\Models\LabTestParameter::find($paramData['parameter_id']);
                        $flag = 'N';

                        if ($parameter) {
                            $flag = $parameter->calculateFlag(
                                $paramData['value'],
                                $investigationOrder->patient->age ?? null,
                                $investigationOrder->patient->gender ?? null
                            );
                        }

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

                // Mark the item as reported
                $item->update([
                    'status'        => 'reported',
                    'test_location' => $orderData['test_location'],
                ]);

                // Mark the order as reported if all items are done
                $allReported = $investigationOrder->items()->whereNotIn('status', ['reported', 'verified', 'cancelled'])->doesntExist();
                if ($allReported) {
                    $investigationOrder->update([
                        'status'       => 'reported',
                        'completed_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('lab-results.index')
            ->with('success', 'Results entered successfully for ' . count($validated['orders']) . ' tests.');
    }

    public function show(LabResult $labResult)
    {
        $labResult->load([
            'investigationOrder.patient',
            'investigationOrder.items.investigation',
            'investigationOrder.visit',
            'investigationOrder.doctor',
            'technician',
            'pathologist',
            'resultItems.parameter',
        ]);

        // Alias so the view can use either $labResult->labOrder or ->investigationOrder
        // and both have items loaded.
        if ($labResult->relationLoaded('investigationOrder')) {
            $labResult->setRelation('labOrder', $labResult->investigationOrder);
        }

        return view('admin.lab.results.show', compact('labResult'));
    }

    public function edit(LabResult $labResult)
    {
        return view('admin.lab.results.edit', compact('labResult'));
    }

    public function update(UpdateLabResultRequest $request, LabResult $labResult)
    {
        $labResult->update($request->validated());

        return redirect()->route('lab-results.show', $labResult)
            ->with('success', 'Results updated successfully.');
    }

    public function verify(LabResult $labResult)
    {
        $labResult->update([
            'status'         => 'final',
            'pathologist_id' => auth()->id(),
            'verified_at'    => now(),
            'reported_at'    => now(),
        ]);

        return back()->with('success', 'Results verified and finalized.');
    }

    public function report(LabResult $labResult)
    {
        $labResult->load([
            'investigationOrder.patient',
            'investigationOrder.doctor',
            'investigationOrder.investigation',
            'investigationOrder.items.investigation',
            'investigationOrder.visit',
            'technician',
            'pathologist',
            'resultItems.parameter',
        ]);

        return view('admin.lab.results.report', compact('labResult'));
    }

    /**
     * Public report view accessible via signed URL (no authentication required).
     * Used for WhatsApp sharing — patient opens the link and sees the report.
     * The signed URL expires after the configured time (default 72 hours).
     */
    public function publicReport(LabResult $labResult)
    {
        $labResult->load([
            'investigationOrder.patient',
            'investigationOrder.doctor',
            'investigationOrder.investigation',
            'investigationOrder.items.investigation',
            'investigationOrder.visit',
            'technician',
            'pathologist',
            'resultItems.parameter',
        ]);

        return view('admin.lab.results.report', compact('labResult'));
    }

    /**
     * Generate a signed URL for sharing the lab report via WhatsApp.
     * Returns the WhatsApp wa.me URL with the signed link as the message body.
     */
    public function shareWhatsApp(LabResult $labResult)
    {
        $labResult->load('investigationOrder.patient');

        $patient = $labResult->investigationOrder?->patient;
        $phone   = $patient?->phone;

        // Generate a signed URL valid for 72 hours
        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'lab-results.public-report',
            now()->addHours(72),
            ['labResult' => $labResult->id]
        );

        // Build the WhatsApp message
        $patientName = $patient?->name ?? 'Patient';
        $message = "Dear {$patientName},\n\n"
                 . "Your laboratory report is ready. You can view it using the link below:\n\n"
                 . $signedUrl . "\n\n"
                 . "This link is valid for 72 hours.\n\n"
                 . "— " . setting('hospital_name', 'Hospital');

        // Format phone for WhatsApp (remove spaces, dashes, leading 0, add country code)
        $whatsappPhone = '';
        if ($phone) {
            $cleaned = preg_replace('/[^0-9+]/', '', $phone);
            // If starts with 0, replace with 92 (Pakistan)
            if (str_starts_with($cleaned, '0')) {
                $cleaned = '92' . substr($cleaned, 1);
            }
            // If doesn't start with +, don't add one (wa.me handles both)
            $whatsappPhone = ltrim($cleaned, '+');
        }

        $whatsappUrl = 'https://wa.me/' . $whatsappPhone . '?text=' . urlencode($message);

        return response()->json([
            'whatsapp_url' => $whatsappUrl,
            'signed_url'   => $signedUrl,
            'phone'        => $whatsappPhone,
        ]);
    }
}
