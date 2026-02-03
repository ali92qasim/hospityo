@extends('admin.layout')

@section('title', 'Appointment Details - Hospital Management System')
@section('page-title', 'Appointment Details')
@section('page-description', 'View complete appointment information')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-orange-500 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-calendar-check text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">{{ $appointment->appointment_no }}</h3>
                        <p class="text-sm text-gray-600">{{ $appointment->doctor->department->name ?? 'No Department' }}</p>
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
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('appointments.edit', $appointment) }}" class="px-4 py-2 bg-medical-green text-white rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-edit mr-2"></i>
                        Edit
                    </a>
                    <a href="{{ route('appointments.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Patient Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-injured mr-2 text-medical-blue"></i>
                        Patient Information
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Patient Name:</span>
                            <span class="font-medium">{{ $appointment->patient->name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Patient Number:</span>
                            <span class="font-medium text-medical-blue">{{ $appointment->patient->patient_no }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium">{{ $appointment->patient->phone }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Age:</span>
                            <span class="font-medium">{{ $appointment->patient->age }} years</span>
                        </div>
                    </div>
                </div>

                <!-- Doctor Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-md mr-2 text-medical-green"></i>
                        Doctor Information
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Doctor Name:</span>
                            <span class="font-medium">Dr. {{ $appointment->doctor->name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Specialization:</span>
                            <span class="font-medium">{{ $appointment->doctor->specialization }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Qualification:</span>
                            <span class="font-medium">{{ $appointment->doctor->qualification }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Consultation Fee:</span>
                            <span class="font-medium">${{ number_format($appointment->doctor->consultation_fee, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Appointment Details -->
                <div class="md:col-span-2">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-orange-500"></i>
                        Appointment Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Appointment Number:</span>
                                <span class="font-medium text-orange-600">{{ $appointment->appointment_no }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Department:</span>
                                <span class="font-medium">{{ $appointment->doctor->department->name ?? 'No Department' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Date:</span>
                                <span class="font-medium">{{ $appointment->appointment_date->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Time:</span>
                                <span class="font-medium">{{ $appointment->appointment_time->format('h:i A') }}</span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Status:</span>
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$appointment->status] }}">
                                    {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reason & Notes -->
                @if($appointment->reason || $appointment->notes)
                <div class="md:col-span-2">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-file-alt mr-2 text-purple-500"></i>
                        Additional Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($appointment->reason)
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Reason for Visit</h5>
                            <p class="text-gray-600 bg-gray-50 p-3 rounded-lg">{{ $appointment->reason }}</p>
                        </div>
                        @endif
                        @if($appointment->notes)
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Additional Notes</h5>
                            <p class="text-gray-600 bg-gray-50 p-3 rounded-lg">{{ $appointment->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Registration Info -->
                <div class="md:col-span-2 mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock mr-2 text-gray-500"></i>
                        Registration Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Scheduled On:</span>
                            <span class="font-medium">{{ $appointment->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Last Updated:</span>
                            <span class="font-medium">{{ $appointment->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection