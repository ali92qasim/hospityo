<?php

return [
    'dual_write_enabled' => env('VISITS_DUAL_WRITE', true),
    'dual_write_legacy_columns' => env('VISITS_DUAL_WRITE_LEGACY', false),
    'log_child_mismatches' => env('VISITS_LOG_CHILD_MISMATCHES', true),
    'read_from_child' => [
        'opd' => env('VISITS_READ_OPD_CHILD', false),
        'ipd' => env('VISITS_READ_IPD_CHILD', false),
        'emergency' => env('VISITS_READ_EMERGENCY_CHILD', false),
    ],
    'enforce_workflow_transitions' => env('VISITS_ENFORCE_WORKFLOW', false),
    'require_typed_visit_routes' => env('VISITS_REQUIRE_TYPED_ROUTES', true),
    'workflow_accordion_ui' => env('VISITS_WORKFLOW_ACCORDION_UI', true),

    /*
    | Default date filter for typed visit list pages (opd / ipd / emergency).
    | Empty string = all time. Set to "today" via env for day-to-day OPD workflows.
    */
    'list_default_date_filter' => [
        'opd' => env('VISITS_OPD_LIST_DATE_FILTER', ''),
        'ipd' => env('VISITS_IPD_LIST_DATE_FILTER', ''),
        'emergency' => env('VISITS_EMERGENCY_LIST_DATE_FILTER', ''),
    ],
];
