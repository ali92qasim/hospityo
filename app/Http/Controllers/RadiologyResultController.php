<?php

namespace App\Http\Controllers;

use App\Models\RadiologyResult;
use App\Models\InvestigationOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RadiologyResultController extends Controller
{
    public function store(Request $request, InvestigationOrder $investigationOrder)
    {
        // Validate that this is a radiology investigation
        if (!$investigationOrder->isRadiology()) {
            return back()->withErrors([
                'error' => 'Cannot create radiology result for non-radiology investigation: ' . $investigationOrder->investigation->name
            ]);
        }

        $validated = $request->validate([
            'report_text' => 'nullable|string',
            'impression' => 'nullable|string',
            'report_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'status' => 'required|in:draft,final,amended',
        ]);

        // Handle file upload
        $filePath = null;
        if ($request->hasFile('report_file')) {
            $filePath = $request->file('report_file')->store('radiology-reports', 'public');
        }

        // Create radiology result
        $result = RadiologyResult::create([
            'investigation_order_id' => $investigationOrder->id,
            'report_text' => $validated['report_text'] ?? null,
            'impression' => $validated['impression'] ?? null,
            'file_path' => $filePath,
            'status' => $validated['status'],
            'radiologist_id' => auth()->id(),
            'reported_at' => $validated['status'] === 'final' ? now() : null
        ]);

        // Update investigation order status
        $investigationOrder->update([
            'status' => 'reported',
            'completed_at' => now()
        ]);

        return redirect()->route('radiology-results.show', $result)
            ->with('success', 'Radiology result created successfully.');
    }

    public function show(RadiologyResult $radiologyResult)
    {
        $radiologyResult->load([
            'investigationOrder.patient',
            'investigationOrder.investigation',
            'investigationOrder.visit',
            'investigationOrder.doctor',
            'radiologist'
        ]);

        return view('admin.radiology.results.show', compact('radiologyResult'));
    }

    public function update(Request $request, RadiologyResult $radiologyResult)
    {
        $validated = $request->validate([
            'report_text' => 'nullable|string',
            'impression' => 'nullable|string',
            'report_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'status' => 'required|in:draft,final,amended',
        ]);

        // Handle file upload
        if ($request->hasFile('report_file')) {
            // Delete old file if exists
            if ($radiologyResult->file_path) {
                Storage::disk('public')->delete($radiologyResult->file_path);
            }
            $validated['file_path'] = $request->file('report_file')->store('radiology-reports', 'public');
        }

        // Update reported_at timestamp when status changes to final
        if ($validated['status'] === 'final' && $radiologyResult->status !== 'final') {
            $validated['reported_at'] = now();
        }

        $radiologyResult->update($validated);

        return redirect()->route('radiology-results.show', $radiologyResult)
            ->with('success', 'Radiology result updated successfully.');
    }

    public function index(Request $request)
    {
        $query = RadiologyResult::with([
            'investigationOrder.patient',
            'investigationOrder.investigation',
            'radiologist'
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $results = $query->latest()->paginate(15);

        return view('admin.radiology.results.index', compact('results'));
    }

    public function create(InvestigationOrder $investigationOrder)
    {
        // Validate that this is a radiology investigation
        if (!$investigationOrder->isRadiology()) {
            return redirect()->back()->withErrors([
                'error' => 'Cannot create radiology result for non-radiology investigation: ' . $investigationOrder->investigation->name
            ]);
        }

        $investigationOrder->load(['patient', 'visit', 'investigation']);
        return view('admin.radiology.results.create', compact('investigationOrder'));
    }

    public function edit(RadiologyResult $radiologyResult)
    {
        $radiologyResult->load([
            'investigationOrder.patient',
            'investigationOrder.investigation',
            'investigationOrder.visit'
        ]);

        return view('admin.radiology.results.edit', compact('radiologyResult'));
    }

    public function destroy(RadiologyResult $radiologyResult)
    {
        // Delete associated file if exists
        if ($radiologyResult->file_path) {
            Storage::disk('public')->delete($radiologyResult->file_path);
        }

        $radiologyResult->delete();

        return redirect()->route('radiology-results.index')
            ->with('success', 'Radiology result deleted successfully.');
    }
}
