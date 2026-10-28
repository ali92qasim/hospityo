<?php

use App\Support\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;

function rolePermissionSeederPermissions(): array
{
    return (new ReflectionClass(RolePermissionSeeder::class))->getConstant('PERMISSIONS');
}

it('lists hr permissions under hr group', function () {
    $groups = PermissionRegistry::grouped();

    expect($groups)->toHaveKey('hr');
});

it('lists patient permissions including restore patients', function () {
    expect(PermissionRegistry::forModule('patients'))->toEqualCanonicalizing([
        'view patients',
        'create patients',
        'edit patients',
        'delete patients',
        'restore patients',
    ]);
});

it('lists doctor permissions including restore doctors and view doctor assignments', function () {
    expect(PermissionRegistry::forModule('doctors'))->toEqualCanonicalizing([
        'view doctors',
        'create doctors',
        'edit doctors',
        'delete doctors',
        'restore doctors',
        'view doctor assignments',
    ]);
});

it('flat list has no duplicate permission strings', function () {
    $flat = PermissionRegistry::flat();

    expect($flat)->toBe(array_values(array_unique($flat)))
        ->and(count($flat))->toBe(count(array_unique($flat)));
});

it('flat returns the same list as all', function () {
    expect(PermissionRegistry::flat())->toBe(PermissionRegistry::all());
});

it('flat list includes every permission from RolePermissionSeeder', function () {
    $seederPermissions = rolePermissionSeederPermissions();
    $registryPermissions = PermissionRegistry::flat();

    foreach ($seederPermissions as $permission) {
        expect($registryPermissions)->toContain($permission);
    }

    expect(count($seederPermissions))->toBeLessThanOrEqual(count($registryPermissions));
});

it('registers unique permission lists for accounting and pharmacy catalog children', function () {
    expect(PermissionRegistry::forModule('accounting.profit-loss'))->toBe(['view profit and loss'])
        ->and(PermissionRegistry::forModule('pharmacy.pos'))->toEqualCanonicalizing(['view pos', 'dispense pharmacy'])
        ->and(PermissionRegistry::forModule('accounting'))->toContain('view chart of accounts')
        ->and(PermissionRegistry::forModule('accounting'))->not->toContain('view profit and loss')
        ->and(PermissionRegistry::forModule('pharmacy'))->toContain('view prescriptions')
        ->and(PermissionRegistry::forModule('pharmacy'))->not->toContain('view pos');

    $flat = PermissionRegistry::flat();
    expect($flat)->toBe(array_values(array_unique($flat)));
});

it('splits settings permission registry so parent forModule excludes child names', function () {
    expect(PermissionRegistry::forModule('settings'))->not->toContain('access settings.prescription-print')
        ->and(PermissionRegistry::forModule('settings'))->not->toContain('access settings.lab-report-print')
        ->and(PermissionRegistry::forModule('settings'))->toEqualCanonicalizing([
            'access settings',
            'manage settings',
            'view settings',
            'edit settings',
            'manage subscription',
        ])
        ->and(PermissionRegistry::forModule('settings.prescription-print'))->toBe(['access settings.prescription-print'])
        ->and(PermissionRegistry::forModule('settings.lab-report-print'))->toBe(['access settings.lab-report-print'])
        ->and(PermissionRegistry::forModule('settings.hospital-info'))->toBe(['access settings.hospital-info']);

    $flat = PermissionRegistry::flat();
    expect($flat)->toBe(array_values(array_unique($flat)));
});

it('keeps doctor share settings access rules-only without duplicating operational permissions', function () {
    expect(PermissionRegistry::forModule('settings.doctor-share'))->toEqualCanonicalizing([
        'manage doctor shares',
        'view share rules',
        'create share rules',
        'edit share rules',
        'delete share rules',
    ])
        ->and(PermissionRegistry::forModule('settings.doctor-share'))->not->toContain(
            'view share items',
            'view settlements',
            'view share reports',
        )
        ->and(PermissionRegistry::forModule('doctor-share'))->toContain(
            'view share items',
            'view settlements',
            'view share reports',
        )
        ->and(PermissionRegistry::forModule('doctor-share'))->not->toContain(
            'manage doctor shares',
            'view share rules',
        );

    $flat = PermissionRegistry::flat();
    foreach ([
        'manage doctor shares',
        'view share rules',
        'create share rules',
        'edit share rules',
        'delete share rules',
        'view share items',
        'view settlements',
        'view share reports',
    ] as $permission) {
        expect(array_count_values($flat)[$permission] ?? 0)->toBe(1);
    }
});

it('splits hr permissions onto catalog children and keeps view hr on each', function () {
    expect(PermissionRegistry::forModule('hr'))->toBe(['view hr'])
        ->and(PermissionRegistry::forModule('hr.employees'))->toContain('view employees', 'view designations', 'view department staff', 'view hr documents', 'view hr')
        ->and(PermissionRegistry::forModule('hr.employees'))->not->toContain('view payroll runs')
        ->and(PermissionRegistry::forModule('hr.attendance-leave'))->toContain('view attendance', 'view leave requests', 'view leave types', 'view leave balances', 'view hr')
        ->and(PermissionRegistry::forModule('hr.payroll'))->toContain('view payroll runs', 'view payslips', 'view salary components', 'view employee salary', 'view hr')
        ->and(PermissionRegistry::forModule('hr.scheduling'))->toContain('view shifts', 'view duty roster', 'view shift swaps', 'view hr')
        ->and(PermissionRegistry::forModule('hr.payroll'))->not->toContain('view employees');

    $flat = PermissionRegistry::flat();
    expect($flat)->toBe(array_values(array_unique($flat)))
        ->and($flat)->toContain('view hr');
});

it('splits ot permissions onto catalog children including manage pac', function () {
    expect(PermissionRegistry::forModule('ot'))->toEqualCanonicalizing([
            'view surgeries', 'create surgeries', 'edit surgeries', 'delete surgeries',
            'manage theatres',
        ])
        ->and(PermissionRegistry::forModule('ot.pac'))->toBe(['manage pac'])
        ->and(PermissionRegistry::forModule('ot.checklist'))->toBe(['manage surgical checklists'])
        ->and(PermissionRegistry::forModule('ot.consumables'))->toBe(['manage ot consumables'])
        ->and(PermissionRegistry::forModule('ot.sterilization'))->toBe(['manage sterilization']);

    expect(PermissionRegistry::flat())->toContain('manage pac')
        ->and(PermissionRegistry::flat())->toBe(array_values(array_unique(PermissionRegistry::flat())));
});

it('lists imaging studies, imaging orders and radiology results without delete', function () {
    expect(PermissionRegistry::forModule('imaging'))->toEqualCanonicalizing([
        'view imaging studies',
        'create imaging studies',
        'edit imaging studies',
        'view imaging orders',
        'create imaging orders',
        'edit imaging orders',
        'view radiology results',
        'create radiology results',
        'edit radiology results',
    ])
        ->and(PermissionRegistry::flat())->not->toContain('delete radiology results');
});

it('lists visits permissions with view assigned visits and without delete', function () {
    expect(PermissionRegistry::forModule('visits'))->toEqualCanonicalizing([
        'view visits',
        'create visits',
        'edit visits',
        'view assigned visits',
    ])
        ->and(PermissionRegistry::flat())->not->toContain('delete visits');
});
