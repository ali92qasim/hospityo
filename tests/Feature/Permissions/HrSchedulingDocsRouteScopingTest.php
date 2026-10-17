<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Department;
use App\Models\DocumentRequirement;
use App\Models\DutyRoster;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| HR scheduling, department-staff and document route scoping (RBAC wave 2, Task 7)
|--------------------------------------------------------------------------
| Each of the 24 `hr.shifts.*`, `hr.department-staff.*` and `hr.documents.*`
| routes accepts exactly its action permission; reads additionally accept the
| legacy `view hr`. Approve/reject swap, shift destroy, document verify/unverify
| and requirement destroy are Super Admin only (H3/H4/H10/H11).
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

    hrSdScopeTenant();

    $this->department = Department::create(['name' => 'Hr Sd Scope Dept', 'code' => 'HSD1']);
    $this->otherDepartment = Department::create(['name' => 'Hr Sd Scope Other', 'code' => 'HSD2']);

    $this->employee = Employee::create([
        'name' => 'Hr Sd Scope Employee',
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
        'department_id' => $this->department->id,
    ]);
    $this->employee2 = Employee::create([
        'name' => 'Hr Sd Scope Employee Two',
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
        'department_id' => $this->department->id,
    ]);

    $shiftAttrs = fn (string $name, string $code) => [
        'name' => $name,
        'code' => $code,
        'start_time' => '08:00',
        'end_time' => '16:00',
        'working_hours' => 8,
    ];
    $this->shift = Shift::create($shiftAttrs('Hr Sd Scope Shift', 'HSS'));
    $this->shift2 = Shift::create($shiftAttrs('Hr Sd Scope Shift Two', 'HS2'));
    // Never referenced by any roster, so destroy can succeed on it.
    $this->spareShift = Shift::create($shiftAttrs('Hr Sd Scope Spare Shift', 'HSP'));

    $swap = fn () => ShiftSwapRequest::create([
        'requester_id' => $this->employee->id,
        'target_id' => $this->employee2->id,
        'swap_date' => '2026-06-01',
        'requester_shift_id' => $this->shift->id,
        'target_shift_id' => $this->shift2->id,
        'status' => 'pending',
    ]);
    $this->swapApprove = $swap();
    $this->swapReject = $swap();

    $docAttrs = fn (string $title, bool $verified) => [
        'employee_id' => $this->employee->id,
        'title' => $title,
        'document_type' => 'cnic',
        'file_path' => 'documents/hr-sd-scope.pdf',
        'is_verified' => $verified,
    ];
    $this->docToVerify = EmployeeDocument::create($docAttrs('Hr Sd Scope Unverified', false));
    $this->docToUnverify = EmployeeDocument::create($docAttrs('Hr Sd Scope Verified', true));

    $this->requirement = DocumentRequirement::create([
        'document_type' => 'cnic',
        'label' => 'Hr Sd Scope Requirement',
        'applicable_to' => 'all',
    ]);
});

function hrSdScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, ['hr', 'hr.employees', 'hr.scheduling'], true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function hrSdScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Hr Sd Scope User',
        'email' => 'hr-sd-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function hrSdScopePayload($test, string $route): array
{
    return match ($route) {
        'hr.shifts.store' => [
            'name' => 'Hr Sd Scope New Shift',
            'code' => 'HNS',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'working_hours' => 8,
        ],
        'hr.shifts.update' => [
            'name' => 'Hr Sd Scope Renamed',
            'code' => 'HSS',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'working_hours' => 8,
        ],
        'hr.shifts.store-roster' => [
            'roster' => [$test->employee->id => ['2026-06-02' => ['shift_id' => $test->shift->id]]],
        ],
        'hr.shifts.auto-generate' => ['week_start' => '2026-06-01'],
        'hr.department-staff.update-head' => [
            'head_employee_id' => $test->employee->id,
            'monthly_budget' => 5000,
        ],
        'hr.department-staff.transfer' => [
            'employee_id' => $test->employee->id,
            'department_id' => $test->otherDepartment->id,
        ],
        'hr.documents.store-requirement' => [
            'document_type' => 'passport',
            'label' => 'Hr Sd Scope New Requirement',
            'applicable_to' => 'all',
        ],
        default => [],
    };
}

/** Route parameters for a given route name, from the fixtures. */
function hrSdScopeParams($test, string $route): array
{
    return match ($route) {
        'hr.shifts.edit', 'hr.shifts.update' => ['shift' => $test->shift->id],
        'hr.shifts.destroy' => ['shift' => $test->spareShift->id],
        'hr.shifts.approve-swap' => ['shiftSwapRequest' => $test->swapApprove->id],
        'hr.shifts.reject-swap' => ['shiftSwapRequest' => $test->swapReject->id],
        'hr.department-staff.show', 'hr.department-staff.update-head' => ['department' => $test->department->id],
        'hr.documents.verify' => ['document' => $test->docToVerify->id],
        'hr.documents.unverify' => ['document' => $test->docToUnverify->id],
        'hr.documents.destroy-requirement' => ['documentRequirement' => $test->requirement->id],
        default => [],
    };
}

function hrSdScopeRequest($test, string $method, string $route)
{
    $url = route($route, hrSdScopeParams($test, $route));

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, hrSdScopePayload($test, $route));
}

/**
 * route => [method, accepted permissions, sibling permissions that must NOT work].
 * Reads accept the legacy `view hr`; writes never do.
 */
function hrSdScopeMatrix(): array
{
    $shifts = ['view shifts', 'create shifts', 'edit shifts', 'delete shifts'];
    $roster = ['view duty roster', 'create duty roster', 'edit duty roster'];
    $swaps = ['view shift swaps', 'approve shift swaps'];
    $staff = ['view department staff', 'edit department staff'];
    $docs = ['view hr documents', 'create hr documents', 'edit hr documents', 'delete hr documents', 'verify hr documents'];

    return [
        'hr.shifts.index' => ['get', ['view shifts', 'view hr'], $shifts],
        'hr.shifts.create' => ['get', ['create shifts'], $shifts],
        'hr.shifts.store' => ['post', ['create shifts'], $shifts],
        'hr.shifts.edit' => ['get', ['edit shifts'], $shifts],
        'hr.shifts.update' => ['put', ['edit shifts'], $shifts],
        'hr.shifts.destroy' => ['delete', ['delete shifts'], $shifts],
        'hr.shifts.roster' => ['get', ['view duty roster', 'view hr'], $roster],
        'hr.shifts.store-roster' => ['post', ['create duty roster'], $roster],
        'hr.shifts.auto-generate' => ['post', ['create duty roster'], $roster],
        'hr.shifts.swap-requests' => ['get', ['view shift swaps', 'view hr'], $swaps],
        'hr.shifts.approve-swap' => ['post', ['approve shift swaps'], $swaps],
        'hr.shifts.reject-swap' => ['post', ['approve shift swaps'], $swaps],
        'hr.department-staff.index' => ['get', ['view department staff', 'view hr'], $staff],
        'hr.department-staff.show' => ['get', ['view department staff', 'view hr'], $staff],
        'hr.department-staff.update-head' => ['put', ['edit department staff'], $staff],
        'hr.department-staff.transfer' => ['post', ['edit department staff'], $staff],
        'hr.documents.index' => ['get', ['view hr documents', 'view hr'], $docs],
        'hr.documents.compliance' => ['get', ['view hr documents', 'view hr'], $docs],
        'hr.documents.requirements' => ['get', ['view hr documents', 'view hr'], $docs],
        'hr.documents.create-requirement' => ['get', ['create hr documents'], $docs],
        'hr.documents.store-requirement' => ['post', ['create hr documents'], $docs],
        'hr.documents.destroy-requirement' => ['delete', ['delete hr documents'], $docs],
        'hr.documents.verify' => ['post', ['verify hr documents'], $docs],
        'hr.documents.unverify' => ['post', ['verify hr documents'], $docs],
    ];
}

// ── (a) Deny: any non-accepted permission (wrong verb, or view hr on writes) ─

dataset('hr scheduling docs escalations', function () {
    foreach (hrSdScopeMatrix() as $route => [$method, $accepted, $siblings]) {
        $candidates = array_values(array_diff(array_unique(array_merge($siblings, ['view hr'])), $accepted));
        foreach ($candidates as $permission) {
            yield "{$route} via {$permission}" => [$method, $route, $permission];
        }
    }
});

it('forbids a non-accepted permission on each HR scheduling/staff/document route', function (string $method, string $route, string $permission) {
    $this->actingAs(hrSdScopeUser([$permission]));

    hrSdScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr scheduling docs escalations');

dataset('hr scheduling docs all routes', function () {
    foreach (hrSdScopeMatrix() as $route => [$method]) {
        yield $route => [$method, $route];
    }
});

it('forbids a user with no permissions on every HR scheduling/staff/document route', function (string $method, string $route) {
    $this->actingAs(hrSdScopeUser([]));

    hrSdScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr scheduling docs all routes');

dataset('hr scheduling docs view-hr reads', function () {
    foreach (hrSdScopeMatrix() as $route => [$method, $accepted]) {
        if (in_array('view hr', $accepted, true)) {
            yield $route => [$method, $route];
        }
    }
});

dataset('hr scheduling docs view-hr writes', function () {
    foreach (hrSdScopeMatrix() as $route => [$method, $accepted]) {
        if (! in_array('view hr', $accepted, true)) {
            yield $route => [$method, $route];
        }
    }
});

it('lets view hr alone read each HR scheduling/staff/document surface', function (string $method, string $route) {
    $this->actingAs(hrSdScopeUser(['view hr']));

    hrSdScopeRequest($this, $method, $route)->assertOk();
})->with('hr scheduling docs view-hr reads');

it('forbids view hr alone on every HR scheduling/staff/document write or form route', function (string $method, string $route) {
    $this->actingAs(hrSdScopeUser(['view hr']));

    hrSdScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr scheduling docs view-hr writes');

// ── (b) Positive control: each accepted permission passes ─────────────────

dataset('hr scheduling docs positive controls', function () {
    foreach (hrSdScopeMatrix() as $route => [$method, $accepted]) {
        foreach ($accepted as $permission) {
            yield "{$route} via {$permission}" => [$method, $route, $permission];
        }
    }
});

it('allows each accepted permission on each HR scheduling/staff/document route', function (string $method, string $route, string $permission) {
    $this->actingAs(hrSdScopeUser([$permission]));

    $response = hrSdScopeRequest($this, $method, $route);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
    expect($response->getStatusCode())->not->toBe(403);
})->with('hr scheduling docs positive controls');

// ── (c) Side effects: a blocked write writes nothing ──────────────────────

it('creates no shift when view shifts posts store', function () {
    $this->actingAs(hrSdScopeUser(['view shifts', 'view hr']));
    $before = Shift::count();

    $response = hrSdScopeRequest($this, 'post', 'hr.shifts.store');

    expect(Shift::count())->toBe($before)
        ->and(Shift::where('code', 'HNS')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('creates a shift when create shifts posts store', function () {
    $this->actingAs(hrSdScopeUser(['create shifts']));

    hrSdScopeRequest($this, 'post', 'hr.shifts.store')->assertRedirect();

    expect(Shift::where('code', 'HNS')->exists())->toBeTrue();
});

it('leaves the shift unchanged when create shifts puts update', function () {
    $this->actingAs(hrSdScopeUser(['create shifts', 'view shifts']));

    $response = hrSdScopeRequest($this, 'put', 'hr.shifts.update');

    expect($this->shift->fresh()->name)->toBe('Hr Sd Scope Shift');
    $response->assertForbidden();
});

it('renames the shift when edit shifts puts update', function () {
    $this->actingAs(hrSdScopeUser(['edit shifts']));

    hrSdScopeRequest($this, 'put', 'hr.shifts.update')->assertRedirect();

    expect($this->shift->fresh()->name)->toBe('Hr Sd Scope Renamed');
});

it('does not delete the shift when edit shifts deletes destroy', function () {
    $this->actingAs(hrSdScopeUser(['edit shifts', 'create shifts', 'view shifts', 'view hr']));

    $response = hrSdScopeRequest($this, 'delete', 'hr.shifts.destroy');

    expect(Shift::find($this->spareShift->id))->not->toBeNull();
    $response->assertForbidden();
});

it('deletes the shift when delete shifts deletes destroy', function () {
    $this->actingAs(hrSdScopeUser(['delete shifts']));

    hrSdScopeRequest($this, 'delete', 'hr.shifts.destroy')->assertRedirect();

    expect(Shift::find($this->spareShift->id))->toBeNull();
});

it('writes no roster when view duty roster posts store-roster', function () {
    $this->actingAs(hrSdScopeUser(['view duty roster', 'view hr']));

    $response = hrSdScopeRequest($this, 'post', 'hr.shifts.store-roster');

    expect(DutyRoster::count())->toBe(0);
    $response->assertForbidden();
});

it('writes the roster when create duty roster posts store-roster', function () {
    $this->actingAs(hrSdScopeUser(['create duty roster']));

    hrSdScopeRequest($this, 'post', 'hr.shifts.store-roster')->assertRedirect();

    expect(DutyRoster::where('employee_id', $this->employee->id)->whereDate('date', '2026-06-02')->exists())->toBeTrue();
});

it('generates no roster when edit duty roster posts auto-generate', function () {
    $this->actingAs(hrSdScopeUser(['edit duty roster', 'view duty roster', 'view hr']));

    $response = hrSdScopeRequest($this, 'post', 'hr.shifts.auto-generate');

    expect(DutyRoster::count())->toBe(0);
    $response->assertForbidden();
});

it('generates the roster when create duty roster posts auto-generate', function () {
    $this->actingAs(hrSdScopeUser(['create duty roster']));

    hrSdScopeRequest($this, 'post', 'hr.shifts.auto-generate')->assertRedirect();

    expect(DutyRoster::count())->toBeGreaterThan(0);
});

it('leaves the swap pending when view shift swaps posts approve-swap', function () {
    $this->actingAs(hrSdScopeUser(['view shift swaps', 'view hr']));

    $response = hrSdScopeRequest($this, 'post', 'hr.shifts.approve-swap');

    expect($this->swapApprove->fresh()->status)->toBe('pending')
        ->and(DutyRoster::count())->toBe(0);
    $response->assertForbidden();
});

it('approves the swap and rewrites the roster when approve shift swaps posts approve-swap', function () {
    $user = hrSdScopeUser(['approve shift swaps']);
    $this->actingAs($user);

    hrSdScopeRequest($this, 'post', 'hr.shifts.approve-swap')->assertRedirect();

    expect($this->swapApprove->fresh()->status)->toBe('approved')
        ->and($this->swapApprove->fresh()->approved_by)->toBe($user->id)
        ->and(DutyRoster::count())->toBe(2);
});

it('leaves the swap pending when view hr posts reject-swap', function () {
    $this->actingAs(hrSdScopeUser(['view hr', 'view shift swaps']));

    $response = hrSdScopeRequest($this, 'post', 'hr.shifts.reject-swap');

    expect($this->swapReject->fresh()->status)->toBe('pending');
    $response->assertForbidden();
});

it('rejects the swap when approve shift swaps posts reject-swap', function () {
    $this->actingAs(hrSdScopeUser(['approve shift swaps']));

    hrSdScopeRequest($this, 'post', 'hr.shifts.reject-swap')->assertRedirect();

    expect($this->swapReject->fresh()->status)->toBe('rejected');
});

it('leaves the department head unchanged when view department staff puts update-head', function () {
    $this->actingAs(hrSdScopeUser(['view department staff', 'view hr']));

    $response = hrSdScopeRequest($this, 'put', 'hr.department-staff.update-head');

    expect($this->department->fresh()->head_employee_id)->toBeNull();
    $response->assertForbidden();
});

it('sets the department head when edit department staff puts update-head', function () {
    $this->actingAs(hrSdScopeUser(['edit department staff']));

    hrSdScopeRequest($this, 'put', 'hr.department-staff.update-head')->assertRedirect();

    expect($this->department->fresh()->head_employee_id)->toBe($this->employee->id);
});

it('does not transfer the employee when view hr posts transfer', function () {
    $this->actingAs(hrSdScopeUser(['view hr', 'view department staff']));

    $response = hrSdScopeRequest($this, 'post', 'hr.department-staff.transfer');

    expect($this->employee->fresh()->department_id)->toBe($this->department->id);
    $response->assertForbidden();
});

it('transfers the employee when edit department staff posts transfer', function () {
    $this->actingAs(hrSdScopeUser(['edit department staff']));

    hrSdScopeRequest($this, 'post', 'hr.department-staff.transfer')->assertRedirect();

    expect($this->employee->fresh()->department_id)->toBe($this->otherDepartment->id);
});

it('creates no requirement when view hr documents posts store-requirement', function () {
    $this->actingAs(hrSdScopeUser(['view hr documents', 'view hr']));
    $before = DocumentRequirement::count();

    $response = hrSdScopeRequest($this, 'post', 'hr.documents.store-requirement');

    expect(DocumentRequirement::count())->toBe($before);
    $response->assertForbidden();
});

it('creates a requirement when create hr documents posts store-requirement', function () {
    $this->actingAs(hrSdScopeUser(['create hr documents']));

    hrSdScopeRequest($this, 'post', 'hr.documents.store-requirement')->assertRedirect();

    expect(DocumentRequirement::where('label', 'Hr Sd Scope New Requirement')->exists())->toBeTrue();
});

it('does not delete the requirement when create hr documents deletes destroy-requirement', function () {
    $this->actingAs(hrSdScopeUser(['create hr documents', 'edit hr documents', 'view hr documents', 'view hr']));

    $response = hrSdScopeRequest($this, 'delete', 'hr.documents.destroy-requirement');

    expect(DocumentRequirement::find($this->requirement->id))->not->toBeNull();
    $response->assertForbidden();
});

it('deletes the requirement when delete hr documents deletes destroy-requirement', function () {
    $this->actingAs(hrSdScopeUser(['delete hr documents']));

    hrSdScopeRequest($this, 'delete', 'hr.documents.destroy-requirement')->assertRedirect();

    expect(DocumentRequirement::find($this->requirement->id))->toBeNull();
});

it('leaves the document unverified when edit hr documents posts verify', function () {
    $this->actingAs(hrSdScopeUser(['edit hr documents', 'view hr documents', 'view hr']));

    $response = hrSdScopeRequest($this, 'post', 'hr.documents.verify');

    expect($this->docToVerify->fresh()->is_verified)->toBeFalse()
        ->and($this->docToVerify->fresh()->verified_by)->toBeNull();
    $response->assertForbidden();
});

it('verifies the document when verify hr documents posts verify', function () {
    $user = hrSdScopeUser(['verify hr documents']);
    $this->actingAs($user);

    hrSdScopeRequest($this, 'post', 'hr.documents.verify')->assertRedirect();

    expect($this->docToVerify->fresh()->is_verified)->toBeTrue()
        ->and($this->docToVerify->fresh()->verified_by)->toBe($user->id);
});

it('leaves the document verified when view hr posts unverify', function () {
    $this->actingAs(hrSdScopeUser(['view hr', 'view hr documents']));

    $response = hrSdScopeRequest($this, 'post', 'hr.documents.unverify');

    expect($this->docToUnverify->fresh()->is_verified)->toBeTrue();
    $response->assertForbidden();
});

it('unverifies the document when verify hr documents posts unverify', function () {
    $this->actingAs(hrSdScopeUser(['verify hr documents']));

    hrSdScopeRequest($this, 'post', 'hr.documents.unverify')->assertRedirect();

    expect($this->docToUnverify->fresh()->is_verified)->toBeFalse();
});

// ── H3/H4/H10/H11: seeded Hospital Administrator loses the Super-Admin-only actions ─

it('denies the seeded Hospital Administrator swap approvals, shift delete, verify and requirement delete but keeps the rest', function () {
    $this->seed(RolePermissionSeeder::class);

    $ha = User::create([
        'name' => 'Seeded HA Hr Sd',
        'email' => 'hr-sd-scope-ha-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $ha->assignRole('Hospital Administrator');
    $this->actingAs($ha);

    // H3: shifts.approve-swap / reject-swap
    hrSdScopeRequest($this, 'post', 'hr.shifts.approve-swap')->assertForbidden();
    hrSdScopeRequest($this, 'post', 'hr.shifts.reject-swap')->assertForbidden();
    expect($this->swapApprove->fresh()->status)->toBe('pending')
        ->and($this->swapReject->fresh()->status)->toBe('pending');

    // H4: shifts.destroy
    hrSdScopeRequest($this, 'delete', 'hr.shifts.destroy')->assertForbidden();
    expect(Shift::find($this->spareShift->id))->not->toBeNull();

    // H10: documents.verify / unverify
    hrSdScopeRequest($this, 'post', 'hr.documents.verify')->assertForbidden();
    hrSdScopeRequest($this, 'post', 'hr.documents.unverify')->assertForbidden();
    expect($this->docToVerify->fresh()->is_verified)->toBeFalse()
        ->and($this->docToUnverify->fresh()->is_verified)->toBeTrue();

    // H11: documents.destroy-requirement
    hrSdScopeRequest($this, 'delete', 'hr.documents.destroy-requirement')->assertForbidden();
    expect(DocumentRequirement::find($this->requirement->id))->not->toBeNull();

    // What the seeder still grants keeps working.
    foreach ([
        'hr.shifts.index', 'hr.shifts.create', 'hr.shifts.edit', 'hr.shifts.roster', 'hr.shifts.swap-requests',
        'hr.department-staff.index', 'hr.department-staff.show',
        'hr.documents.index', 'hr.documents.compliance', 'hr.documents.requirements', 'hr.documents.create-requirement',
    ] as $route) {
        hrSdScopeRequest($this, 'get', $route)->assertOk();
    }
    foreach ([
        ['post', 'hr.shifts.store'],
        ['put', 'hr.shifts.update'],
        ['post', 'hr.shifts.store-roster'],
        ['post', 'hr.shifts.auto-generate'],
        ['post', 'hr.documents.store-requirement'],
    ] as [$method, $route]) {
        expect(hrSdScopeRequest($this, $method, $route)->getStatusCode())->not->toBe(403);
    }

    // HA keeps department-staff writes (edit department staff).
    hrSdScopeRequest($this, 'put', 'hr.department-staff.update-head')->assertRedirect();
    hrSdScopeRequest($this, 'post', 'hr.department-staff.transfer')->assertRedirect();
    expect($this->department->fresh()->head_employee_id)->toBe($this->employee->id)
        ->and($this->employee->fresh()->department_id)->toBe($this->otherDepartment->id);
});
