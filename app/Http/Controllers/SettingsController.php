<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Models\Tenant;
use App\Support\SettingsAccess;
use App\Support\SettingsSectionRegistry;
use DateTime;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        foreach (SettingsSectionRegistry::children() as $section) {
            if ($section['route'] && SettingsAccess::canAccessSection($user, $section['key'], 'GET') && $this->tenantHasSettingsSection($section['key'])) {
                return redirect()->route($section['route']);
            }
        }

        abort(403);
    }

    public function hospitalInfo()
    {
        return view('settings.hospital-info', [
            'timezones' => $this->timezonesGroupedByRegion(),
        ]);
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

        Setting::set('timezone_auto_set', '1');

        return redirect()->route('settings.index')->with('success', 'Settings updated successfully');
    }

    public function detectTimezone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'timezone'],
        ]);

        if (setting('timezone_auto_set') === '1') {
            return response()->json([
                'updated' => false,
                'timezone' => setting('timezone', $validated['timezone']),
            ]);
        }

        Setting::set('timezone', $validated['timezone']);
        Setting::set('timezone_auto_set', '1');

        if (setting('time_format', 'H:i') === 'H:i') {
            Setting::set('time_format', 'h:i A');
        }

        return response()->json([
            'updated' => true,
            'timezone' => $validated['timezone'],
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function timezonesGroupedByRegion(): array
    {
        $grouped = [];

        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $region = explode('/', $timezone, 2)[0];
            $offset = (new DateTime('now', new DateTimeZone($timezone)))->format('P');
            $grouped[$region][$timezone] = str_replace('_', ' ', $timezone).' (UTC'.$offset.')';
        }

        return $grouped;
    }

    private function tenantHasSettingsSection(string $sectionKey): bool
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return true;
        }

        return $tenant->hasModule('settings') && $tenant->hasModule($sectionKey);
    }
}
