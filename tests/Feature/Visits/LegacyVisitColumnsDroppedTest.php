<?php

use Illuminate\Support\Facades\Schema;

it('drops legacy visit spine columns while keeping doctor_id', function () {
    $legacyColumns = [
        'discharge_datetime',
        'chief_complaint',
        'diagnosis',
        'treatment',
        'notes',
        'total_charges',
        'bed_no',
        'room_no',
        'priority',
    ];

    foreach ($legacyColumns as $column) {
        expect(Schema::connection('tenant')->hasColumn('visits', $column))
            ->toBeFalse("Expected visits.{$column} to be dropped");
    }

    expect(Schema::connection('tenant')->hasColumn('visits', 'doctor_id'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasColumn('visits', 'closed_at'))->toBeTrue();
});
