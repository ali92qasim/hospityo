<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'designation']);

        if ($request->search) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)
                ->orWhere('first_name', 'like', $s)
                ->orWhere('last_name', 'like', $s)
                ->orWhere('employee_no', 'like', $s)
                ->orWhere('phone', 'like', $s)
                ->orWhere('cnic', 'like', $s));
        }
        if ($request->department_id) $query->byDepartment($request->department_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->employment_type) $query->byType($request->employment_type);

        $employees = $query->latest()->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();

        $stats = [
            'total' => Employee::count(),
            'active' => Employee::active()->count(),
            'on_leave' => Employee::where('status', 'on_leave')->count(),
            'departments' => Employee::active()->distinct('department_id')->count('department_id'),
        ];

        return view('admin.hr.employees.index', compact('employees', 'departments', 'stats'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $designations = Designation::active()->orderBy('category')->orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $doctors = Doctor::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.employees.create', compact('departments', 'designations', 'users', 'doctors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'cnic' => 'nullable|string|max:15',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'blood_group' => 'nullable|string|max:5',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:tenant.departments,id',
            'designation_id' => 'nullable|exists:tenant.designations,id',
            'user_id' => 'nullable|exists:tenant.users,id',
            'doctor_id' => 'nullable|exists:tenant.doctors,id',
            'employment_type' => 'nullable|in:full_time,part_time,contract,intern',
            'joining_date' => 'required|date',
            'probation_end_date' => 'nullable|date|after_or_equal:joining_date',
            'contract_end_date' => 'nullable|date|after_or_equal:joining_date',
            'status' => 'nullable|in:active,on_leave,suspended,terminated,resigned',
            'basic_salary' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:255',
            'default_shift' => 'nullable|in:morning,evening,night',
            'shift_start' => 'nullable',
            'shift_end' => 'nullable',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Set defaults
        $validated['employment_type'] = $validated['employment_type'] ?? 'full_time';
        $validated['status'] = $validated['status'] ?? 'active';

        try {
            if ($request->hasFile('photo')) {
                $validated['photo'] = $request->file('photo')->store(tenant_storage_path('employees'), 'public');
            }

            $employee = Employee::create($validated);

            return redirect()->route('hr.employees.show', $employee)->with('success', 'Employee created successfully.');
        } catch (\Throwable $e) {
            Log::error('[HR] Employee create failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create employee.');
        }
    }

    public function show(Employee $employee)
    {
        $employee->load(['department', 'designation', 'user', 'doctor', 'documents', 'expenseAccount']);
        return view('admin.hr.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $departments = Department::orderBy('name')->get();
        $designations = Designation::active()->orderBy('category')->orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $doctors = Doctor::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.employees.edit', compact('employee', 'departments', 'designations', 'users', 'doctors'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'cnic' => 'nullable|string|max:15',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'blood_group' => 'nullable|string|max:5',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:tenant.departments,id',
            'designation_id' => 'nullable|exists:tenant.designations,id',
            'user_id' => 'nullable|exists:tenant.users,id',
            'doctor_id' => 'nullable|exists:tenant.doctors,id',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'joining_date' => 'required|date',
            'probation_end_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'status' => 'required|in:active,on_leave,suspended,terminated,resigned',
            'basic_salary' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:255',
            'default_shift' => 'nullable|in:morning,evening,night',
            'shift_start' => 'nullable',
            'shift_end' => 'nullable',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            if ($request->hasFile('photo')) {
                if ($employee->photo) Storage::disk('public')->delete($employee->photo);
                $validated['photo'] = $request->file('photo')->store(tenant_storage_path('employees'), 'public');
            }

            $employee->update($validated);

            return redirect()->route('hr.employees.show', $employee)->with('success', 'Employee updated successfully.');
        } catch (\Throwable $e) {
            Log::error('[HR] Employee update failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update employee.');
        }
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('hr.employees.index')->with('success', 'Employee deleted.');
    }

    // ── Document Upload ──
    public function uploadDocument(Request $request, Employee $employee)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'document_type' => 'required|in:cnic,degree,contract,certification,license,other',
            'file' => 'required|file|max:5120',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $path = $request->file('file')->store(tenant_storage_path('employee-docs'), 'public');

        $employee->documents()->create([
            'title' => $request->title,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'expiry_date' => $request->expiry_date,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function deleteDocument(EmployeeDocument $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return back()->with('success', 'Document deleted.');
    }
}
