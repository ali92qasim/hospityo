<?php

namespace App\Support;

final class SettingsSectionRegistry
{
    /**
     * @var array<string, array{
     *     key: string,
     *     label: string,
     *     icon: string,
     *     parentKey: string|null,
     *     order: int,
     *     route: string|null,
     *     view: string|null
     * }>
     */
    private static array $sections = [
        'settings' => [
            'key' => 'settings',
            'label' => 'Settings',
            'icon' => 'fa-cog',
            'parentKey' => null,
            'order' => 0,
            'route' => null,
            'view' => null,
        ],
        'settings.hospital-info' => [
            'key' => 'settings.hospital-info',
            'label' => 'Hospital Info',
            'icon' => 'fa-hospital',
            'parentKey' => 'settings',
            'order' => 10,
            'route' => 'settings.hospital-info',
            'view' => 'settings.hospital-info',
        ],
        'settings.prescription-print' => [
            'key' => 'settings.prescription-print',
            'label' => 'Prescription Print Templates',
            'icon' => 'fa-file-medical',
            'parentKey' => 'settings',
            'order' => 20,
            'route' => 'settings.prescription-print-templates.index',
            'view' => 'settings.prescription-print-templates.index',
        ],
    ];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return self::$sections;
    }

    /** @return array<string, mixed> */
    public static function parent(): array
    {
        return self::$sections['settings'];
    }

    /** @return list<array<string, mixed>> */
    public static function children(): array
    {
        $children = array_values(array_filter(
            self::$sections,
            fn (array $section) => $section['parentKey'] === 'settings'
        ));

        usort($children, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return $children;
    }

    /** @return array<string, mixed>|null */
    public static function get(string $key): ?array
    {
        return self::$sections[$key] ?? null;
    }

    public static function permissionName(string $key): string
    {
        return 'access '.$key;
    }

    /** @return list<string> */
    public static function childKeys(): array
    {
        return array_column(self::children(), 'key');
    }
}
