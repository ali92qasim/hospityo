<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| HR attendance + leave + leave-types route scoping (RBAC wave 2, Task 5)
|--------------------------------------------------------------------------
| Each of the 17 routes (4 attendance, 7 leave, 6 leave-types) accepts exactly
| its action permission; reads additionally accept the legacy `view hr`.
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

    hrAlScopeTenant();

    $this->employee = Employee::create([
        'name' => 'Hr Al Scope Employee',
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
    ]);

    $this->leaveType = LeaveType::create([
        'name' => 'Hr Al Scope Leave',
        'code' => 'HAS',
        'default_days' => 20,
    ]);

    // Never referenced by a request, so destroy can succeed on it.
    $this->spareType = LeaveType::create([
        'name' => 'Hr Al Scope Spare',
        'code' => 'HSP',
        'default_days' => 5,
    ]);

    $this->leaveRequest = LeaveRequest::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => '2026-03-02',
        'end_date' => '2026-03-03',
        'total_days' => 2,
        'reason' => 'Fixture request',
        'status' => 'pending',
    ]);
});

function hrAlScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, ['hr', 'hr.employees', 'hr.attendance-leave'], true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function hrAlScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Hr Al Scope User',
        'email' => 'hr-al-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function hrAlScopePayload($test, string $route): array
{
    return match ($route) {
        'hr.attendance.store-daily' => [
            'date' => '2026-03-02',
            'attendance' => [
                $test->employee->id => ['status' => 'present'],
            ],
        ],
        'hr.leave.store' => [
            'employee_id' => $test->employee->id,
            'leave_type_id' => $test->leaveType->id,
            'start_date' => '2026-04-06',
            'end_date' => '2026-04-07',
            'reason' => 'Created via scoping test',
        ],
        'hr.leave.reject' => [
            'rejection_reason' => 'Rejected via scoping test',
        ],
        'hr.leave-types.store' => [
            'name' => 'Hr Al Scope New Type',
            'code' => 'HNW',
            'default_days' => 7,
        ],
        'hr.leave-types.update' => [
            'name' => 'Hr Al Scope Renamed Type',
            'code' => 'HAS',
            'default_days' => 20,
        ],
        default => [],
    };
}

/** Route parameters for a given route name, from the fixtures. */
function hrAlScopeParams($test, string $route): array
{
    return match ($route) {
        'hr.leave.approve', 'hr.leave.reject', 'hr.leave.cancel' => ['leaveRequest' => $test->leaveRequest->id],
        'hr.leave-types.edit', 'hr.leave-types.update' => ['leaveType' => $test->leaveType->id],
        'hr.leave-types.destroy' => ['leaveType' => $test->spareType->id],
        default => [],
    };
}

function hrAlScopeRequest($test, string $method, string $route)
{
    $url = route($route, hrAlScopeParams($test, $route));

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, hrAlScopePayload($test, $route));
}

// ── (a) Deny: a wrong-verb holder that passes today must get 403 ──────────

dataset('hr attendance + leave escalations', [
    // attendance
    'attendance.mark via view attendance' => ['get', 'hr.attendance.mark', ['view attendance']],
    'attendance.mark via view hr' => ['get', 'hr.attendance.mark', ['view hr']],
    'attendance.mark via edit attendance' => ['get', 'hr.attendance.mark', ['edit attendance']],
    'attendance.store-daily via view attendance' => ['post', 'hr.attendance.store-daily', ['view attendance']],
    'attendance.store-daily via view hr' => ['post', 'hr.attendance.store-daily', ['view hr']],
    'attendance.store-daily via edit attendance' => ['post', 'hr.attendance.store-daily', ['edit attendance']],
    'attendance.index via create attendance' => ['get', 'hr.attendance.index', ['create attendance']],
    'attendance.monthly via create attendance' => ['get', 'hr.attendance.monthly', ['create attendance']],
    'attendance.index via edit attendance' => ['get', 'hr.attendance.index', ['edit attendance']],
    // leave
    'leave.index via create leave requests' => ['get', 'hr.leave.index', ['create leave requests']],
    'leave.index via approve leave requests' => ['get', 'hr.leave.index', ['approve leave requests']],
    'leave.create via view leave requests' => ['get', 'hr.leave.create', ['view leave requests']],
    'leave.create via view hr' => ['get', 'hr.leave.create', ['view hr']],
    'leave.store via view leave requests' => ['post', 'hr.leave.store', ['view leave requests']],
    'leave.store via view hr' => ['post', 'hr.leave.store', ['view hr']],
    'leave.store via edit leave requests' => ['post', 'hr.leave.store', ['edit leave requests']],
    'leave.approve via view hr' => ['post', 'hr.leave.approve', ['view hr']],
    'leave.approve via view leave requests' => ['post', 'hr.leave.approve', ['view leave requests']],
    'leave.approve via create leave requests' => ['post', 'hr.leave.approve', ['create leave requests']],
    'leave.approve via edit leave requests' => ['post', 'hr.leave.approve', ['edit leave requests']],
    'leave.reject via view hr' => ['post', 'hr.leave.reject', ['view hr']],
    'leave.reject via view leave requests' => ['post', 'hr.leave.reject', ['view leave requests']],
    'leave.reject via edit leave requests' => ['post', 'hr.leave.reject', ['edit leave requests']],
    'leave.cancel via view hr' => ['post', 'hr.leave.cancel', ['view hr']],
    'leave.cancel via view leave requests' => ['post', 'hr.leave.cancel', ['view leave requests']],
    'leave.cancel via create leave requests' => ['post', 'hr.leave.cancel', ['create leave requests']],
    'leave.cancel via delete leave requests' => ['post', 'hr.leave.cancel', ['delete leave requests']],
    'leave.balances via view leave requests' => ['get', 'hr.leave.balances', ['view leave requests']],
    'leave.balances via create leave requests' => ['get', 'hr.leave.balances', ['create leave requests']],
    // leave-types
    'leave-types.index via create leave types' => ['get', 'hr.leave-types.index', ['create leave types']],
    'leave-types.index via delete leave types' => ['get', 'hr.leave-types.index', ['delete leave types']],
    'leave-types.create via view leave types' => ['get', 'hr.leave-types.create', ['view leave types']],
    'leave-types.create via view hr' => ['get', 'hr.leave-types.create', ['view hr']],
    'leave-types.store via view leave types' => ['post', 'hr.leave-types.store', ['view leave types']],
    'leave-types.store via view hr' => ['post', 'hr.leave-types.store', ['view hr']],
    'leave-types.edit via view leave types' => ['get', 'hr.leave-types.edit', ['view leave types']],
    'leave-types.edit via view hr' => ['get', 'hr.leave-types.edit', ['view hr']],
    'leave-types.edit via create leave types' => ['get', 'hr.leave-types.edit', ['create leave types']],
    'leave-types.update via view leave types' => ['put', 'hr.leave-types.update', ['view leave types']],
    'leave-types.update via view hr' => ['put', 'hr.leave-types.update', ['view hr']],
    'leave-types.update via create leave types' => ['put', 'hr.leave-types.update', ['create leave types']],
    'leave-types.destroy via view leave types' => ['delete', 'hr.leave-types.destroy', ['view leave types']],
    'leave-types.destroy via view hr' => ['delete', 'hr.leave-types.destroy', ['view hr']],
    'leave-types.destroy via edit leave types' => ['delete', 'hr.leave-types.destroy', ['edit leave types']],
]);

it('forbids a wrong-verb permission on each HR attendance/leave route', function (string $method, string $route, array $permissions) {
    $this->actingAs(hrAlScopeUser($permissions));

    hrAlScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr attendance + leave escalations');

// ── (a2) `view hr` alone: reads 200, every write/form 403 (incl. approve) ─

dataset('hr attendance + leave view-hr reads', [
    'attendance.index' => ['get', 'hr.attendance.index'],
    'attendance.monthly' => ['get', 'hr.attendance.monthly'],
    'leave.index' => ['get', 'hr.leave.index'],
    'leave.balances' => ['get', 'hr.leave.balances'],
    'leave-types.index' => ['get', 'hr.leave-types.index'],
]);

it('lets view hr alone read each HR attendance/leave list', function (string $method, string $route) {
    $this->actingAs(hrAlScopeUser(['view hr']));

    hrAlScopeRequest($this, $method, $route)->assertOk();
})->with('hr attendance + leave view-hr reads');

dataset('hr attendance + leave writes', [
    'attendance.mark' => ['get', 'hr.attendance.mark'],
    'attendance.store-daily' => ['post', 'hr.attendance.store-daily'],
    'leave.create' => ['get', 'hr.leave.create'],
    'leave.store' => ['post', 'hr.leave.store'],
    'leave.approve' => ['post', 'hr.leave.approve'],
    'leave.reject' => ['post', 'hr.leave.reject'],
    'leave.cancel' => ['post', 'hr.leave.cancel'],
    'leave-types.create' => ['get', 'hr.leave-types.create'],
    'leave-types.store' => ['post', 'hr.leave-types.store'],
    'leave-types.edit' => ['get', 'hr.leave-types.edit'],
    'leave-types.update' => ['put', 'hr.leave-types.update'],
    'leave-types.destroy' => ['delete', 'hr.leave-types.destroy'],
]);

it('forbids view hr alone on every HR attendance/leave write route', function (string $method, string $route) {
    $this->actingAs(hrAlScopeUser(['view hr']));

    hrAlScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr attendance + leave writes');

it('forbids a user with no permissions on every HR attendance/leave route', function (string $method, string $route) {
    $this->actingAs(hrAlScopeUser([]));

    hrAlScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr attendance + leave writes');

// ── (b) Positive control: exactly the new permission passes ───────────────

dataset('hr attendance + leave positive controls', [
    'attendance.index via view attendance' => ['get', 'hr.attendance.index', 'view attendance'],
    'attendance.index via view hr' => ['get', 'hr.attendance.index', 'view hr'],
    'attendance.monthly via view attendance' => ['get', 'hr.attendance.monthly', 'view attendance'],
    'attendance.monthly via view hr' => ['get', 'hr.attendance.monthly', 'view hr'],
    'attendance.mark' => ['get', 'hr.attendance.mark', 'create attendance'],
    'attendance.store-daily' => ['post', 'hr.attendance.store-daily', 'create attendance'],
    'leave.index via view leave requests' => ['get', 'hr.leave.index', 'view leave requests'],
    'leave.index via view hr' => ['get', 'hr.leave.index', 'view hr'],
    'leave.create' => ['get', 'hr.leave.create', 'create leave requests'],
    'leave.store' => ['post', 'hr.leave.store', 'create leave requests'],
    'leave.approve' => ['post', 'hr.leave.approve', 'approve leave requests'],
    'leave.reject' => ['post', 'hr.leave.reject', 'approve leave requests'],
    'leave.cancel' => ['post', 'hr.leave.cancel', 'edit leave requests'],
    'leave.balances via view leave balances' => ['get', 'hr.leave.balances', 'view leave balances'],
    'leave.balances via view hr' => ['get', 'hr.leave.balances', 'view hr'],
    'leave-types.index via view leave types' => ['get', 'hr.leave-types.index', 'view leave types'],
    'leave-types.index via view hr' => ['get', 'hr.leave-types.index', 'view hr'],
    'leave-types.create' => ['get', 'hr.leave-types.create', 'create leave types'],
    'leave-types.store' => ['post', 'hr.leave-types.store', 'create leave types'],
    'leave-types.edit' => ['get', 'hr.leave-types.edit', 'edit leave types'],
    'leave-types.update' => ['put', 'hr.leave-types.update', 'edit leave types'],
    'leave-types.destroy' => ['delete', 'hr.leave-types.destroy', 'delete leave types'],
]);

it('allows exactly the new permission on each HR attendance/leave route', function (string $method, string $route, string $permission) {
    $this->actingAs(hrAlScopeUser([$permission]));

    $response = hrAlScopeRequest($this, $method, $route);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
    expect($response->getStatusCode())->not->toBe(403);
})->with('hr attendance + leave positive controls');

// ── (c) Side effects: a blocked write writes nothing ──────────────────────

it('marks no attendance when view attendance posts store-daily', function () {
    $this->actingAs(hrAlScopeUser(['view attendance']));
    $before = Attendance::count();

    $response = hrAlScopeRequest($this, 'post', 'hr.attendance.store-daily');

    expect(Attendance::count())->toBe($before);
    $response->assertForbidden();
});

it('marks no attendance when edit attendance posts store-daily', function () {
    $this->actingAs(hrAlScopeUser(['edit attendance', 'view attendance']));
    $before = Attendance::count();

    $response = hrAlScopeRequest($this, 'post', 'hr.attendance.store-daily');

    expect(Attendance::count())->toBe($before);
    $response->assertForbidden();
});

it('creates a row when create attendance posts store-daily', function () {
    $this->actingAs(hrAlScopeUser(['create attendance']));

    hrAlScopeRequest($this, 'post', 'hr.attendance.store-daily')->assertRedirect();

    expect(Attendance::where('employee_id', $this->employee->id)->where('status', 'present')->count())->toBe(1);
});

it('creates no leave request when view leave requests posts store', function () {
    $this->actingAs(hrAlScopeUser(['view leave requests']));
    $before = LeaveRequest::count();

    $response = hrAlScopeRequest($this, 'post', 'hr.leave.store');

    expect(LeaveRequest::count())->toBe($before);
    $response->assertForbidden();
});

it('leaves the request pending when view hr posts approve', function () {
    $this->actingAs(hrAlScopeUser(['view hr']));

    $response = hrAlScopeRequest($this, 'post', 'hr.leave.approve');

    expect($this->leaveRequest->fresh()->status)->toBe('pending')
        ->and($this->leaveRequest->fresh()->approved_by)->toBeNull()
        ->and(Attendance::count())->toBe(0);
    $response->assertForbidden();
});

it('leaves the request pending when edit leave requests posts approve', function () {
    $this->actingAs(hrAlScopeUser(['edit leave requests', 'create leave requests', 'view leave requests']));

    $response = hrAlScopeRequest($this, 'post', 'hr.leave.approve');

    expect($this->leaveRequest->fresh()->status)->toBe('pending')
        ->and(Attendance::count())->toBe(0);
    $response->assertForbidden();
});

it('leaves the request pending when view hr posts reject', function () {
    $this->actingAs(hrAlScopeUser(['view hr']));

    $response = hrAlScopeRequest($this, 'post', 'hr.leave.reject');

    expect($this->leaveRequest->fresh()->status)->toBe('pending')
        ->and($this->leaveRequest->fresh()->rejection_reason)->toBeNull();
    $response->assertForbidden();
});

it('leaves the request pending when delete leave requests posts cancel', function () {
    $this->actingAs(hrAlScopeUser(['delete leave requests']));

    $response = hrAlScopeRequest($this, 'post', 'hr.leave.cancel');

    expect($this->leaveRequest->fresh()->status)->toBe('pending');
    $response->assertForbidden();
});

it('approves the request when approve leave requests posts approve', function () {
    $user = hrAlScopeUser(['approve leave requests']);
    $this->actingAs($user);

    hrAlScopeRequest($this, 'post', 'hr.leave.approve')->assertRedirect();

    expect($this->leaveRequest->fresh()->status)->toBe('approved')
        ->and($this->leaveRequest->fresh()->approved_by)->toBe($user->id);
});

it('creates no leave type when view leave types posts store', function () {
    $this->actingAs(hrAlScopeUser(['view leave types']));
    $before = LeaveType::count();

    $response = hrAlScopeRequest($this, 'post', 'hr.leave-types.store');

    expect(LeaveType::count())->toBe($before)
        ->and(LeaveType::where('code', 'HNW')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the leave type unchanged when view leave types puts update', function () {
    $this->actingAs(hrAlScopeUser(['view leave types']));

    $response = hrAlScopeRequest($this, 'put', 'hr.leave-types.update');

    expect($this->leaveType->fresh()->name)->toBe('Hr Al Scope Leave');
    $response->assertForbidden();
});

it('does not delete the leave type when edit leave types deletes destroy', function () {
    $this->actingAs(hrAlScopeUser(['edit leave types', 'view leave types']));

    $response = hrAlScopeRequest($this, 'delete', 'hr.leave-types.destroy');

    expect(LeaveType::find($this->spareType->id))->not->toBeNull();
    $response->assertForbidden();
});

it('does not delete the leave type when view hr deletes destroy', function () {
    $this->actingAs(hrAlScopeUser(['view hr']));

    $response = hrAlScopeRequest($this, 'delete', 'hr.leave-types.destroy');

    expect(LeaveType::find($this->spareType->id))->not->toBeNull();
    $response->assertForbidden();
});

it('deletes the leave type when delete leave types deletes destroy', function () {
    $this->actingAs(hrAlScopeUser(['delete leave types']));

    hrAlScopeRequest($this, 'delete', 'hr.leave-types.destroy')->assertRedirect();

    expect(LeaveType::find($this->spareType->id))->toBeNull();
});

// ── H1/H8: seeded Hospital Administrator loses approve, reject, destroy ───

it('denies the seeded Hospital Administrator approve, reject and leave-type destroy but keeps view create and edit', function () {
    $this->seed(RolePermissionSeeder::class);

    $ha = User::create([
        'name' => 'Seeded HA Hr Al',
        'email' => 'hr-al-scope-ha-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $ha->assignRole('Hospital Administrator');
    $this->actingAs($ha);

    // H1: leave.approve + leave.reject
    hrAlScopeRequest($this, 'post', 'hr.leave.approve')->assertForbidden();
    hrAlScopeRequest($this, 'post', 'hr.leave.reject')->assertForbidden();
    expect($this->leaveRequest->fresh()->status)->toBe('pending');

    // H8: leave-types.destroy
    hrAlScopeRequest($this, 'delete', 'hr.leave-types.destroy')->assertForbidden();
    expect(LeaveType::find($this->spareType->id))->not->toBeNull();

    // What the seeder still grants keeps working.
    hrAlScopeRequest($this, 'get', 'hr.attendance.index')->assertOk();
    hrAlScopeRequest($this, 'get', 'hr.attendance.monthly')->assertOk();
    hrAlScopeRequest($this, 'get', 'hr.attendance.mark')->assertOk();
    hrAlScopeRequest($this, 'get', 'hr.leave.index')->assertOk();
    hrAlScopeRequest($this, 'get', 'hr.leave.create')->assertOk();
    hrAlScopeRequest($this, 'get', 'hr.leave.balances')->assertOk();
    hrAlScopeRequest($this, 'get', 'hr.leave-types.index')->assertOk();
    hrAlScopeRequest($this, 'get', 'hr.leave-types.create')->assertOk();
    hrAlScopeRequest($this, 'get', 'hr.leave-types.edit')->assertOk();
    expect(hrAlScopeRequest($this, 'post', 'hr.attendance.store-daily')->getStatusCode())->not->toBe(403);
    expect(hrAlScopeRequest($this, 'post', 'hr.leave.store')->getStatusCode())->not->toBe(403);
    expect(hrAlScopeRequest($this, 'put', 'hr.leave-types.update')->getStatusCode())->not->toBe(403);
    expect(hrAlScopeRequest($this, 'post', 'hr.leave.cancel')->getStatusCode())->not->toBe(403);
});
