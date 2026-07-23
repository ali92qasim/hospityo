<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

class OpeningStockService
{
    public const SETTING_IMPORTED = 'opening_stock_imported';

    public const SETTING_IMPORTED_AT = 'opening_stock_imported_at';

    public const SETTING_IMPORTED_BY = 'opening_stock_imported_by';

    public const SETTING_BATCH_COUNT = 'opening_stock_batch_count';

    public const DEFAULT_SUPPLIER = 'Opening Balance';

    public const DEFAULT_NOTES = 'Opening stock import';

    public static function isLocked(): bool
    {
        return Setting::get(self::SETTING_IMPORTED) === '1';
    }

    /**
     * @return array{locked: bool, imported_at: ?string, imported_by: ?string, batch_count: ?int}
     */
    public static function status(): array
    {
        if (! self::isLocked()) {
            return [
                'locked' => false,
                'imported_at' => null,
                'imported_by' => null,
                'batch_count' => null,
            ];
        }

        $userId = Setting::get(self::SETTING_IMPORTED_BY);
        $userName = $userId
            ? User::query()->whereKey($userId)->value('name')
            : null;

        return [
            'locked' => true,
            'imported_at' => Setting::get(self::SETTING_IMPORTED_AT),
            'imported_by' => $userName,
            'batch_count' => (int) (Setting::get(self::SETTING_BATCH_COUNT) ?? 0),
        ];
    }

    public static function lock(int $userId, int $batchCount): void
    {
        Setting::set(self::SETTING_IMPORTED, '1');
        Setting::set(self::SETTING_IMPORTED_AT, now()->toIso8601String());
        Setting::set(self::SETTING_IMPORTED_BY, (string) $userId);
        Setting::set(self::SETTING_BATCH_COUNT, (string) $batchCount);
        Setting::clearCache();
    }

    public static function importedAtFormatted(): ?string
    {
        $value = Setting::get(self::SETTING_IMPORTED_AT);

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('M d, Y g:i A');
        } catch (\Throwable) {
            return $value;
        }
    }
}
