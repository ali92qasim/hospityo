<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RedirectsAfterWrite;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use App\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class DoctorController extends Controller
{
    use RedirectsAfterWrite;

    public function index(): View
    {
        return view('admin.doctors.index');
    }

    public function data()
    {
        $query = Doctor::query()
            ->orderByDesc('id'); // Latest first (newest doctors)

        return DataTables::eloquent($query)
            ->toJson();
    }

    public function create(): View
    {
        return view('admin.doctors.create');
    }

    public function emailAvailable(Request $request): JsonResponse
    {
        $email = trim((string) $request->query('email', ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return response()->json(['available' => true]);
        }

        $taken = Doctor::query()->where('email', $email)->exists()
            || User::query()->where('email', $email)->exists();

        return response()->json(['available' => ! $taken]);
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate random password
        $randomPassword = bin2hex(random_bytes(8));
        
        // Create user account for doctor
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($randomPassword),
            'email_verified_at' => now()
        ]);

        // Register in tenant_users so central login can find this user
        \App\Models\TenantUser::register($user->email, \App\Models\Tenant::current()->id);

        // Assign Doctor role
        $doctorRole = Role::firstOrCreate(['name' => 'Doctor']);
        $user->assignRole($doctorRole);

        // Create doctor record with user_id
        $validated['user_id'] = $user->id;
        Doctor::create($validated);

        return $this->redirectAfterWrite(
            'doctors.index',
            [],
            ['view doctors'],
            'Doctor created successfully. Login credentials - Email: ' . $validated['email'] . ', Password: ' . $randomPassword,
            'doctors.create',
        );
    }

    public function show(Doctor $doctor): View
    {
        return view('admin.doctors.show', compact('doctor'));
    }

    public function edit(Doctor $doctor): View
    {
        return view('admin.doctors.edit', compact('doctor'));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $validated = $request->validated();

        // Update associated user if exists
        if ($doctor->user) {
            $oldEmail = $doctor->user->email;

            $doctor->user->update([
                'name' => $validated['name'],
                'email' => $validated['email']
            ]);

            $tenant = \App\Models\Tenant::current();
            if ($tenant) {
                if ($doctor->user->wasChanged('email')) {
                    \App\Models\TenantUser::unregister($oldEmail, $tenant->id);
                }
                \App\Models\TenantUser::register($doctor->user->email, $tenant->id);
            }
        }

        $doctor->update($validated);

        return $this->redirectAfterWrite(
            'doctors.index',
            [],
            ['view doctors'],
            'Doctor updated successfully.',
            'doctors.edit',
            ['doctor' => $doctor],
        );
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        $name = $doctor->name;

        // Soft delete: keep the linked User row, but drop the landlord login mapping.
        $doctor->update(['status' => 'inactive']);

        if ($doctor->user) {
            $tenant = \App\Models\Tenant::current();
            if ($tenant) {
                \App\Models\TenantUser::unregister($doctor->user->email, $tenant->id);
            }
        }

        $doctor->delete();

        return $this->redirectAfterWrite(
            'doctors.index',
            [],
            ['view doctors'],
            "Doctor {$name} moved to trash.",
            null,
        );
    }

    public function trashed(): View
    {
        $doctors = Doctor::onlyTrashed()->orderByDesc('deleted_at')->paginate(20);

        return view('admin.doctors.trashed', compact('doctors'));
    }

    public function restore(int $doctor): RedirectResponse
    {
        $model = Doctor::onlyTrashed()->findOrFail($doctor);
        $model->restore();
        $model->update(['status' => 'active']);

        if ($model->user) {
            $tenant = \App\Models\Tenant::current();
            if ($tenant) {
                \App\Models\TenantUser::register($model->user->email, $tenant->id);
            }
        }

        return $this->redirectAfterWrite(
            'doctors.index',
            [],
            ['view doctors'],
            "Doctor {$model->name} restored.",
            'doctors.trashed',
        );
    }

    public function assignments(): View
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();
        if (!$doctor) {
            abort(404);
        }

        $assignedPatients = $doctor->assignedPatients()->paginate(8);

        return view('admin.doctor.assignments', compact('assignedPatients'));
    }
}