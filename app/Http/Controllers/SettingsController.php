<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;

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
            $logoPath = $request->file('hospital_logo')->store('logos', 'public');
            cache()->put('settings.hospital_logo', $logoPath);
        }

        // Store other settings
        foreach ($request->only(['hospital_name', 'hospital_address', 'hospital_phone', 'hospital_email', 'currency', 'timezone', 'date_format', 'time_format']) as $key => $value) {
            cache()->put("settings.{$key}", $value);
        }

        return redirect()->route('settings.index')->with('success', 'Settings updated successfully');
    }
}