<?php

use Illuminate\Support\Facades\Route;

it('does not register visits.destroy', function () {
    expect(Route::has('visits.destroy'))->toBeFalse();
});

it('does not register visits.order-test', function () {
    expect(Route::has('visits.order-test'))->toBeFalse();
});

it('registers visits.order-multiple-imaging-studies exactly once', function () {
    $matches = collect(Route::getRoutes())->filter(
        fn ($route) => $route->getName() === 'visits.order-multiple-imaging-studies'
    );

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->methods())->toContain('POST');
});

it('still registers the live legacy and imaging order routes', function () {
    expect(Route::has('visits.add-test-orders'))->toBeTrue()
        ->and(Route::has('visits.order-multiple-lab-tests'))->toBeTrue()
        ->and(Route::has('visits.order-multiple-imaging-studies'))->toBeTrue()
        ->and(Route::has('test-orders.remove'))->toBeTrue()
        ->and(Route::has('test-orders.result'))->toBeTrue();
});
