<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use App\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class DoctorController extends Controller
{
    public function index(): View
    {
        return view('admin.doctors.index');
    }

    public function data()
    {
        $query = Doctor::query();

        return DataTables::eloquent($query)
            ->toJson();
    }

    public function create(): View
    {
        return view('admin.doctors.create');
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

        return redirect()->route('doctors.index')
            ->with('success', 'Doctor created successfully. Login credentials - Email: ' . $validated['email'] . ', Password: ' . $randomPassword);
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

            if ($doctor->user->wasChanged('email')) {
                $tenant = \App\Models\Tenant::current();
                \App\Models\TenantUser::where('email', $oldEmail)
                    ->where('tenant_id', $tenant->id)->delete();
                \App\Models\TenantUser::register($doctor->user->email, $tenant->id);
            }
        }

        $doctor->update($validated);

        return redirect()->route('doctors.index')
            ->with('success', 'Doctor updated successfully.');
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        // Delete associated user account
        if ($doctor->user) {
            $doctor->user->delete();
        }
        
        $doctor->delete();

        return redirect()->route('doctors.index')
            ->with('success', 'Doctor deleted successfully.');
    }

    public function assignments(): View
    {
        $user = auth()->user();

        if (!$user->hasRole('Doctor')) {
            abort(403);
        }

        $doctor = Doctor::where('user_id', $user->id)->first();
        if (!$doctor) {
            abort(404);
        }

        $assignedPatients = $doctor->assignedPatients()->paginate(8);

        return view('admin.doctor.assignments', compact('assignedPatients'));
    }
}