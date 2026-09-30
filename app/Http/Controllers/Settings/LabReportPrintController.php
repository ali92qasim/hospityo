<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLabReportPrintSettingsRequest;
use App\Http\Requests\UpdateLabReportRosterRequest;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabReportRosterDoctor;
use App\Services\LabReportBuilder;
use App\Services\LabReportPreviewFixture;
use App\Support\LabReportPrintSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabReportPrintController extends Controller
{
    public function edit()
    {
        $rosterRows = LabReportRosterDoctor::query()
            ->with('doctor')
            ->orderBy('sort_order')
            ->get();

        $doctors = Doctor::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'qualification']);

        return view('settings.lab-report-print.edit', [
            'toggles' => LabReportPrintSettings::get(),
            'rosterRows' => $rosterRows,
            'doctors' => $doctors,
        ]);
    }

    public function preview(Request $request)
    {
        $order = LabOrder::query()
            ->whereHas('results', function ($query) {
                $query->whereHas('resultItems');
            })
            ->latest('id')
            ->first();

        $report = $order
            ? LabReportBuilder::build($order)
            : LabReportPreviewFixture::report();

        return view('admin.lab.results.report', [
            'report' => $report,
            'isPublic' => true,
            'embed' => true,
        ]);
    }

    public function update(UpdateLabReportPrintSettingsRequest $request)
    {
        LabReportPrintSettings::put($request->validated());

        return redirect()
            ->route('settings.lab-report-print.edit')
            ->with('success', 'Lab report print settings updated.');
    }

    public function updateRoster(UpdateLabReportRosterRequest $request)
    {
        $doctorIds = $request->validated('doctor_ids') ?? [];

        DB::transaction(function () use ($doctorIds) {
            LabReportRosterDoctor::query()->delete();

            foreach ($doctorIds as $index => $doctorId) {
                LabReportRosterDoctor::create([
                    'doctor_id' => $doctorId,
                    'sort_order' => $index,
                ]);
            }
        });

        return redirect()
            ->route('settings.lab-report-print.edit')
            ->with('success', 'Consultant roster updated.');
    }
}
