@extends('admin.layout')

@section('title', 'IPD Report')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">In-Patient Department (IPD) Report</h1>
            <p class="text-gray-600 mt-1">Admission statistics and bed occupancy analysis</p>
        </div>
        <button onclick="window.print()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
            <label class="block text-sm font-medium text-gray-700 mb-2">Ward</label>
            <select name="ward_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="">All Wards</option>
                @foreach($wards as $ward)
                    <option value="{{ $ward->id }}" {{ $wardId == $ward->id ? 'selected' : '' }}>
                        {{ $ward->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="">All Status</option>
                <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="discharged" {{ $status == 'discharged' ? 'selected' : '' }}>Discharged</option>
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
                <p class="text-sm text-gray-600">Total Admissions</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_admissions'] }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-procedures text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Active Admissions</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['active_admissions'] }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-bed text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Discharged</p>
                <p class="text-2xl font-bold text-purple-600">{{ $stats['discharged'] }}</p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-sign-out-alt text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Bed Occupancy</p>
                <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['bed_occupancy_rate'], 1) }}%</p>
            </div>
            <div class="bg-orange-100 rounded-full p-3">
                <i class="fas fa-chart-pie text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Key Metrics -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Average Length of Stay</h3>
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-4 mr-4">
                <i class="fas fa-calendar-day text-blue-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-4xl font-bold text-blue-600">{{ number_format($stats['avg_length_of_stay'], 1) }}</p>
                <p class="text-sm text-gray-500">days</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Total Bed Days</h3>
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-4 mr-4">
                <i class="fas fa-bed text-purple-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-4xl font-bold text-purple-600">{{ $stats['total_bed_days'] }}</p>
                <p class="text-sm text-gray-500">days</p>
            </div>
        </div>
    </div>
</div>

<!-- Ward-wise Statistics -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Ward-wise Bed Occupancy</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ward</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Beds</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Occupied</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admissions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Occupancy Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($wardStats as $ward)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $ward['ward']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $ward['ward']->type }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $ward['total_beds'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                        {{ $ward['occupied_beds'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                        {{ $ward['available_beds'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $ward['admissions'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="h-2 rounded-full {{ $ward['occupancy_rate'] >= 80 ? 'bg-red-600' : ($ward['occupancy_rate'] >= 60 ? 'bg-yellow-600' : 'bg-green-600') }}" 
                                     style="width: {{ $ward['occupancy_rate'] }}%"></div>
                            </div>
                            <span class="text-sm text-gray-900">{{ number_format($ward['occupancy_rate'], 1) }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No ward data available
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Doctor-wise Admissions -->
@if($doctorStats->count() > 0)
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Doctor-wise Admissions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Admissions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discharged</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($doctorStats as $doctor)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $doctor['doctor']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $doctor['doctor']->specialization }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $doctor['admissions'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                        {{ $doctor['active'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-600">
                        {{ $doctor['discharged'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Diagnosis-wise Admissions -->
@if($diagnosisStats->count() > 0)
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Top Diagnoses</h2>
    </div>
    <div class="p-6">
        <div class="space-y-3">
            @foreach($diagnosisStats as $diagnosis)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-700 font-medium">{{ $diagnosis['diagnosis'] }}</span>
                    <span class="text-gray-900">{{ $diagnosis['count'] }} admissions</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $stats['total_admissions'] > 0 ? ($diagnosis['count'] / $stats['total_admissions'] * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Recent Admissions -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Recent Admissions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ward/Bed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admission Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discharge Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Length of Stay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($admissions->take(20) as $admission)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $admission->patient->name }}</div>
                        <div class="text-xs text-gray-500">{{ $admission->patient->patient_no }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $admission->bed->ward->name }}</div>
                        <div class="text-xs text-gray-500">Bed {{ $admission->bed->bed_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $admission->visit->doctor->name ?? 'N/A' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ \Carbon\Carbon::parse($admission->admission_date)->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $admission->discharge_date ? \Carbon\Carbon::parse($admission->discharge_date)->format('d M Y') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @php
                            $endDate = $admission->discharge_date ?? now();
                            $days = \Carbon\Carbon::parse($admission->admission_date)->diffInDays($endDate);
                        @endphp
                        {{ $days }} {{ $days == 1 ? 'day' : 'days' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($admission->discharge_date)
                            <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                <i class="fas fa-sign-out-alt mr-1"></i>Discharged
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-bed mr-1"></i>Active
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        No admissions found for the selected period
                    </td>
                </tr>
                @endforelse
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
