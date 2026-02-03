<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function update(Request $request)
    {
        $request->validate([
            'hospital_name' => 'required|string|max:255',
            'hospital_address' => 'required|string',
            'hospital_phone' => 'required|string|max:20',
            'hospital_email' => 'required|email|max:255',
            'hospital_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'currency' => 'required|string|max:10',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'time_format' => 'required|string'
        ]);

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