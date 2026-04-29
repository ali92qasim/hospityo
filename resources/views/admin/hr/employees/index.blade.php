@extends('admin.layout')

@section('title', 'Employees')
@section('page-title', 'Employees')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Employees</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-medical-blue text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Active</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['active'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">On Leave</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['on_leave'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-minus text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Departments</p>
                <p class="text-2xl font-bold text-purple-600">{{ $stats['departments'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-building text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4">
        <form action="{{ route('hr.employees.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employees..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
            </div>
            <div>
                <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">All Departments</option>
                    @foreach($departments ?? [] as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="on_leave" {{ request('status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                    <option value="resigned" {{ request('status') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
            </div>
            <div>
                <select name="employment_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">All Types</option>
                    <option value="full_time" {{ request('employment_type') == 'full_time' ? 'selected' : '' }}>Full Time</option>
                    <option value="part_time" {{ request('employment_type') == 'part_time' ? 'selected' : '' }}>Part Time</option>
                    <option value="contract" {{ request('employment_type') == 'contract' ? 'selected' : '' }}>Contract</option>
                    <option value="intern" {{ request('employment_type') == 'intern' ? 'selected' : '' }}>Intern</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Employees Table -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Employee List</h3>
                <p class="text-sm text-gray-600">Total: {{ $employees->total() }} employees</p>
            </div>
            <a href="{{ route('hr.employees.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add Employee
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Designation</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees as $employee)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        @if($employee->photo)
                            <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center text-white text-sm font-medium">
                                {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-medical-blue font-medium">{{ $employee->employee_no }}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $employee->email }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $employee->department->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $employee->designation->name ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @php
                            $typeBadges = [
                                'full_time' => 'bg-blue-100 text-blue-800',
                                'part_time' => 'bg-purple-100 text-purple-800',
                                'contract'  => 'bg-orange-100 text-orange-800',
                                'intern'    => 'bg-cyan-100 text-cyan-800',
                            ];
                            $typeBadge = $typeBadges[$employee->employment_type] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $typeBadge }}">
                            {{ ucwords(str_replace('_', ' ', $employee->employment_type)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusBadges = [
                                'active'     => 'bg-green-100 text-green-800',
                                'on_leave'   => 'bg-yellow-100 text-yellow-800',
                                'suspended'  => 'bg-orange-100 text-orange-800',
                                'terminated' => 'bg-red-100 text-red-800',
                                'resigned'   => 'bg-gray-100 text-gray-800',
                            ];
                            $statusBadge = $statusBadges[$employee->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $statusBadge }}">
                            {{ ucwords(str_replace('_', ' ', $employee->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $employee->phone ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm font-medium space-x-2">
                        <a href="{{ route('hr.employees.show', $employee) }}" class="text-medical-blue hover:text-blue-700">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('hr.employees.edit', $employee) }}" class="text-medical-green hover:text-green-700">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('hr.employees.destroy', $employee) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this employee?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                        <p>No employees found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $employees->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
