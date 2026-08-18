<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLabResultRequest;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Services\LabReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Yajra\DataTables\Facades\DataTables;

class LabResultController extends Controller
{
    public function index(Request $request)
    {
        return redirect()
            ->route('lab.results.index')
            ->with('info', 'Lab results have moved to the Laboratory section.');
    }

    public function indexLab(Request $request)
    {
        $pendingOrders = $this->pendingOrdersGrouped($request);

        return view('admin.lab.results.index', compact('pendingOrders'));
    }

    public function data(Request $request)
    {
        $query = LabResult::query()
            ->select('lab_results.*')
            ->with([
                'labOrder:id,order_number,patient_id',
                'labOrder.patient:id,name,phone',
                'labOrder.items:id,lab_order_id,lab_test_id',
                'labOrder.items.labTest:id,name',
            ])
            ->orderByDesc('lab_results.id');

        return DataTables::eloquent($query)
            ->addColumn('order_number', function (LabResult $result) {
                return $result->labOrder?->order_number ?? 'N/A';
            })
            ->addColumn('patient_name', function (LabResult $result) {
                return $result->labOrder?->patient?->name ?? 'Unknown Patient';
            })
            ->addColumn('tests_list', function (LabResult $result) {
                $tests = $result->labOrder?->items
                    ->map(fn ($item) => $item->labTest?->name)
                    ->filter()
                    ->unique()
                    ->values();

                return $tests && $tests->isNotEmpty()
                    ? $tests->join(', ')
                    : 'Unknown Test';
            })
            ->filter(function ($query) use ($request) {
                $keyword = trim((string) $request->input('search.value', ''));

                if ($keyword === '') {
                    return;
                }

                $query->where(function ($builder) use ($keyword) {
                    $builder->where('lab_results.status', 'like', "%{$keyword}%")
                        ->orWhereHas('labOrder', function ($orderQuery) use ($keyword) {
                            $orderQuery->where('order_number', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('labOrder', function ($orderQuery) use ($keyword) {
                            $orderQuery->whereHas('patient', function ($patientQuery) use ($keyword) {
                                $patientQuery->where('name', 'like', "%{$keyword}%")
                                    ->orWhere('phone', 'like', "%{$keyword}%");
                            });
                        })
                        ->orWhereHas('labOrder', function ($orderQuery) use ($keyword) {
                            $orderQuery->whereHas('items.labTest', function ($labTestQuery) use ($keyword) {
                                $labTestQuery->where('name', 'like', "%{$keyword}%");
                            });
                        });
                });
            }, false)
            ->makeHidden(['investigationOrder', 'technician', 'labOrder', 'resultItems'])
            ->only([
                'id',
                'order_number',
                'patient_name',
                'tests_list',
                'status',
                'reported_at',
                'tested_at',
            ])
            ->toJson();
    }

    private function pendingOrdersGrouped(Request $request)
    {
        return LabOrder::with([
                'patient',
                'visit',
                'items.labTest.parameters',
            ])
            ->whereHas('items', function ($q) {
                $q->whereNotIn('status', ['reported', 'verified', 'cancelled']);
            })
            ->get()
            ->groupBy(function ($order) {
                return $order->patient_id.'_'.$order->visit_id;
            });
    }

    public function createBatch(Request $request)
    {
        $patientId = $request->patient_id;
        $visitId   = $request->visit_id;

        if (! $patientId) {
            return redirect()->route('lab-results.index')
                ->with('error', 'Patient ID is required.');
        }

        $query = LabOrder::with(['patient', 'visit', 'items.labTest.parameters'])
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

    public function create(LabOrderItem $orderItem)
    {
        $orderItem->load(['order.patient', 'order.visit', 'labTest.parameters']);

        $labOrder = $orderItem;

        return view('admin.lab.results.create', compact('labOrder'));
    }

    public function store(Request $request, LabOrderItem $orderItem)
    {
        $validated = $request->validate([
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
                'lab_order_id'  => $orderItem->lab_order_id,
                'results'       => [],
                'interpretation'=> $validated['interpretation'] ?? null,
                'comments'      => $validated['comments'] ?? null,
                'status'        => 'preliminary',
                'technician_id' => auth()->id(),
                'tested_at'     => now(),
            ]);

            if (! empty($validated['parameters'])) {
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

            $orderItem->update([
                'status' => 'reported',
            ]);

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
            ->with('success', 'Lab result entered successfully.');
    }

    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'orders'                             => 'required|array',
            'orders.*.investigation_order_id'    => 'required|integer',
            'orders.*.item_id'                   => 'required|integer',
            'orders.*.result_text'               => 'nullable|string',
            'orders.*.parameters'                => 'nullable|array',
            'orders.*.parameters.*.parameter_id' => 'nullable|integer',
            'orders.*.parameters.*.value'        => 'required_with:orders.*.parameters.*.parameter_id|string',
            'orders.*.parameters.*.unit'         => 'nullable|string',
            'orders.*.notes'                     => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['orders'] as $orderData) {
                $item = LabOrderItem::find($orderData['item_id']);

                if (! $item) {
                    continue;
                }

                $labOrder = $item->order;

                if (! $labOrder) {
                    continue;
                }

                $result = LabResult::create([
                    'lab_order_id'  => $labOrder->id,
                    'results'       => [],
                    'comments'      => $orderData['notes'] ?? null,
                    'status'        => 'preliminary',
                    'technician_id' => auth()->id(),
                    'tested_at'     => now(),
                ]);

                if (! empty($orderData['parameters'])) {
                    foreach ($orderData['parameters'] as $paramData) {
                        if (empty($paramData['parameter_id'])) {
                            continue;
                        }

                        $parameter = \App\Models\LabTestParameter::find($paramData['parameter_id']);
                        $flag = 'N';

                        if ($parameter) {
                            $flag = $parameter->calculateFlag(
                                $paramData['value'],
                                $labOrder->patient->age ?? null,
                                $labOrder->patient->gender ?? null
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

                $item->update([
                    'status' => 'reported',
                ]);

                $allReported = $labOrder->items()->whereNotIn('status', ['reported', 'verified', 'cancelled'])->doesntExist();
                if ($allReported) {
                    $labOrder->update([
                        'status'       => 'reported',
                        'completed_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('lab-results.index')
            ->with('success', 'Results entered successfully for '.count($validated['orders']).' tests.');
    }

    public function show(LabResult $labResult)
    {
        $labResult->load([
            'labOrder.patient',
            'labOrder.items.labTest',
            'labOrder.visit',
            'labOrder.doctor',
            'technician',
            'pathologist',
            'resultItems.parameter',
        ]);

        if ($labResult->relationLoaded('labOrder')) {
            $labResult->setRelation('investigationOrder', $labResult->labOrder);
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
        $labResult->load('labOrder');

        $report = LabReportBuilder::build($labResult->labOrder);

        return view('admin.lab.results.report', compact('report'));
    }

    public function orderReport(LabOrder $investigationOrder)
    {
        $report = LabReportBuilder::build($investigationOrder);

        return view('admin.lab.results.report', compact('report'));
    }

    public function publicReport(LabResult $labResult)
    {
        $labResult->load('labOrder');

        if ($labResult->labOrder) {
            return redirect()->route('lab-report.show', $labResult->labOrder->ensureShareToken());
        }

        abort(404);
    }

    public function shareWhatsApp(Request $request, LabResult $labResult)
    {
        $labResult->load('labOrder.patient');

        $order = $labResult->labOrder;
        if (! $order) {
            if ($request->boolean('redirect')) {
                return redirect()->back()->with('error', 'Lab order not found for this result.');
            }

            return response()->json(['message' => 'Lab order not found for this result.'], 404);
        }

        return $this->whatsAppShareResponse($request, $order);
    }

    public function shareOrderWhatsApp(Request $request, LabOrder $investigationOrder)
    {
        $investigationOrder->load('patient');

        return $this->whatsAppShareResponse($request, $investigationOrder);
    }

    private function whatsAppShareResponse(Request $request, LabOrder $order)
    {
        $patient = $order->patient;
        $phone = $patient?->phone;

        URL::forceRootUrl(request()->getSchemeAndHttpHost());

        $shareUrl = $order->publicReportUrl();
        $patientName = $patient?->name ?? 'Patient';
        $hospitalName = setting('hospital_name', 'Hospital');

        $message = "Dear {$patientName},\n\n"
            . "Your laboratory report from {$hospitalName} is ready.\n"
            . "Open this link, then enter your Patient Number and Mobile Number to view or download it:\n\n"
            . "{$shareUrl}\n\n"
            . "— {$hospitalName}";

        $whatsappPhone = '';
        if ($phone) {
            $cleaned = preg_replace('/[^0-9+]/', '', $phone) ?? '';
            if (str_starts_with($cleaned, '0')) {
                $cleaned = '92'.substr($cleaned, 1);
            }
            $whatsappPhone = ltrim($cleaned, '+');
        }

        $whatsappUrl = $whatsappPhone !== ''
            ? 'https://wa.me/'.$whatsappPhone.'?text='.urlencode($message)
            : null;

        if ($request->boolean('redirect')) {
            if (! $whatsappUrl) {
                return redirect()->back()->with('error', 'Patient mobile number is missing. Use Copy Link instead.');
            }

            return redirect()->away($whatsappUrl);
        }

        return response()->json([
            'whatsapp_url' => $whatsappUrl,
            'share_url' => $shareUrl,
            'phone' => $whatsappPhone,
            'message' => $whatsappPhone === '' ? 'Patient mobile number is missing.' : null,
        ]);
    }
}
