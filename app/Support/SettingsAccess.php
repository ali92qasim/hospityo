<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;

final class SettingsAccess
{
    public static function canAccessSection(Authenticatable $user, string $sectionKey, ?string $httpMethod = null): bool
    {
        if (SettingsSectionRegistry::get($sectionKey) === null) {
            return false;
        }

        $method = strtoupper($httpMethod ?? request()?->method() ?? 'GET');

        if ($sectionKey === 'settings.hospital-info') {
            if (in_array($method, ['GET', 'HEAD'], true)) {
                return $user->can('access settings.hospital-info') || $user->can('view settings');
            }

            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return $user->can('access settings.hospital-info') || $user->can('edit settings');
            }

            return false;
        }

        if ($sectionKey === 'settings.prescription-print') {
            return $user->can('access settings.prescription-print');
        }

        return false;
    }

    public static function canAccessAnySection(Authenticatable $user): bool
    {
        if ($user->can('access settings') || $user->can('manage settings')) {
            return true;
        }

        foreach (SettingsSectionRegistry::childKeys() as $key) {
            if (self::canAccessSection($user, $key, 'GET')) {
                return true;
            }
        }

        return false;
    }
}
