<?php

use Illuminate\Support\Facades\Schema;

it('creates doctor_share_rates with unique doctor plus category', function () {
    expect(Schema::connection('tenant')->hasTable('doctor_share_rates'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasColumns('doctor_share_rates', [
            'id', 'doctor_id', 'service_category', 'percentage', 'created_at', 'updated_at',
        ]))->toBeTrue();
});
