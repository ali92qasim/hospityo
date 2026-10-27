<?php

use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Services route scoping (RBAC wave 3 remainder, Task 2)
|--------------------------------------------------------------------------
| Each services HTTP route accepts exactly one action permission.
| There is no delete action: the destroy route and `delete services`
| permission no longer exist. Import routes require `create services`.
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    svcScopeTenant();

    $this->service = svcScopeService();
});

function svcScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function svcScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Services Scope User',
        'email' => 'svc-scope-user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function svcScopeService(): Service
{
    return Service::create([
        'name' => 'Scope Service',
        'code' => 'SCOPE-'.uniqid(),
        'category' => 'consultation',
        'price' => 500,
        'is_active' => true,
    ]);
}

function svcScopePayload(string $route): array
{
    return match ($route) {
        'services.store', 'services.update' => [
            'name' => 'Scope Payload Service',
            'code' => 'PAYLOAD-'.uniqid(),
            'category' => 'procedure',
            'price' => 750,
        ],
        default => [],
    };
}

function svcScopeUrl(string $route, Service $service): string
{
    return in_array($route, ['services.edit', 'services.update'], true)
        ? route($route, ['service' => $service->id])
        : route($route);
}

function svcScopeRequest($test, string $method, string $route, Service $service)
{
    $url = svcScopeUrl($route, $service);

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, svcScopePayload($route));
}

// ── 1. Deny: wrong-verb holder must get 403 ──────────────────────────────

dataset('services escalations', [
    'index via create only' => ['get', 'services.index', ['create services']],
    'index via edit only' => ['get', 'services.index', ['edit services']],
    'create via view only' => ['get', 'services.create', ['view services']],
    'create via edit only' => ['get', 'services.create', ['edit services']],
    'store via view only' => ['post', 'services.store', ['view services']],
    'store via edit only' => ['post', 'services.store', ['edit services']],
    'edit via view only' => ['get', 'services.edit', ['view services']],
    'edit via create only' => ['get', 'services.edit', ['create services']],
    'update via view only' => ['put', 'services.update', ['view services']],
    'update via create only' => ['put', 'services.update', ['create services']],
    'import via view only' => ['post', 'services.import', ['view services']],
    'import via edit only' => ['post', 'services.import', ['edit services']],
    'import-status via view only' => ['get', 'services.import-status', ['view services']],
    'import-status via edit only' => ['get', 'services.import-status', ['edit services']],
]);

it('forbids a wrong-verb permission on each services route', function (string $method, string $route, array $permissions) {
    $this->actingAs(svcScopeUser($permissions));

    svcScopeRequest($this, $method, $route, $this->service)->assertForbidden();
})->with('services escalations');

// ── 2. Positive control: exactly the matching permission passes ──────────

dataset('services positive controls', [
    'index' => ['get', 'services.index', 'view services'],
    'create' => ['get', 'services.create', 'create services'],
    'store' => ['post', 'services.store', 'create services'],
    'edit' => ['get', 'services.edit', 'edit services'],
    'update' => ['put', 'services.update', 'edit services'],
    'import-status' => ['get', 'services.import-status', 'create services'],
]);

it('allows exactly the matching permission on each services route', function (string $method, string $route, string $permission) {
    $this->actingAs(svcScopeUser([$permission]));

    $response = svcScopeRequest($this, $method, $route, $this->service);

    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()->assertSessionHasNoErrors();
})->with('services positive controls');

it('lets create services reach the import action (validation, not 403)', function () {
    $this->actingAs(svcScopeUser(['create services']));

    $response = $this->post(route('services.import'), []);

    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);
    $response->assertSessionHasErrors('file');
});

// ── 3. Side effects: a blocked write writes nothing ──────────────────────

it('creates no service when view services posts store', function () {
    $this->actingAs(svcScopeUser(['view services']));
    $count = Service::count();

    svcScopeRequest($this, 'post', 'services.store', $this->service)->assertForbidden();

    expect(Service::count())->toBe($count);
});

it('leaves the service unchanged when view services puts update', function () {
    $this->actingAs(svcScopeUser(['view services']));

    svcScopeRequest($this, 'put', 'services.update', $this->service)->assertForbidden();

    expect($this->service->fresh()->name)->toBe('Scope Service');
});

// ── 4. No delete: route, permission and registry entry are gone ──────────

it('does not register a services destroy route', function () {
    expect(Route::has('services.destroy'))->toBeFalse();
});

it('has no DELETE route for a service URL', function () {
    $this->actingAs(svcScopeUser(['view services', 'create services', 'edit services']));

    $this->delete(url('services/'.$this->service->id))->assertStatus(405);

    expect(Service::find($this->service->id))->not->toBeNull();
});

it('does not define destroy on the service controller', function () {
    expect(method_exists(\App\Http\Controllers\ServiceController::class, 'destroy'))->toBeFalse();
});

it('no longer lists delete services in the permission registry', function () {
    expect(PermissionRegistry::all())->not->toContain('delete services');
});

it('no longer grants delete services in the role seeder', function () {
    expect(file_get_contents(database_path('seeders/RolePermissionSeeder.php')))
        ->not->toContain('delete services');
});

it('forbids a user with only delete services on every services route', function () {
    $this->actingAs(svcScopeUser(['delete services']));

    foreach ([
        ['get', 'services.index'],
        ['get', 'services.create'],
        ['post', 'services.store'],
        ['get', 'services.edit'],
        ['put', 'services.update'],
        ['post', 'services.import'],
        ['get', 'services.import-status'],
    ] as [$method, $route]) {
        svcScopeRequest($this, $method, $route, $this->service)->assertForbidden();
    }
});

// ── 5. UI gating ─────────────────────────────────────────────────────────

it('shows no Delete control on the services index', function () {
    $this->actingAs(svcScopeUser(['view services', 'create services', 'edit services']));

    $this->get(route('services.index'))
        ->assertOk()
        ->assertDontSee('fa-trash', false)
        ->assertDontSee('name="_method" value="DELETE"', false)
        ->assertDontSee('data-confirm-text="Delete"', false);
});

it('hides add, import and edit controls from a view-only user on the index', function () {
    $this->actingAs(svcScopeUser(['view services']));

    $this->get(route('services.index'))
        ->assertOk()
        ->assertDontSee(e(route('services.create')), false)
        ->assertDontSee(e(route('services.edit', $this->service)), false)
        ->assertDontSee('Import CSV / Excel');
});

it('shows add, import and edit controls to create and edit holders on the index', function () {
    $this->actingAs(svcScopeUser(['view services', 'create services', 'edit services']));

    $this->get(route('services.index'))
        ->assertOk()
        ->assertSee(e(route('services.create')), false)
        ->assertSee(e(route('services.edit', $this->service)), false)
        ->assertSee('Import CSV / Excel');
});
