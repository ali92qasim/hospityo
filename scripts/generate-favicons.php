<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public';

if (! extension_loaded('gd')) {
    fwrite(STDERR, "PHP GD extension is required.\n");
    exit(1);
}

function cubicPoint(array $p0, array $p1, array $p2, array $p3, float $t): array
{
    $u = 1 - $t;
    $uu = $u * $u;
    $tt = $t * $t;

    return [
        $uu * $u * $p0[0] + 3 * $uu * $t * $p1[0] + 3 * $u * $tt * $p2[0] + $tt * $t * $p3[0],
        $uu * $u * $p0[1] + 3 * $uu * $t * $p1[1] + 3 * $u * $tt * $p2[1] + $tt * $t * $p3[1],
    ];
}

function sampleCubic(array $p0, array $p1, array $p2, array $p3, int $steps = 20): array
{
    $points = [];
    for ($i = 0; $i <= $steps; $i++) {
        $points[] = cubicPoint($p0, $p1, $p2, $p3, $i / $steps);
    }

    return $points;
}

/** Spec U path on the 32×32 canvas, as a closed polygon. */
function uPolygon(): array
{
    $points = [[7.0, 6.0], [12.0, 6.0], [12.0, 19.2]];
    $points = array_merge($points, sampleCubic([12.0, 19.2], [12.0, 21.9], [13.7, 23.5], [16.0, 23.5]));
    $points = array_merge($points, sampleCubic([16.0, 23.5], [18.3, 23.5], [20.0, 21.9], [20.0, 19.2]));
    $points[] = [20.0, 6.0];
    $points[] = [25.0, 6.0];
    $points[] = [25.0, 19.2];
    $points = array_merge($points, sampleCubic([25.0, 19.2], [25.0, 24.4], [21.3, 28.0], [16.0, 28.0]));
    $points = array_merge($points, sampleCubic([16.0, 28.0], [10.7, 28.0], [7.0, 24.4], [7.0, 19.2]));
    $points[] = [7.0, 6.0];

    return $points;
}

function hexColor($image, string $hex): int
{
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    return imagecolorallocate($image, $r, $g, $b);
}

function fillRoundedRect($image, int $size, int $radius, int $color): void
{
    imagefilledrectangle($image, $radius, 0, $size - $radius - 1, $size - 1, $color);
    imagefilledrectangle($image, 0, $radius, $size - 1, $size - $radius - 1, $color);
    imagefilledellipse($image, $radius, $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $size - 1 - $radius, $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $radius, $size - 1 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $size - 1 - $radius, $size - 1 - $radius, $radius * 2, $radius * 2, $color);
}

function renderMark(int $size)
{
    $scale = $size / 32.0;
    $image = imagecreatetruecolor($size, $size);
    imagealphablending($image, true);
    imagesavealpha($image, false);

    $light = hexColor($image, 'F0F8FF');
    $blue = hexColor($image, '0066CC');
    $green = hexColor($image, '00A86B');

    imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, $light);
    fillRoundedRect($image, $size, (int) round(6 * $scale), $light);

    $polygon = [];
    foreach (uPolygon() as [$x, $y]) {
        $polygon[] = (int) round($x * $scale);
        $polygon[] = (int) round($y * $scale);
    }
    imagefilledpolygon($image, $polygon, $blue);

    $cx = (int) round(24.2 * $scale);
    $cy = (int) round(8.2 * $scale);
    $d = (int) max(2, round(2.8 * $scale * 2));
    imagefilledellipse($image, $cx, $cy, $d, $d, $green);

    return $image;
}

function pngBytes($image): string
{
    ob_start();
    imagepng($image);

    return (string) ob_get_clean();
}

function writeIco(string $path, array $imagesBySize): void
{
    $count = count($imagesBySize);
    $offset = 6 + (16 * $count);
    $entries = '';
    $payload = '';

    foreach ($imagesBySize as $size => $bytes) {
        $entries .= pack(
            'C4v2V2',
            $size === 256 ? 0 : $size,
            $size === 256 ? 0 : $size,
            0,
            0,
            1,
            32,
            strlen($bytes),
            $offset
        );
        $payload .= $bytes;
        $offset += strlen($bytes);
    }

    file_put_contents($path, pack('v3', 0, 1, $count) . $entries . $payload);
}

$png180 = renderMark(180);
imagepng($png180, $public . DIRECTORY_SEPARATOR . 'apple-touch-icon.png');

$ico16 = pngBytes(renderMark(16));
$ico32 = pngBytes(renderMark(32));
writeIco($public . DIRECTORY_SEPARATOR . 'favicon.ico', [16 => $ico16, 32 => $ico32]);

echo "Wrote apple-touch-icon.png and favicon.ico\n";
