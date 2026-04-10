<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    private array $fields = [
        'office_address' => 'Office Address',
        'office_phone' => 'Phone Number',
        'office_email' => 'Email Address',
        'office_city' => 'City',
        'office_country' => 'Country',
        'office_hours' => 'Office Hours',
        'facebook_url' => 'Facebook URL',
        'twitter_url' => 'Twitter / X URL',
        'linkedin_url' => 'LinkedIn URL',
        'whatsapp_number' => 'WhatsApp Number',
    ];

    public function edit()
    {
        $settings = SiteSetting::getAll();
        $fields = $this->fields;
        return view('super-admin.site-settings', compact('settings', 'fields'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate(
            collect($this->fields)->mapWithKeys(fn($label, $key) => [$key => 'nullable|string|max:500'])->toArray()
        );

        foreach ($this->fields as $key => $label) {
            SiteSetting::set($key, $validated[$key] ?? null);
        }

        SiteSetting::clearCache();

        return back()->with('success', 'Site settings updated successfully.');
    }
}
