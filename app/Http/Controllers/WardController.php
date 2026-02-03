<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWardRequest;
use App\Http\Requests\UpdateWardRequest;
use App\Models\Ward;
use App\Models\Department;

class WardController extends Controller
{
    public function index()
    {
        $wards = Ward::with('department')->latest()->paginate(10);
        return view('admin.wards.index', compact('wards'));
    }

    public function create()
    {
        $departments = Department::where('status', 'active')->get();
        return view('admin.wards.create', compact('departments'));
    }

    public function store(StoreWardRequest $request)
    {
        Ward::create($request->validated());

        return redirect()->route('wards.index')->with('success', 'Ward created successfully.');
    }

    public function edit(Ward $ward)
    {
        $departments = Department::where('status', 'active')->get();
        return view('admin.wards.edit', compact('ward', 'departments'));
    }

    public function update(UpdateWardRequest $request, Ward $ward)
    {
        $ward->update($request->validated());

        return redirect()->route('wards.index')->with('success', 'Ward updated successfully.');
    }

    public function destroy(Ward $ward)
    {
        $ward->delete();
        return redirect()->route('wards.index')->with('success', 'Ward deleted successfully.');
    }
}