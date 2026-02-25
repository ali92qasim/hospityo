@extends('admin.layout')

@section('title', 'Revenue Report')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Revenue Report</h1>
            <p class="text-gray-600 mt-1">Revenue analysis and trends</p>
        </div>
        <button onclick="window.print()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
        <div class="flex items-end">
            <button type="submit" class="w-full bg-medical-blue text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Generate Report
            </button>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm opacity-90">Total Revenue</p>
            <i class="fas fa-chart-line text-2xl opacity-75"></i>
        </div>
        <p class="text-3xl font-bold">₨{{ number_format($totals['total_revenue'], 2) }}</p>
        <p class="text-xs opacity-75 mt-2">{{ $totals['total_bills'] }} bills generated</p>
    </div>

    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm opacity-90">Collected</p>
            <i class="fas fa-money-bill-wave text-2xl opacity-75"></i>
        </div>
        <p class="text-3xl font-bold">₨{{ number_format($totals['total_collected'], 2) }}</p>
        <p class="text-xs opacity-75 mt-2">{{ $totals['total_revenue'] > 0 ? number_format($totals['total_collected'] / $totals['total_revenue'] * 100, 1) : 0 }}% collection rate</p>
    </div>

    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm opacity-90">Outstanding</p>
            <i class="fas fa-exclamation-triangle text-2xl opacity-75"></i>
        </div>
        <p class="text-3xl font-bold">₨{{ number_format($totals['total_outstanding'], 2) }}</p>
        <p class="text-xs opacity-75 mt-2">{{ $totals['total_revenue'] > 0 ? number_format($totals['total_outstanding'] / $totals['total_revenue'] * 100, 1) : 0 }}% pending</p>
    </div>

    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm opacity-90">Avg Bill Value</p>
            <i class="fas fa-calculator text-2xl opacity-75"></i>
        </div>
        <p class="text-3xl font-bold">₨{{ $totals['total_bills'] > 0 ? number_format($totals['total_revenue'] / $totals['total_bills'], 2) : 0 }}</p>
        <p class="text-xs opacity-75 mt-2">Per transaction</p>
    </div>
</div>

<!-- Revenue by Service -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Revenue by Service</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">% of Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($serviceRevenue as $service)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $service['service'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $service['quantity'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ₨{{ number_format($service['revenue'], 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div class="flex items-center">
                            <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $totals['total_revenue'] > 0 ? ($service['revenue'] / $totals['total_revenue'] * 100) : 0 }}%"></div>
                            </div>
                            <span>{{ $totals['total_revenue'] > 0 ? number_format($service['revenue'] / $totals['total_revenue'] * 100, 1) : 0 }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                        No service revenue data available
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($serviceRevenue->count() > 0)
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td class="px-6 py-4 text-sm text-gray-900">Total</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $serviceRevenue->sum('quantity') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">₨{{ number_format($serviceRevenue->sum('revenue'), 2) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">100%</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<!-- Revenue by Doctor -->
@if($doctorRevenue->count() > 0)
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Revenue by Doctor</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bills</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collected</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collection %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($doctorRevenue as $doctor)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $doctor['doctor']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $doctor['doctor']->specialization }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $doctor['bills'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ₨{{ number_format($doctor['revenue'], 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                        ₨{{ number_format($doctor['collected'], 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $doctor['revenue'] > 0 ? number_format($doctor['collected'] / $doctor['revenue'] * 100, 1) : 0 }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td class="px-6 py-4 text-sm text-gray-900">Total</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $doctorRevenue->sum('bills') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">₨{{ number_format($doctorRevenue->sum('revenue'), 2) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">₨{{ number_format($doctorRevenue->sum('collected'), 2) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $doctorRevenue->sum('revenue') > 0 ? number_format($doctorRevenue->sum('collected') / $doctorRevenue->sum('revenue') * 100, 1) : 0 }}%
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

<!-- Daily Revenue Trend -->
@if($dailyRevenue->count() > 0)
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Daily Revenue Trend</h2>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bills</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collected</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($dailyRevenue as $date => $data)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $data['bills'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ₨{{ number_format($data['revenue'], 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                            ₨{{ number_format($data['collected'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
