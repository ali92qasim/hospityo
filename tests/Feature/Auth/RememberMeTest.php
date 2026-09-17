<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [],
        'permission.testing' => true,
    ]);

    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);

    $this->plan = Plan::create([
        'slug' => 'starter',
        'name' => 'Starter',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => ['patients'],
        'is_active' => true,
    ]);

    $this->tenant = Tenant::create([
        'name' => 'Remember Clinic',
        'slug' => 'remember-clinic',
        'domain' => 'remember-clinic.test',
        'database' => 'tenant_remember_clinic',
        'email' => 'clinic@example.com',
        'status' => 'active',
        'plan_id' => $this->plan->id,
    ]);

    TenantUser::register('admin@clinic.test', $this->tenant->id);

    $this->user = User::create([
        'name' => 'Clinic Admin',
        'email' => 'admin@clinic.test',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
});

it('shows an unchecked remember me checkbox on the public sign in form', function () {
    $html = $this->get(route('central.login'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('name="remember"')
        ->and($html)->toContain('Remember me')
        ->and($html)->not->toContain('name="remember" checked')
        ->and($html)->not->toContain("name='remember' checked");
});

it('stores remember preference on the one-time login token when checked', function () {
    $this->post(route('central.login.submit'), [
        'email' => 'admin@clinic.test',
        'password' => 'password',
        'remember' => '1',
    ])->assertRedirect();

    $mapping = TenantUser::where('email', 'admin@clinic.test')->first();

    expect($mapping->login_token)->not->toBeNull()
        ->and((bool) $mapping->login_remember)->toBeTrue();
});

it('does not remember the session when the checkbox is left unchecked', function () {
    $this->post(route('central.login.submit'), [
        'email' => 'admin@clinic.test',
        'password' => 'password',
    ])->assertRedirect();

    $mapping = TenantUser::where('email', 'admin@clinic.test')->first();

    expect($mapping->login_token)->not->toBeNull()
        ->and((bool) $mapping->login_remember)->toBeFalse();
});

it('logs in with a remember cookie only when the sign in asked to remember', function () {
    $mapping = TenantUser::where('email', 'admin@clinic.test')->first();
    $mapping->update([
        'login_token' => 'one-time-token',
        'login_remember' => true,
    ]);

    app()->instance(config('multitenancy.current_tenant_container_key'), $this->tenant);

    $this->get(route('login', [
        'token' => 'one-time-token',
        'email' => 'admin@clinic.test',
    ]))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);
    expect($this->user->fresh()->remember_token)->not->toBeNull()
        ->and(TenantUser::where('email', 'admin@clinic.test')->first()->login_token)->toBeNull();
});

it('logs in without a remember cookie when remember was not requested', function () {
    $mapping = TenantUser::where('email', 'admin@clinic.test')->first();
    $mapping->update([
        'login_token' => 'one-time-token',
        'login_remember' => false,
    ]);

    app()->instance(config('multitenancy.current_tenant_container_key'), $this->tenant);

    $this->get(route('login', [
        'token' => 'one-time-token',
        'email' => 'admin@clinic.test',
    ]))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);
    expect($this->user->fresh()->remember_token)->toBeNull();
    expect(Auth::viaRemember())->toBeFalse();
});
