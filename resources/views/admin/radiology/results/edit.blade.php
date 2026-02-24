@extends('admin.layout')

@section('title', 'Edit Radiology Result - Hospital Management System')
@section('page-title', 'Edit Radiology Result')
@section('page-description', 'Update radiology/cardiology test results')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Edit {{ ucfirst($radiologyResult->investigationOrder->investigation->type) }} Result</h3>
                    <p class="text-sm text-gray-600">{{ $radiologyResult->investigationOrder->investigation?->name ?? 'Unknown Test' }} - {{ $radiologyResult->investigationOrder->patient?->name ?? 'Unknown Patient' }}</p>
                </div>
                <a href="{{ route('radiology-results.show', $radiologyResult) }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>

        <div class="p-6">
            <form action="{{ route('radiology-results.update', $radiologyResult) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    <!-- Report Text -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Report / Findings *</label>
                        <textarea name="report_text" rows="8" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                  placeholder="Enter detailed findings and observations..." required>{{ old('report_text', $radiologyResult->report_text) }}</textarea>
                    </div>

                    <!-- Impression -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Impression / Conclusion *</label>
                        <textarea name="impression" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                                  placeholder="Enter clinical impression and conclusion..." required>{{ old('impression', $radiologyResult->impression) }}</textarea>
                    </div>

                    <!-- Current File -->
                    @if($radiologyResult->file_path)
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h5 class="font-medium text-blue-800 mb-2">Current Report File</h5>
                        <a href="{{ Storage::url($radiologyResult->file_path) }}" target="_blank" 
                           class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-file-download mr-2"></i>
                            Download Current Report
                        </a>
                    </div>
                    @endif

                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $radiologyResult->file_path ? 'Replace Report/Images (Optional)' : 'Upload Report/Images (Optional)' }}
                        </label>
                        <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG (Max 10MB)</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Report Status *</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="draft" {{ old('status', $radiologyResult->status) === 'draft' ? 'selected' : '' }}>Draft (Not yet finalized)</option>
                            <option value="final" {{ old('status', $radiologyResult->status) === 'final' ? 'selected' : '' }}>Final (Ready for review)</option>
                            <option value="amended" {{ old('status', $radiologyResult->status) === 'amended' ? 'selected' : '' }}>Amended (Corrected report)</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('radiology-results.show', $radiologyResult) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Update Result
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
