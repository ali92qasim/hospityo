<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DoctorController extends Controller
{
    public function index(): View
    {
        $doctors = Doctor::latest()->paginate(10);
        return view('admin.doctors.index', compact('doctors'));
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
            $doctor->user->update([
                'name' => $validated['name'],
                'email' => $validated['email']
            ]);
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
}