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

    public function show($id)
    {
        $investigation = Investigation::with('parameters')->findOrFail($id);
        $labTest = $investigation; // Backward compatibility for views
        return view('admin.lab.tests.show', compact('investigation', 'labTest'));
    }

    public function edit($id)
    {
        $investigation = Investigation::with('parameters')->findOrFail($id);
        $labTest = $investigation; // Backward compatibility for views
        return view('admin.lab.tests.edit', compact('investigation', 'labTest'));
    }

    public function update(UpdateLabTestRequest $request, $id)
    {
        $investigation = Investigation::findOrFail($id);
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

    public function destroy($id)
    {
        $investigation = Investigation::findOrFail($id);
        $investigation->delete();
        return redirect()->route('investigations.index')->with('success', 'Investigation deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');

            if (!$handle) {
                return back()->with('error', 'Could not read the file.');
            }

            // Read header row
            $header = fgetcsv($handle);
            if (!$header || !in_array('code', $header) || !in_array('name', $header)) {
                fclose($handle);
                return back()->with('error', 'Invalid file format. Please use the provided template.');
            }

            $header = array_map('trim', $header);
            $created = 0;
            $updated = 0;
            $errors = [];
            $rowNum = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;

                // Skip empty rows
                if (!array_filter($row)) continue;

                // Pad row to match header length
                $row = array_pad($row, count($header), '');
                $data = array_combine($header, $row);

                // Validate required fields
                $code = trim($data['code'] ?? '');
                $name = trim($data['name'] ?? '');

                if (empty($code) || empty($name)) {
                    $errors[] = "Row {$rowNum}: code and name are required.";
                    continue;
                }

                try {
                    // Create or update investigation
                    $investigation = Investigation::updateOrCreate(
                        ['code' => $code],
                        [
                            'name' => $name,
                            'category' => trim($data['category'] ?? 'hematology'),
                            'sample_type' => trim($data['sample_type'] ?? '') ?: null,
                            'price' => (float) ($data['price'] ?? 0),
                            'turnaround_time' => trim($data['turnaround_time'] ?? '') ?: null,
                            'description' => trim($data['description'] ?? '') ?: null,
                            'instructions' => trim($data['instructions'] ?? '') ?: null,
                            'is_active' => true,
                        ]
                    );

                    if ($investigation->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    // Process parameters (param_1 through param_10)
                    $hasParams = false;
                    $params = [];

                    for ($i = 1; $i <= 10; $i++) {
                        $paramName = trim($data["param_{$i}_name"] ?? '');
                        if (empty($paramName)) continue;

                        $hasParams = true;
                        $params[] = [
                            'parameter_name' => $paramName,
                            'unit' => trim($data["param_{$i}_unit"] ?? '') ?: null,
                            'data_type' => 'numeric',
                            'reference_ranges' => !empty(trim($data["param_{$i}_reference_range"] ?? ''))
                                ? ['range' => trim($data["param_{$i}_reference_range"])]
                                : null,
                            'display_order' => $i,
                            'is_active' => true,
                        ];
                    }

                    // Replace parameters if any were provided
                    if ($hasParams) {
                        $investigation->parameters()->delete();
                        foreach ($params as $param) {
                            $investigation->parameters()->create($param);
                        }
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Row {$rowNum} ({$code}): {$e->getMessage()}";
                }
            }

            fclose($handle);

            $message = "{$created} investigations created, {$updated} updated.";
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' row(s) had errors.';
                Log::warning('[Investigation Import] Errors', ['errors' => $errors]);
            }

            return back()->with('success', $message)
                         ->with('import_errors', $errors);
        } catch (\Throwable $e) {
            Log::error('[Investigation Import] Failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
