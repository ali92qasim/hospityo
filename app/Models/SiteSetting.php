<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $connection = 'landlord';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, string $default = ''): string
    {
        return Cache::remember("site_setting.{$key}", 3600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("site_setting.{$key}");
    }

    public static function getAll(): array
    {
        return Cache::remember('site_settings.all', 3600, function () {
            $settings = static::pluck('value', 'key')->toArray();
            // Don't cache empty results — allows retry after seeding
            if (empty($settings)) {
                Cache::forget('site_settings.all');
            }
            return $settings;
        });
    }

    public static function clearCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("site_setting.{$key}");
        }
        Cache::forget('site_settings.all');
    }
}
