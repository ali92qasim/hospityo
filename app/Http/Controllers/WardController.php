<?php

namespace App\Http\Controllers;

use App\Models\Ward;
use App\Models\Department;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'capacity' => 'required|integer|min:1',
            'ward_type' => 'required|in:general,private,icu,emergency',
            'status' => 'required|in:active,inactive'
        ]);

        Ward::create($validated);

        return redirect()->route('wards.index')->with('success', 'Ward created successfully.');
    }

    public function edit(Ward $ward)
    {
        $departments = Department::where('status', 'active')->get();
        return view('admin.wards.edit', compact('ward', 'departments'));
    }

    public function update(Request $request, Ward $ward)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'capacity' => 'required|integer|min:1',
            'ward_type' => 'required|in:general,private,icu,emergency',
            'status' => 'required|in:active,inactive'
        ]);

        $ward->update($validated);

        return redirect()->route('wards.index')->with('success', 'Ward updated successfully.');
    }

    public function destroy(Ward $ward)
    {
        $ward->delete();
        return redirect()->route('wards.index')->with('success', 'Ward deleted successfully.');
    }
}