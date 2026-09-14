<?php

namespace App\Support;

use App\Models\ModuleRegistry;
use Illuminate\Contracts\Auth\Authenticatable;

final class SettingsAccess
{
    public static function canAccessSection(Authenticatable $user, string $sectionKey, ?string $httpMethod = null): bool
    {
        $definition = ModuleRegistry::definitions()[$sectionKey] ?? null;
        if ($definition === null || ($definition['parent'] ?? null) !== 'settings') {
            return false;
        }

        $method = strtoupper($httpMethod ?? request()?->method() ?? 'GET');
        $names = PermissionRegistry::forModule($sectionKey);

        if (! empty($definition['bundled'])) {
            $hasChild = self::userCanAny($user, $names);

            if (in_array($method, ['GET', 'HEAD'], true)) {
                return $hasChild || $user->can('view settings');
            }

            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return $hasChild || $user->can('edit settings');
            }

            return false;
        }

        return self::userCanAny($user, $names);
    }

    public static function canAccessAnySection(Authenticatable $user): bool
    {
        foreach (self::settingsChildSlugs() as $key) {
            if (self::canAccessSection($user, $key, 'GET')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function settingsChildSlugs(): array
    {
        $slugs = [];

        foreach (ModuleRegistry::definitions() as $slug => $definition) {
            if (($definition['parent'] ?? null) === 'settings') {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * @param  list<string>  $names
     */
    private static function userCanAny(Authenticatable $user, array $names): bool
    {
        foreach ($names as $name) {
            if ($user->can($name)) {
                return true;
            }
        }

        return false;
    }
}
