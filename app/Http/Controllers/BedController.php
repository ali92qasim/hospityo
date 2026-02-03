<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBedRequest;
use App\Http\Requests\UpdateBedRequest;
use App\Models\Bed;
use App\Models\Ward;

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

    public function store(StoreBedRequest $request)
    {
        Bed::create($request->validated());

        return redirect()->route('beds.index')->with('success', 'Bed created successfully.');
    }

    public function edit(Bed $bed)
    {
        $wards = Ward::where('status', 'active')->get();
        return view('admin.beds.edit', compact('bed', 'wards'));
    }

    public function update(UpdateBedRequest $request, Bed $bed)
    {
        $bed->update($request->validated());

        return redirect()->route('beds.index')->with('success', 'Bed updated successfully.');
    }

    public function destroy(Bed $bed)
    {
        $bed->delete();
        return redirect()->route('beds.index')->with('success', 'Bed deleted successfully.');
    }
}