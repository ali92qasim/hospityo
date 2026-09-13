<?php

use App\Models\Medicine;
use App\Models\Unit;
use App\Services\PurchaseOrderUnitPayload;

it('includes the base unit when medicine and unit ids are strings like MySQL', function () {
    $tab = new Unit([
        'name' => 'Tablet',
        'abbreviation' => 'TAB',
        'is_active' => true,
    ]);
    $tab->id = 5;
    $tab->base_unit_id = null;

    $medicine = new Medicine([
        'name' => 'Panadol Extra',
        'status' => 'active',
    ]);
    $medicine->id = 9;
    $medicine->base_unit_id = '5';

    $payload = PurchaseOrderUnitPayload::make(collect([$medicine]), collect([$tab]));

    $units = $payload->unitsFor('9');

    expect($units)->toHaveCount(1)
        ->and($units[0]['id'])->toBe(5)
        ->and($units[0]['abbreviation'])->toBe('TAB')
        ->and($units[0]['base_unit_id'])->toBe(5);
});

it('includes packing units that share the medicine base unit', function () {
    $tab = new Unit(['name' => 'Tablet', 'abbreviation' => 'TAB', 'is_active' => true]);
    $tab->id = 5;
    $tab->base_unit_id = null;

    $pack = new Unit(['name' => 'Pack 10', 'abbreviation' => 'P10', 'is_active' => true]);
    $pack->id = 8;
    $pack->base_unit_id = '5';

    $medicine = new Medicine(['name' => 'Panadol Extra', 'status' => 'active']);
    $medicine->id = 9;
    $medicine->base_unit_id = '5';

    $payload = PurchaseOrderUnitPayload::make(collect([$medicine]), collect([$tab, $pack]));
    $ids = array_column($payload->unitsFor(9), 'id');

    expect($ids)->toEqualCanonicalizing([5, 8]);
});
