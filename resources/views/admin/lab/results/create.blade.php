@extends('admin.layout')

@section('title', 'Add Investigation Result - Hospital Management System')
@section('page-title', 'Add Investigation Result')
@section('page-description', 'Enter test results')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Add Test Result</h3>
                    <p class="text-sm text-gray-600">{{ $labOrder->investigation?->name ?? 'Unknown Test' }} - {{ $labOrder->patient?->name ?? 'Unknown Patient' }}</p>
                </div>
                <a href="{{ route('lab-results.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Investigation Results
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
                        <div><span class="text-blue-600">Test:</span> {{ $labOrder->investigation?->name ?? 'Unknown Test' }}</div>
                        <div><span class="text-blue-600">Priority:</span> {{ strtoupper($labOrder->priority) }}</div>
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-medium text-green-800 mb-2">Patient Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="text-green-600">Name:</span> {{ $labOrder->patient?->name ?? 'Unknown Patient' }}</div>
                        <div><span class="text-green-600">Age:</span> {{ $labOrder->patient?->age ?? 'N/A' }} years</div>
                        <div><span class="text-green-600">Gender:</span> {{ $labOrder->patient ? ucfirst($labOrder->patient->gender) : 'N/A' }}</div>
                    </div>
                </div>

                <!-- Test Information -->
                <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="font-medium text-purple-800 mb-2">Test Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="text-purple-600">Category:</span> {{ $labOrder->investigation ? ucfirst($labOrder->investigation->category) : 'N/A' }}</div>
                        <div><span class="text-purple-600">Sample:</span> {{ $labOrder->investigation ? ucfirst($labOrder->investigation->sample_type) : 'N/A' }}</div>
                        <div><span class="text-purple-600">Location:</span> 
                            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full font-medium
                                {{ ($labOrder->test_location ?? 'indoor') === 'indoor' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                <i class="fas {{ ($labOrder->test_location ?? 'indoor') === 'indoor' ? 'fa-building' : 'fa-external-link-alt' }} mr-1"></i>
                                {{ ($labOrder->test_location ?? 'indoor') === 'indoor' ? 'Indoor Lab' : 'External Lab' }}
                            </span>
                        </div>
                        <div><span class="text-purple-600">Ordered:</span> {{ $labOrder->ordered_at ? $labOrder->ordered_at->format('M d, Y') : 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <form action="{{ route('lab-orders.results.store', $labOrder) }}" method="POST">
                @csrf
                
                <div class="space-y-6">
                    <!-- Test Location -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-800 mb-4">Test Location</h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Processing Location</label>
                            <select name="test_location" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                                <option value="indoor" {{ ($labOrder->test_location ?? 'indoor') === 'indoor' ? 'selected' : '' }}>
                                    <i class="fas fa-building"></i> Indoor Lab
                                </option>
                                <option value="outdoor" {{ ($labOrder->test_location ?? 'indoor') === 'outdoor' ? 'selected' : '' }}>
                                    <i class="fas fa-external-link-alt"></i> External Lab
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Select where this test was processed</p>
                        </div>
                    </div>

                    <!-- Test Results -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-800 mb-4">Test Results</h4>
                        
                        @if($labOrder->investigation->parameters && $labOrder->investigation->parameters->count() > 0)
                            <!-- Parameter-based Results -->
                            <div class="overflow-x-auto">
                                <table class="w-full border border-gray-200 rounded-lg">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Parameter</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Value *</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reference Range</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($labOrder->investigation->parameters as $paramIndex => $parameter)
                                            <tr>
                                                <td class="px-4 py-2 font-medium text-gray-900">{{ $parameter->parameter_name }}</td>
                                                <td class="px-4 py-2">
                                                    <input type="hidden" name="parameters[{{ $paramIndex }}][parameter_id]" value="{{ $parameter->id }}">
                                                    <input type="text" name="parameters[{{ $paramIndex }}][value]" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-medical-blue" 
                                                           required>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <input type="text" name="parameters[{{ $paramIndex }}][unit]" 
                                                           value="{{ $parameter->unit }}" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-medical-blue" 
                                                           readonly>
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-600">
                                                    {{ is_array($parameter->reference_ranges) ? ($parameter->reference_ranges['range'] ?? '-') : ($parameter->reference_ranges ?? '-') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <!-- Text-based Results (for tests without parameters) -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Test Result *</label>
                                    <textarea name="result_text" rows="4" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                              placeholder="Enter test results..." required></textarea>
                                </div>
                            </div>
                        @endif
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