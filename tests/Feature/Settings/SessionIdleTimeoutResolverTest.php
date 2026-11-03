<?php

use App\Models\Setting;
use App\Support\SessionIdleTimeout;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config([
        'security.idle_timeout_minutes' => 30,
        'security.idle_timeout_min' => 5,
        'security.idle_timeout_max' => 60,
        'security.idle_warning_seconds' => 60,
    ]);

    Cache::flush();
});

it('uses the env/config default when no tenant override is set', function () {
    expect(SessionIdleTimeout::minutes())->toBe(30);
});

it('uses a valid tenant setting override', function () {
    Setting::set('session_idle_timeout_minutes', '15');

    expect(SessionIdleTimeout::minutes())->toBe(15);
});

it('falls back to the default when tenant override is out of bounds', function () {
    Setting::set('session_idle_timeout_minutes', '120');

    expect(SessionIdleTimeout::minutes())->toBe(30);
});

it('falls back to the default when tenant override is non-numeric', function () {
    Setting::set('session_idle_timeout_minutes', 'abc');

    expect(SessionIdleTimeout::minutes())->toBe(30);
});

it('clamps the configured default into the allowed band', function () {
    config(['security.idle_timeout_minutes' => 1]);

    expect(SessionIdleTimeout::minutes())->toBe(5);
});

it('exposes warning seconds from config', function () {
    expect(SessionIdleTimeout::warningSeconds())->toBe(60);
});
