@extends('admin.layout')

@section('title', 'Appointments - Hospital Management System')
@section('page-title', 'Appointments')
@section('page-description', 'Manage patient appointments')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Patient Appointments</h3>
                <p class="text-sm text-gray-600">Total: {{ $appointments->total() }} appointments</p>
            </div>
            <a href="{{ route('appointments.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Schedule Appointment
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($appointments as $appointment)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-calendar-check text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $appointment->appointment_no }}</div>
                                <div class="text-sm text-gray-500">{{ $appointment->doctor->specialization ?? 'General' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $appointment->patient->name }}</div>
                        <div class="text-xs text-gray-500">{{ $appointment->patient->patient_no }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">Dr. {{ $appointment->doctor->name }}</div>
                        <div class="text-xs text-gray-500">{{ $appointment->doctor->specialization }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ optional($appointment->appointment_datetime)->format('M d, Y') ?? '-' }}</div>
                        <div class="text-xs text-gray-500">{{ optional($appointment->appointment_datetime)->format('h:i A') ?? '-' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusColors = [
                                'scheduled' => 'bg-blue-100 text-blue-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                'no_show' => 'bg-gray-100 text-gray-800'
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$appointment->status] }}">
                            {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium space-x-2">
                        <a href="{{ route('appointments.show', $appointment) }}" class="text-medical-blue hover:text-blue-700">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('appointments.edit', $appointment) }}" class="text-medical-green hover:text-green-700">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('appointments.destroy', $appointment) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-calendar-check text-4xl mb-4 text-gray-300"></i>
                        <p>No appointments found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($appointments->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $appointments->links() }}
    </div>
    @endif
</div>
@endsection
