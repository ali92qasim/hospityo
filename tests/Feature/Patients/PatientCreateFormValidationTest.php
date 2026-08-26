<?php

use App\Http\Requests\StorePatientRequest;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Permission::findOrCreate('view patients', 'web');
    Permission::findOrCreate('create patients', 'web');
});

function makePatientFormUser(): User
{
    $user = User::create([
        'name' => 'Patient Form User',
        'email' => 'patient-form-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo(['view patients', 'create patients']);

    return $user;
}

it('create patient form uses client validation hook matching store rules', function () {
    $this->actingAs(makePatientFormUser())
        ->get(route('patients.create'))
        ->assertOk()
        ->assertSee('id="patient-create-form"', false)
        ->assertSee('novalidate', false)
        ->assertSee('data-landmark="patient-create-form"', false)
        ->assertDontSee('name="email"', false)
        ->assertDontSee('data-email-available-url', false);
});

it('store patient request rules stay aligned with client validation contract', function () {
    expect((new StorePatientRequest)->rules())->toBe([
        'name' => 'required|string|max:255',
        'gender' => 'required|in:male,female,other',
        'age' => 'required|integer|min:1|max:150',
        'phone' => 'required|string|max:20',
        'marital_status' => 'nullable|in:single,married,divorced,widowed',
        'present_address' => 'nullable|string',
        'permanent_address' => 'nullable|string',
        'emergency_name' => 'nullable|string|max:255',
        'emergency_phone' => 'nullable|string|max:20',
        'emergency_relation' => 'nullable|string|max:100',
    ]);
});

it('client patient validator submits the form after checks pass', function () {
    $js = file_get_contents(resource_path('js/patient-create-validation.js'));

    expect($js)->toContain('submitFormAutomatically: true')
        ->and($js)->not->toContain('emailAvailableUrl')
        ->and($js)->not->toContain('[name="email"]');
});
