<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:doctors,email|unique:users,email',
            'gender' => 'required|in:male,female,other',
            'experience_years' => 'required|integer|min:0|max:50',
            'address' => 'nullable|string',
            'consultation_fee' => 'required|numeric|min:0',
            'department_id' => 'required|exists:departments,id',
            'available_days' => 'nullable|array',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i|after:shift_start',
            'status' => 'required|in:active,inactive',
        ]);

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

    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:doctors,email,' . $doctor->id . '|unique:users,email,' . ($doctor->user_id ?? 'NULL'),
            'gender' => 'required|in:male,female,other',
            'experience_years' => 'required|integer|min:0|max:50',
            'address' => 'nullable|string',
            'consultation_fee' => 'required|numeric|min:0',
            'department_id' => 'required|exists:departments,id',
            'available_days' => 'nullable|array',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i|after:shift_start',
            'status' => 'required|in:active,inactive',
        ]);

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