@extends('admin.layout')

@section('title', 'Lab Test Report')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Lab Test Report</h1>
            <p class="text-gray-600 mt-1">Laboratory and radiology test statistics</p>
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
            <label class="block text-sm font-medium text-gray-700 mb-2">Test Type</label>
            <select name="test_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="">All Types</option>
                <option value="lab" {{ $testType == 'lab' ? 'selected' : '' }}>Laboratory</option>
                <option value="radiology" {{ $testType == 'radiology' ? 'selected' : '' }}>Radiology</option>
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
                <p class="text-sm text-gray-600">Total Orders</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_orders'] }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Completed</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Lab Tests</p>
                <p class="text-2xl font-bold text-purple-600">{{ $stats['lab_tests'] }}</p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-flask text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Radiology</p>
                <p class="text-2xl font-bold text-orange-600">{{ $stats['radiology_tests'] }}</p>
            </div>
            <div class="bg-orange-100 rounded-full p-3">
                <i class="fas fa-x-ray text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Status Breakdown -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-700">Completed</h3>
            <span class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $stats['total_orders'] > 0 ? ($stats['completed'] / $stats['total_orders'] * 100) : 0 }}%"></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ $stats['total_orders'] > 0 ? number_format($stats['completed'] / $stats['total_orders'] * 100, 1) : 0 }}% completion rate</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-700">In Progress</h3>
            <span class="text-2xl font-bold text-blue-600">{{ $stats['in_progress'] }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $stats['total_orders'] > 0 ? ($stats['in_progress'] / $stats['total_orders'] * 100) : 0 }}%"></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ $stats['total_orders'] > 0 ? number_format($stats['in_progress'] / $stats['total_orders'] * 100, 1) : 0 }}% of total</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-700">Sample Collected</h3>
            <span class="text-2xl font-bold text-yellow-600">{{ $stats['sample_collected'] }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $stats['total_orders'] > 0 ? ($stats['sample_collected'] / $stats['total_orders'] * 100) : 0 }}%"></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ $stats['total_orders'] > 0 ? number_format($stats['sample_collected'] / $stats['total_orders'] * 100, 1) : 0 }}% of total</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-700">Pending</h3>
            <span class="text-2xl font-bold text-red-600">{{ $stats['pending'] }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-red-600 h-2 rounded-full" style="width: {{ $stats['total_orders'] > 0 ? ($stats['pending'] / $stats['total_orders'] * 100) : 0 }}%"></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ $stats['total_orders'] > 0 ? number_format($stats['pending'] / $stats['total_orders'] * 100, 1) : 0 }}% of total</p>
    </div>
</div>

<!-- Performance Metrics -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Performance Metrics</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center p-4 bg-blue-50 rounded-lg">
            <p class="text-sm text-gray-600 mb-2">Avg Turnaround Time</p>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($avgTurnaroundTime, 1) }}</p>
            <p class="text-xs text-gray-500 mt-1">hours</p>
        </div>
        <div class="text-center p-4 bg-green-50 rounded-lg">
            <p class="text-sm text-gray-600 mb-2">Completion Rate</p>
            <p class="text-3xl font-bold text-green-600">{{ $stats['total_orders'] > 0 ? number_format($stats['completed'] / $stats['total_orders'] * 100, 1) : 0 }}%</p>
            <p class="text-xs text-gray-500 mt-1">of all orders</p>
        </div>
        <div class="text-center p-4 bg-purple-50 rounded-lg">
            <p class="text-sm text-gray-600 mb-2">Daily Average</p>
            <p class="text-3xl font-bold text-purple-600">{{ $dailyTrend->count() > 0 ? number_format($stats['total_orders'] / $dailyTrend->count(), 1) : 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">tests per day</p>
        </div>
    </div>
</div>

<!-- Test-wise Breakdown -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Test-wise Breakdown</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Orders</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($testBreakdown as $test)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $test['investigation']->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 py-1 text-xs rounded-full {{ $test['investigation']->type === 'lab' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800' }}">
                            {{ ucfirst($test['investigation']->type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $test['count'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                        {{ $test['completed'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                        {{ $test['pending'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $test['count'] > 0 ? number_format($test['completed'] / $test['count'] * 100, 1) : 0 }}%
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No test data available for the selected period
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Doctor-wise Orders -->
@if($doctorOrders->count() > 0)
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Doctor-wise Test Orders</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Orders</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($doctorOrders as $doctor)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $doctor['doctor']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $doctor['doctor']->specialization }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $doctor['orders'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                        {{ $doctor['completed'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $doctor['orders'] > 0 ? number_format($doctor['completed'] / $doctor['orders'] * 100, 1) : 0 }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

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
