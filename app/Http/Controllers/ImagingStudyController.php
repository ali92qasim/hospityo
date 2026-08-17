<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImagingStudyRequest;
use App\Http\Requests\UpdateImagingStudyRequest;
use App\Jobs\Tenant\ImportInvestigationsJob;
use App\Models\ImagingStudy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ImagingStudyController extends Controller
{
    public function index()
    {
        return view('admin.imaging.studies.index');
    }

    public function data()
    {
        return DataTables::eloquent(ImagingStudy::query()->orderByDesc('id'))->toJson();
    }

    public function create()
    {
        return view('admin.imaging.studies.create');
    }

    public function store(StoreImagingStudyRequest $request)
    {
        ImagingStudy::create($request->validated());

        return redirect()->route('imaging.studies.index')->with('success', 'Imaging study created successfully.');
    }

    public function show(ImagingStudy $imagingStudy)
    {
        return view('admin.imaging.studies.show', compact('imagingStudy'));
    }

    public function edit(ImagingStudy $imagingStudy)
    {
        return view('admin.imaging.studies.edit', compact('imagingStudy'));
    }

    public function update(UpdateImagingStudyRequest $request, ImagingStudy $imagingStudy)
    {
        $imagingStudy->update($request->safe()->except('parameters'));

        return redirect()->route('imaging.studies.index')->with('success', 'Imaging study updated successfully.');
    }

    public function destroy(ImagingStudy $imagingStudy)
    {
        $imagingStudy->delete();

        return redirect()->route('imaging.studies.index')->with('success', 'Imaging study deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path     = $request->file('file')->store('imports/investigations', 'local');
        $cacheKey = 'investigation_import_'.auth()->id().'_'.Str::random(8);

        Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

        ImportInvestigationsJob::dispatch(
            $path,
            $cacheKey,
            auth()->id(),
            'imaging'
        );

        return redirect()->route('imaging.studies.index')
            ->with('import_pending', true)
            ->with('import_cache_key', $cacheKey);
    }

    public function importStatus(Request $request)
    {
        $key = $request->query('key');

        if (! $key) {
            return response()->json(['status' => 'not_found']);
        }

        $result = Cache::get($key);

        if ($result === null) {
            return response()->json(['status' => 'not_found']);
        }

        if ($result['status'] === 'pending') {
            return response()->json(['status' => 'pending']);
        }

        Cache::forget($key);

        return response()->json($result);
    }
}
