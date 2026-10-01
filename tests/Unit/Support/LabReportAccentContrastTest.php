<?php

use App\Support\LabReportAccentContrast;

it('computes high contrast for default teal against white', function () {
    $ratio = LabReportAccentContrast::ratioAgainstWhite('#0F766E');
    expect($ratio)->toBeGreaterThan(4.5)
        ->and(LabReportAccentContrast::failsMinimum('#0F766E'))->toBeFalse();
});

it('flags pale yellow as failing the 4.5:1 minimum', function () {
    expect(LabReportAccentContrast::ratioAgainstWhite('#FDE68A'))->toBeLessThan(3.0)
        ->and(LabReportAccentContrast::failsMinimum('#FDE68A'))->toBeTrue();
});

it('treats white vs white as failing', function () {
    expect(LabReportAccentContrast::failsMinimum('#FFFFFF'))->toBeTrue();
});

it('uses 4.5:1 as the minimum because white text sits on the accent', function () {
    expect(LabReportAccentContrast::MIN_RATIO)->toBe(4.5)
        ->and(LabReportAccentContrast::failsMinimum('#33847E'))->toBeTrue()   // 4.43
        ->and(LabReportAccentContrast::failsMinimum('#30827C'))->toBeFalse()  // 4.56
        ->and(LabReportAccentContrast::failsMinimum('#0F766E'))->toBeFalse(); // 5.47
});

it('still honours an explicit minimum override', function () {
    expect(LabReportAccentContrast::failsMinimum('#33847E', 3.0))->toBeFalse()
        ->and(LabReportAccentContrast::failsMinimum('#33847E', 4.5))->toBeTrue();
});
