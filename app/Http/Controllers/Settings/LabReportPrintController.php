<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLabReportPrintSettingsRequest;
use App\Support\LabReportPrintSettings;

class LabReportPrintController extends Controller
{
    public function edit()
    {
        return view('settings.lab-report-print.edit', [
            'toggles' => LabReportPrintSettings::get(),
        ]);
    }

    public function update(UpdateLabReportPrintSettingsRequest $request)
    {
        LabReportPrintSettings::put($request->validated());

        return redirect()
            ->route('settings.lab-report-print.edit')
            ->with('success', 'Lab report print settings updated.');
    }
}
