<?php

use App\Support\SettingsSectionRegistry;

it('registers a settings parent and three children in order', function () {
    $parent = SettingsSectionRegistry::parent();
    $children = SettingsSectionRegistry::children();

    expect($parent['key'])->toBe('settings')
        ->and($parent['parentKey'])->toBeNull()
        ->and($parent['route'])->toBeNull()
        ->and($children)->toHaveCount(3)
        ->and($children[0]['key'])->toBe('settings.hospital-info')
        ->and($children[0]['route'])->toBe('settings.hospital-info')
        ->and($children[1]['key'])->toBe('settings.prescription-print')
        ->and($children[1]['route'])->toBe('settings.prescription-print-templates.index')
        ->and($children[2]['key'])->toBe('settings.doctor-share')
        ->and($children[2]['label'])->toBe('Doctor Share')
        ->and($children[2]['order'])->toBe(30)
        ->and($children[2]['route'])->toBe('doctor-share.rates.index')
        ->and($children[2]['view'])->toBe('doctor-share.rates.index');
});

it('maps catalog keys to access permission names', function () {
    expect(SettingsSectionRegistry::permissionName('settings'))->toBe('access settings')
        ->and(SettingsSectionRegistry::permissionName('settings.hospital-info'))
        ->toBe('access settings.hospital-info')
        ->and(SettingsSectionRegistry::permissionName('settings.prescription-print'))
        ->toBe('access settings.prescription-print')
        ->and(SettingsSectionRegistry::permissionName('settings.doctor-share'))
        ->toBe('access settings.doctor-share');
});
