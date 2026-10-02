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

it('never displays a failing ratio as 4.50 or higher', function () {
    // #777777 is ~4.4781:1 — fails 4.5, and used to display as "4.5" via 1-dp rounding.
    expect(LabReportAccentContrast::ratioAgainstWhite('#777777'))->toBeGreaterThanOrEqual(4.45)->toBeLessThan(4.5)
        ->and(LabReportAccentContrast::failsMinimum('#777777'))->toBeTrue()
        ->and(LabReportAccentContrast::displayRatio('#777777'))->toBe('4.47');

    // #288479 is ~4.49985:1 — the closest a failing teal gets; still shows below 4.50.
    expect(LabReportAccentContrast::failsMinimum('#288479'))->toBeTrue()
        ->and(LabReportAccentContrast::displayRatio('#288479'))->toBe('4.49');
});

it('displays a ratio just above 4.5 as at least 4.50 and passes', function () {
    // #298478 is ~4.50064:1.
    expect(LabReportAccentContrast::ratioAgainstWhite('#298478'))->toBeGreaterThanOrEqual(4.5)
        ->and(LabReportAccentContrast::failsMinimum('#298478'))->toBeFalse()
        ->and(LabReportAccentContrast::displayRatio('#298478'))->toBe('4.50');
});

it('displays ratios truncated to two decimals', function () {
    expect(LabReportAccentContrast::displayRatio('#0F766E'))->toBe('5.47')
        ->and(LabReportAccentContrast::displayRatio('#33847E'))->toBe('4.42')
        ->and(LabReportAccentContrast::displayRatio('#30827C'))->toBe('4.55')
        ->and(LabReportAccentContrast::displayRatio('#FFFFFF'))->toBe('1.00');
});
