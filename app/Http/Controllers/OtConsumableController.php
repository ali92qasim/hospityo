<?php

namespace App\Http\Controllers;

use App\Models\OtConsumable;
use App\Models\OtConsumableStockIn;
use App\Models\OtConsumableUsage;
use App\Models\Surgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OtConsumableController extends Controller
{
    // ── Consumable Catalog ─────────────────────────────────────────────────────

    public function index(Request $request)
    {
        try {
            $query = OtConsumable::query();

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            if ($request->has('low_stock')) {
                $query->belowReorderLevel();
            }

            $consumables = $query->orderBy('name')->paginate(20)->withQueryString();
            $lowStockCount = OtConsumable::active()->belowReorderLevel()->count();

            return view('admin.ot.consumables.index', compact('consumables', 'lowStockCount'));
        } catch (\Throwable $e) {
            Log::error('[OT Consumables] Index failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load consumables.');
        }
    }

    public function create()
    {
        return view('admin.ot.consumables.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'sku'                      => 'nullable|string|max:100|unique:tenant.ot_consumables,sku',
            'category'                 => 'required|in:instrument,implant,disposable,suture,drape,other',
            'unit'                     => 'required|string|max:20',
            'reorder_level'            => 'required|integer|min:0',
            'unit_cost'                => 'nullable|numeric|min:0',
            'supplier_name'            => 'nullable|string|max:255',
            'is_reusable'              => 'boolean',
            'requires_serial_tracking' => 'boolean',
            'notes'                    => 'nullable|string|max:1000',
        ]);

        try {
            OtConsumable::create(array_merge($validated, [
                'current_stock' => 0,
                'is_active'     => true,
            ]));
        } catch (\Throwable $e) {
            Log::error('[OT Consumables] Store failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create consumable.');
        }

        return redirect()->route('ot.consumables.index')->with('success', 'Consumable created successfully.');
    }

    public function edit(OtConsumable $consumable)
    {
        return view('admin.ot.consumables.edit', compact('consumable'));
    }

    public function update(Request $request, OtConsumable $consumable)
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'sku'                      => 'nullable|string|max:100|unique:tenant.ot_consumables,sku,' . $consumable->id,
            'category'                 => 'required|in:instrument,implant,disposable,suture,drape,other',
            'unit'                     => 'required|string|max:20',
            'reorder_level'            => 'required|integer|min:0',
            'unit_cost'                => 'nullable|numeric|min:0',
            'supplier_name'            => 'nullable|string|max:255',
            'is_reusable'              => 'boolean',
            'requires_serial_tracking' => 'boolean',
            'is_active'                => 'boolean',
            'notes'                    => 'nullable|string|max:1000',
        ]);

        try {
            $consumable->update($validated);
        } catch (\Throwable $e) {
            Log::error('[OT Consumables] Update failed', ['id' => $consumable->id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update consumable.');
        }

        return redirect()->route('ot.consumables.index')->with('success', 'Consumable updated.');
    }

    // ── Stock-In ──────────────────────────────────────────────────────────────

    public function stockIn(OtConsumable $consumable)
    {
        return view('admin.ot.consumables.stock-in', compact('consumable'));
    }

    public function processStockIn(Request $request, OtConsumable $consumable)
    {
        $validated = $request->validate([
            'quantity'      => 'required|integer|min:1',
            'unit_cost'     => 'nullable|numeric|min:0',
            'batch_no'      => 'nullable|string|max:100',
            'expiry_date'   => 'nullable|date',
            'serial_number' => 'nullable|string|max:255',
            'supplier_name' => 'nullable|string|max:255',
            'reference_no'  => 'nullable|string|max:100',
        ]);

        try {
            DB::connection('tenant')->transaction(function () use ($validated, $consumable) {
                OtConsumableStockIn::create([
                    'ot_consumable_id'   => $consumable->id,
                    'quantity'           => $validated['quantity'],
                    'remaining_quantity' => $validated['quantity'],
                    'unit_cost'          => $validated['unit_cost'] ?? 0,
                    'batch_no'           => $validated['batch_no'] ?? null,
                    'expiry_date'        => $validated['expiry_date'] ?? null,
                    'serial_number'      => $validated['serial_number'] ?? null,
                    'supplier_name'      => $validated['supplier_name'] ?? null,
                    'reference_no'       => $validated['reference_no'] ?? null,
                    'created_by'         => auth()->id(),
                ]);

                $consumable->increment('current_stock', $validated['quantity']);
            });
        } catch (\Throwable $e) {
            Log::error('[OT Consumables] Stock-in failed', ['id' => $consumable->id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to record stock-in.');
        }

        return redirect()->route('ot.consumables.index')->with('success', "Stock added: {$validated['quantity']} {$consumable->unit} of {$consumable->name}.");
    }

    // ── Surgery Usage (record consumption) ────────────────────────────────────

    public function usageForm(Surgery $surgery)
    {
        try {
            $surgery->load(['patient', 'doctor', 'consumableUsages.consumable']);
            $consumables = OtConsumable::active()->orderBy('name')->get();
            return view('admin.ot.consumables.usage', compact('surgery', 'consumables'));
        } catch (\Throwable $e) {
            Log::error('[OT Consumables] Usage form failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load usage form.');
        }
    }

    public function recordUsage(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.consumable_id'=> 'required|exists:tenant.ot_consumables,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.serial_number'=> 'nullable|string|max:255',
            'items.*.notes'        => 'nullable|string|max:500',
        ]);

        try {
            DB::connection('tenant')->transaction(function () use ($validated, $surgery) {
                foreach ($validated['items'] as $item) {
                    $consumable = OtConsumable::findOrFail($item['consumable_id']);

                    // FIFO consumption (skip for reusable instruments — they're just logged, not deducted)
                    $stockInId = null;
                    if (!$consumable->is_reusable) {
                        if ($consumable->current_stock < $item['quantity']) {
                            throw new \Exception("Insufficient stock for {$consumable->name}. Available: {$consumable->current_stock}");
                        }
                        $stockInId = $consumable->consumeFifo($item['quantity']);
                    }

                    OtConsumableUsage::create([
                        'surgery_id'       => $surgery->id,
                        'ot_consumable_id' => $consumable->id,
                        'stock_in_id'      => $stockInId,
                        'quantity_used'    => $item['quantity'],
                        'serial_number'    => $item['serial_number'] ?? null,
                        'notes'            => $item['notes'] ?? null,
                        'recorded_by'      => auth()->id(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('[OT Consumables] Record usage failed', ['surgery_id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('ot.surgeries.show', $surgery)
            ->with('success', 'Consumable usage recorded.');
    }

    // ── Reorder Alerts ────────────────────────────────────────────────────────

    public function reorderAlerts()
    {
        try {
            $lowStock = OtConsumable::active()
                ->belowReorderLevel()
                ->orderBy('current_stock')
                ->get();

            return view('admin.ot.consumables.reorder-alerts', compact('lowStock'));
        } catch (\Throwable $e) {
            Log::error('[OT Consumables] Reorder alerts failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load reorder alerts.');
        }
    }
}
