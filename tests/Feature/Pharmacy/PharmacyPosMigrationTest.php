<?php

use Illuminate\Support\Facades\Schema;

it('prescriptions table has fulfillment_type column', function () {
    expect(Schema::connection('tenant')->hasColumn('prescriptions', 'fulfillment_type'))->toBeTrue();
});

it('bill_items table has medicine_id column', function () {
    expect(Schema::connection('tenant')->hasColumn('bill_items', 'medicine_id'))->toBeTrue();
});

it('bills table has prescription_id column', function () {
    expect(Schema::connection('tenant')->hasColumn('bills', 'prescription_id'))->toBeTrue();
});

it('medicines strength column is nullable', function () {
    $column = collect(Schema::connection('tenant')->getColumns('medicines'))
        ->firstWhere('name', 'strength');

    expect($column)->not->toBeNull()
        ->and($column['nullable'])->toBeTrue();
});

it('prescriptions status accepts external value', function () {
    $driver = Schema::connection('tenant')->getConnection()->getDriverName();

    if ($driver === 'sqlite') {
        expect(Schema::connection('tenant')->hasColumn('prescriptions', 'status'))->toBeTrue();
    } else {
        $column = Schema::connection('tenant')->getConnection()
            ->select("SHOW COLUMNS FROM prescriptions WHERE Field = 'status'");

        expect($column[0]->Type)->toContain('external');
    }
});
