<?php

it('uses expected visit config defaults', function () {
    expect(config('visits.dual_write_enabled'))->toBeTrue()
        ->and(config('visits.dual_write_legacy_columns'))->toBeTrue()
        ->and(config('visits.log_child_mismatches'))->toBeTrue()
        ->and(config('visits.read_from_child.opd'))->toBeFalse()
        ->and(config('visits.read_from_child.ipd'))->toBeFalse()
        ->and(config('visits.read_from_child.emergency'))->toBeFalse();
});
