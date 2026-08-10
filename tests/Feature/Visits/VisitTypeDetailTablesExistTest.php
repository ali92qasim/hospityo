<?php

use Illuminate\Support\Facades\Schema;

it('creates visit type detail tables', function () {
    expect(Schema::connection('tenant')->hasTable('opd_visits'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('ipd_visits'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('emergency_visits'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('visit_class_histories'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasColumn('visits', 'closed_at'))->toBeTrue();
});

it('does not add doctor_id to opd_visits child table', function () {
    expect(Schema::connection('tenant')->hasColumn('opd_visits', 'doctor_id'))->toBeFalse();
});
