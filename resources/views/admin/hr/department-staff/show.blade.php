@extends('admin.layout')

@section('title', 'Department Staff — ' . $department->name)
@section('page-title', 'Department Staff')

@section('content')
<div class="max-w-7xl mx-auto">

    <!-- Header Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center">
                    @if($department->headEmployee?->photo)
                        <img src="{{ asset('storage/' . $department->headEmployee->photo) }}" alt="{{ $department->headEmployee->full_name }}" class="w-16 h-16 rounded-full object-cover mr-4">
                    @else
                        <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center text-white text-xl font-bold mr-4">
                            <i class="fas fa-building"></i>
                        </div>
                    @endif
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">{{ $department->name }}</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-medical-blue">{{ $department->code }}</span>
                            <span class="px-2 py-1 text-xs rounded-full {{ $department->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($department->status) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-user-tie mr-1"></i>
                            Head: {{ $department->headEmployee?->full_name ?? 'Not Assigned' }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('hr.department-staff.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Departments
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
            <p class="text-xs text-gray-600 mt-1">Total Staff</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['active'] ?? 0 }}</p>
            <p class="text-xs text-gray-600 mt-1">Active</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['on_leave'] ?? 0 }}</p>
            <p class="text-xs text-gray-600 mt-1">On Leave</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-green-500">{{ $stats['present_today'] ?? 0 }}</p>
            <p class="text-xs text-gray-600 mt-1">Present Today</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-red-500">{{ $stats['absent_today'] ?? 0 }}</p>
            <p class="text-xs text-gray-600 mt-1">Absent Today</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-medical-blue">{{ format_currency($stats['salary_cost'] ?? 0) }}</p>
            <p class="text-xs text-gray-600 mt-1">Monthly Salary</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-purple-600">{{ $stats['leaves_this_month'] ?? 0 }}</p>
            <p class="text-xs text-gray-600 mt-1">Leaves This Month</p>
        </div>
    </div>

    <!-- Breakdowns: Employment Type + Designation -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Employment Type Breakdown -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-200">
                <h4 class="text-md font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-briefcase mr-2 text-medical-blue"></i>
                    Employment Type Breakdown
                </h4>
            </div>
            <div class="p-4 space-y-3">
                @php
                    $types = ['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern'];
                    $typeColors = ['full_time' => 'bg-blue-500', 'part_time' => 'bg-purple-500', 'contract' => 'bg-orange-500', 'intern' => 'bg-cyan-500'];
                    $maxTypeCount = $typeBreakdown->max() ?: 1;
                @endphp
                @foreach($types as $key => $label)
                    @php $count = $typeBreakdown[$key] ?? 0; @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                            <span class="text-sm font-semibold text-gray-800">{{ $count }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="{{ $typeColors[$key] }} h-2 rounded-full" style="width: {{ $maxTypeCount > 0 ? ($count / $maxTypeCount) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Designation Breakdown -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-200">
                <h4 class="text-md font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-id-badge mr-2 text-medical-blue"></i>
                    Designation Breakdown
                </h4>
            </div>
            <div class="p-4">
                @if($designationBreakdown->count())
                    <div class="space-y-2">
                        @foreach($designationBreakdown as $designation => $count)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <span class="text-sm text-gray-700">{{ $designation }}</span>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-medical-blue">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 text-center py-4">No active employees</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Department Settings -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-4 border-b border-gray-200">
            <h4 class="text-md font-semibold text-gray-800 flex items-center">
                <i class="fas fa-cog mr-2 text-gray-500"></i>
                Department Settings
            </h4>
        </div>
        <div class="p-4">
            <form action="{{ route('hr.department-staff.update-head', $department) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Head Employee</label>
                        <select name="head_employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                            <option value="">— Select Head —</option>
                            @foreach($allEmployees as $emp)
                                <option value="{{ $emp->id }}" {{ $department->head_employee_id == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }} ({{ $emp->employee_no }})
                                </option>
                            @endforeach
                        </select>
                        @error('head_employee_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Budget</label>
                        <input type="number" name="monthly_budget" value="{{ old('monthly_budget', $department->monthly_budget) }}" step="0.01" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                               placeholder="0.00">
                        @error('monthly_budget') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>Update Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff List Table -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-users mr-2 text-medical-blue"></i>
                Staff List
            </h4>
            <p class="text-sm text-gray-600">{{ $employees->count() }} employees in this department</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Designation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($employees as $employee)
                        @php
                            $statusBadges = [
                                'active'     => 'bg-green-100 text-green-800',
                                'on_leave'   => 'bg-yellow-100 text-yellow-800',
                                'suspended'  => 'bg-orange-100 text-orange-800',
                                'terminated' => 'bg-red-100 text-red-800',
                                'resigned'   => 'bg-gray-100 text-gray-800',
                            ];
                            $typeBadges = [
                                'full_time' => 'bg-blue-100 text-blue-800',
                                'part_time' => 'bg-purple-100 text-purple-800',
                                'contract'  => 'bg-orange-100 text-orange-800',
                                'intern'    => 'bg-cyan-100 text-cyan-800',
                            ];
                            $attendance = $todayAttendance[$employee->id] ?? null;
                            $attendanceDots = [
                                'present'  => 'bg-green-500',
                                'absent'   => 'bg-red-500',
                                'late'     => 'bg-yellow-500',
                                'on_leave' => 'bg-blue-500',
                            ];
                            $attendanceLabels = [
                                'present'  => 'Present',
                                'absent'   => 'Absent',
                                'late'     => 'Late',
                                'on_leave' => 'On Leave',
                            ];
                            $attStatus = $attendance?->status;
                            $dotColor = $attendanceDots[$attStatus] ?? 'bg-gray-400';
                            $attLabel = $attendanceLabels[$attStatus] ?? 'No Record';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    @if($employee->photo)
                                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="w-10 h-10 rounded-full object-cover mr-3">
                                    @else
                                        <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $employee->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-medical-blue font-medium">{{ $employee->employee_no }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $employee->designation->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $typeBadges[$employee->employment_type] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucwords(str_replace('_', ' ', $employee->employment_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusBadges[$employee->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucwords(str_replace('_', ' ', $employee->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $dotColor }} mr-2"></span>
                                    <span class="text-sm text-gray-700">{{ $attLabel }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $employee->basic_salary ? format_currency($employee->basic_salary) : '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <a href="{{ route('hr.employees.show', $employee) }}" class="text-medical-blue hover:text-blue-700" title="View Employee">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                <p>No employees in this department</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- This Month's Leaves -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-4 border-b border-gray-200">
            <h4 class="text-md font-semibold text-gray-800 flex items-center">
                <i class="fas fa-calendar-minus mr-2 text-yellow-500"></i>
                This Month's Leaves
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($monthLeaves as $leave)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-900">{{ $leave->employee?->full_name ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                    {{ $leave->leaveType?->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">{{ $leave->start_date?->format('M d, Y') }}</td>
                            <td class="px-6 py-3 text-sm text-gray-700">{{ $leave->end_date?->format('M d, Y') }}</td>
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $leave->days ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-calendar-check text-3xl mb-2 text-gray-300"></i>
                                <p>No approved leaves this month</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transfer Employee -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-4 border-b border-gray-200">
            <h4 class="text-md font-semibold text-gray-800 flex items-center">
                <i class="fas fa-exchange-alt mr-2 text-orange-500"></i>
                Transfer Employee
            </h4>
        </div>
        <div class="p-4">
            <form action="{{ route('hr.department-staff.transfer') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                        <select name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_no }})</option>
                            @endforeach
                        </select>
                        @error('employee_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Transfer To Department</label>
                        <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="">— Select Department —</option>
                            @foreach(\App\Models\Department::where('id', '!=', $department->id)->where('status', 'active')->orderBy('name')->get() as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors"
                                onclick="return confirm('Are you sure you want to transfer this employee?')">
                            <i class="fas fa-exchange-alt mr-2"></i>Transfer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
