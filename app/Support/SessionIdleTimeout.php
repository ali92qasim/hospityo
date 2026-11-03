<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

class SessionIdleTimeout
{
    public static function minutes(): int
    {
        $min = (int) config('security.idle_timeout_min', 5);
        $max = (int) config('security.idle_timeout_max', 60);
        $default = self::clamp((int) config('security.idle_timeout_minutes', 30), $min, $max);

        try {
            $override = Setting::get('session_idle_timeout_minutes');
        } catch (Throwable) {
            return $default;
        }

        if ($override === null || $override === '') {
            return $default;
        }

        if (! is_numeric($override)) {
            return $default;
        }

        $minutes = (int) $override;

        if ($minutes < $min || $minutes > $max) {
            return $default;
        }

        return $minutes;
    }

    public static function warningSeconds(): int
    {
        $seconds = (int) config('security.idle_warning_seconds', 60);

        return max(10, $seconds);
    }

    public static function seconds(): int
    {
        return self::minutes() * 60;
    }

    private static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
