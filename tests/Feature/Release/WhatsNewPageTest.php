<?php

use App\Console\Commands\AppReleaseCommand;
use App\Models\ChangelogEntry;
use App\Models\Release;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    $this->app['db']->purge('landlord');
    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    Release::create([
        'version' => '1.0.0',
        'summary' => AppReleaseCommand::INITIAL_BLURB,
        'released_at' => now()->subDay(),
    ]);

    $newer = Release::create([
        'version' => '1.1.0',
        'summary' => null,
        'released_at' => now(),
    ]);
    ChangelogEntry::create([
        'release_id' => $newer->id,
        'category' => 'added',
        'description' => 'Add VERSION file and config app.version',
        'sort_order' => 0,
    ]);
    ChangelogEntry::create([
        'release_id' => $newer->id,
        'category' => 'fixed',
        'description' => 'Scope rate sync deletes to submitted doctors',
        'sort_order' => 1,
    ]);
});

function whatsNewUser(): User
{
    return User::create([
        'name' => 'Whats New User',
        'email' => 'whats-new-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
}

function whatsNewTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

it('lists releases newest first for any authenticated tenant user', function () {
    whatsNewTenant([]);
    $this->actingAs(whatsNewUser())
        ->get(route('whats-new.index'))
        ->assertOk()
        ->assertSeeInOrder(['v1.1.0', 'v1.0.0'])
        ->assertSee('Added')
        ->assertSee('Add VERSION file and config app.version')
        ->assertSee('Fixed')
        ->assertSee('Scope rate sync deletes to submitted doctors')
        ->assertSee(AppReleaseCommand::INITIAL_BLURB);
});

it('is reachable on a tenant whose plan has no extra modules', function () {
    whatsNewTenant([]);
    $this->actingAs(whatsNewUser())
        ->get(route('whats-new.index'))
        ->assertOk();
});

it('redirects guests to login', function () {
    $this->get(route('whats-new.index'))
        ->assertRedirect();
});

it('lists the same releases for a super admin', function () {
    $superAdmin = SuperAdmin::create([
        'name' => 'Super Admin',
        'email' => 'super-whats-new-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($superAdmin, 'super_admin')
        ->get(route('super-admin.whats-new.index'))
        ->assertOk()
        ->assertSee('v1.1.0')
        ->assertSee(AppReleaseCommand::INITIAL_BLURB);
});
