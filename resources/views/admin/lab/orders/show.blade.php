@extends('admin.layout')

@section('title', 'Lab Order Details - Hospital Management System')
@section('page-title', 'Lab Order Details')
@section('page-description', 'View laboratory order information')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">{{ $labOrder->order_number }}</h3>
                    <p class="text-sm text-gray-600">{{ $labOrder->investigation?->name ?? 'Unknown Test' }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    @php
                        $statusColors = [
                            'ordered' => 'bg-blue-100 text-blue-800',
                            'collected' => 'bg-yellow-100 text-yellow-800',
                            'testing' => 'bg-purple-100 text-purple-800',
                            'verified' => 'bg-orange-100 text-orange-800',
                            'reported' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800'
                        ];
                    @endphp
                    <span class="px-3 py-1 text-sm rounded-full {{ $statusColors[$labOrder->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst(str_replace('_', ' ', $labOrder->status)) }}
                    </span>
                    <a href="{{ route('lab-orders.index') }}" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Investigation Orders
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Order Information -->
                <div class="space-y-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-medium text-blue-800 mb-3">Order Information</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-blue-600">Order Number:</span>
                                <span class="font-medium">{{ $labOrder->order_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-600">Test:</span>
                                <span class="font-medium">{{ $labOrder->investigation?->name ?? 'Unknown Test' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-600">Category:</span>
                                <span>{{ $labOrder->investigation ? ucfirst($labOrder->investigation->category) : 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-600">Sample Type:</span>
                                <span>{{ $labOrder->investigation ? ucfirst($labOrder->investigation->sample_type) : 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-600">Priority:</span>
                                <span class="px-2 py-1 text-xs rounded-full {{ $labOrder->priority === 'stat' ? 'bg-red-100 text-red-800' : ($labOrder->priority === 'urgent' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ strtoupper($labOrder->priority) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-600">Price:</span>
                                <span class="font-medium">₨{{ $labOrder->investigation ? number_format($labOrder->investigation->price, 0) : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 rounded-lg p-4">
                        <h4 class="font-medium text-green-800 mb-3">Patient Information</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-green-600">Name:</span>
                                <span class="font-medium">{{ $labOrder->patient?->name ?? 'Unknown Patient' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-600">Patient No:</span>
                                <span>{{ $labOrder->patient?->patient_no ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-600">Age:</span>
                                <span>{{ $labOrder->patient?->age ?? 'N/A' }} years</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-600">Gender:</span>
                                <span>{{ $labOrder->patient ? ucfirst($labOrder->patient->gender) : 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-600">Phone:</span>
                                <span>{{ $labOrder->patient?->phone ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    @if($labOrder->doctor)
                    <div class="bg-purple-50 rounded-lg p-4">
                        <h4 class="font-medium text-purple-800 mb-3">Ordering Doctor</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-purple-600">Name:</span>
                                <span class="font-medium">Dr. {{ $labOrder->doctor?->name ?? 'Unknown Doctor' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-purple-600">Specialization:</span>
                                <span>{{ $labOrder->doctor?->specialization ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Timeline & Actions -->
                <div class="space-y-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-800 mb-3">Timeline</h4>
                        <div class="space-y-3">
                            <div class="flex items-center text-sm">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                                <div>
                                    <span class="font-medium">Ordered</span>
                                    <div class="text-gray-500">{{ $labOrder->ordered_at ? $labOrder->ordered_at->format('M d, Y h:i A') : 'N/A' }}</div>
                                </div>
                            </div>
                            
                            @if($labOrder->sample_collected_at)
                            <div class="flex items-center text-sm">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                                <div>
                                    <span class="font-medium">Sample Collected</span>
                                    <div class="text-gray-500">{{ $labOrder->sample_collected_at ? $labOrder->sample_collected_at->format('M d, Y h:i A') : 'N/A' }}</div>
                                </div>
                            </div>
                            @endif
                            
                            @if($labOrder->sample_received_at)
                            <div class="flex items-center text-sm">
                                <div class="w-3 h-3 bg-purple-500 rounded-full mr-3"></div>
                                <div>
                                    <span class="font-medium">Sample Received</span>
                                    <div class="text-gray-500">{{ $labOrder->sample_received_at ? $labOrder->sample_received_at->format('M d, Y h:i A') : 'N/A' }}</div>
                                </div>
                            </div>
                            @endif
                            
                            @if($labOrder->completed_at)
                            <div class="flex items-center text-sm">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                <div>
                                    <span class="font-medium">Completed</span>
                                    <div class="text-gray-500">{{ $labOrder->completed_at ? $labOrder->completed_at->format('M d, Y h:i A') : 'N/A' }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($labOrder->clinical_notes)
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <h4 class="font-medium text-yellow-800 mb-2">Clinical Notes</h4>
                        <p class="text-sm text-yellow-700">{{ $labOrder->clinical_notes }}</p>
                    </div>
                    @endif

                    @if($labOrder->investigation?->instructions)
                    <div class="bg-indigo-50 rounded-lg p-4">
                        <h4 class="font-medium text-indigo-800 mb-2">Test Instructions</h4>
                        <p class="text-sm text-indigo-700">{{ $labOrder->investigation->instructions }}</p>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        @if($labOrder->status === 'ordered')
                            <form action="{{ route('lab-orders.collect-sample', $labOrder) }}" method="POST" class="mb-3">
                                @csrf
                                <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">
                                    <i class="fas fa-vial mr-2"></i>Mark Sample Collected
                                </button>
                            </form>
                        @endif

                        @if($labOrder->status === 'collected')
                            <form action="{{ route('lab-orders.receive-sample', $labOrder) }}" method="POST" class="mb-3">
                                @csrf
                                <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                                    <i class="fas fa-check mr-2"></i>Mark Sample Received
                                </button>
                            </form>
                        @endif

                        @if($labOrder->result)
                            <a href="{{ route('lab-results.show', $labOrder->result) }}" class="block w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-center">
                                <i class="fas fa-chart-line mr-2"></i>View Results
                            </a>
                        @endif

                        <a href="{{ route('lab-orders.edit', $labOrder) }}" class="block w-full bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center">
                            <i class="fas fa-edit mr-2"></i>Edit Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection