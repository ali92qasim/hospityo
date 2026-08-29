<?php

use App\Support\SettingsSectionRegistry;

it('registers a settings parent and two children in order', function () {
    $parent = SettingsSectionRegistry::parent();
    $children = SettingsSectionRegistry::children();

    expect($parent['key'])->toBe('settings')
        ->and($parent['parentKey'])->toBeNull()
        ->and($parent['route'])->toBeNull()
        ->and($children)->toHaveCount(2)
        ->and($children[0]['key'])->toBe('settings.hospital-info')
        ->and($children[0]['route'])->toBe('settings.hospital-info')
        ->and($children[1]['key'])->toBe('settings.prescription-print')
        ->and($children[1]['route'])->toBe('settings.prescription-print-templates.index');
});

it('maps catalog keys to access permission names', function () {
    expect(SettingsSectionRegistry::permissionName('settings'))->toBe('access settings')
        ->and(SettingsSectionRegistry::permissionName('settings.hospital-info'))
        ->toBe('access settings.hospital-info')
        ->and(SettingsSectionRegistry::permissionName('settings.prescription-print'))
        ->toBe('access settings.prescription-print');
});
