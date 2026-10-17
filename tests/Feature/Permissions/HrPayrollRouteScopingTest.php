<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryComponent;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| HR payroll route scoping (RBAC wave 2, Task 6)
|--------------------------------------------------------------------------
| Each of the 16 `hr.payroll.*` routes accepts exactly its action permission;
| reads additionally accept the legacy `view hr`. Approve is Super Admin only
| (H2) and destroy-component is Super Admin only (H9).
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);

    hrPayScopeTenant();

    $this->employee = Employee::create([
        'name' => 'Hr Pay Scope Employee',
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
    ]);

    $this->component = SalaryComponent::create([
        'name' => 'Hr Pay Scope Component',
        'code' => 'HPS',
        'type' => 'allowance',
        'calculation' => 'fixed',
        'default_amount' => 100,
    ]);

    // Never referenced by a request except destroy, so destroy can succeed on it.
    $this->spareComponent = SalaryComponent::create([
        'name' => 'Hr Pay Scope Spare',
        'code' => 'HSP',
        'type' => 'deduction',
        'calculation' => 'fixed',
        'default_amount' => 10,
    ]);

    $creator = User::create([
        'name' => 'Hr Pay Scope Creator',
        'email' => 'hr-pay-scope-creator-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->payrollRun = PayrollRun::create([
        'title' => 'Payroll — Fixture',
        'year' => 2026,
        'month' => 2,
        'status' => 'draft',
        'created_by' => $creator->id,
    ]);

    $this->payslip = Payslip::create([
        'payroll_run_id' => $this->payrollRun->id,
        'employee_id' => $this->employee->id,
    ]);
});

function hrPayScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, ['hr', 'hr.employees', 'hr.payroll'], true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function hrPayScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Hr Pay Scope User',
        'email' => 'hr-pay-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function hrPayScopePayload($test, string $route): array
{
    return match ($route) {
        'hr.payroll.generate' => ['year' => 2026, 'month' => 5],
        'hr.payroll.mark-paid' => ['payment_method' => 'cash', 'payment_date' => '2026-03-01'],
        'hr.payroll.store-component' => [
            'name' => 'Hr Pay Scope New Component',
            'code' => 'HNW',
            'type' => 'allowance',
            'calculation' => 'fixed',
            'default_amount' => 50,
        ],
        'hr.payroll.update-component' => [
            'name' => 'Hr Pay Scope Renamed',
            'code' => 'HPS',
            'type' => 'allowance',
            'calculation' => 'fixed',
            'default_amount' => 100,
        ],
        'hr.payroll.update-employee-salary' => [
            'overrides' => [$test->component->id => 250],
        ],
        default => [],
    };
}

/** Route parameters for a given route name, from the fixtures. */
function hrPayScopeParams($test, string $route): array
{
    return match ($route) {
        'hr.payroll.show', 'hr.payroll.cancel', 'hr.payroll.approve' => ['payrollRun' => $test->payrollRun->id],
        'hr.payroll.payslip', 'hr.payroll.print-payslip', 'hr.payroll.mark-paid' => ['payslip' => $test->payslip->id],
        'hr.payroll.edit-component', 'hr.payroll.update-component' => ['salaryComponent' => $test->component->id],
        'hr.payroll.destroy-component' => ['salaryComponent' => $test->spareComponent->id],
        'hr.payroll.employee-salary', 'hr.payroll.update-employee-salary' => ['employee' => $test->employee->id],
        default => [],
    };
}

function hrPayScopeRequest($test, string $method, string $route)
{
    $url = route($route, hrPayScopeParams($test, $route));

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, hrPayScopePayload($test, $route));
}

// ── (a) Deny: a wrong-verb holder that passes today must get 403 ──────────

dataset('hr payroll escalations', [
    // payroll runs
    'index via create payroll runs' => ['get', 'hr.payroll.index', ['create payroll runs']],
    'index via edit payroll runs' => ['get', 'hr.payroll.index', ['edit payroll runs']],
    'index via delete payroll runs' => ['get', 'hr.payroll.index', ['delete payroll runs']],
    'index via approve payroll runs' => ['get', 'hr.payroll.index', ['approve payroll runs']],
    'show via create payroll runs' => ['get', 'hr.payroll.show', ['create payroll runs']],
    'show via edit payroll runs' => ['get', 'hr.payroll.show', ['edit payroll runs']],
    'show via delete payroll runs' => ['get', 'hr.payroll.show', ['delete payroll runs']],
    'generate via view payroll runs' => ['post', 'hr.payroll.generate', ['view payroll runs']],
    'generate via view hr' => ['post', 'hr.payroll.generate', ['view hr']],
    'generate via edit payroll runs' => ['post', 'hr.payroll.generate', ['edit payroll runs']],
    'generate via delete payroll runs' => ['post', 'hr.payroll.generate', ['delete payroll runs']],
    'cancel via view payroll runs' => ['post', 'hr.payroll.cancel', ['view payroll runs']],
    'cancel via view hr' => ['post', 'hr.payroll.cancel', ['view hr']],
    'cancel via create payroll runs' => ['post', 'hr.payroll.cancel', ['create payroll runs']],
    'cancel via delete payroll runs' => ['post', 'hr.payroll.cancel', ['delete payroll runs']],
    'approve via view hr' => ['post', 'hr.payroll.approve', ['view hr']],
    'approve via view payroll runs' => ['post', 'hr.payroll.approve', ['view payroll runs']],
    'approve via create payroll runs' => ['post', 'hr.payroll.approve', ['create payroll runs']],
    'approve via edit payroll runs' => ['post', 'hr.payroll.approve', ['edit payroll runs']],
    // payslips
    'payslip via edit payslips' => ['get', 'hr.payroll.payslip', ['edit payslips']],
    'payslip via view payroll runs' => ['get', 'hr.payroll.payslip', ['view payroll runs']],
    'print-payslip via edit payslips' => ['get', 'hr.payroll.print-payslip', ['edit payslips']],
    'print-payslip via view payroll runs' => ['get', 'hr.payroll.print-payslip', ['view payroll runs']],
    'mark-paid via view payslips' => ['post', 'hr.payroll.mark-paid', ['view payslips']],
    'mark-paid via view hr' => ['post', 'hr.payroll.mark-paid', ['view hr']],
    'mark-paid via edit payroll runs' => ['post', 'hr.payroll.mark-paid', ['edit payroll runs']],
    // salary components
    'components via create salary components' => ['get', 'hr.payroll.components', ['create salary components']],
    'components via edit salary components' => ['get', 'hr.payroll.components', ['edit salary components']],
    'components via delete salary components' => ['get', 'hr.payroll.components', ['delete salary components']],
    'create-component via view salary components' => ['get', 'hr.payroll.create-component', ['view salary components']],
    'create-component via view hr' => ['get', 'hr.payroll.create-component', ['view hr']],
    'create-component via edit salary components' => ['get', 'hr.payroll.create-component', ['edit salary components']],
    'create-component via delete salary components' => ['get', 'hr.payroll.create-component', ['delete salary components']],
    'store-component via view salary components' => ['post', 'hr.payroll.store-component', ['view salary components']],
    'store-component via view hr' => ['post', 'hr.payroll.store-component', ['view hr']],
    'store-component via edit salary components' => ['post', 'hr.payroll.store-component', ['edit salary components']],
    'store-component via delete salary components' => ['post', 'hr.payroll.store-component', ['delete salary components']],
    'edit-component via view salary components' => ['get', 'hr.payroll.edit-component', ['view salary components']],
    'edit-component via view hr' => ['get', 'hr.payroll.edit-component', ['view hr']],
    'edit-component via create salary components' => ['get', 'hr.payroll.edit-component', ['create salary components']],
    'edit-component via delete salary components' => ['get', 'hr.payroll.edit-component', ['delete salary components']],
    'update-component via view salary components' => ['put', 'hr.payroll.update-component', ['view salary components']],
    'update-component via view hr' => ['put', 'hr.payroll.update-component', ['view hr']],
    'update-component via create salary components' => ['put', 'hr.payroll.update-component', ['create salary components']],
    'update-component via delete salary components' => ['put', 'hr.payroll.update-component', ['delete salary components']],
    'destroy-component via view salary components' => ['delete', 'hr.payroll.destroy-component', ['view salary components']],
    'destroy-component via view hr' => ['delete', 'hr.payroll.destroy-component', ['view hr']],
    'destroy-component via create salary components' => ['delete', 'hr.payroll.destroy-component', ['create salary components']],
    'destroy-component via edit salary components' => ['delete', 'hr.payroll.destroy-component', ['edit salary components']],
    // employee salary
    'employee-salary via edit employee salary' => ['get', 'hr.payroll.employee-salary', ['edit employee salary']],
    'update-employee-salary via view employee salary' => ['post', 'hr.payroll.update-employee-salary', ['view employee salary']],
    'update-employee-salary via view hr' => ['post', 'hr.payroll.update-employee-salary', ['view hr']],
]);

it('forbids a wrong-verb permission on each HR payroll route', function (string $method, string $route, array $permissions) {
    $this->actingAs(hrPayScopeUser($permissions));

    hrPayScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr payroll escalations');

// ── (a2) `view hr` alone: reads 200, every write/form 403 (incl. approve) ─

dataset('hr payroll view-hr reads', [
    'index' => ['get', 'hr.payroll.index'],
    'show' => ['get', 'hr.payroll.show'],
    'payslip' => ['get', 'hr.payroll.payslip'],
    'print-payslip' => ['get', 'hr.payroll.print-payslip'],
    'components' => ['get', 'hr.payroll.components'],
    'employee-salary' => ['get', 'hr.payroll.employee-salary'],
]);

it('lets view hr alone read each HR payroll surface', function (string $method, string $route) {
    $this->actingAs(hrPayScopeUser(['view hr']));

    hrPayScopeRequest($this, $method, $route)->assertOk();
})->with('hr payroll view-hr reads');

dataset('hr payroll writes', [
    'generate' => ['post', 'hr.payroll.generate'],
    'cancel' => ['post', 'hr.payroll.cancel'],
    'approve' => ['post', 'hr.payroll.approve'],
    'mark-paid' => ['post', 'hr.payroll.mark-paid'],
    'create-component' => ['get', 'hr.payroll.create-component'],
    'store-component' => ['post', 'hr.payroll.store-component'],
    'edit-component' => ['get', 'hr.payroll.edit-component'],
    'update-component' => ['put', 'hr.payroll.update-component'],
    'destroy-component' => ['delete', 'hr.payroll.destroy-component'],
    'update-employee-salary' => ['post', 'hr.payroll.update-employee-salary'],
]);

it('forbids view hr alone on every HR payroll write route', function (string $method, string $route) {
    $this->actingAs(hrPayScopeUser(['view hr']));

    hrPayScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr payroll writes');

it('forbids a user with no permissions on every HR payroll route', function (string $method, string $route) {
    $this->actingAs(hrPayScopeUser([]));

    hrPayScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr payroll writes');

it('forbids a user with no permissions on every HR payroll read route', function (string $method, string $route) {
    $this->actingAs(hrPayScopeUser([]));

    hrPayScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr payroll view-hr reads');

// ── (b) Positive control: exactly the new permission passes ───────────────

dataset('hr payroll positive controls', [
    'index via view payroll runs' => ['get', 'hr.payroll.index', 'view payroll runs'],
    'index via view hr' => ['get', 'hr.payroll.index', 'view hr'],
    'show via view payroll runs' => ['get', 'hr.payroll.show', 'view payroll runs'],
    'show via view hr' => ['get', 'hr.payroll.show', 'view hr'],
    'generate' => ['post', 'hr.payroll.generate', 'create payroll runs'],
    'cancel' => ['post', 'hr.payroll.cancel', 'edit payroll runs'],
    'approve' => ['post', 'hr.payroll.approve', 'approve payroll runs'],
    'payslip via view payslips' => ['get', 'hr.payroll.payslip', 'view payslips'],
    'payslip via view hr' => ['get', 'hr.payroll.payslip', 'view hr'],
    'print-payslip via view payslips' => ['get', 'hr.payroll.print-payslip', 'view payslips'],
    'print-payslip via view hr' => ['get', 'hr.payroll.print-payslip', 'view hr'],
    'mark-paid' => ['post', 'hr.payroll.mark-paid', 'edit payslips'],
    'components via view salary components' => ['get', 'hr.payroll.components', 'view salary components'],
    'components via view hr' => ['get', 'hr.payroll.components', 'view hr'],
    'create-component' => ['get', 'hr.payroll.create-component', 'create salary components'],
    'store-component' => ['post', 'hr.payroll.store-component', 'create salary components'],
    'edit-component' => ['get', 'hr.payroll.edit-component', 'edit salary components'],
    'update-component' => ['put', 'hr.payroll.update-component', 'edit salary components'],
    'destroy-component' => ['delete', 'hr.payroll.destroy-component', 'delete salary components'],
    'employee-salary via view employee salary' => ['get', 'hr.payroll.employee-salary', 'view employee salary'],
    'employee-salary via view hr' => ['get', 'hr.payroll.employee-salary', 'view hr'],
    'update-employee-salary' => ['post', 'hr.payroll.update-employee-salary', 'edit employee salary'],
]);

it('allows exactly the new permission on each HR payroll route', function (string $method, string $route, string $permission) {
    $this->actingAs(hrPayScopeUser([$permission]));

    $response = hrPayScopeRequest($this, $method, $route);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
    expect($response->getStatusCode())->not->toBe(403);
})->with('hr payroll positive controls');

// ── (c) Side effects: a blocked write writes nothing ──────────────────────

it('generates no payroll run when view payroll runs posts generate', function () {
    $this->actingAs(hrPayScopeUser(['view payroll runs']));
    $before = PayrollRun::count();

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.generate');

    expect(PayrollRun::count())->toBe($before)
        ->and(PayrollRun::where('month', 5)->exists())->toBeFalse();
    $response->assertForbidden();
});

it('generates no payroll run when edit payroll runs posts generate', function () {
    $this->actingAs(hrPayScopeUser(['edit payroll runs', 'view payroll runs']));
    $before = PayrollRun::count();

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.generate');

    expect(PayrollRun::count())->toBe($before);
    $response->assertForbidden();
});

it('generates a run and payslip when create payroll runs posts generate', function () {
    $this->actingAs(hrPayScopeUser(['create payroll runs']));

    hrPayScopeRequest($this, 'post', 'hr.payroll.generate')->assertRedirect();

    $run = PayrollRun::where('year', 2026)->where('month', 5)->first();
    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('draft')
        ->and(Payslip::where('payroll_run_id', $run->id)->count())->toBe(1);
});

it('leaves the run draft when view hr posts approve', function () {
    $this->actingAs(hrPayScopeUser(['view hr']));

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.approve');

    expect($this->payrollRun->fresh()->status)->toBe('draft')
        ->and($this->payrollRun->fresh()->approved_by)->toBeNull();
    $response->assertForbidden();
});

it('leaves the run draft when edit payroll runs posts approve', function () {
    $this->actingAs(hrPayScopeUser(['edit payroll runs', 'create payroll runs', 'view payroll runs']));

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.approve');

    expect($this->payrollRun->fresh()->status)->toBe('draft')
        ->and($this->payrollRun->fresh()->approved_by)->toBeNull();
    $response->assertForbidden();
});

it('approves the run when approve payroll runs posts approve', function () {
    $user = hrPayScopeUser(['approve payroll runs']);
    $this->actingAs($user);

    hrPayScopeRequest($this, 'post', 'hr.payroll.approve')->assertRedirect();

    expect($this->payrollRun->fresh()->status)->toBe('completed')
        ->and($this->payrollRun->fresh()->approved_by)->toBe($user->id);
});

it('leaves the run draft when delete payroll runs posts cancel', function () {
    $this->actingAs(hrPayScopeUser(['delete payroll runs']));

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.cancel');

    expect($this->payrollRun->fresh()->status)->toBe('draft');
    $response->assertForbidden();
});

it('cancels the run when edit payroll runs posts cancel', function () {
    $this->actingAs(hrPayScopeUser(['edit payroll runs']));

    hrPayScopeRequest($this, 'post', 'hr.payroll.cancel')->assertRedirect();

    expect($this->payrollRun->fresh()->status)->toBe('cancelled');
});

it('leaves the payslip pending when view payslips posts mark-paid', function () {
    $this->actingAs(hrPayScopeUser(['view payslips']));

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.mark-paid');

    expect($this->payslip->fresh()->payment_status)->toBe('pending');
    $response->assertForbidden();
});

it('leaves the payslip pending when view hr posts mark-paid', function () {
    $this->actingAs(hrPayScopeUser(['view hr']));

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.mark-paid');

    expect($this->payslip->fresh()->payment_status)->toBe('pending');
    $response->assertForbidden();
});

it('marks the payslip paid when edit payslips posts mark-paid', function () {
    $this->actingAs(hrPayScopeUser(['edit payslips']));

    hrPayScopeRequest($this, 'post', 'hr.payroll.mark-paid')->assertRedirect();

    expect($this->payslip->fresh()->payment_status)->toBe('paid')
        ->and($this->payslip->fresh()->payment_method)->toBe('cash');
});

it('creates no salary component when view salary components posts store-component', function () {
    $this->actingAs(hrPayScopeUser(['view salary components']));
    $before = SalaryComponent::count();

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.store-component');

    expect(SalaryComponent::count())->toBe($before)
        ->and(SalaryComponent::where('code', 'HNW')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('creates a salary component when create salary components posts store-component', function () {
    $this->actingAs(hrPayScopeUser(['create salary components']));

    hrPayScopeRequest($this, 'post', 'hr.payroll.store-component')->assertRedirect();

    expect(SalaryComponent::where('code', 'HNW')->exists())->toBeTrue();
});

it('leaves the component unchanged when view salary components puts update-component', function () {
    $this->actingAs(hrPayScopeUser(['view salary components']));

    $response = hrPayScopeRequest($this, 'put', 'hr.payroll.update-component');

    expect($this->component->fresh()->name)->toBe('Hr Pay Scope Component');
    $response->assertForbidden();
});

it('leaves the component unchanged when create salary components puts update-component', function () {
    $this->actingAs(hrPayScopeUser(['create salary components']));

    $response = hrPayScopeRequest($this, 'put', 'hr.payroll.update-component');

    expect($this->component->fresh()->name)->toBe('Hr Pay Scope Component');
    $response->assertForbidden();
});

it('renames the component when edit salary components puts update-component', function () {
    $this->actingAs(hrPayScopeUser(['edit salary components']));

    hrPayScopeRequest($this, 'put', 'hr.payroll.update-component')->assertRedirect();

    expect($this->component->fresh()->name)->toBe('Hr Pay Scope Renamed');
});

it('does not delete the component when edit salary components deletes destroy-component', function () {
    $this->actingAs(hrPayScopeUser(['edit salary components', 'view salary components']));

    $response = hrPayScopeRequest($this, 'delete', 'hr.payroll.destroy-component');

    expect(SalaryComponent::find($this->spareComponent->id))->not->toBeNull();
    $response->assertForbidden();
});

it('does not delete the component when view hr deletes destroy-component', function () {
    $this->actingAs(hrPayScopeUser(['view hr']));

    $response = hrPayScopeRequest($this, 'delete', 'hr.payroll.destroy-component');

    expect(SalaryComponent::find($this->spareComponent->id))->not->toBeNull();
    $response->assertForbidden();
});

it('deletes the component when delete salary components deletes destroy-component', function () {
    $this->actingAs(hrPayScopeUser(['delete salary components']));

    hrPayScopeRequest($this, 'delete', 'hr.payroll.destroy-component')->assertRedirect();

    expect(SalaryComponent::find($this->spareComponent->id))->toBeNull();
});

it('writes no salary override when view employee salary posts update-employee-salary', function () {
    $this->actingAs(hrPayScopeUser(['view employee salary']));

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.update-employee-salary');

    expect(DB::connection('tenant')->table('employee_salary_components')->count())->toBe(0);
    $response->assertForbidden();
});

it('writes no salary override when view hr posts update-employee-salary', function () {
    $this->actingAs(hrPayScopeUser(['view hr']));

    $response = hrPayScopeRequest($this, 'post', 'hr.payroll.update-employee-salary');

    expect(DB::connection('tenant')->table('employee_salary_components')->count())->toBe(0);
    $response->assertForbidden();
});

it('writes the salary override when edit employee salary posts update-employee-salary', function () {
    $this->actingAs(hrPayScopeUser(['edit employee salary']));

    hrPayScopeRequest($this, 'post', 'hr.payroll.update-employee-salary')->assertRedirect();

    $row = DB::connection('tenant')->table('employee_salary_components')
        ->where('employee_id', $this->employee->id)
        ->where('salary_component_id', $this->component->id)
        ->first();
    expect($row)->not->toBeNull()
        ->and((float) $row->amount)->toBe(250.0);
});

// ── H2/H9: seeded Hospital Administrator loses approve + destroy-component ─

it('denies the seeded Hospital Administrator approve and destroy-component but keeps the rest of payroll', function () {
    $this->seed(RolePermissionSeeder::class);

    $ha = User::create([
        'name' => 'Seeded HA Hr Pay',
        'email' => 'hr-pay-scope-ha-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $ha->assignRole('Hospital Administrator');
    $this->actingAs($ha);

    // H2: payroll.approve
    hrPayScopeRequest($this, 'post', 'hr.payroll.approve')->assertForbidden();
    expect($this->payrollRun->fresh()->status)->toBe('draft')
        ->and($this->payrollRun->fresh()->approved_by)->toBeNull();

    // H9: payroll.destroy-component
    hrPayScopeRequest($this, 'delete', 'hr.payroll.destroy-component')->assertForbidden();
    expect(SalaryComponent::find($this->spareComponent->id))->not->toBeNull();

    // What the seeder still grants keeps working.
    hrPayScopeRequest($this, 'get', 'hr.payroll.index')->assertOk();
    hrPayScopeRequest($this, 'get', 'hr.payroll.show')->assertOk();
    hrPayScopeRequest($this, 'get', 'hr.payroll.payslip')->assertOk();
    hrPayScopeRequest($this, 'get', 'hr.payroll.print-payslip')->assertOk();
    hrPayScopeRequest($this, 'get', 'hr.payroll.components')->assertOk();
    hrPayScopeRequest($this, 'get', 'hr.payroll.create-component')->assertOk();
    hrPayScopeRequest($this, 'get', 'hr.payroll.edit-component')->assertOk();
    hrPayScopeRequest($this, 'get', 'hr.payroll.employee-salary')->assertOk();
    expect(hrPayScopeRequest($this, 'post', 'hr.payroll.generate')->getStatusCode())->not->toBe(403);
    expect(hrPayScopeRequest($this, 'post', 'hr.payroll.cancel')->getStatusCode())->not->toBe(403);
    expect(hrPayScopeRequest($this, 'post', 'hr.payroll.mark-paid')->getStatusCode())->not->toBe(403);
    expect(hrPayScopeRequest($this, 'post', 'hr.payroll.store-component')->getStatusCode())->not->toBe(403);
    expect(hrPayScopeRequest($this, 'put', 'hr.payroll.update-component')->getStatusCode())->not->toBe(403);
    expect(hrPayScopeRequest($this, 'post', 'hr.payroll.update-employee-salary')->getStatusCode())->not->toBe(403);
});
