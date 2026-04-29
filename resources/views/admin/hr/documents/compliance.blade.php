@extends('admin.layout')

@section('title', 'Document Compliance Report')
@section('page-title', 'Document Compliance Report')

@section('content')
<!-- Back Link -->
<div class="mb-4">
    <a href="{{ route('hr.documents.index') }}" class="text-medical-blue hover:text-blue-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i>Back to Documents
    </a>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Employees</p>
                <p class="text-2xl font-bold text-gray-800">{{ $overallStats['total_employees'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-medical-blue text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Fully Compliant</p>
                <p class="text-2xl font-bold text-green-600">{{ $overallStats['fully_compliant'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Missing Documents</p>
                <p class="text-2xl font-bold text-red-600">{{ $overallStats['with_missing'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-file-excel text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Expired Documents</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $overallStats['with_expired'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Compliance Percentage Bar -->
@php
    $totalEmp = $overallStats['total_employees'] ?? 0;
    $compliantEmp = $overallStats['fully_compliant'] ?? 0;
    $compliancePercent = $totalEmp > 0 ? round(($compliantEmp / $totalEmp) * 100, 1) : 0;
@endphp
<div class="bg-white rounded-lg shadow-sm mb-6 p-5">
    <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-medium text-gray-700">Overall Compliance Rate</h4>
        <span class="text-sm font-bold {{ $compliancePercent >= 80 ? 'text-green-600' : ($compliancePercent >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
            {{ $compliancePercent }}%
        </span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-4">
        <div class="h-4 rounded-full transition-all duration-500 {{ $compliancePercent >= 80 ? 'bg-green-500' : ($compliancePercent >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
             style="width: {{ $compliancePercent }}%"></div>
    </div>
    <p class="text-xs text-gray-500 mt-1">{{ $compliantEmp }} of {{ $totalEmp }} employees are fully compliant</p>
</div>

<!-- Department Filter -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4">
        <form action="{{ route('hr.documents.compliance') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Department</label>
                <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">All Departments</option>
                    @foreach($departments ?? [] as $department)
                        <option value="{{ $department->id }}" {{ $departmentId == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
        </form>
    </div>
</div>

<!-- Compliance Table -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Employee Compliance Details</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Required</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Missing</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Expired</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Expiring</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Missing Documents</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($compliance as $row)
                <tr class="{{ !$row['compliant'] ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50' }}">
                    {{-- Employee --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($row['employee']->photo)
                                <img src="{{ asset('storage/' . $row['employee']->photo) }}" alt="{{ $row['employee']->full_name }}" class="w-10 h-10 rounded-full object-cover mr-3">
                            @else
                                <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                    {{ strtoupper(substr($row['employee']->first_name, 0, 1) . substr($row['employee']->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="text-sm font-medium text-gray-900">{{ $row['employee']->full_name }}</div>
                        </div>
                    </td>

                    {{-- Department --}}
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row['employee']->department->name ?? '—' }}</td>

                    {{-- Required --}}
                    <td class="px-6 py-4 text-sm text-gray-900 text-center">{{ $row['required'] }}</td>

                    {{-- Uploaded --}}
                    <td class="px-6 py-4 text-sm text-gray-900 text-center">{{ $row['uploaded'] }}</td>

                    {{-- Missing --}}
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-medium {{ $row['missing'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $row['missing'] }}</span>
                    </td>

                    {{-- Expired --}}
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-medium {{ $row['expired'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $row['expired'] }}</span>
                    </td>

                    {{-- Expiring --}}
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-medium {{ $row['expiring'] > 0 ? 'text-yellow-600' : 'text-gray-900' }}">{{ $row['expiring'] }}</span>
                    </td>

                    {{-- Status --}}
                    <td class="px-6 py-4 text-center">
                        @if($row['compliant'])
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Compliant
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Non-Compliant
                            </span>
                        @endif
                    </td>

                    {{-- Missing Documents --}}
                    <td class="px-6 py-4">
                        @if($row['missing_docs']->isNotEmpty())
                            <span class="text-xs text-red-600">{{ $row['missing_docs']->join(', ') }}</span>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-clipboard-check text-4xl mb-4 text-gray-300"></i>
                        <p>No employees found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
