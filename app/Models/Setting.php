<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

/**
 * Persistent key-value store for tenant settings.
 * Uses the tenant DB (not cache) as the source of truth,
 * with a cache layer for performance.
 */
class Setting extends Model
{
    use UsesTenantConnection;

    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value. Reads from cache first, falls back to DB.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'settings.' . $key;

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $record = static::where('key', $key)->first();
            return $record ? $record->value : $default;
        });
    }

    /**
     * Set a setting value. Writes to DB and updates cache.
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::put('settings.' . $key, $value, 3600);
    }

    /**
     * Get all settings as a key-value array.
     */
    public static function getAll(): array
    {
        return Cache::remember('tenant_settings.all', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear all cached settings (call after bulk updates).
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget('settings.' . $key);
        }
        Cache::forget('tenant_settings.all');
    }
}
