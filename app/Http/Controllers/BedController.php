<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RedirectsAfterWrite;
use App\Http\Requests\StoreBedRequest;
use App\Http\Requests\UpdateBedRequest;
use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BedController extends Controller
{
    use RedirectsAfterWrite;

    public function index()
    {
        $beds = Bed::with('ward')->latest()->paginate(10);
        return view('admin.beds.index', compact('beds'));
    }

    public function create()
    {
        $wards = Ward::where('status', 'active')->get();
        return view('admin.beds.create', compact('wards'));
    }

    public function store(StoreBedRequest $request)
    {
        Bed::create($request->validated());

        return $this->redirectAfterWrite(
            'beds.index',
            [],
            ['view beds'],
            'Bed created successfully.',
            'beds.create',
        );
    }

    public function edit(Bed $bed)
    {
        $wards = Ward::where('status', 'active')->get();
        return view('admin.beds.edit', compact('bed', 'wards'));
    }

    public function update(UpdateBedRequest $request, Bed $bed)
    {
        $bed->update($request->validated());

        return $this->redirectAfterWrite(
            'beds.index',
            [],
            ['view beds'],
            'Bed updated successfully.',
            'beds.edit',
            ['bed' => $bed],
        );
    }

    public function destroy(Bed $bed)
    {
        $bed->delete();

        return $this->redirectAfterWrite(
            'beds.index',
            [],
            ['view beds'],
            'Bed deleted successfully.',
        );
    }

    /**
     * Lightweight status toggle for free beds (available ↔ maintenance).
     * Occupancy remains admit/discharge's job; full edit form is unchanged.
     */
    public function updateStatus(Request $request, Bed $bed): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:available,occupied,maintenance',
        ]);

        $requested = $validated['status'];

        if ($requested === 'occupied') {
            return back()->with('error', 'Occupied status can only be set by admitting a patient.');
        }

        if ($bed->status === 'occupied') {
            return back()->with('error', 'Cannot change status while the bed is occupied.');
        }

        if ($bed->admissions()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot change status while the bed has an active admission.');
        }

        if (! in_array($bed->status, ['available', 'maintenance'], true)
            || ! in_array($requested, ['available', 'maintenance'], true)) {
            return back()->with('error', 'Only available and maintenance statuses can be set from this action.');
        }

        if ($bed->status !== $requested) {
            $bed->update(['status' => $requested]);
        }

        return $this->redirectAfterWrite(
            'beds.index',
            [],
            ['view beds'],
            'Bed status updated successfully.',
        );
    }
}
