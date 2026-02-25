@extends('admin.layout')

@section('title', 'Department Performance Report')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Department Performance Report</h1>
            <p class="text-gray-600 mt-1">Comprehensive department-wise performance metrics</p>
        </div>
        <button onclick="window.print()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}" max="{{ today()->format('Y-m-d') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
            <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="">All Departments</option>
                @foreach($allDepartments as $dept)
                    <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-medical-blue text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Generate
            </button>
        </div>
    </form>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Departments</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_departments'] }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-building text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Doctors</p>
                <p class="text-2xl font-bold text-purple-600">{{ $stats['total_doctors'] }}</p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-user-md text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Visits</p>
                <p class="text-2xl font-bold text-orange-600">{{ $stats['total_visits'] }}</p>
            </div>
            <div class="bg-orange-100 rounded-full p-3">
                <i class="fas fa-clipboard-list text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Revenue</p>
                <p class="text-2xl font-bold text-green-600">Rs {{ number_format($stats['total_revenue']) }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Department Performance Table -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Department-wise Performance Overview</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctors</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Visits</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appointments</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prescriptions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Investigations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patients</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($performance as $perf)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $perf['department']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $perf['department']->description }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $perf['doctors_count'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $perf['total_visits'] }}</div>
                        <div class="text-xs text-gray-500">{{ number_format($perf['avg_visits_per_doctor'], 1) }}/doctor</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $perf['completion_rate'] }}%"></div>
                            </div>
                            <span class="text-sm text-gray-900">{{ number_format($perf['completion_rate'], 1) }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $perf['total_appointments'] }}</div>
                        <div class="text-xs text-gray-500">{{ $perf['completed_appointments'] }} completed</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $perf['total_prescriptions'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $perf['total_investigations'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-green-600">Rs {{ number_format($perf['total_revenue']) }}</div>
                        <div class="text-xs text-gray-500">Rs {{ number_format($perf['avg_revenue_per_doctor']) }}/doctor</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $perf['unique_patients'] }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                        No performance data available for the selected period
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Revenue Comparison -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Revenue Comparison</h2>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @foreach($revenueComparison as $dept)
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-700 font-medium">{{ $dept['department'] }}</span>
                    <div class="text-right">
                        <span class="text-green-600 font-medium">Rs {{ number_format($dept['revenue']) }}</span>
                        <span class="text-xs text-gray-500 ml-2">(Rs {{ number_format($dept['collected']) }} collected)</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-green-600 h-3 rounded-full" style="width: {{ $stats['total_revenue'] > 0 ? ($dept['revenue'] / $stats['total_revenue'] * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Visit Comparison -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Visit Comparison</h2>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @foreach($visitComparison as $dept)
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-700 font-medium">{{ $dept['department'] }}</span>
                    <div class="text-right">
                        <span class="text-blue-600 font-medium">{{ $dept['visits'] }} visits</span>
                        <span class="text-xs text-gray-500 ml-2">({{ $dept['completed'] }} completed)</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $stats['total_visits'] > 0 ? ($dept['visits'] / $stats['total_visits'] * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Efficiency Metrics -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Efficiency Metrics (Per Doctor Average)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Visits/Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Revenue/Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Efficiency Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($efficiencyMetrics as $metric)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $metric['department'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ number_format($metric['avg_visits_per_doctor'], 1) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                        Rs {{ number_format($metric['avg_revenue_per_doctor']) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $metric['completion_rate'] }}%"></div>
                            </div>
                            <span class="text-sm text-gray-900">{{ number_format($metric['completion_rate'], 1) }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            // Simple efficiency score based on visits and completion rate
                            $efficiencyScore = ($metric['avg_visits_per_doctor'] * $metric['completion_rate']) / 100;
                            $scoreColor = $efficiencyScore >= 50 ? 'text-green-600' : ($efficiencyScore >= 25 ? 'text-yellow-600' : 'text-red-600');
                        @endphp
                        <span class="text-sm font-medium {{ $scoreColor }}">
                            {{ number_format($efficiencyScore, 1) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}
</style>
@endpush
@endsection
