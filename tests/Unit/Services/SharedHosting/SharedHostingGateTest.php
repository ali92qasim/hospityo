<?php

use App\Services\SharedHosting\SharedHosting;

it('is disabled when SHARED_HOSTING is false even in production', function () {
    config(['shared_hosting.enabled' => false]);
    $this->app['env'] = 'production';

    expect(SharedHosting::enabled())->toBeFalse();
});

it('is disabled when SHARED_HOSTING is true but APP_ENV is local', function () {
    config(['shared_hosting.enabled' => true]);
    $this->app['env'] = 'local';

    expect(SharedHosting::enabled())->toBeFalse();
});

it('is disabled when SHARED_HOSTING is true but APP_ENV is testing', function () {
    config(['shared_hosting.enabled' => true]);
    $this->app['env'] = 'testing';

    expect(SharedHosting::enabled())->toBeFalse();
});

it('is enabled when SHARED_HOSTING is true and APP_ENV is production', function () {
    config(['shared_hosting.enabled' => true]);
    $this->app['env'] = 'production';

    expect(SharedHosting::enabled())->toBeTrue();
});
