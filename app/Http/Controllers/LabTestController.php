<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    public function index(Request $request)
    {
        $query = LabTest::query();
        
        if ($request->category) {
            $query->byCategory($request->category);
        }
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }
        
        $tests = $query->latest()->paginate(15);
        return view('admin.lab.tests.index', compact('tests'));
    }

    public function create()
    {
        return view('admin.lab.tests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:lab_tests',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:hematology,biochemistry,microbiology,immunology,pathology,molecular',
            'sample_type' => 'required|in:blood,urine,stool,sputum,csf,tissue,swab,other',
            'price' => 'required|numeric|min:0',
            'turnaround_time' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
            'parameters' => 'nullable|array'
        ]);

        LabTest::create($validated);
        return redirect()->route('lab-tests.index')->with('success', 'Lab test created successfully.');
    }

    public function show(LabTest $labTest)
    {
        return view('admin.lab.tests.show', compact('labTest'));
    }

    public function edit(LabTest $labTest)
    {
        return view('admin.lab.tests.edit', compact('labTest'));
    }

    public function update(Request $request, LabTest $labTest)
    {
        $validated = $request->validate([
            'code' => 'required|unique:lab_tests,code,' . $labTest->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:hematology,biochemistry,microbiology,immunology,pathology,molecular',
            'sample_type' => 'required|in:blood,urine,stool,sputum,csf,tissue,swab,other',
            'price' => 'required|numeric|min:0',
            'turnaround_time' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
            'parameters' => 'nullable|array'
        ]);

        $labTest->update($validated);
        return redirect()->route('lab-tests.index')->with('success', 'Lab test updated successfully.');
    }

    public function destroy(LabTest $labTest)
    {
        $labTest->delete();
        return redirect()->route('lab-tests.index')->with('success', 'Lab test deleted successfully.');
    }
}