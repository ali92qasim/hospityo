<?php

use App\Support\PricingPersona;

it('maps laboratory and pharmacy to professional', function () {
    expect(PricingPersona::recommendedPlanSlug('laboratory'))->toBe('professional')
        ->and(PricingPersona::recommendedPlanSlug('pharmacy'))->toBe('professional');
});

it('maps physiotherapy to starter and hospital to enterprise', function () {
    expect(PricingPersona::recommendedPlanSlug('physiotherapy'))->toBe('starter')
        ->and(PricingPersona::recommendedPlanSlug('hospital'))->toBe('enterprise');
});

it('returns null for unknown personas', function () {
    expect(PricingPersona::recommendedPlanSlug('unknown'))->toBeNull()
        ->and(PricingPersona::recommendedPlanSlug(null))->toBeNull();
});
