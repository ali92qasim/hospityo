<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::latest()->paginate(10);
        return view('admin.doctors.index', compact('doctors'));
    }

    public function create()
    {
        return view('admin.doctors.create');
    }

    public function store(StoreDoctorRequest $request)
    {
        $validated = $request->validated();

        // Create user account for doctor
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('doctor123'), // Default password
            'email_verified_at' => now()
        ]);

        // Assign Doctor role
        $doctorRole = Role::firstOrCreate(['name' => 'Doctor']);
        $user->assignRole($doctorRole);

        // Create doctor record with user_id
        $validated['user_id'] = $user->id;
        Doctor::create($validated);

        return redirect()->route('doctors.index')
            ->with('success', 'Doctor created successfully. Login credentials - Email: ' . $validated['email'] . ', Password: doctor123');
    }

    public function show(Doctor $doctor)
    {
        return view('admin.doctors.show', compact('doctor'));
    }

    public function edit(Doctor $doctor)
    {
        return view('admin.doctors.edit', compact('doctor'));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor)
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

    public function destroy(Doctor $doctor)
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