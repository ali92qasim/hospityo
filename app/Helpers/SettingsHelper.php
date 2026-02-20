<?php

if (!function_exists('setting')) {
    /**
     * Get a setting value from cache with fallback to default
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, $default = null)
    {
        return cache("settings.{$key}", $default);
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Get the currency symbol based on the currency code
     *
     * @param string|null $currency
     * @return string
     */
    function currency_symbol(?string $currency = null): string
    {
        $currency = $currency ?? setting('currency', 'PKR');
        
        return match ($currency) {
            'PKR' => '₨',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'AED' => 'د.إ',
            'SAR' => '﷼',
            default => $currency,
        };
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format a number as currency
     *
     * @param float|int $amount
     * @param string|null $currency
     * @return string
     */
    function format_currency($amount, ?string $currency = null): string
    {
        $currency = $currency ?? setting('currency', 'PKR');
        $symbol = currency_symbol($currency);
        
        // Format with 2 decimal places and thousands separator
        $formatted = number_format((float) $amount, 2, '.', ',');
        
        // Position symbol based on currency
        return match ($currency) {
            'EUR' => "{$formatted} {$symbol}",
            default => "{$symbol} {$formatted}",
        };
    }
}

if (!function_exists('app_timezone')) {
    /**
     * Get the application timezone
     *
     * @return string
     */
    function app_timezone(): string
    {
        return setting('timezone', config('app.timezone', 'Asia/Karachi'));
    }
}

if (!function_exists('format_date')) {
    /**
     * Format a date according to system settings
     *
     * @param mixed $date
     * @param string|null $format
     * @return string
     */
    function format_date($date, ?string $format = null): string
    {
        if (!$date) {
            return '';
        }
        
        $format = $format ?? setting('date_format', 'd/m/Y');
        
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        
        if ($date instanceof \Carbon\Carbon) {
            return $date->timezone(app_timezone())->format($format);
        }
        
        return '';
    }
}

if (!function_exists('format_time')) {
    /**
     * Format a time according to system settings
     *
     * @param mixed $time
     * @param string|null $format
     * @return string
     */
    function format_time($time, ?string $format = null): string
    {
        if (!$time) {
            return '';
        }
        
        $format = $format ?? setting('time_format', 'H:i');
        
        if (is_string($time)) {
            $time = \Carbon\Carbon::parse($time);
        }
        
        if ($time instanceof \Carbon\Carbon) {
            return $time->timezone(app_timezone())->format($format);
        }
        
        return '';
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format a datetime according to system settings
     *
     * @param mixed $datetime
     * @param string|null $dateFormat
     * @param string|null $timeFormat
     * @return string
     */
    function format_datetime($datetime, ?string $dateFormat = null, ?string $timeFormat = null): string
    {
        if (!$datetime) {
            return '';
        }
        
        $dateFormat = $dateFormat ?? setting('date_format', 'd/m/Y');
        $timeFormat = $timeFormat ?? setting('time_format', 'H:i');
        
        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }
        
        if ($datetime instanceof \Carbon\Carbon) {
            return $datetime->timezone(app_timezone())->format("{$dateFormat} {$timeFormat}");
        }
        
        return '';
    }
}

if (!function_exists('hospital_name')) {
    /**
     * Get the hospital name
     *
     * @return string
     */
    function hospital_name(): string
    {
        return setting('hospital_name', 'Hospital Management System');
    }
}

if (!function_exists('hospital_info')) {
    /**
     * Get hospital information
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function hospital_info(string $key, $default = null)
    {
        return setting("hospital_{$key}", $default);
    }
}
