<?php

it('renders medicine and unit row actions from data-can flags in node', function () {
    exec('node '.escapeshellarg(base_path('tests/js/pharmacy-catalog-row-actions.mjs')), $output, $code);

    expect($code)->toBe(0, implode("\n", $output));
});
