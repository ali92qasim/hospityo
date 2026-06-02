<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function update(UpdateSettingsRequest $request)
    {
        // Handle logo upload
        if ($request->hasFile('hospital_logo')) {
            $logoPath = $request->file('hospital_logo')->store(tenant_storage_path('logos'), 'public');
            Setting::set('hospital_logo', $logoPath);
        }

        // Store other settings — persisted in DB, cached for performance
        $settingKeys = [
            'hospital_name', 'hospital_address', 'hospital_phone',
            'hospital_email', 'currency', 'timezone',
            'date_format', 'time_format',
        ];

        foreach ($settingKeys as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key));
            }
        }

        return redirect()->route('settings.index')->with('success', 'Settings updated successfully');
    }
}
