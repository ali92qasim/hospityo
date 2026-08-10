<?php

return [
    'dual_write_enabled' => env('VISITS_DUAL_WRITE', true),
    'dual_write_legacy_columns' => env('VISITS_DUAL_WRITE_LEGACY', true),
    'log_child_mismatches' => env('VISITS_LOG_CHILD_MISMATCHES', true),
    'read_from_child' => [
        'opd' => env('VISITS_READ_OPD_CHILD', false),
        'ipd' => env('VISITS_READ_IPD_CHILD', false),
        'emergency' => env('VISITS_READ_EMERGENCY_CHILD', false),
    ],
];
