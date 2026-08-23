<?php

use App\Models\ModuleRegistry;

it('maps pharmacy pos routes to pharmacy module', function () {
    expect(ModuleRegistry::moduleForRoute('pharmacy.pos.index'))->toBe('pharmacy');
});

it('maps departments routes to departments module', function () {
    expect(ModuleRegistry::moduleForRoute('departments.index'))->toBe('departments');
});

it('maps accounting routes to accounting module', function () {
    expect(ModuleRegistry::moduleForRoute('accounting.chart-of-accounts'))->toBe('accounting');
});

it('maps imaging routes to imaging module', function () {
    expect(ModuleRegistry::moduleForRoute('imaging.orders.index'))->toBe('imaging');
});

it('maps lab routes to laboratory module', function () {
    expect(ModuleRegistry::moduleForRoute('lab.orders.index'))->toBe('laboratory');
});

it('maps radiology results to imaging module', function () {
    expect(ModuleRegistry::moduleForRoute('radiology-results.create'))->toBe('imaging');
});

it('maps taxes routes to billing module', function () {
    expect(ModuleRegistry::moduleForRoute('taxes.index'))->toBe('billing');
});

it('registers exactly 18 modules', function () {
    expect(ModuleRegistry::all())->toHaveCount(18);
});
