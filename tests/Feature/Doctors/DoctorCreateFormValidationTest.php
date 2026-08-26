<?php

use App\Http\Requests\StoreDoctorRequest;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Permission::findOrCreate('view doctors', 'web');
    Permission::findOrCreate('create doctors', 'web');
});

function makeDoctorFormUser(): User
{
    $user = User::create([
        'name' => 'Doctor Form User',
        'email' => 'doctor-form-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo(['view doctors', 'create doctors']);

    return $user;
}

it('create doctor form uses client validation hook matching store rules', function () {
    $this->actingAs(makeDoctorFormUser())
        ->get(route('doctors.create'))
        ->assertOk()
        ->assertSee('id="doctor-create-form"', false)
        ->assertSee('novalidate', false)
        ->assertSee('data-landmark="doctor-create-form"', false);
});

it('store doctor request rules stay aligned with client validation contract', function () {
    expect((new StoreDoctorRequest)->rules())->toBe([
        'name' => 'required|string|max:255',
        'specialization' => 'required|string|max:255',
        'qualification' => 'required|string|max:255',
        'pmdc_number' => 'nullable|string|max:50',
        'phone' => 'required|string|max:20',
        'email' => 'required|email|unique:tenant.doctors,email|unique:tenant.users,email',
        'gender' => 'required|in:male,female,other',
        'experience_years' => 'required|integer|min:0|max:50',
        'address' => 'nullable|string',
        'consultation_fee' => 'required|numeric|min:0',
        'department_id' => 'required|exists:tenant.departments,id',
        'available_days' => 'nullable|array',
        'shift_start' => 'required|date_format:H:i',
        'shift_end' => 'required|date_format:H:i|after:shift_start',
        'status' => 'required|in:active,inactive',
    ]);
});
