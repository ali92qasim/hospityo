<?php

namespace App\Support;

final class PrescriptionPrintPaper
{
    /** @return array{0: float, 1: float} width mm, height mm */
    public static function dimensionsMm(string $size, string $orientation): array
    {
        $sizes = [
            'A4' => [210.0, 297.0],
            'Letter' => [215.9, 279.4],
        ];

        if (! isset($sizes[$size])) {
            throw new \InvalidArgumentException("Unsupported paper size [{$size}].");
        }

        [$width, $height] = $sizes[$size];

        return $orientation === 'landscape' ? [$height, $width] : [$width, $height];
    }
}
