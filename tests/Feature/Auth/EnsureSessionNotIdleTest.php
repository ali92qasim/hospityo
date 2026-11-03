<?php

use App\Http\Middleware\EnsureSessionNotIdle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    config([
        'security.idle_timeout_minutes' => 30,
        'security.idle_timeout_min' => 5,
        'security.idle_timeout_max' => 60,
    ]);
});

it('passes through for guests without touching the session activity stamp', function () {
    $request = Request::create('/dashboard', 'GET');
    $session = app('session.store');
    $session->start();
    $request->setLaravelSession($session);

    $response = (new EnsureSessionNotIdle)->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok')
        ->and($session->has('last_activity_at'))->toBeFalse();
});

it('records last_activity_at for an authenticated user on first request', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $request = Request::create('/dashboard', 'GET');
    $session = app('session.store');
    $session->start();
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $before = now()->getTimestamp();
    $response = (new EnsureSessionNotIdle)->handle($request, fn () => response('ok'));
    $after = now()->getTimestamp();

    expect($response->getContent())->toBe('ok')
        ->and($session->get('last_activity_at'))->toBeGreaterThanOrEqual($before)
        ->and($session->get('last_activity_at'))->toBeLessThanOrEqual($after)
        ->and(Auth::check())->toBeTrue();
});

it('keeps the session alive when activity is within the idle timeout', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $request = Request::create('/dashboard', 'GET');
    $session = app('session.store');
    $session->start();
    $session->put('last_activity_at', now()->subMinutes(10)->getTimestamp());
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $response = (new EnsureSessionNotIdle)->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok')
        ->and(Auth::check())->toBeTrue()
        ->and($session->get('last_activity_at'))->toBeGreaterThan(now()->subMinute()->getTimestamp());
});

it('logs out and redirects when idle timeout is exceeded', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $request = Request::create('/dashboard', 'GET');
    $session = app('session.store');
    $session->start();
    $session->put('last_activity_at', now()->subMinutes(31)->getTimestamp());
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $response = (new EnsureSessionNotIdle)->handle($request, fn () => response('ok'));

    expect(Auth::check())->toBeFalse()
        ->and($response->isRedirect())->toBeTrue();
});

it('skips idle enforcement on logout routes', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $request = Request::create('/logout', 'POST');
    $request->setRouteResolver(function () {
        $route = new Illuminate\Routing\Route('POST', '/logout', fn () => null);
        $route->name('logout');

        return $route;
    });

    $session = app('session.store');
    $session->start();
    $session->put('last_activity_at', now()->subMinutes(31)->getTimestamp());
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $response = (new EnsureSessionNotIdle)->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok')
        ->and(Auth::check())->toBeTrue();
});

it('recovers when last_activity_at is corrupt', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $request = Request::create('/dashboard', 'GET');
    $session = app('session.store');
    $session->start();
    $session->put('last_activity_at', ['not', 'a', 'timestamp']);
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $response = (new EnsureSessionNotIdle)->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok')
        ->and(Auth::check())->toBeTrue()
        ->and(is_numeric($session->get('last_activity_at')))->toBeTrue();
});
