@extends('admin.layout')

@section('title', 'Radiology Result - Hospital Management System')
@section('page-title', 'Radiology Result')
@section('page-description', 'View radiology/cardiology test result')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800">{{ $radiologyResult->investigationOrder->investigation->name }}</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Order #{{ $radiologyResult->investigationOrder->order_number }} - 
                        {{ $radiologyResult->investigationOrder->patient->name }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1 text-sm rounded-full font-medium
                        {{ $radiologyResult->status === 'final' ? 'bg-green-100 text-green-800' : 
                           ($radiologyResult->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                        {{ ucfirst($radiologyResult->status) }}
                    </span>
                    <a href="{{ route('lab-results.index') }}" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Patient & Order Info -->
        <div class="p-6 bg-gray-50 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Patient Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="font-medium">Name:</span> {{ $radiologyResult->investigationOrder->patient->name }}</div>
                        <div><span class="font-medium">Age:</span> {{ $radiologyResult->investigationOrder->patient->age }} years</div>
                        <div><span class="font-medium">Gender:</span> {{ ucfirst($radiologyResult->investigationOrder->patient->gender) }}</div>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Order Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="font-medium">Visit:</span> {{ $radiologyResult->investigationOrder->visit->visit_no }}</div>
                        <div><span class="font-medium">Ordered:</span> {{ $radiologyResult->investigationOrder->ordered_at->format('M d, Y H:i') }}</div>
                        <div><span class="font-medium">Doctor:</span> {{ $radiologyResult->investigationOrder->doctor->name ?? 'N/A' }}</div>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Result Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="font-medium">Radiologist:</span> {{ $radiologyResult->radiologist->name ?? 'N/A' }}</div>
                        <div><span class="font-medium">Reported:</span> {{ $radiologyResult->reported_at ? $radiologyResult->reported_at->format('M d, Y H:i') : 'Not finalized' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="p-6">
            @if($radiologyResult->investigationOrder->clinical_notes)
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h5 class="font-medium text-yellow-800 mb-2">Clinical Notes</h5>
                <p class="text-sm text-yellow-700">{{ $radiologyResult->investigationOrder->clinical_notes }}</p>
            </div>
            @endif

            <!-- Findings -->
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-3">Findings</h4>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-gray-700 whitespace-pre-line">{{ $radiologyResult->report_text }}</p>
                </div>
            </div>

            <!-- Impression -->
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-3">Impression</h4>
                <div class="p-4 bg-blue-50 rounded-lg">
                    <p class="text-gray-700 whitespace-pre-line">{{ $radiologyResult->impression }}</p>
                </div>
            </div>

            <!-- Attached File -->
            @if($radiologyResult->file_path)
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-3">Attached Report/Images</h4>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <a href="{{ Storage::url($radiologyResult->file_path) }}" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-file-download mr-2"></i>
                        Download Report
                    </a>
                </div>
            </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <a href="{{ route('lab-results.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Results
                </a>
                <div class="flex space-x-3">
                    @can('edit visits')
                    <a href="{{ route('radiology-results.edit', $radiologyResult) }}" 
                       class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                        <i class="fas fa-edit mr-2"></i>Edit Result
                    </a>
                    @endcan
                    <button onclick="window.print()" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-print mr-2"></i>Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
@endsection
