<?php

namespace App\Support;

use App\Models\Setting;

final class LabReportPrintSettings
{
    public const SETTING_KEY = 'lab_report_print';

    /** @var array<string, bool> */
    public const DEFAULTS = [
        'show_logo' => true,
        'show_qr' => true,
        'show_hospital_address' => true,
        'show_hospital_phone' => true,
        'show_hospital_email' => true,
        'show_hospital_website' => true,
        'show_patient_band' => true,
        'show_reviewers' => true,
        'show_page_numbers' => true,
    ];

    /** @return array<string, bool> */
    public static function get(): array
    {
        $raw = Setting::get(self::SETTING_KEY);
        $decoded = is_string($raw) ? json_decode($raw, true) : [];
        if (! is_array($decoded)) {
            $decoded = [];
        }

        $toggles = self::DEFAULTS;
        foreach (self::DEFAULTS as $key => $default) {
            if (array_key_exists($key, $decoded)) {
                $toggles[$key] = (bool) $decoded[$key];
            }
        }

        return $toggles;
    }

    /** @param  array<string, mixed>  $toggles */
    public static function put(array $toggles): void
    {
        $normalized = [];
        foreach (self::DEFAULTS as $key => $default) {
            $normalized[$key] = array_key_exists($key, $toggles)
                ? filter_var($toggles[$key], FILTER_VALIDATE_BOOLEAN)
                : false;
        }

        Setting::set(self::SETTING_KEY, json_encode($normalized));
    }
}
