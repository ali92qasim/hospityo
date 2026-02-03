<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Http\Request;

class BedController extends Controller
{
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bed_number' => 'required|string|max:255',
            'ward_id' => 'required|exists:wards,id',
            'bed_type' => 'required|in:general,private,icu,emergency',
            'daily_rate' => 'required|numeric|min:0',
            'status' => 'required|in:available,occupied,maintenance'
        ]);

        Bed::create($validated);

        return redirect()->route('beds.index')->with('success', 'Bed created successfully.');
    }

    public function edit(Bed $bed)
    {
        $wards = Ward::where('status', 'active')->get();
        return view('admin.beds.edit', compact('bed', 'wards'));
    }

    public function update(Request $request, Bed $bed)
    {
        $validated = $request->validate([
            'bed_number' => 'required|string|max:255',
            'ward_id' => 'required|exists:wards,id',
            'bed_type' => 'required|in:general,private,icu,emergency',
            'daily_rate' => 'required|numeric|min:0',
            'status' => 'required|in:available,occupied,maintenance'
        ]);

        $bed->update($validated);

        return redirect()->route('beds.index')->with('success', 'Bed updated successfully.');
    }

    public function destroy(Bed $bed)
    {
        $bed->delete();
        return redirect()->route('beds.index')->with('success', 'Bed deleted successfully.');
    }
}