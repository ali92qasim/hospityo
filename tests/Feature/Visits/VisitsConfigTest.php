<?php

it('uses expected visit config defaults', function () {
    expect(config('visits.dual_write_enabled'))->toBeTrue()
        ->and(config('visits.dual_write_legacy_columns'))->toBeFalse()
        ->and(config('visits.log_child_mismatches'))->toBeTrue()
        ->and(config('visits.enforce_workflow_transitions'))->toBeFalse()
        ->and(config('visits.require_typed_visit_routes'))->toBeTrue()
        ->and(config('visits.workflow_accordion_ui'))->toBeTrue()
        ->and(config('visits.read_from_child.opd'))->toBeFalse()
        ->and(config('visits.read_from_child.ipd'))->toBeFalse()
        ->and(config('visits.read_from_child.emergency'))->toBeFalse()
        ->and(config('visits.list_default_date_filter.opd'))->toBe('')
        ->and(config('visits.list_default_date_filter.ipd'))->toBe('')
        ->and(config('visits.list_default_date_filter.emergency'))->toBe('');
});
