@extends('admin.layout')

@section('title', 'Department Details - Hospital Management System')
@section('page-title', 'Department Details')
@section('page-description', 'View complete department information')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-building text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">{{ $department->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $department->code }}</p>
                        <span class="px-2 py-1 text-xs rounded-full {{ $department->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($department->status) }}
                        </span>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('departments.edit', $department) }}" class="px-4 py-2 bg-medical-green text-white rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-edit mr-2"></i>
                        Edit
                    </a>
                    <a href="{{ route('departments.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Basic Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-medical-blue"></i>
                        Basic Information
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Department Name:</span>
                            <span class="font-medium">{{ $department->name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Department Code:</span>
                            <span class="font-medium text-medical-blue">{{ $department->code }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Status:</span>
                            <span class="px-2 py-1 text-xs rounded-full {{ $department->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($department->status) }}
                            </span>
                        </div>
                        @if($department->location)
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Location:</span>
                            <span class="font-medium">{{ $department->location }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Management & Contact -->
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-tie mr-2 text-medical-green"></i>
                        Management & Contact
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Head of Department:</span>
                            <span class="font-medium">{{ $department->head_of_department ?: 'Not assigned' }}</span>
                        </div>
                        @if($department->phone)
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium">{{ $department->phone }}</span>
                        </div>
                        @endif
                        @if($department->email)
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium">{{ $department->email }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Description -->
                @if($department->description)
                <div class="md:col-span-2">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-file-alt mr-2 text-purple-500"></i>
                        Description
                    </h4>
                    <p class="text-gray-600 bg-gray-50 p-4 rounded-lg">{{ $department->description }}</p>
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
                            <span class="text-gray-600">Created On:</span>
                            <span class="font-medium">{{ $department->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Last Updated:</span>
                            <span class="font-medium">{{ $department->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection