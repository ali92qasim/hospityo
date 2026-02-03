<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabTestRequest;
use App\Http\Requests\UpdateLabTestRequest;
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

    public function store(StoreLabTestRequest $request)
    {
        LabTest::create($request->validated());
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

    public function update(UpdateLabTestRequest $request, LabTest $labTest)
    {
        $labTest->update($request->validated());
        return redirect()->route('lab-tests.index')->with('success', 'Lab test updated successfully.');
    }

    public function destroy(LabTest $labTest)
    {
        $labTest->delete();
        return redirect()->route('lab-tests.index')->with('success', 'Lab test deleted successfully.');
    }
}