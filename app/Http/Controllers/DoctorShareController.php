<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareRate;
use App\Models\DoctorShareSettlement;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DoctorShareController extends Controller
{
    /**
     * Show rates for doctors that currently have at least one matrix cell.
     */
    public function ratesIndex(): View
    {
        $categoryOptions = $this->entitledRateCategoryOptions();
        $ratesByDoctor = DoctorShareRate::query()
            ->get()
            ->groupBy('doctor_id');

        $doctors = Doctor::query()
            ->orderBy('name')
            ->get();
        $doctorsById = $doctors->keyBy('id');

        $rateRows = $ratesByDoctor
            ->filter(fn ($rates, $doctorId) => $doctorsById->has($doctorId))
            ->map(function ($rates, $doctorId) use ($categoryOptions, $doctorsById) {
                $ratesByCategory = $rates->keyBy('service_category');

                return [
                    'doctor_id' => (int) $doctorId,
                    'doctor' => $doctorsById->get($doctorId),
                    'rates' => collect($categoryOptions)
                        ->mapWithKeys(fn ($label, $category) => [
                            $category => $ratesByCategory->get($category)?->percentage,
                        ])
                        ->all(),
                ];
            })
            ->values();

        return view('admin.doctor-share.rates.index', compact(
            'doctors',
            'rateRows',
            'categoryOptions'
        ));
    }

    /**
     * Replace rates for the doctors present in the submitted matrix.
     */
    public function ratesSync(Request $request): RedirectResponse
    {
        if ($request->input('doctors') === null) {
            $request->merge(['doctors' => []]);
        }

        $entitledCategories = array_keys($this->entitledRateCategoryOptions());
        foreach ((array) $request->input('doctors', []) as $doctor) {
            if (! is_array($doctor)) {
                continue;
            }

            foreach (DoctorShareRate::CATEGORIES as $category) {
                if (! in_array($category, $entitledCategories, true)
                    && array_key_exists($category, $doctor)
                    && $doctor[$category] !== null
                    && $doctor[$category] !== '') {
                    abort(403);
                }
            }
        }

        $categoryRules = collect(DoctorShareRate::CATEGORIES)
            ->mapWithKeys(fn ($category) => [
                "doctors.*.{$category}" => ['nullable', 'numeric', 'min:0', 'max:100'],
            ])
            ->all();

        $validated = $request->validate([
            'doctors' => ['present', 'array'],
            'doctors.*' => ['required', 'array'],
            'doctors.*.doctor_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists(Doctor::class, 'id'),
            ],
            ...$categoryRules,
        ]);

        DB::connection('tenant')->transaction(function () use ($validated, $entitledCategories) {
            $doctorIds = collect($validated['doctors'])
                ->pluck('doctor_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($doctorIds !== []) {
                DoctorShareRate::query()->whereIn('doctor_id', $doctorIds)->delete();
            }

            foreach ($validated['doctors'] as $doctor) {
                foreach ($entitledCategories as $category) {
                    $percentage = $doctor[$category] ?? null;

                    if ($percentage === null || $percentage === '') {
                        continue;
                    }

                    DoctorShareRate::create([
                        'doctor_id' => $doctor['doctor_id'],
                        'service_category' => $category,
                        'percentage' => $percentage,
                    ]);
                }
            }
        });

        return redirect()->route('doctor-share.rates.index')
            ->with('success', 'Doctor share rates updated successfully.');
    }

    /**
     * Redirect legacy rule-list links to the rates matrix.
     */
    public function rulesIndex(): RedirectResponse
    {
        return redirect()->route('doctor-share.rates.index');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Share Items — Task 5.1
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * List share items with filters and summary totals.
     * Task 5.1
     */
    public function itemsIndex(Request $request): View
    {
        $query = DoctorShareItem::with(['doctor', 'bill', 'billItem', 'allocations'])
            ->withSum('allocations', 'amount')
            ->latest();

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $items = $query->paginate(25)->withQueryString();

        // Summary totals for the current filter (separate query — not just current page)
        $summaryQuery = DoctorShareItem::query();

        if ($request->filled('doctor_id')) {
            $summaryQuery->where('doctor_id', $request->doctor_id);
        }

        if ($request->filled('status')) {
            $summaryQuery->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $summaryQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $summaryQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $totalRevenue = (float) (clone $summaryQuery)->sum('base_amount');
        $totalDoctorShare = (float) (clone $summaryQuery)->sum('share_amount');
        $totalHospitalShare = max(0, $totalRevenue - $totalDoctorShare);

        $doctors = Doctor::orderBy('name')->get();

        return view('admin.doctor-share.items.index', compact(
            'items',
            'doctors',
            'totalRevenue',
            'totalHospitalShare',
            'totalDoctorShare'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Settlements — Task 5.2
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * List all settlement batches.
     * Task 5.2
     */
    public function settlementsIndex(Request $request): View
    {
        $settlements = DoctorShareSettlement::with(['doctor', 'createdBy'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.doctor-share.settlements.index', compact('settlements'));
    }

    /**
     * Preview eligible items for a settlement run.
     * Task 5.2
     */
    public function settlementsPreview(Request $request): View
    {
        $doctors = Doctor::orderBy('name')->get();

        $eligibleItems = collect();

        if ($request->filled('date_from') || $request->filled('date_to') || $request->filled('doctor_id')) {
            $query = DoctorShareItem::with(['doctor', 'bill'])
                ->where('status', 'pending')
                ->whereNull('settlement_id')
                ->whereHas('allocations');

            if ($request->filled('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $eligibleItems = $query->get();
        }

        $hasItems = $eligibleItems->isNotEmpty();

        $previewTotal = $eligibleItems->sum('share_amount');

        return view('admin.doctor-share.settlements.preview', compact(
            'doctors',
            'eligibleItems',
            'hasItems',
            'previewTotal'
        ));
    }

    /**
     * Execute a settlement run inside a transaction.
     * Task 5.2
     */
    public function settlementsStore(Request $request): RedirectResponse
    {
        $request->validate([
            'doctor_id'  => ['nullable', Rule::exists(Doctor::class, 'id')],
            'date_from'  => ['required', 'date'],
            'date_to'    => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        // Re-query eligible items (same logic as preview)
        $query = DoctorShareItem::with(['allocations'])
            ->where('status', 'pending')
            ->whereNull('settlement_id')
            ->whereHas('allocations');

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $eligibleItems = $query->get();

        if ($eligibleItems->isEmpty()) {
            return back()->withErrors(['error' => 'No eligible items found.']);
        }

        try {
            $settlement = DB::connection('tenant')->transaction(function () use ($request, $eligibleItems) {
                $totalAmount = $eligibleItems->sum('share_amount');
                $count       = $eligibleItems->count();

                $settlement = DoctorShareSettlement::create([
                    'doctor_id'            => $request->doctor_id ?: null,
                    'date_from'            => $request->date_from,
                    'date_to'              => $request->date_to,
                    'item_count'           => $count,
                    'total_settled_amount' => $totalAmount,
                    'created_by'           => auth()->id(),
                ]);

                foreach ($eligibleItems as $item) {
                    $collected = $item->allocations()->sum('amount');

                    $item->update([
                        'status'                 => 'settled',
                        'settlement_id'          => $settlement->id,
                        'collected_at_settlement' => $collected,
                    ]);
                }

                return $settlement;
            });

            // Write audit log for the settlement (DoctorShareSettlement does not use Auditable trait)
            AuditLog::create([
                'user_id'        => auth()->id(),
                'event'          => 'created',
                'auditable_type' => DoctorShareSettlement::class,
                'auditable_id'   => $settlement->id,
                'old_values'     => null,
                'new_values'     => [
                    'doctor_id'            => $settlement->doctor_id,
                    'date_from'            => $settlement->date_from,
                    'date_to'              => $settlement->date_to,
                    'item_count'           => $settlement->item_count,
                    'total_settled_amount' => $settlement->total_settled_amount,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $count = $settlement->item_count;
            $total = number_format($settlement->total_settled_amount, 2);

            return redirect()->route('doctor-share.settlements.index')
                ->with('success', "Settlement completed: {$count} items settled, total PKR {$total}.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Settlement failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Show a single settlement batch with its items.
     * Task 5.2
     */
    public function settlementsShow(DoctorShareSettlement $settlement): View
    {
        $settlement->load(['shareItems.doctor', 'shareItems.bill', 'doctor', 'createdBy']);

        return view('admin.doctor-share.settlements.show', compact('settlement'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Reports — Task 5.3
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build the doctor share report with summary and detail.
     * Task 5.3
     */
    public function reportsIndex(Request $request): View
    {
        [$summary, $details, $doctors] = $this->buildReportData($request, paginate: true);
        $reportBillTypes = $this->entitledReportBillTypes();

        return view('admin.doctor-share.reports.index', compact('summary', 'details', 'doctors', 'reportBillTypes'));
    }

    /**
     * Print-friendly version of the report (no pagination, no layout).
     * Task 5.3
     */
    public function reportsPrint(Request $request): View
    {
        [$summary, $details, $doctors] = $this->buildReportData($request, paginate: false);

        $settings = [
            'hospital_name'    => setting('hospital_name', config('app.name', 'Hospital Management System')),
            'hospital_address' => setting('hospital_address', ''),
            'hospital_phone'   => setting('hospital_phone', ''),
            'hospital_logo'    => setting('hospital_logo', ''),
        ];

        return view('admin.doctor-share.reports.print', compact('summary', 'details', 'doctors', 'settings'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build the shared query, summary, and detail data for reports.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: mixed, 2: \Illuminate\Database\Eloquent\Collection}
     */
    private function buildReportData(Request $request, bool $paginate): array
    {
        $doctors = Doctor::orderBy('name')->get();

        // Base query with eager loads
        $baseQuery = DoctorShareItem::with(['doctor', 'bill'])
            ->latest();

        if ($request->filled('doctor_id')) {
            $baseQuery->where('doctor_id', $request->doctor_id);
        }

        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('bill_type')) {
            $this->abortUnlessReportBillTypeEntitled((string) $request->bill_type);
            $baseQuery->whereHas('billItem', function ($q) use ($request) {
                $q->where('item_category', $request->bill_type);
            });
        }

        // Summary: group by doctor_id (remove inherited ordering — not valid with GROUP BY)
        $summaryRows = (clone $baseQuery)
            ->reorder()
            ->select('doctor_id')
            ->selectRaw('SUM(share_amount) as total_earned')
            ->selectRaw('SUM(CASE WHEN status = "pending" THEN share_amount ELSE 0 END) as total_pending')
            ->selectRaw('SUM(CASE WHEN status = "settled" THEN share_amount ELSE 0 END) as total_settled')
            ->groupBy('doctor_id')
            ->with('doctor')
            ->get();

        // Attach total_collected from allocations for each doctor in the summary
        $summary = $summaryRows->map(function ($row) use ($baseQuery) {
            $collected = DB::connection('tenant')
                ->table('doctor_share_allocations')
                ->whereIn(
                    'doctor_share_item_id',
                    (clone $baseQuery)
                        ->reorder()
                        ->where('doctor_id', $row->doctor_id)
                        ->select('doctor_share_items.id')
                )
                ->sum('amount');

            $row->total_collected = $collected;

            return $row;
        });

        // Detail list
        $detailQuery = (clone $baseQuery)->withSum('allocations', 'amount');

        $details = $paginate
            ? $detailQuery->paginate(25)->withQueryString()
            : $detailQuery->get();

        return [$summary, $details, $doctors];
    }

    /**
     * @return array<string, string>
     */
    private function entitledReportBillTypes(): array
    {
        $types = [];

        foreach ([
            'opd' => ['visits', 'OPD'],
            'ipd' => ['ipd', 'IPD'],
            'emergency' => ['emergency', 'Emergency'],
            'lab' => ['laboratory', 'Lab'],
            'imaging' => ['imaging', 'Imaging'],
            'pharmacy' => ['pharmacy', 'Pharmacy'],
        ] as $value => [$module, $label]) {
            if (Tenant::currentHasModule($module)) {
                $types[$value] = $label;
            }
        }

        return $types;
    }

    /**
     * @return array<string, string>
     */
    private function entitledRateCategoryOptions(): array
    {
        $options = ['general' => 'General'];

        foreach ([
            'opd' => ['visits', 'OPD'],
            'ipd' => ['ipd', 'IPD'],
            'emergency' => ['emergency', 'Emergency'],
            'lab' => ['laboratory', 'Lab'],
            'imaging' => ['imaging', 'Imaging'],
            'pharmacy' => ['pharmacy', 'Pharmacy'],
        ] as $category => [$module, $label]) {
            if (Tenant::currentHasModule($module)) {
                $options[$category] = $label;
            }
        }

        return $options;
    }

    private function abortUnlessReportBillTypeEntitled(string $billType): void
    {
        $module = match ($billType) {
            'opd' => 'visits',
            'ipd' => 'ipd',
            'emergency' => 'emergency',
            'lab' => 'laboratory',
            'imaging' => 'imaging',
            'pharmacy' => 'pharmacy',
            default => null,
        };

        abort_unless($module !== null, 403);
        Tenant::abortUnlessCurrentHasModule($module);
    }
}
