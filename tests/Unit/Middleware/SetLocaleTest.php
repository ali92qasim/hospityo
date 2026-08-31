<?php

use App\Http\Middleware\SetLocale;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

uses(Tests\TestCase::class);

it('does not resolve the session user while the tenant is still provisioning', function () {
    $tenant = new Tenant([
        'name' => 'New Clinic',
        'slug' => 'new-clinic',
        'status' => 'provisioning',
    ]);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $userResolverCalls = 0;
    $request = Request::create('/');
    $request->setUserResolver(function () use (&$userResolverCalls) {
        $userResolverCalls++;
        throw new RuntimeException('Tenant database is not ready');
    });

    $response = (new SetLocale)->handle($request, fn () => new Response('ok'));

    expect($userResolverCalls)->toBe(0)
        ->and($response->getContent())->toBe('ok');
});

it('may resolve the session user once the tenant is active', function () {
    $tenant = new Tenant([
        'name' => 'Ready Clinic',
        'slug' => 'ready-clinic',
        'status' => 'active',
    ]);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $userResolverCalls = 0;
    $request = Request::create('/');
    $request->setUserResolver(function () use (&$userResolverCalls) {
        $userResolverCalls++;

        return (object) ['locale' => 'en'];
    });

    $response = (new SetLocale)->handle($request, fn () => new Response('ok'));

    expect($userResolverCalls)->toBe(1)
        ->and($response->getContent())->toBe('ok');
});
