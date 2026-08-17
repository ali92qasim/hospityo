<?php

namespace App\Http\Controllers;

use App\Models\ImagingOrder;
use App\Models\ImagingReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RadiologyResultController extends Controller
{
    public function store(Request $request, ImagingOrder $imagingOrder)
    {
        $validated = $request->validate([
            'report_text' => 'nullable|string',
            'impression' => 'nullable|string',
            'report_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'status' => 'required|in:draft,final,amended',
        ]);

        $filePath = null;
        if ($request->hasFile('report_file')) {
            $filePath = $request->file('report_file')->store(tenant_storage_path('radiology-reports'), 'public');
        }

        $result = ImagingReport::create([
            'imaging_order_id' => $imagingOrder->id,
            'report_text' => $validated['report_text'] ?? null,
            'impression' => $validated['impression'] ?? null,
            'file_path' => $filePath,
            'status' => $validated['status'],
            'radiologist_id' => auth()->id(),
            'reported_at' => $validated['status'] === 'final' ? now() : null,
        ]);

        $imagingOrder->update([
            'status' => 'reported',
            'completed_at' => now(),
        ]);
        $imagingOrder->items()->update(['status' => 'reported']);

        return redirect()->route('radiology-results.show', $result)
            ->with('success', 'Imaging report created successfully.');
    }

    public function show(ImagingReport $radiologyResult)
    {
        $radiologyResult->load([
            'imagingOrder.patient',
            'imagingOrder.items.imagingStudy',
            'imagingOrder.visit',
            'imagingOrder.doctor',
            'radiologist',
        ]);

        if ($radiologyResult->relationLoaded('imagingOrder')) {
            $radiologyResult->setRelation('investigationOrder', $radiologyResult->imagingOrder);
        }

        return view('admin.radiology.results.show', compact('radiologyResult'));
    }

    public function update(Request $request, ImagingReport $radiologyResult)
    {
        $validated = $request->validate([
            'report_text' => 'nullable|string',
            'impression' => 'nullable|string',
            'report_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'status' => 'required|in:draft,final,amended',
        ]);

        if ($request->hasFile('report_file')) {
            if ($radiologyResult->file_path) {
                Storage::disk('public')->delete($radiologyResult->file_path);
            }
            $validated['file_path'] = $request->file('report_file')->store(tenant_storage_path('radiology-reports'), 'public');
        }

        if ($validated['status'] === 'final' && $radiologyResult->status !== 'final') {
            $validated['reported_at'] = now();
        }

        $radiologyResult->update($validated);

        return redirect()->route('radiology-results.show', $radiologyResult)
            ->with('success', 'Imaging report updated successfully.');
    }

    public function index(Request $request)
    {
        return redirect()
            ->route('imaging.reports.index')
            ->with('info', 'Radiology results have moved to Imaging Reports.');
    }

    public function indexImaging(Request $request)
    {
        $query = ImagingReport::with([
            'imagingOrder.patient',
            'imagingOrder.items.imagingStudy',
            'radiologist',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $results = $query->latest()->paginate(15);

        return view('admin.imaging.reports.index', compact('results'));
    }

    public function create(ImagingOrder $imagingOrder)
    {
        $imagingOrder->load(['patient', 'visit', 'items.imagingStudy']);

        $investigationOrder = $imagingOrder;

        return view('admin.radiology.results.create', compact('investigationOrder'));
    }

    public function edit(ImagingReport $radiologyResult)
    {
        $radiologyResult->load([
            'imagingOrder.patient',
            'imagingOrder.items.imagingStudy',
            'imagingOrder.visit',
        ]);

        if ($radiologyResult->relationLoaded('imagingOrder')) {
            $radiologyResult->setRelation('investigationOrder', $radiologyResult->imagingOrder);
        }

        return view('admin.radiology.results.edit', compact('radiologyResult'));
    }

    public function destroy(ImagingReport $radiologyResult)
    {
        if ($radiologyResult->file_path) {
            Storage::disk('public')->delete($radiologyResult->file_path);
        }

        $radiologyResult->delete();

        return redirect()->route('imaging.reports.index')
            ->with('success', 'Imaging report deleted successfully.');
    }
}
