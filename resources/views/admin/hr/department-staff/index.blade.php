@extends('admin.layout')

@section('title', 'Department Staff Overview')
@section('page-title', 'Department Staff Overview')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Departments</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_departments'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-building text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Staff</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_staff'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-medical-blue text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Active Staff</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['active_staff'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Monthly Salary Cost</p>
                <p class="text-2xl font-bold text-medical-blue">{{ format_currency($stats['total_salary_cost'] ?? 0) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-medical-blue text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Department Cards Grid -->
<div class="mb-4">
    <h3 class="text-lg font-semibold text-gray-800">Departments</h3>
    <p class="text-sm text-gray-600">Staff overview by department</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    @forelse($departments as $department)
        <div class="bg-white rounded-lg shadow-sm border-l-4 {{ $department->status === 'active' ? 'border-green-500' : 'border-red-400' }} hover:shadow-md transition-shadow">
            <div class="p-5">
                <!-- Department Header -->
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-800">{{ $department->name }}</h4>
                        <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-medical-blue">
                            {{ $department->code }}
                        </span>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full {{ $department->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($department->status) }}
                    </span>
                </div>

                <!-- Head of Department -->
                <div class="flex items-center mb-4 pb-4 border-b border-gray-100">
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user-tie text-gray-500 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Head of Department</p>
                        <p class="text-sm font-medium text-gray-800">
                            {{ $department->headEmployee?->full_name ?? 'Not Assigned' }}
                        </p>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="text-center p-2 bg-blue-50 rounded-lg">
                        <p class="text-lg font-bold text-medical-blue">{{ $department->active_employees ?? 0 }}</p>
                        <p class="text-xs text-gray-600">Active</p>
                    </div>
                    <div class="text-center p-2 bg-green-50 rounded-lg">
                        <p class="text-lg font-bold text-green-600">{{ $department->total_doctors ?? 0 }}</p>
                        <p class="text-xs text-gray-600">Doctors</p>
                    </div>
                    <div class="text-center p-2 bg-yellow-50 rounded-lg">
                        <p class="text-lg font-bold text-yellow-600">{{ $department->on_leave_employees ?? 0 }}</p>
                        <p class="text-xs text-gray-600">On Leave</p>
                    </div>
                </div>

                <!-- Monthly Salary Cost -->
                @if($department->total_salary_cost)
                    <div class="flex items-center justify-between mb-4 text-sm">
                        <span class="text-gray-600">Monthly Salary</span>
                        <span class="font-semibold text-gray-800">{{ format_currency($department->total_salary_cost) }}</span>
                    </div>
                @endif

                <!-- View Details Button -->
                <a href="{{ route('hr.department-staff.show', $department) }}"
                   class="block w-full text-center px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <i class="fas fa-eye mr-2"></i>View Details
                </a>
            </div>
        </div>
    @empty
        <div class="md:col-span-2 xl:col-span-3">
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <i class="fas fa-building text-4xl mb-4 text-gray-300"></i>
                <p class="text-gray-500">No departments found</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
