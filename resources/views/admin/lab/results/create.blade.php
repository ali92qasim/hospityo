@extends('admin.layout')

@section('title', 'Add Lab Result - Hospital Management System')
@section('page-title', 'Add Lab Result')
@section('page-description', 'Enter laboratory test results')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Add Test Result</h3>
                    <p class="text-sm text-gray-600">{{ $labOrder->labTest->name }} - {{ $labOrder->patient->name }}</p>
                </div>
                <a href="{{ route('lab-results.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Lab Results
                </a>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Order Information -->
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-medium text-blue-800 mb-2">Order Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="text-blue-600">Order #:</span> {{ $labOrder->order_number }}</div>
                        <div><span class="text-blue-600">Test:</span> {{ $labOrder->labTest->name }}</div>
                        <div><span class="text-blue-600">Priority:</span> {{ strtoupper($labOrder->priority) }}</div>
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-medium text-green-800 mb-2">Patient Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="text-green-600">Name:</span> {{ $labOrder->patient->name }}</div>
                        <div><span class="text-green-600">Age:</span> {{ $labOrder->patient->age }} years</div>
                        <div><span class="text-green-600">Gender:</span> {{ ucfirst($labOrder->patient->gender) }}</div>
                    </div>
                </div>

                <!-- Test Information -->
                <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="font-medium text-purple-800 mb-2">Test Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="text-purple-600">Category:</span> {{ ucfirst($labOrder->labTest->category) }}</div>
                        <div><span class="text-purple-600">Sample:</span> {{ ucfirst($labOrder->labTest->sample_type) }}</div>
                        <div><span class="text-purple-600">Ordered:</span> {{ $labOrder->ordered_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>

            <form action="{{ route('lab-orders.results.store', $labOrder) }}" method="POST">
                @csrf
                
                <div class="space-y-6">
                    <!-- Test Results -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-800 mb-4">Test Results</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Result Value *</label>
                                <input type="text" name="results[value]" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                       placeholder="Enter test result value" required>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit</label>
                                    <input type="text" name="results[unit]" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                           placeholder="e.g., mg/dL, cells/μL">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Reference Range</label>
                                    <input type="text" name="results[reference_range]" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                           placeholder="e.g., 70-100 mg/dL">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Flag</label>
                                <select name="results[flag]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                                    <option value="">Normal</option>
                                    <option value="high">High</option>
                                    <option value="low">Low</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Clinical Interpretation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Clinical Interpretation</label>
                        <textarea name="interpretation" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                  placeholder="Enter clinical interpretation of results..."></textarea>
                    </div>

                    <!-- Comments -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Comments</label>
                        <textarea name="comments" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                  placeholder="Additional comments or notes..."></textarea>
                    </div>

                    @if($labOrder->clinical_notes)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h5 class="font-medium text-yellow-800 mb-2">Clinical Notes from Doctor</h5>
                        <p class="text-sm text-yellow-700">{{ $labOrder->clinical_notes }}</p>
                    </div>
                    @endif
                </div>

                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('lab-results.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Save Results
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection