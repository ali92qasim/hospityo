<?php

use App\Http\Requests\AddDoctorToCareTeamRequest;
use App\Http\Requests\AssignDoctorRequest;
use App\Http\Requests\CreatePrescriptionRequest;
use App\Http\Requests\OrderMultipleImagingStudiesRequest;
use App\Http\Requests\OrderMultipleLabTestsRequest;
use App\Http\Requests\SetPrimaryDoctorRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\StoreImagingOrderRequest;
use App\Http\Requests\StoreIpdDoctorVisitNoteRequest;
use App\Http\Requests\StoreIpdGpeRecordRequest;
use App\Http\Requests\StoreLabOrderRequest;
use App\Http\Requests\StorePrescriptionPrintTemplateRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Requests\UpdateImagingOrderRequest;
use App\Http\Requests\UpdateLabOrderRequest;
use App\Http\Requests\UpdateLabReportRosterRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\Visit;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);
});

function makeSoftDeleteDoctor(string $name = 'Dr Trashed'): Doctor
{
    return Doctor::create([
        'name' => $name,
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001234567',
        'email' => 'dr-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
    ]);
}

function makeSoftDeletePatient(): Patient
{
    return Patient::create([
        'name' => 'Rel Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001112233',
    ]);
}

it('still resolves visit.doctor after the doctor is soft-deleted', function () {
    $doctor = makeSoftDeleteDoctor();
    $visit = Visit::create([
        'patient_id' => makeSoftDeletePatient()->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'open',
        'doctor_id' => $doctor->id,
    ]);

    $doctor->delete();

    $reloaded = Visit::query()->findOrFail($visit->id);
    expect($reloaded->doctor)->not->toBeNull()
        ->and($reloaded->doctor->id)->toBe($doctor->id)
        ->and($reloaded->doctor->trashed())->toBeTrue()
        ->and($reloaded->doctor->name)->toBe('Dr Trashed');
});

it('still resolves appointment.doctor after the doctor is soft-deleted', function () {
    $doctor = makeSoftDeleteDoctor();
    $appointment = Appointment::create([
        'patient_id' => makeSoftDeletePatient()->id,
        'doctor_id' => $doctor->id,
        'appointment_datetime' => now()->addDay(),
        'status' => 'scheduled',
    ]);

    $doctor->delete();

    $reloaded = Appointment::query()->findOrFail($appointment->id);
    expect($reloaded->doctor)->not->toBeNull()
        ->and($reloaded->doctor->trashed())->toBeTrue();
});

it('declares withTrashed on every belongsTo Doctor relation', function () {
    $models = [
        \App\Models\Visit::class, \App\Models\Surgery::class, \App\Models\PrescriptionPrintTemplate::class,
        \App\Models\Prescription::class, \App\Models\LabReportRosterDoctor::class, \App\Models\LabOrder::class,
        \App\Models\IpdGpeRecord::class, \App\Models\IpdDoctorVisitNote::class, \App\Models\IpdCareTeam::class,
        \App\Models\ImagingOrder::class, \App\Models\Employee::class, \App\Models\DoctorShareSettlement::class,
        \App\Models\DoctorShareRule::class, \App\Models\DoctorShareRate::class, \App\Models\DoctorShareAllocation::class,
        \App\Models\DoctorShareItem::class, \App\Models\Appointment::class,
    ];

    foreach ($models as $model) {
        $relation = (new $model)->doctor();
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletingScope::class, $relation->getQuery()->removedScopes(), "{$model}::doctor() must use withTrashed()");
    }
});

dataset('doctor id rules', [
    'StoreAppointmentRequest' => [StoreAppointmentRequest::class, 'doctor_id'],
    'UpdateAppointmentRequest' => [UpdateAppointmentRequest::class, 'doctor_id'],
    'UpdateVisitRequest' => [UpdateVisitRequest::class, 'doctor_id'],
    'StoreLabOrderRequest' => [StoreLabOrderRequest::class, 'doctor_id'],
    'UpdateLabOrderRequest' => [UpdateLabOrderRequest::class, 'doctor_id'],
    'StoreImagingOrderRequest' => [StoreImagingOrderRequest::class, 'doctor_id'],
    'UpdateImagingOrderRequest' => [UpdateImagingOrderRequest::class, 'doctor_id'],
    'StoreIpdDoctorVisitNoteRequest' => [StoreIpdDoctorVisitNoteRequest::class, 'doctor_id'],
    'StoreIpdGpeRecordRequest' => [StoreIpdGpeRecordRequest::class, 'doctor_id'],
    'SetPrimaryDoctorRequest' => [SetPrimaryDoctorRequest::class, 'doctor_id'],
    'OrderMultipleLabTestsRequest' => [OrderMultipleLabTestsRequest::class, 'doctor_id'],
    'OrderMultipleImagingStudiesRequest' => [OrderMultipleImagingStudiesRequest::class, 'doctor_id'],
    'CreatePrescriptionRequest' => [CreatePrescriptionRequest::class, 'doctor_id'],
    'AssignDoctorRequest' => [AssignDoctorRequest::class, 'doctor_id'],
    'AddDoctorToCareTeamRequest' => [AddDoctorToCareTeamRequest::class, 'doctor_id'],
    'StorePrescriptionPrintTemplateRequest' => [StorePrescriptionPrintTemplateRequest::class, 'doctor_id'],
    'UpdateLabReportRosterRequest' => [UpdateLabReportRosterRequest::class, 'doctor_ids.*'],
]);

it('rejects a soft-deleted doctor id but accepts a live one', function (string $requestClass, string $field) {
    $rules = (new $requestClass)->rules();

    $live = makeSoftDeleteDoctor('Dr Live');
    $trashed = makeSoftDeleteDoctor('Dr Gone');
    $trashed->delete();

    $trashedValue = str_ends_with($field, '.*') ? ['doctor_ids' => [$trashed->id]] : [$field => $trashed->id];
    $liveValue = str_ends_with($field, '.*') ? ['doctor_ids' => [$live->id]] : [$field => $live->id];
    $key = str_ends_with($field, '.*') ? 'doctor_ids.*' : $field;

    $bad = Validator::make($trashedValue, [$key => $rules[$field]]);
    $good = Validator::make($liveValue, [$key => $rules[$field]]);

    expect($bad->fails())->toBeTrue()
        ->and($bad->errors()->has($field === 'doctor_ids.*' ? 'doctor_ids.0' : $field))->toBeTrue()
        ->and($good->errors()->has($field === 'doctor_ids.*' ? 'doctor_ids.0' : $field))->toBeFalse();
})->with('doctor id rules');
