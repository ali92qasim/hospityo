@extends('admin.layout')

@section('title', 'Add Radiology Result - Hospital Management System')
@section('page-title', 'Add Radiology Result')
@section('page-description', 'Enter radiology/cardiology test results')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Add {{ ucfirst($investigationOrder->investigation->type) }} Result</h3>
                    <p class="text-sm text-gray-600">{{ $investigationOrder->investigation?->name ?? 'Unknown Test' }} - {{ $investigationOrder->patient?->name ?? 'Unknown Patient' }}</p>
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
                        <div><span class="text-blue-600">Order #:</span> {{ $investigationOrder->order_number }}</div>
                        <div><span class="text-blue-600">Test:</span> {{ $investigationOrder->investigation?->name ?? 'Unknown Test' }}</div>
                        <div><span class="text-blue-600">Type:</span> 
                            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full font-medium
                                {{ $investigationOrder->investigation->type === 'radiology' ? 'bg-purple-100 text-purple-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($investigationOrder->investigation->type) }}
                            </span>
                        </div>
                        <div><span class="text-blue-600">Priority:</span> {{ strtoupper($investigationOrder->priority) }}</div>
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-medium text-green-800 mb-2">Patient Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="text-green-600">Name:</span> {{ $investigationOrder->patient?->name ?? 'Unknown Patient' }}</div>
                        <div><span class="text-green-600">Age:</span> {{ $investigationOrder->patient?->age ?? 'N/A' }} years</div>
                        <div><span class="text-green-600">Gender:</span> {{ $investigationOrder->patient ? ucfirst($investigationOrder->patient->gender) : 'N/A' }}</div>
                    </div>
                </div>

                <!-- Test Information -->
                <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="font-medium text-purple-800 mb-2">Test Information</h4>
                    <div class="space-y-1 text-sm">
                        <div><span class="text-purple-600">Category:</span> {{ $investigationOrder->investigation ? ucfirst($investigationOrder->investigation->category) : 'N/A' }}</div>
                        <div><span class="text-purple-600">Ordered:</span> {{ $investigationOrder->ordered_at ? $investigationOrder->ordered_at->format('M d, Y H:i') : 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <form action="{{ route('radiology-results.store', $investigationOrder) }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="space-y-6">
                    <!-- Report Text -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Report / Findings *</label>
                        <textarea name="report_text" rows="8" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                  placeholder="Enter detailed findings and observations..." required></textarea>
                        <p class="text-xs text-gray-500 mt-1">Describe the findings from the {{ $investigationOrder->investigation->type }} examination</p>
                    </div>

                    <!-- Impression -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Impression / Conclusion *</label>
                        <textarea name="impression" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                  placeholder="Enter clinical impression and conclusion..." required></textarea>
                        <p class="text-xs text-gray-500 mt-1">Summarize the key findings and clinical significance</p>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Report/Images (Optional)</label>
                        <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG (Max 10MB)</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Report Status *</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="draft">Draft (Not yet finalized)</option>
                            <option value="final" selected>Final (Ready for review)</option>
                            <option value="amended">Amended (Corrected report)</option>
                        </select>
                    </div>

                    @if($investigationOrder->clinical_notes)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h5 class="font-medium text-yellow-800 mb-2">Clinical Notes from Doctor</h5>
                        <p class="text-sm text-yellow-700">{{ $investigationOrder->clinical_notes }}</p>
                    </div>
                    @endif
                </div>

                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('lab-results.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Save Result
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
