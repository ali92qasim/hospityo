<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabTestRequest;
use App\Http\Requests\UpdateLabTestRequest;
use App\Models\Investigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvestigationController extends Controller
{
    public function index(Request $request)
    {
        $query = Investigation::query();

        if ($request->category) {
            $query->byCategory($request->category);
        }

        if ($request->type) {
            $query->byType($request->type);
        }

        if ($request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('code', 'like', $searchTerm);
            });
        }

        $investigations = $query->latest()->paginate(15);
        $tests = $investigations; // Backward compatibility for views
        return view('admin.lab.tests.index', compact('investigations', 'tests'));
    }

    public function create()
    {
        return view('admin.lab.tests.create');
    }

    public function store(StoreLabTestRequest $request)
    {
        $investigation = Investigation::create($request->validated());

        // Handle parameters
        if ($request->has('parameters')) {
            foreach ($request->parameters as $index => $paramData) {
                if (!empty($paramData['name'])) {
                    $investigation->parameters()->create([
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

        return redirect()->route('investigations.index')->with('success', 'Investigation created successfully.');
    }

    public function show(Investigation $investigation)
    {
        $investigation->load('parameters');
        $labTest = $investigation; // Backward compatibility for views
        return view('admin.lab.tests.show', compact('investigation', 'labTest'));
    }

    public function edit(Investigation $investigation)
    {
        $investigation->load('parameters');
        $labTest = $investigation; // Backward compatibility for views
        return view('admin.lab.tests.edit', compact('investigation', 'labTest'));
    }

    public function update(UpdateLabTestRequest $request, Investigation $investigation)
    {
        $investigation->update($request->validated());

        // Handle parameters
        if ($request->has('parameters')) {
            // Delete existing parameters
            $investigation->parameters()->delete();

            // Create new parameters
            foreach ($request->parameters as $index => $paramData) {
                if (!empty($paramData['name'])) {
                    $investigation->parameters()->create([
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

        return redirect()->route('investigations.index')->with('success', 'Investigation updated successfully.');
    }

    public function destroy(Investigation $investigation)
    {
        $investigation->delete();
        return redirect()->route('investigations.index')->with('success', 'Investigation deleted successfully.');
    }
}
