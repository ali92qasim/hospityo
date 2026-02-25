@extends('admin.layout')

@section('title', 'Doctor Performance Report')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Doctor Performance Report</h1>
            <p class="text-gray-600 mt-1">Comprehensive doctor performance metrics and analytics</p>
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
            <label class="block text-sm font-medium text-gray-700 mb-2">Doctor</label>
            <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="">All Doctors</option>
                @foreach($allDoctors as $doc)
                    <option value="{{ $doc->id }}" {{ $doctorId == $doc->id ? 'selected' : '' }}>
                        {{ $doc->name }}
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
                <p class="text-sm text-gray-600">Total Doctors</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_doctors'] }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-user-md text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Visits</p>
                <p class="text-2xl font-bold text-purple-600">{{ $stats['total_visits'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ number_format($stats['avg_visits_per_doctor'], 1) }} per doctor</p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-clipboard-list text-purple-600 text-xl"></i>
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

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Prescriptions</p>
                <p class="text-2xl font-bold text-orange-600">{{ $stats['total_prescriptions'] }}</p>
            </div>
            <div class="bg-orange-100 rounded-full p-3">
                <i class="fas fa-file-prescription text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Doctor Performance Table -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Individual Doctor Performance</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
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
                        <div class="text-sm font-medium text-gray-900">{{ $perf['doctor']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $perf['doctor']->specialization }}</div>
                        <div class="text-xs text-gray-400">{{ $perf['doctor']->department?->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $perf['total_visits'] }}</div>
                        <div class="text-xs text-gray-500">{{ number_format($perf['avg_visits_per_day'], 1) }}/day</div>
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
                        <div class="text-xs text-gray-500">
                            {{ $perf['completed_appointments'] }} completed
                            @if($perf['cancelled_appointments'] > 0)
                                <span class="text-red-500">, {{ $perf['cancelled_appointments'] }} cancelled</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $perf['total_prescriptions'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $perf['total_investigations'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-green-600">Rs {{ number_format($perf['total_revenue']) }}</div>
                        <div class="text-xs text-gray-500">Rs {{ number_format($perf['total_collected']) }} collected</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $perf['unique_patients'] }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        No performance data available for the selected period
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Department-wise Performance -->
@if($departmentPerformance->count() > 0)
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Department-wise Performance</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctors</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Visits</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prescriptions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg per Doctor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($departmentPerformance as $dept)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $dept['department']->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $dept['doctors'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $dept['visits'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $dept['prescriptions'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                        Rs {{ number_format($dept['revenue']) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ number_format($dept['visits'] / $dept['doctors'], 1) }} visits
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Performance Insights -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Top Performer by Visits -->
    @if($performance->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Top Performer (Visits)</h3>
        @php $topVisits = $performance->first(); @endphp
        <div class="flex items-center mb-3">
            <div class="bg-blue-100 rounded-full p-3 mr-3">
                <i class="fas fa-trophy text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $topVisits['doctor']->name }}</p>
                <p class="text-xs text-gray-500">{{ $topVisits['doctor']->specialization }}</p>
            </div>
        </div>
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Total Visits:</span>
                <span class="font-medium text-gray-900">{{ $topVisits['total_visits'] }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Completion Rate:</span>
                <span class="font-medium text-green-600">{{ number_format($topVisits['completion_rate'], 1) }}%</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Top Performer by Revenue -->
    @if($performance->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Top Performer (Revenue)</h3>
        @php $topRevenue = $performance->sortByDesc('total_revenue')->first(); @endphp
        <div class="flex items-center mb-3">
            <div class="bg-green-100 rounded-full p-3 mr-3">
                <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $topRevenue['doctor']->name }}</p>
                <p class="text-xs text-gray-500">{{ $topRevenue['doctor']->specialization }}</p>
            </div>
        </div>
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Total Revenue:</span>
                <span class="font-medium text-green-600">Rs {{ number_format($topRevenue['total_revenue']) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Collected:</span>
                <span class="font-medium text-gray-900">Rs {{ number_format($topRevenue['total_collected']) }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Most Active Prescriber -->
    @if($performance->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Most Active Prescriber</h3>
        @php $topPrescriber = $performance->sortByDesc('total_prescriptions')->first(); @endphp
        <div class="flex items-center mb-3">
            <div class="bg-purple-100 rounded-full p-3 mr-3">
                <i class="fas fa-file-prescription text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $topPrescriber['doctor']->name }}</p>
                <p class="text-xs text-gray-500">{{ $topPrescriber['doctor']->specialization }}</p>
            </div>
        </div>
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Prescriptions:</span>
                <span class="font-medium text-purple-600">{{ $topPrescriber['total_prescriptions'] }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Investigations:</span>
                <span class="font-medium text-gray-900">{{ $topPrescriber['total_investigations'] }}</span>
            </div>
        </div>
    </div>
    @endif
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
