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

        if (self::hasParentAccess($user, $method)) {
            return true;
        }

        return $user->can(SettingsSectionRegistry::permissionName($sectionKey));
    }

    public static function canAccessAnySection(Authenticatable $user): bool
    {
        if (self::hasParentAccess($user, 'GET') || self::hasParentAccess($user, 'POST')) {
            return true;
        }

        foreach (SettingsSectionRegistry::childKeys() as $key) {
            if ($user->can(SettingsSectionRegistry::permissionName($key))) {
                return true;
            }
        }

        return false;
    }

    private static function hasParentAccess(Authenticatable $user, string $method): bool
    {
        if ($user->can('access settings') || $user->can('manage settings')) {
            return true;
        }

        if (in_array($method, ['GET', 'HEAD'], true) && $user->can('view settings')) {
            return true;
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $user->can('edit settings')) {
            return true;
        }

        return false;
    }
}
