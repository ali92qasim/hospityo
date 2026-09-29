<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();
});

function performanceReportTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function performanceReportUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Performance Report User',
        'email' => 'perf-report-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo($permissions);

    return $user;
}

function seedPerformanceDoctor(): Doctor
{
    $department = Department::create([
        'name' => 'Perf Dept '.uniqid(),
        'code' => 'PD'.uniqid(),
        'status' => 'active',
    ]);

    return Doctor::create([
        'name' => 'Dr Perf',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '0300'.random_int(1000000, 9999999),
        'email' => 'dr-perf-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
}

it('loads the doctor performance report without an InvestigationOrder class error', function () {
    performanceReportTenant();
    seedPerformanceDoctor();
    $this->actingAs(performanceReportUser([
        'view reports',
        'view reports.doctor-performance',
    ]));

    $response = $this->get(route('reports.doctor-performance'));

    expect($response->status())->not->toBe(500);
    $response->assertOk();
    expect($response->getContent())->not->toContain('InvestigationOrder');
});

it('loads the department performance report without an InvestigationOrder class error', function () {
    performanceReportTenant();
    seedPerformanceDoctor();
    $this->actingAs(performanceReportUser([
        'view reports',
        'view reports.department-performance',
    ]));

    $response = $this->get(route('reports.department-performance'));

    expect($response->status())->not->toBe(500);
    $response->assertOk();
    expect($response->getContent())->not->toContain('Class "App\\Http\\Controllers\\InvestigationOrder" not found');
});
