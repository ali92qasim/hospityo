<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Designation;
use App\Models\DocumentRequirement;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\SalaryComponent;
use App\Models\Shift;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| HR AD-1: redirect after write only to a page the user can view (Task 8)
|--------------------------------------------------------------------------
| A writer without the matching `view …` permission must not be bounced into
| a list that 403s. Writes that already `back()` (approve, reject, verify,
| cancel, mark-paid, department-staff) are intentionally not covered here.
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

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $this->employee = Employee::create([
        'name' => 'Hr Wr Employee',
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
    ]);
    $this->designation = Designation::create([
        'name' => 'Hr Wr Designation',
        'category' => 'admin',
        'description' => 'Fixture',
    ]);
    $this->leaveType = LeaveType::create(['name' => 'Hr Wr Leave', 'code' => 'HWL', 'default_days' => 20]);
    $this->shift = Shift::create([
        'name' => 'Hr Wr Shift', 'code' => 'HWS',
        'start_time' => '08:00', 'end_time' => '16:00', 'working_hours' => 8,
    ]);
    $this->requirement = DocumentRequirement::create([
        'document_type' => 'cnic', 'label' => 'Hr Wr Requirement', 'applicable_to' => 'all',
    ]);
    $this->component = SalaryComponent::create([
        'name' => 'Hr Wr Component', 'code' => 'HWC',
        'type' => 'allowance', 'calculation' => 'fixed', 'default_amount' => 10,
    ]);
});

function hrWrUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Hr Wr User',
        'email' => 'hr-wr-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * case => [method, URL, payload, write permission, view permission, list URL, fallback URL|null (back())].
 * URLs that depend on state created by the request are closures.
 */
function hrWrCase($test, string $name): array
{
    return match ($name) {
        'employees.store' => [
            'post', route('hr.employees.store'),
            ['name' => 'Hr Wr New Employee', 'joining_date' => '2026-02-01'],
            'create employees', 'view employees',
            fn () => route('hr.employees.index'), fn () => route('hr.employees.create'),
        ],
        'employees.update' => [
            'put', route('hr.employees.update', $test->employee->id),
            ['name' => 'Hr Wr Renamed', 'employment_type' => 'full_time', 'joining_date' => '2026-01-01', 'status' => 'active'],
            'edit employees', 'view employees',
            fn () => route('hr.employees.show', $test->employee->id), fn () => route('hr.employees.edit', $test->employee->id),
        ],
        'employees.destroy' => [
            'delete', route('hr.employees.destroy', $test->employee->id), [],
            'delete employees', 'view employees',
            fn () => route('hr.employees.index'), null,
        ],
        'designations.store' => [
            'post', route('hr.designations.store'),
            ['name' => 'Hr Wr New Designation', 'category' => 'admin', 'description' => 'x'],
            'create designations', 'view designations',
            fn () => route('hr.designations.index'), fn () => route('hr.designations.create'),
        ],
        'leave.store' => [
            'post', route('hr.leave.store'),
            [
                'employee_id' => $test->employee->id, 'leave_type_id' => $test->leaveType->id,
                'start_date' => '2026-04-06', 'end_date' => '2026-04-07', 'reason' => 'AD-1',
            ],
            'create leave requests', 'view leave requests',
            fn () => route('hr.leave.index'), fn () => route('hr.leave.create'),
        ],
        'leave-types.destroy' => [
            'delete', route('hr.leave-types.destroy', $test->leaveType->id), [],
            'delete leave types', 'view leave types',
            fn () => route('hr.leave-types.index'), null,
        ],
        'attendance.store-daily' => [
            'post', route('hr.attendance.store-daily'),
            ['date' => '2026-03-02', 'attendance' => [$test->employee->id => ['status' => 'present']]],
            'create attendance', 'view attendance',
            fn () => route('hr.attendance.index', ['date' => '2026-03-02']),
            fn () => route('hr.attendance.mark', ['date' => '2026-03-02']),
        ],
        'payroll.generate' => [
            'post', route('hr.payroll.generate'), ['year' => 2026, 'month' => 5],
            'create payroll runs', 'view payroll runs',
            fn () => route('hr.payroll.show', PayrollRun::query()->latest('id')->firstOrFail()), null,
        ],
        'payroll.store-component' => [
            'post', route('hr.payroll.store-component'),
            ['name' => 'Hr Wr New Component', 'code' => 'HWN', 'type' => 'allowance', 'calculation' => 'fixed', 'default_amount' => 5],
            'create salary components', 'view salary components',
            fn () => route('hr.payroll.components'), fn () => route('hr.payroll.create-component'),
        ],
        'shifts.store' => [
            'post', route('hr.shifts.store'),
            ['name' => 'Hr Wr New Shift', 'code' => 'HWN', 'start_time' => '09:00', 'end_time' => '17:00', 'working_hours' => 8],
            'create shifts', 'view shifts',
            fn () => route('hr.shifts.index'), fn () => route('hr.shifts.create'),
        ],
        'shifts.destroy' => [
            'delete', route('hr.shifts.destroy', $test->shift->id), [],
            'delete shifts', 'view shifts',
            fn () => route('hr.shifts.index'), null,
        ],
        'shifts.store-roster' => [
            'post', route('hr.shifts.store-roster'),
            [
                'period' => 'weekly', 'week_start' => '2026-06-01',
                'roster' => [$test->employee->id => ['2026-06-02' => ['shift_id' => $test->shift->id]]],
            ],
            'create duty roster', 'view duty roster',
            fn () => route('hr.shifts.roster', ['period' => 'weekly', 'week_start' => '2026-06-01']), null,
        ],
        'shifts.auto-generate' => [
            'post', route('hr.shifts.auto-generate'), ['week_start' => '2026-06-01'],
            'create duty roster', 'view duty roster',
            fn () => route('hr.shifts.roster', ['week_start' => '2026-06-01']), null,
        ],
        'documents.store-requirement' => [
            'post', route('hr.documents.store-requirement'),
            ['document_type' => 'passport', 'label' => 'Hr Wr New Requirement', 'applicable_to' => 'all'],
            'create hr documents', 'view hr documents',
            fn () => route('hr.documents.requirements'), fn () => route('hr.documents.create-requirement'),
        ],
        'documents.destroy-requirement' => [
            'delete', route('hr.documents.destroy-requirement', $test->requirement->id), [],
            'delete hr documents', 'view hr documents',
            fn () => route('hr.documents.requirements'), null,
        ],
    };
}

function hrWrCases(): array
{
    return array_map(
        fn (string $name) => [$name],
        [
            'employees.store', 'employees.update', 'employees.destroy', 'designations.store',
            'leave.store', 'leave-types.destroy', 'attendance.store-daily', 'payroll.generate',
            'payroll.store-component', 'shifts.store', 'shifts.destroy', 'shifts.store-roster',
            'shifts.auto-generate', 'documents.store-requirement', 'documents.destroy-requirement',
        ],
    );
}

dataset('hr list-bound writes', array_combine(
    array_column(hrWrCases(), 0),
    hrWrCases(),
));

function hrWrSend($test, array $case, array $permissions)
{
    [$method, $url, $payload] = $case;

    $test->actingAs(hrWrUser($permissions));
    $test->from($previous = url('/dashboard?from=hr-wr'));

    return [$test->{$method}($url, $payload), $previous];
}

it('sends a write-only holder to the fallback (or back) with the success flash', function (string $name) {
    $case = hrWrCase($this, $name);
    [, , , $write, , , $fallback] = $case;

    [$response, $previous] = hrWrSend($this, $case, [$write]);

    $response->assertRedirect($fallback === null ? $previous : $fallback())
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
})->with('hr list-bound writes');

it('sends a write + view holder to the list with the success flash', function (string $name) {
    $case = hrWrCase($this, $name);
    [, , , $write, $view, $list] = $case;

    [$response] = hrWrSend($this, $case, [$write, $view]);

    $response->assertRedirect($list())
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
})->with('hr list-bound writes');

it('treats the legacy view hr permission as able to view the list', function (string $name) {
    $case = hrWrCase($this, $name);
    [, , , $write, , $list] = $case;

    [$response] = hrWrSend($this, $case, [$write, 'view hr']);

    $response->assertRedirect($list())->assertSessionHas('success');
})->with('hr list-bound writes');
