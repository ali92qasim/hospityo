@extends('admin.layout')

@section('title', 'Patient Details - Hospital Management System')
@section('page-title', 'Patient Details')
@section('page-description', 'View complete patient information')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-medical-blue rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">{{ $patient->name }}</h3>
                        <p class="text-sm text-gray-600">Patient ID: {{ $patient->patient_no }}</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('patients.edit', $patient) }}" class="px-4 py-2 bg-medical-green text-white rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-edit mr-2"></i>
                        Edit
                    </a>
                    <a href="{{ route('patients.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Personal Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user mr-2 text-medical-blue"></i>
                        Personal Information
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Full Name:</span>
                            <span class="font-medium">{{ $patient->name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Patient Number:</span>
                            <span class="font-medium text-medical-blue">{{ $patient->patient_no }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Gender:</span>
                            <span class="font-medium">{{ ucfirst($patient->gender) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Age:</span>
                            <span class="font-medium">{{ $patient->age }} years</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium">{{ $patient->phone }}</span>
                        </div>
                        @if($patient->marital_status)
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Marital Status:</span>
                            <span class="font-medium">{{ ucfirst($patient->marital_status) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-phone mr-2 text-red-500"></i>
                        Emergency Contact
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Name:</span>
                            <span class="font-medium">{{ $patient->emergency_name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium">{{ $patient->emergency_phone }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Relationship:</span>
                            <span class="font-medium">{{ $patient->emergency_relation }}</span>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                @if($patient->present_address || $patient->permanent_address)
                <div class="md:col-span-2">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-map-marker-alt mr-2 text-medical-green"></i>
                        Address Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($patient->present_address)
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Present Address</h5>
                            <p class="text-gray-600 bg-gray-50 p-3 rounded-lg">{{ $patient->present_address }}</p>
                        </div>
                        @endif
                        @if($patient->permanent_address)
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Permanent Address</h5>
                            <p class="text-gray-600 bg-gray-50 p-3 rounded-lg">{{ $patient->permanent_address }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Registration Info -->
                <div class="md:col-span-2 mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calendar mr-2 text-gray-500"></i>
                        Registration Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Registered On:</span>
                            <span class="font-medium">{{ $patient->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Last Updated:</span>
                            <span class="font-medium">{{ $patient->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection