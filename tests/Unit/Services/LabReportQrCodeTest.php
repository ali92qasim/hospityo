<?php

use App\Services\LabReportQrCode;

it('renders a deterministic svg for a fixed url', function () {
    $url = 'https://example.test/lab-report/fixed-token-abc';

    $first = LabReportQrCode::svg($url);
    $second = LabReportQrCode::svg($url);

    expect($first)->toContain('<svg')
        ->and($first)->toContain('</svg>')
        ->and($first)->toBe($second);
});
