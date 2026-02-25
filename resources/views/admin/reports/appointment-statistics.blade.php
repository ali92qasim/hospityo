@extends('admin.layout')

@section('title', 'Appointment Statistics Report')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Appointment Statistics Report</h1>
            <p class="text-gray-600 mt-1">Comprehensive appointment analytics and trends</p>
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
            <label class="block text-sm font-medium text-gray-700 mb-2">Doctor</label>
            <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="">All Doctors</option>
                @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" {{ $doctorId == $doctor->id ? 'selected' : '' }}>
                        {{ $doctor->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="">All Status</option>
                <option value="scheduled" {{ $status == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="no_show" {{ $status == 'no_show' ? 'selected' : '' }}>No Show</option>
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
                <p class="text-sm text-gray-600">Total Appointments</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_appointments'] }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Completed</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ number_format($stats['completion_rate'], 1) }}% rate</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Cancelled</p>
                <p class="text-2xl font-bold text-red-600">{{ $stats['cancelled'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ number_format($stats['cancellation_rate'], 1) }}% rate</p>
            </div>
            <div class="bg-red-100 rounded-full p-3">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">No Show</p>
                <p class="text-2xl font-bold text-orange-600">{{ $stats['no_show'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ number_format($stats['no_show_rate'], 1) }}% rate</p>
            </div>
            <div class="bg-orange-100 rounded-full p-3">
                <i class="fas fa-user-slash text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Status Breakdown -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-700 mb-4">Scheduled</h3>
        <div class="flex items-center justify-between mb-2">
            <span class="text-3xl font-bold text-blue-600">{{ $stats['scheduled'] }}</span>
            <i class="fas fa-clock text-blue-600 text-2xl"></i>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $stats['total_appointments'] > 0 ? ($stats['scheduled'] / $stats['total_appointments'] * 100) : 0 }}%"></div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-700 mb-4">Completed</h3>
        <div class="flex items-center justify-between mb-2">
            <span class="text-3xl font-bold text-green-600">{{ $stats['completed'] }}</span>
            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $stats['completion_rate'] }}%"></div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-700 mb-4">Cancelled</h3>
        <div class="flex items-center justify-between mb-2">
            <span class="text-3xl font-bold text-red-600">{{ $stats['cancelled'] }}</span>
            <i class="fas fa-times-circle text-red-600 text-2xl"></i>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-red-600 h-2 rounded-full" style="width: {{ $stats['cancellation_rate'] }}%"></div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-700 mb-4">No Show</h3>
        <div class="flex items-center justify-between mb-2">
            <span class="text-3xl font-bold text-orange-600">{{ $stats['no_show'] }}</span>
            <i class="fas fa-user-slash text-orange-600 text-2xl"></i>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-orange-600 h-2 rounded-full" style="width: {{ $stats['no_show_rate'] }}%"></div>
        </div>
    </div>
</div>

<!-- Time Slot and Day Analysis -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Time Slot Analysis -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Appointments by Time Slot</h2>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                @foreach($timeSlotAnalysis as $slot)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 font-medium">{{ $slot['time_label'] }}</span>
                        <span class="text-gray-900">{{ $slot['count'] }} appointments</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $stats['total_appointments'] > 0 ? ($slot['count'] / $stats['total_appointments'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Day of Week Analysis -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Appointments by Day of Week</h2>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                @foreach($dayOfWeekAnalysis as $day)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 font-medium">{{ $day['day'] }}</span>
                        <span class="text-gray-900">{{ $day['count'] }} appointments</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $stats['total_appointments'] > 0 ? ($day['count'] / $stats['total_appointments'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Doctor-wise Appointments -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Doctor-wise Appointment Breakdown</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scheduled</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cancelled</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No Show</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($doctorAppointments as $doctor)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $doctor['doctor']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $doctor['doctor']->specialization }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $doctor['total'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                        {{ $doctor['scheduled'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                        {{ $doctor['completed'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                        {{ $doctor['cancelled'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600">
                        {{ $doctor['no_show'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $doctor['completion_rate'] }}%"></div>
                            </div>
                            <span class="text-sm text-gray-900">{{ number_format($doctor['completion_rate'], 1) }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        No appointment data available for the selected period
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Appointments -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Recent Appointments</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($appointments->take(20) as $appointment)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ \Carbon\Carbon::parse($appointment->appointment_datetime)->format('d M Y, h:i A') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $appointment->patient->name }}</div>
                        <div class="text-xs text-gray-500">{{ $appointment->patient->phone }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $appointment->doctor->name }}</div>
                        <div class="text-xs text-gray-500">{{ $appointment->doctor->specialization }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $appointment->reason ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($appointment->status === 'scheduled')
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-clock mr-1"></i>Scheduled
                            </span>
                        @elseif($appointment->status === 'completed')
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Completed
                            </span>
                        @elseif($appointment->status === 'cancelled')
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Cancelled
                            </span>
                        @elseif($appointment->status === 'no_show')
                            <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">
                                <i class="fas fa-user-slash mr-1"></i>No Show
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        No appointments found
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
