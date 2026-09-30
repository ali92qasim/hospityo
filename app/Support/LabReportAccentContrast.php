<?php

namespace App\Support;

final class LabReportAccentContrast
{
    public static function ratioAgainstWhite(string $hex): float
    {
        $hex = strtoupper(trim($hex));
        if (! preg_match('/^#[0-9A-F]{6}$/', $hex)) {
            return 0.0;
        }

        $r = hexdec(substr($hex, 1, 2)) / 255;
        $g = hexdec(substr($hex, 3, 2)) / 255;
        $b = hexdec(substr($hex, 5, 2)) / 255;
        $L = self::channel($r) * 0.2126 + self::channel($g) * 0.7152 + self::channel($b) * 0.0722;
        $Lwhite = 1.0;
        $lighter = max($L, $Lwhite);
        $darker = min($L, $Lwhite);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public static function failsMinimum(string $hex, float $min = 3.0): bool
    {
        return self::ratioAgainstWhite($hex) < $min;
    }

    private static function channel(float $c): float
    {
        return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }
}
