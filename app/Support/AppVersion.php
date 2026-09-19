<?php

namespace App\Support;

final class AppVersion
{
    public static function path(): string
    {
        return base_path('VERSION');
    }

    public static function read(): string
    {
        $path = self::path();
        if (! is_file($path)) {
            return '0.0.0';
        }

        $value = trim((string) file_get_contents($path));

        return $value === '' ? '0.0.0' : $value;
    }

    public static function write(string $version): void
    {
        file_put_contents(self::path(), $version."\n");
    }
}
