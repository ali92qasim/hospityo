@extends('admin.layout')

@section('title', 'Doctor Details - Hospital Management System')
@section('page-title', 'Doctor Details')
@section('page-description', 'View complete doctor information')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-medical-green rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user-md text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">Dr. {{ $doctor->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $doctor->doctor_no }} • {{ $doctor->specialization }}</p>
                        <span class="px-2 py-1 text-xs rounded-full {{ $doctor->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($doctor->status) }}
                        </span>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('doctors.edit', $doctor) }}" class="px-4 py-2 bg-medical-green text-white rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-edit mr-2"></i>
                        Edit
                    </a>
                    <a href="{{ route('doctors.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
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
                            <span class="font-medium">Dr. {{ $doctor->name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Doctor Number:</span>
                            <span class="font-medium text-medical-blue">{{ $doctor->doctor_no }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Gender:</span>
                            <span class="font-medium">{{ ucfirst($doctor->gender) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium">{{ $doctor->phone }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium">{{ $doctor->email }}</span>
                        </div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-stethoscope mr-2 text-medical-green"></i>
                        Professional Information
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Specialization:</span>
                            <span class="font-medium">{{ $doctor->specialization }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Qualification:</span>
                            <span class="font-medium">{{ $doctor->qualification }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Experience:</span>
                            <span class="font-medium">{{ $doctor->experience_years }} years</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Consultation Fee:</span>
                            <span class="font-medium">${{ number_format($doctor->consultation_fee, 2) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Status:</span>
                            <span class="px-2 py-1 text-xs rounded-full {{ $doctor->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($doctor->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Schedule Information -->
                <div class="md:col-span-2">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock mr-2 text-yellow-500"></i>
                        Schedule Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Shift Start:</span>
                                <span class="font-medium">{{ $doctor->shift_start }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Shift End:</span>
                                <span class="font-medium">{{ $doctor->shift_end }}</span>
                            </div>
                        </div>
                        @if($doctor->available_days)
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Available Days</h5>
                            <div class="flex flex-wrap gap-2">
                                @foreach($doctor->available_days as $day)
                                <span class="px-2 py-1 bg-medical-light text-medical-blue text-xs rounded-full">{{ $day }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Address Information -->
                @if($doctor->address)
                <div class="md:col-span-2">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                        Address Information
                    </h4>
                    <p class="text-gray-600 bg-gray-50 p-3 rounded-lg">{{ $doctor->address }}</p>
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
                            <span class="font-medium">{{ $doctor->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Last Updated:</span>
                            <span class="font-medium">{{ $doctor->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection