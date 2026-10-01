<?php

namespace App\Support;

use App\Models\Setting;

final class LabReportPrintSettings
{
    public const SETTING_KEY = 'lab_report_print';

    /** @var array<string, bool|int|string> */
    public const DEFAULTS = [
        'show_logo' => true,
        'show_qr' => true,
        'show_hospital_address' => true,
        'show_hospital_phone' => true,
        'show_hospital_email' => true,
        'show_hospital_website' => true,
        'show_phc_registration' => true,
        'show_footer_address' => false,
        'show_footer_phone' => false,
        'show_footer_email' => false,
        'show_footer_website' => false,
        'show_patient_band' => true,
        'show_reviewers' => true,
        'show_page_numbers' => true,
        'previous_values_count' => 3,
        'accent_color' => '#0F766E',
    ];

    /** @return array<string, bool|int|string> */
    public static function get(): array
    {
        $raw = Setting::get(self::SETTING_KEY);
        $decoded = is_string($raw) ? json_decode($raw, true) : [];
        if (! is_array($decoded)) {
            $decoded = [];
        }

        $toggles = self::DEFAULTS;
        foreach (self::DEFAULTS as $key => $default) {
            if (! array_key_exists($key, $decoded)) {
                continue;
            }

            if ($key === 'previous_values_count') {
                $toggles[$key] = max(1, min(5, (int) $decoded[$key]));
                continue;
            }

            if ($key === 'accent_color') {
                $toggles[$key] = self::normalizeAccentColor($decoded[$key]);
                continue;
            }

            $toggles[$key] = (bool) $decoded[$key];
        }

        return $toggles;
    }

    /** @param  array<string, mixed>  $toggles */
    public static function put(array $toggles): void
    {
        $normalized = [];
        foreach (self::DEFAULTS as $key => $default) {
            if ($key === 'previous_values_count') {
                $normalized[$key] = array_key_exists($key, $toggles)
                    ? max(1, min(5, (int) $toggles[$key]))
                    : (int) $default;
                continue;
            }

            if ($key === 'accent_color') {
                $normalized[$key] = array_key_exists($key, $toggles)
                    ? self::normalizeAccentColor($toggles[$key])
                    : (string) $default;
                continue;
            }

            $normalized[$key] = array_key_exists($key, $toggles)
                ? filter_var($toggles[$key], FILTER_VALIDATE_BOOLEAN)
                : false;
        }

        Setting::set(self::SETTING_KEY, json_encode($normalized));
    }

    private static function normalizeAccentColor(mixed $value): string
    {
        if (! is_string($value)) {
            return (string) self::DEFAULTS['accent_color'];
        }

        $value = strtoupper(trim($value));
        if (! preg_match('/^#[0-9A-F]{6}$/', $value)) {
            return (string) self::DEFAULTS['accent_color'];
        }

        return $value;
    }
}
