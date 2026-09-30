<?php

use App\Support\LabReportAccentContrast;

it('computes high contrast for default teal against white', function () {
    $ratio = LabReportAccentContrast::ratioAgainstWhite('#0F766E');
    expect($ratio)->toBeGreaterThan(3.0)
        ->and(LabReportAccentContrast::failsMinimum('#0F766E'))->toBeFalse();
});

it('flags pale yellow as failing the 3:1 UI chrome threshold', function () {
    expect(LabReportAccentContrast::ratioAgainstWhite('#FDE68A'))->toBeLessThan(3.0)
        ->and(LabReportAccentContrast::failsMinimum('#FDE68A'))->toBeTrue();
});

it('treats white vs white as failing', function () {
    expect(LabReportAccentContrast::failsMinimum('#FFFFFF'))->toBeTrue();
});
