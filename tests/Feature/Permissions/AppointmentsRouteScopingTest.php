<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Appointments route scoping (RBAC wave 3 remainder, Task 1)
|--------------------------------------------------------------------------
| Each appointments HTTP route accepts exactly one action permission.
| There is no delete action: the destroy route and `delete appointments`
| permission no longer exist. calendar.events requires `view appointments`.
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    apptScopeTenant();

    $this->appointment = apptScopeAppointment();
});

function apptScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function apptScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Appointments Scope User',
        'email' => 'appt-scope-user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function apptScopeAppointment(): Appointment
{
    $patient = Patient::create([
        'name' => 'Appt Scope Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001112222',
    ]);

    $doctor = Doctor::create([
        'name' => 'Appt Scope Doctor',
        'specialization' => 'Cardiology',
        'qualification' => 'MBBS',
        'phone' => '03003334444',
        'email' => 'appt-scope-doctor-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 8,
        'consultation_fee' => 1500,
        'available_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        'shift_start' => '09:00',
        'shift_end' => '17:00',
        'status' => 'active',
    ]);

    return Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_datetime' => now()->addDays(3)->setTime(10, 0),
        'status' => 'scheduled',
        'reason' => 'Scope check',
    ]);
}

function apptScopePayload(string $route, Appointment $appointment): array
{
    return match ($route) {
        'appointments.store', 'appointments.update' => [
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'appointment_datetime' => now()->addDays(5)->setTime(11, 0)->format('Y-m-d H:i'),
            'reason' => 'Scope payload',
        ],
        default => [],
    };
}

function apptScopeUrl(string $route, Appointment $appointment): string
{
    return in_array($route, ['appointments.show', 'appointments.edit', 'appointments.update'], true)
        ? route($route, ['appointment' => $appointment->id])
        : route($route);
}

function apptScopeRequest($test, string $method, string $route, Appointment $appointment, array $extra = [])
{
    $url = apptScopeUrl($route, $appointment);

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, array_merge(apptScopePayload($route, $appointment), $extra));
}

// ── 1. Deny: wrong-verb holder must get 403 ──────────────────────────────

dataset('appointments escalations', [
    'index via create only' => ['get', 'appointments.index', ['create appointments']],
    'index via edit only' => ['get', 'appointments.index', ['edit appointments']],
    'show via create only' => ['get', 'appointments.show', ['create appointments']],
    'show via edit only' => ['get', 'appointments.show', ['edit appointments']],
    'create via view only' => ['get', 'appointments.create', ['view appointments']],
    'create via edit only' => ['get', 'appointments.create', ['edit appointments']],
    'store via view only' => ['post', 'appointments.store', ['view appointments']],
    'store via edit only' => ['post', 'appointments.store', ['edit appointments']],
    'edit via view only' => ['get', 'appointments.edit', ['view appointments']],
    'edit via create only' => ['get', 'appointments.edit', ['create appointments']],
    'update via view only' => ['put', 'appointments.update', ['view appointments']],
    'update via create only' => ['put', 'appointments.update', ['create appointments']],
    'calendar via create only' => ['get', 'calendar.events', ['create appointments']],
    'calendar via edit only' => ['get', 'calendar.events', ['edit appointments']],
]);

it('forbids a wrong-verb permission on each appointments route', function (string $method, string $route, array $permissions) {
    $this->actingAs(apptScopeUser($permissions));

    apptScopeRequest($this, $method, $route, $this->appointment)->assertForbidden();
})->with('appointments escalations');

// ── 2. Positive control: exactly the matching permission passes ──────────

dataset('appointments positive controls', [
    'index' => ['get', 'appointments.index', 'view appointments'],
    'show' => ['get', 'appointments.show', 'view appointments'],
    'create' => ['get', 'appointments.create', 'create appointments'],
    'store' => ['post', 'appointments.store', 'create appointments'],
    'edit' => ['get', 'appointments.edit', 'edit appointments'],
    'update' => ['put', 'appointments.update', 'edit appointments'],
    'calendar' => ['get', 'calendar.events', 'view appointments'],
]);

it('allows exactly the matching permission on each appointments route', function (string $method, string $route, string $permission) {
    $this->actingAs(apptScopeUser([$permission]));

    $response = apptScopeRequest($this, $method, $route, $this->appointment);

    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()->assertSessionHasNoErrors();
})->with('appointments positive controls');

// ── 3. Side effects: a blocked write writes nothing ──────────────────────

it('creates no appointment when view appointments posts store', function () {
    $this->actingAs(apptScopeUser(['view appointments']));
    $count = Appointment::count();

    apptScopeRequest($this, 'post', 'appointments.store', $this->appointment)->assertForbidden();

    expect(Appointment::count())->toBe($count);
});

it('leaves the appointment unchanged when view appointments puts update', function () {
    $this->actingAs(apptScopeUser(['view appointments']));

    apptScopeRequest($this, 'put', 'appointments.update', $this->appointment)->assertForbidden();

    expect($this->appointment->fresh()->reason)->toBe('Scope check');
});

// ── 4. No delete: route, permission and registry entry are gone ──────────

it('does not register an appointments destroy route', function () {
    expect(Route::has('appointments.destroy'))->toBeFalse();
});

it('has no DELETE route for an appointment URL', function () {
    $this->actingAs(apptScopeUser(['view appointments', 'create appointments', 'edit appointments']));

    $this->delete(url('appointments/'.$this->appointment->id))->assertStatus(405);

    expect(Appointment::find($this->appointment->id))->not->toBeNull();
});

it('does not define destroy on the appointment controller', function () {
    expect(method_exists(\App\Http\Controllers\AppointmentController::class, 'destroy'))->toBeFalse();
});

it('no longer lists delete appointments in the permission registry', function () {
    expect(PermissionRegistry::all())->not->toContain('delete appointments');
});

it('forbids a user with only delete appointments on every appointments route', function () {
    $this->actingAs(apptScopeUser(['delete appointments']));

    foreach ([
        ['get', 'appointments.index'],
        ['get', 'appointments.create'],
        ['post', 'appointments.store'],
        ['get', 'appointments.show'],
        ['get', 'appointments.edit'],
        ['put', 'appointments.update'],
        ['get', 'calendar.events'],
    ] as [$method, $route]) {
        apptScopeRequest($this, $method, $route, $this->appointment)->assertForbidden();
    }
});

// ── 5. UI gating ─────────────────────────────────────────────────────────

it('shows no Delete control on the appointments index', function () {
    $this->actingAs(apptScopeUser(['view appointments', 'create appointments', 'edit appointments']));

    $this->get(route('appointments.index'))
        ->assertOk()
        ->assertDontSee('fa-trash', false)
        ->assertDontSee('method="POST" class="inline"', false)
        ->assertDontSee('name="_method" value="DELETE"', false);
});

it('hides schedule and edit links from a view-only user on the index', function () {
    $this->actingAs(apptScopeUser(['view appointments']));

    $this->get(route('appointments.index'))
        ->assertOk()
        ->assertSee(e(route('appointments.show', $this->appointment)), false)
        ->assertDontSee(e(route('appointments.create')), false)
        ->assertDontSee(e(route('appointments.edit', $this->appointment)), false);
});

it('shows schedule and edit links to create and edit holders on the index', function () {
    $this->actingAs(apptScopeUser(['view appointments', 'create appointments', 'edit appointments']));

    $this->get(route('appointments.index'))
        ->assertOk()
        ->assertSee(e(route('appointments.create')), false)
        ->assertSee(e(route('appointments.edit', $this->appointment)), false);
});
