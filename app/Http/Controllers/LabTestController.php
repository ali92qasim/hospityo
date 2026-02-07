<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabTestRequest;
use App\Http\Requests\UpdateLabTestRequest;
use App\Models\LabTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LabTestController extends Controller
{
    public function index(Request $request)
    {
        $query = LabTest::query();

        if ($request->category) {
            $query->byCategory($request->category);
        }

        if ($request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('code', 'like', $searchTerm);
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
        $labTest = LabTest::create($request->validated());

        // Handle parameters
        if ($request->has('parameters')) {
            foreach ($request->parameters as $index => $paramData) {
                if (!empty($paramData['name'])) {
                    $labTest->parameters()->create([
                        'parameter_name' => $paramData['name'],
                        'unit' => $paramData['unit'] ?? null,
                        'data_type' => 'numeric',
                        'reference_ranges' => !empty($paramData['reference_range']) ? ['range' => $paramData['reference_range']] : null,
                        'display_order' => $index + 1,
                        'is_active' => true
                    ]);
                }
            }
        }

        return redirect()->route('lab-tests.index')->with('success', 'Lab test created successfully.');
    }

    public function show(LabTest $labTest)
    {
        $labTest->load('parameters');
        return view('admin.lab.tests.show', compact('labTest'));
    }

    public function edit(LabTest $labTest)
    {
        $labTest->load('parameters');
        return view('admin.lab.tests.edit', compact('labTest'));
    }

    public function update(UpdateLabTestRequest $request, LabTest $labTest)
    {
        $labTest->update($request->validated());

        // Handle parameters
        if ($request->has('parameters')) {
            // Delete existing parameters
            $labTest->parameters()->delete();

            // Create new parameters
            foreach ($request->parameters as $index => $paramData) {
                if (!empty($paramData['name'])) {
                    $labTest->parameters()->create([
                        'parameter_name' => $paramData['name'],
                        'unit' => $paramData['unit'] ?? null,
                        'data_type' => 'numeric',
                        'reference_ranges' => !empty($paramData['reference_range']) ? ['range' => $paramData['reference_range']] : null,
                        'display_order' => $index + 1,
                        'is_active' => true
                    ]);
                }
            }
        }

        return redirect()->route('lab-tests.index')->with('success', 'Lab test updated successfully.');
    }

    public function destroy(LabTest $labTest)
    {
        $labTest->delete();
        return redirect()->route('lab-tests.index')->with('success', 'Lab test deleted successfully.');
    }
}
