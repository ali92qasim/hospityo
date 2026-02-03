@extends('admin.layout')

@section('title', 'Visit Details - Hospital Management System')
@section('page-title', 'Visit Details')
@section('page-description', 'View complete visit information')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    @php
                        $typeColors = [
                            'opd' => 'bg-blue-500',
                            'ipd' => 'bg-green-500',
                            'emergency' => 'bg-red-500'
                        ];
                        $typeIcons = [
                            'opd' => 'fas fa-user-check',
                            'ipd' => 'fas fa-bed',
                            'emergency' => 'fas fa-ambulance'
                        ];
                    @endphp
                    <div class="w-16 h-16 {{ $typeColors[$visit->visit_type] }} rounded-full flex items-center justify-center mr-4">
                        <i class="{{ $typeIcons[$visit->visit_type] }} text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">{{ $visit->visit_no }}</h3>
                        <p class="text-sm text-gray-600">{{ strtoupper($visit->visit_type) }} Visit</p>
                        @php
                            $statusColors = [
                                'active' => 'bg-blue-100 text-blue-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'discharged' => 'bg-gray-100 text-gray-800',
                                'transferred' => 'bg-yellow-100 text-yellow-800'
                            ];
                            $priorityColors = [
                                'low' => 'bg-gray-100 text-gray-800',
                                'medium' => 'bg-blue-100 text-blue-800',
                                'high' => 'bg-orange-100 text-orange-800',
                                'critical' => 'bg-red-100 text-red-800'
                            ];
                        @endphp
                        <div class="flex space-x-2 mt-1">
                            <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$visit->status] }}">
                                {{ ucfirst($visit->status) }}
                            </span>
                            <span class="px-2 py-1 text-xs rounded-full {{ $priorityColors[$visit->priority] }}">
                                {{ ucfirst($visit->priority) }} Priority
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('visits.edit', $visit) }}" class="px-4 py-2 bg-medical-green text-white rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-edit mr-2"></i>
                        Edit
                    </a>
                    <a href="{{ route('visits.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
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
                            <span class="font-medium">{{ $visit->patient->name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Patient Number:</span>
                            <span class="font-medium text-medical-blue">{{ $visit->patient->patient_no }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Age:</span>
                            <span class="font-medium">{{ $visit->patient->age }} years</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium">{{ $visit->patient->phone }}</span>
                        </div>
                    </div>
                </div>

                <!-- Doctor & Department -->
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-md mr-2 text-medical-green"></i>
                        Medical Team
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Doctor:</span>
                            <span class="font-medium">Dr. {{ $visit->doctor->name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Specialization:</span>
                            <span class="font-medium">{{ $visit->doctor->specialization }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Department:</span>
                            <span class="font-medium">{{ $visit->doctor->department->name ?? 'No Department' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Consultation Fee:</span>
                            <span class="font-medium">${{ number_format($visit->doctor->consultation_fee, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Visit Details -->
                <div class="md:col-span-2">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clipboard-list mr-2 text-purple-500"></i>
                        Visit Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Visit Number:</span>
                                <span class="font-medium text-purple-600">{{ $visit->visit_no }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Visit Type:</span>
                                <span class="font-medium">{{ strtoupper($visit->visit_type) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Visit Date:</span>
                                <span class="font-medium">{{ $visit->visit_datetime->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Visit Time:</span>
                                <span class="font-medium">{{ $visit->visit_datetime->format('h:i A') }}</span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            @if($visit->room_no || $visit->bed_no)
                                @if($visit->room_no)
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Room Number:</span>
                                    <span class="font-medium">{{ $visit->room_no }}</span>
                                </div>
                                @endif
                                @if($visit->bed_no)
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Bed Number:</span>
                                    <span class="font-medium">{{ $visit->bed_no }}</span>
                                </div>
                                @endif
                            @endif
                            @if($visit->discharge_datetime)
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Discharge Date:</span>
                                <span class="font-medium">{{ $visit->discharge_datetime->format('M d, Y h:i A') }}</span>
                            </div>
                            @endif
                            @if($visit->total_charges > 0)
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Total Charges:</span>
                                <span class="font-medium text-green-600">${{ number_format($visit->total_charges, 2) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Medical Information -->
                @if($visit->chief_complaint || $visit->diagnosis || $visit->treatment)
                <div class="md:col-span-2">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-stethoscope mr-2 text-red-500"></i>
                        Medical Information
                    </h4>
                    <div class="space-y-4">
                        @if($visit->chief_complaint)
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Chief Complaint</h5>
                            <p class="text-gray-600 bg-gray-50 p-3 rounded-lg">{{ $visit->chief_complaint }}</p>
                        </div>
                        @endif
                        @if($visit->diagnosis)
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Diagnosis</h5>
                            <p class="text-gray-600 bg-blue-50 p-3 rounded-lg">{{ $visit->diagnosis }}</p>
                        </div>
                        @endif
                        @if($visit->treatment)
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Treatment</h5>
                            <p class="text-gray-600 bg-green-50 p-3 rounded-lg">{{ $visit->treatment }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Notes -->
                @if($visit->notes)
                <div class="md:col-span-2">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-sticky-note mr-2 text-yellow-500"></i>
                        Additional Notes
                    </h4>
                    <p class="text-gray-600 bg-yellow-50 p-4 rounded-lg">{{ $visit->notes }}</p>
                </div>
                @endif

                <!-- Visit Timeline -->
                <div class="md:col-span-2 mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock mr-2 text-gray-500"></i>
                        Visit Timeline
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Visit Created:</span>
                            <span class="font-medium">{{ $visit->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Last Updated:</span>
                            <span class="font-medium">{{ $visit->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection