<?php



use App\Models\Patient;

use App\Models\Tenant;

use App\Models\User;

use App\Services\SidebarService;

use Illuminate\Contracts\Auth\Authenticatable;

use Spatie\Permission\Models\Permission;



beforeEach(function () {

    $this->service = new SidebarService;

});



function makeSidebarUser(array $permissions = ['view visits', 'create visits', 'view wards', 'view beds']): Authenticatable

{

    $user = User::create([

        'name' => 'Nav User',

        'email' => 'nav-'.uniqid().'@example.com',

        'password' => bcrypt('password'),

        'email_verified_at' => now(),

    ]);



    foreach ($permissions as $permission) {

        Permission::findOrCreate($permission, 'web');

    }



    $user->givePermissionTo($permissions);



    return $user;

}



function makeTenantWithModules(array $modules): Tenant

{

    $tenant = Mockery::mock(Tenant::class);

    $tenant->shouldReceive('hasModule')

        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));



    return $tenant;

}



it('uses a single opd sidebar link instead of a queue plus register group', function () {

    $user = makeSidebarUser(['view visits', 'create visits']);

    $tenant = makeTenantWithModules(['visits', 'ipd']);

    $menu = $this->service->build($user, $tenant);



    $opd = collect($menu)->firstWhere('label', 'OPD');

    expect($opd['type'])->toBe('link')

        ->and($opd['route'])->toBe('visits.index')

        ->and($opd['route_params'])->toBe(['visit_type' => 'opd']);



    $labels = collect($menu)->pluck('label');

    expect($labels)->not->toContain('Visits')

        ->and(collect($menu)->where('type', 'group')->firstWhere('label', 'OPD'))->toBeNull();

});



it('uses a single emergency sidebar link', function () {

    $user = makeSidebarUser(['view visits']);

    $tenant = makeTenantWithModules(['emergency']);

    $menu = $this->service->build($user, $tenant);

    $emergency = collect($menu)->firstWhere('label', 'Emergency');

    expect($emergency['type'])->toBe('link')

        ->and($emergency['route_params']['visit_type'])->toBe('emergency');

});



it('ipd management has one visit list child not two register links', function () {

    $user = makeSidebarUser(['view visits', 'view wards', 'view beds']);

    $tenant = makeTenantWithModules(['visits', 'ipd']);

    $menu = $this->service->build($user, $tenant);

    $ipd = collect($menu)->firstWhere('label', 'IPD Management');

    $visitChildren = collect($ipd['items'])->filter(fn ($i) => ($i['route_params']['visit_type'] ?? null) === 'ipd');

    expect($visitChildren)->toHaveCount(1)

        ->and($visitChildren->first()['label'])->toBe('Admitted Patients');

});



it('gates ipd visit links behind both ipd and visits modules', function () {

    $user = makeSidebarUser();

    $tenant = makeTenantWithModules(['visits']);



    $menu = $this->service->build($user, $tenant);



    expect(collect($menu)->pluck('label'))->toContain('OPD')

        ->and(collect($menu)->pluck('label'))->not->toContain('Emergency', 'IPD Management');

});



it('links type-specific sidebar items to filtered visit list routes', function () {

    $user = makeSidebarUser();

    $tenant = makeTenantWithModules(['visits', 'ipd']);



    $menu = $this->service->build($user, $tenant);

    $opd = collect($menu)->firstWhere('label', 'OPD');



    expect($opd['route'])->toBe('visits.index')

        ->and($opd['route_params'])->toBe(['visit_type' => 'opd'])

        ->and(route($opd['route'], $opd['route_params']))->toContain('visit_type=opd');

});



it('renders opd list page with type filter preset', function () {

    $this->user = makeSidebarUser(['view visits']);



    $this->withoutMiddleware([

        \App\Http\Middleware\EnsureTenantActive::class,

        \App\Http\Middleware\SetTenantTimezone::class,

        \App\Http\Middleware\CheckModule::class,

    ]);



    $this->actingAs($this->user);



    Patient::create([

        'name' => 'OPD Nav Patient',

        'gender' => 'male',

        'age' => 30,

        'phone' => '03001112233',

        'emergency_name' => 'Relative',

        'emergency_phone' => '03001112234',

        'emergency_relation' => 'Sibling',

    ]);



    $this->get(route('visits.index', ['visit_type' => 'opd']))

        ->assertOk()

        ->assertSee('OPD')

        ->assertSee('data-visit-type="opd"', false);

});


