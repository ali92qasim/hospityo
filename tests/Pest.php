<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit/Services');

/*
|--------------------------------------------------------------------------
| Tenant Test Setup
|--------------------------------------------------------------------------
| For tests in Feature/Accounting and Feature/HR, we need tenant tables.
| RefreshDatabase runs default migrations, but tenant migrations are in a
| separate path. We run them explicitly here.
*/
uses()
    ->beforeEach(function () {
        // Run tenant-specific migrations (tables like accounts, employees, etc.)
        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'tenant',
        ]);
    })
        ->in('Feature/Accounting', 'Feature/DoctorShare', 'Feature/HR', 'Feature/OT', 'Feature/Billing', 'Feature/Lab', 'Feature/Imaging', 'Feature/Pharmacy', 'Feature/Visits', 'Feature/Doctors', 'Feature/Patients', 'Feature/Appointments', 'Feature/Navigation', 'Feature/Module', 'Feature/Permissions', 'Feature/Commands', 'Feature/Backup', 'Feature/Settings', 'Feature/Flash', 'Unit/Services');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function otCatalogTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function fingerprintDoctorShareHistory(): string
{
    $tables = [
        'doctor_share_items',
        'doctor_share_allocations',
        'doctor_share_settlements',
        'doctor_share_rules',
        'doctor_share_rule_service',
    ];

    $payload = [];
    foreach ($tables as $table) {
        $payload[$table] = Illuminate\Support\Facades\DB::connection('tenant')->table($table)->orderBy('id')->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    return hash('sha256', json_encode($payload));
}

function otCatalogUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'OT Catalog User',
        'email' => 'ot-catalog-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo($permissions);

    return $user;
}
