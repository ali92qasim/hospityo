<?php

use App\Models\Unit;
use App\Services\MedicineStockConversion;

it('converts pack quantity and price to base units', function () {
    $base = Unit::create([
        'name' => 'Tablet',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $pack = Unit::create([
        'name' => 'Pack 10',
        'abbreviation' => 'P10',
        'base_unit_id' => $base->id,
        'conversion_factor' => 10,
        'type' => 'packaging',
        'is_active' => true,
    ]);

    $result = MedicineStockConversion::toBaseUnits($pack, 5, 500.0);

    expect($result['base_quantity'])->toBe(50)
        ->and($result['base_unit_cost'])->toBe(50.0)
        ->and($result['total_cost'])->toBe(2500.0);
});

it('passes through base unit one-to-one', function () {
    $tab = Unit::create([
        'name' => 'Tablet',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $result = MedicineStockConversion::toBaseUnits($tab, 20, 2.5);

    expect($result['base_quantity'])->toBe(20)
        ->and($result['base_unit_cost'])->toBe(2.5)
        ->and($result['total_cost'])->toBe(50.0);
});
