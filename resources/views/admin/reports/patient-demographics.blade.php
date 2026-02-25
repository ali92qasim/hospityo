@extends('admin.layout')

@section('title', 'Patient Demographics Report')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Patient Demographics Report</h1>
            <p class="text-gray-600 mt-1">Patient population analysis and demographics</p>
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
            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date (New Registrations)</label>
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
                <p class="text-sm text-gray-600">Total Patients</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_patients'] }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">New Registrations</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['new_patients'] }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-user-plus text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Male Patients</p>
                <p class="text-2xl font-bold text-blue-600">{{ $stats['male_patients'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $stats['total_patients'] > 0 ? number_format($stats['male_patients'] / $stats['total_patients'] * 100, 1) : 0 }}%</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-mars text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Female Patients</p>
                <p class="text-2xl font-bold text-pink-600">{{ $stats['female_patients'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $stats['total_patients'] > 0 ? number_format($stats['female_patients'] / $stats['total_patients'] * 100, 1) : 0 }}%</p>
            </div>
            <div class="bg-pink-100 rounded-full p-3">
                <i class="fas fa-venus text-pink-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Gender Distribution -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Gender Distribution</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center">
            <div class="relative inline-flex items-center justify-center w-32 h-32">
                <svg class="transform -rotate-90 w-32 h-32">
                    <circle cx="64" cy="64" r="56" stroke="#e5e7eb" stroke-width="12" fill="none" />
                    <circle cx="64" cy="64" r="56" stroke="#3b82f6" stroke-width="12" fill="none"
                            stroke-dasharray="{{ $stats['total_patients'] > 0 ? (($stats['male_patients'] / $stats['total_patients']) * 351.86) : 0 }} 351.86" />
                </svg>
                <span class="absolute text-2xl font-bold text-blue-600">{{ $stats['total_patients'] > 0 ? number_format($stats['male_patients'] / $stats['total_patients'] * 100, 1) : 0 }}%</span>
            </div>
            <p class="mt-2 text-sm font-medium text-gray-700">Male</p>
            <p class="text-xs text-gray-500">{{ $stats['male_patients'] }} patients</p>
        </div>
        <div class="text-center">
            <div class="relative inline-flex items-center justify-center w-32 h-32">
                <svg class="transform -rotate-90 w-32 h-32">
                    <circle cx="64" cy="64" r="56" stroke="#e5e7eb" stroke-width="12" fill="none" />
                    <circle cx="64" cy="64" r="56" stroke="#ec4899" stroke-width="12" fill="none"
                            stroke-dasharray="{{ $stats['total_patients'] > 0 ? (($stats['female_patients'] / $stats['total_patients']) * 351.86) : 0 }} 351.86" />
                </svg>
                <span class="absolute text-2xl font-bold text-pink-600">{{ $stats['total_patients'] > 0 ? number_format($stats['female_patients'] / $stats['total_patients'] * 100, 1) : 0 }}%</span>
            </div>
            <p class="mt-2 text-sm font-medium text-gray-700">Female</p>
            <p class="text-xs text-gray-500">{{ $stats['female_patients'] }} patients</p>
        </div>
        @if($stats['other_gender'] > 0)
        <div class="text-center">
            <div class="relative inline-flex items-center justify-center w-32 h-32">
                <svg class="transform -rotate-90 w-32 h-32">
                    <circle cx="64" cy="64" r="56" stroke="#e5e7eb" stroke-width="12" fill="none" />
                    <circle cx="64" cy="64" r="56" stroke="#8b5cf6" stroke-width="12" fill="none"
                            stroke-dasharray="{{ $stats['total_patients'] > 0 ? (($stats['other_gender'] / $stats['total_patients']) * 351.86) : 0 }} 351.86" />
                </svg>
                <span class="absolute text-2xl font-bold text-purple-600">{{ $stats['total_patients'] > 0 ? number_format($stats['other_gender'] / $stats['total_patients'] * 100, 1) : 0 }}%</span>
            </div>
            <p class="mt-2 text-sm font-medium text-gray-700">Other</p>
            <p class="text-xs text-gray-500">{{ $stats['other_gender'] }} patients</p>
        </div>
        @endif
    </div>
</div>

<!-- Age Distribution -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Age Distribution</h2>
    </div>
    <div class="p-6">
        <div class="space-y-3">
            @foreach($ageGroups as $group => $data)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-700 font-medium">{{ $group }} years</span>
                    <span class="text-gray-900">{{ $data['count'] }} patients ({{ $stats['total_patients'] > 0 ? number_format($data['count'] / $stats['total_patients'] * 100, 1) : 0 }}%)</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $stats['total_patients'] > 0 ? ($data['count'] / $stats['total_patients'] * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Blood Group Distribution -->
@if($bloodGroups->count() > 0)
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Blood Group Distribution</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($bloodGroups as $blood)
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <div class="text-3xl font-bold text-red-600 mb-2">{{ $blood['blood_group'] }}</div>
                <div class="text-sm text-gray-600">{{ $blood['count'] }} patients</div>
                <div class="text-xs text-gray-500">{{ $stats['total_patients'] > 0 ? number_format($blood['count'] / $stats['total_patients'] * 100, 1) : 0 }}%</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Top Frequent Visitors -->
@if($visitFrequency->count() > 0)
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Most Frequent Visitors</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gender</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Visits</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($visitFrequency as $visitor)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $visitor['patient']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $visitor['patient']->phone }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $visitor['patient']->patient_no }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ ucfirst($visitor['patient']->gender) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $visitor['patient']->date_of_birth ? \Carbon\Carbon::parse($visitor['patient']->date_of_birth)->age : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                        {{ $visitor['visit_count'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Monthly Registration Trend -->
@if($monthlyTrend->count() > 0)
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Monthly Registration Trend</h2>
    </div>
    <div class="p-6">
        <div class="space-y-3">
            @foreach($monthlyTrend as $month => $data)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-700 font-medium">{{ \Carbon\Carbon::parse($month)->format('F Y') }}</span>
                    <span class="text-gray-900">{{ $data['count'] }} registrations ({{ $data['male'] }} M, {{ $data['female'] }} F)</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $stats['new_patients'] > 0 ? ($data['count'] / $stats['new_patients'] * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
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
