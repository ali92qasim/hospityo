<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareRule;
use App\Models\DoctorShareSettlement;
use App\Models\Investigation;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DoctorShareController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // Share Rules — Tasks 4.1–4.4
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * List all share rules with optional filters.
     * Task 4.1
     */
    public function rulesIndex(Request $request): View
    {
        $query = DoctorShareRule::with(['doctor', 'service', 'services', 'investigation'])
            ->latest();

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $rules   = $query->paginate(20)->withQueryString();
        $doctors = Doctor::orderBy('name')->get();

        return view('admin.doctor-share.rules.index', compact('rules', 'doctors'));
    }

    /**
     * Show the create rule form.
     * Task 4.2
     */
    public function rulesCreate(): View
    {
        $doctors        = Doctor::orderBy('name')->get();
        $services       = Service::orderBy('name')->get();
        $investigations = Investigation::orderBy('name')->get();

        return view('admin.doctor-share.rules.create', compact('doctors', 'services', 'investigations'));
    }

    /**
     * Validate and persist a new share rule.
     * Task 4.2
     */
    public function rulesStore(Request $request): RedirectResponse
    {
        $validated = $this->validateRuleRequest($request);

        if ($error = $this->validateRuleScope($validated)) {
            return back()->withInput()->withErrors(['doctor_id' => $error]);
        }

        $serviceIds = $validated['service_ids'];
        unset($validated['service_ids']);
        $validated['service_id'] = null;
        $validated['created_by'] = auth()->id();

        $rule = DoctorShareRule::create($validated);
        $rule->services()->sync($serviceIds);

        return redirect()->route('doctor-share.rules.index')
            ->with('success', 'Share rule created successfully.');
    }

    /**
     * Show the edit rule form.
     * Task 4.3
     */
    public function rulesEdit(DoctorShareRule $rule): View
    {
        $rule->load(['doctor', 'service', 'services', 'investigation']);

        $doctors        = Doctor::orderBy('name')->get();
        $services       = Service::orderBy('name')->get();
        $investigations = Investigation::orderBy('name')->get();

        $hasPendingItems = $rule->shareItems()->where('status', 'pending')->exists();

        return view('admin.doctor-share.rules.edit', compact(
            'rule',
            'doctors',
            'services',
            'investigations',
            'hasPendingItems'
        ));
    }

    /**
     * Validate and update an existing share rule.
     * Task 4.3
     */
    public function rulesUpdate(Request $request, DoctorShareRule $rule): RedirectResponse
    {
        $validated = $this->validateRuleRequest($request);

        if ($error = $this->validateRuleScope($validated, $rule->id)) {
            return back()->withInput()->withErrors(['doctor_id' => $error]);
        }

        $serviceIds = $validated['service_ids'];
        unset($validated['service_ids']);
        $validated['service_id'] = null;

        $rule->update($validated);
        $rule->services()->sync($serviceIds);

        return redirect()->route('doctor-share.rules.index')
            ->with('success', 'Share rule updated successfully.');
    }

    /**
     * Delete a share rule (blocked if it has associated share history).
     * Task 4.4
     */
    public function rulesDestroy(DoctorShareRule $rule): RedirectResponse
    {
        if ($rule->shareItems()->exists()) {
            return back()->withErrors([
                'error' => 'This rule has associated share history and must be deactivated instead of deleted.',
            ]);
        }

        // DoctorShareRule uses the Auditable trait — audit log is written automatically
        $rule->delete();

        return redirect()->route('doctor-share.rules.index')
            ->with('success', 'Share rule deleted successfully.');
    }

    /**
     * Toggle the active/inactive state of a share rule.
     * Task 4.4
     */
    public function toggleRule(Request $request, DoctorShareRule $rule): JsonResponse|RedirectResponse
    {
        $rule->is_active = ! $rule->is_active;
        $rule->save();

        // DoctorShareRule uses the Auditable trait — audit log is written automatically on save

        if ($request->expectsJson()) {
            return response()->json(['active' => $rule->is_active]);
        }

        $status = $rule->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "Share rule {$status} successfully.");
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

        return view('admin.doctor-share.reports.index', compact('summary', 'details', 'doctors'));
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
            $baseQuery->whereHas('bill', function ($q) use ($request) {
                $q->where('bill_type', $request->bill_type);
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
        $summary = $summaryRows->map(function ($row) {
            $collected = DB::connection('tenant')
                ->table('doctor_share_allocations')
                ->whereIn(
                    'doctor_share_item_id',
                    DoctorShareItem::where('doctor_id', $row->doctor_id)->select('id')
                )
                ->sum('amount');

            $row->total_collected = $collected;

            return $row;
        });

        // Detail list
        $details = $paginate
            ? (clone $baseQuery)->paginate(25)->withQueryString()
            : (clone $baseQuery)->get();

        return [$summary, $details, $doctors];
    }

    private function validateRuleRequest(Request $request): array
    {
        $validated = $request->validate([
            'doctor_id'        => ['nullable', Rule::exists(Doctor::class, 'id')],
            'service_ids'      => ['nullable', 'array'],
            'service_ids.*'    => [Rule::exists(Service::class, 'id')],
            'investigation_id' => ['nullable', Rule::exists(Investigation::class, 'id')],
            'share_type'       => ['required', 'in:percentage,fixed'],
            'share_value'      => ['required', 'numeric', 'min:0.01'],
            'applies_to'       => ['required', 'in:opd,ipd,investigation,emergency,all'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['share_type'] === 'percentage' && $validated['share_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'share_value' => 'Share value cannot exceed 100 for percentage type.',
            ]);
        }

        $validated['service_ids'] = collect($validated['service_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $validated['doctor_id'] = $validated['doctor_id'] ?? null;
        $validated['investigation_id'] = $validated['investigation_id'] ?? null;

        return $validated;
    }

    private function validateRuleScope(array $validated, ?int $excludeRuleId = null): ?string
    {
        $doctorId = $validated['doctor_id'] ?? null;
        $investigationId = $validated['investigation_id'] ?? null;
        $serviceIds = $validated['service_ids'];
        $appliesTo = $validated['applies_to'];

        if ($investigationId && $serviceIds !== []) {
            return 'Select either specific services or a specific investigation, not both.';
        }

        if ($serviceIds === [] && ! $investigationId) {
            $exists = DoctorShareRule::query()
                ->where('doctor_id', $doctorId)
                ->whereNull('investigation_id')
                ->where('applies_to', $appliesTo)
                ->whereDoesntHave('services')
                ->when($excludeRuleId, fn ($query) => $query->where('id', '!=', $excludeRuleId))
                ->exists();

            if ($exists) {
                return 'A default rule with this doctor and bill type already exists.';
            }

            return null;
        }

        if ($investigationId) {
            $exists = DoctorShareRule::query()
                ->where('doctor_id', $doctorId)
                ->where('investigation_id', $investigationId)
                ->where('applies_to', $appliesTo)
                ->when($excludeRuleId, fn ($query) => $query->where('id', '!=', $excludeRuleId))
                ->exists();

            if ($exists) {
                return 'A rule for this investigation already exists for the selected doctor and bill type.';
            }

            return null;
        }

        $overlap = DoctorShareRule::query()
            ->where('doctor_id', $doctorId)
            ->where('applies_to', $appliesTo)
            ->whereNull('investigation_id')
            ->when($excludeRuleId, fn ($query) => $query->where('id', '!=', $excludeRuleId))
            ->whereHas('services', fn ($query) => $query->whereIn('services.id', $serviceIds))
            ->exists();

        if ($overlap) {
            return 'One or more selected services already belong to another rule for this doctor and bill type.';
        }

        return null;
    }
}
