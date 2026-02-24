@extends('admin.layout')

@section('title', 'Enter Investigation Results - Hospital Management System')
@section('page-title', 'Enter Investigation Results')
@section('page-description', 'Enter results for multiple tests')

@section('content')
@if($labOrders->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
        <i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-3"></i>
        <h3 class="text-lg font-semibold text-yellow-800 mb-2">No Pending Tests Found</h3>
        <p class="text-yellow-700 mb-4">There are no pending lab tests for this patient.</p>
        <a href="{{ route('lab-results.index') }}" class="inline-flex items-center px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Results
        </a>
    </div>
@else
@php
    $patient = $labOrders->first()->patient;
    $visit = $labOrders->first()->visit;
@endphp

<!-- Patient Header -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="bg-blue-50 px-6 py-4 border-b border-blue-200">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-xl font-semibold text-blue-900">{{ $patient->name }}</h3>
                <div class="flex items-center space-x-4 text-sm text-blue-700 mt-1">
                    <span><i class="fas fa-phone mr-1"></i>{{ $patient->phone }}</span>
                    <span><i class="fas fa-calendar mr-1"></i>{{ $patient->date_of_birth?->format('M d, Y') ?? 'N/A' }}</span>
                    <span><i class="fas fa-clipboard-list mr-1"></i>{{ $visit->visit_no }}</span>
                </div>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                    {{ $labOrders->count() }} test{{ $labOrders->count() > 1 ? 's' : '' }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Results Entry Form -->
<form action="{{ route('lab-results.store-batch') }}" method="POST" id="batch-results-form">
    @csrf
    
    <div class="space-y-6">
        @foreach($labOrders as $index => $labOrder)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Test Header -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">{{ $labOrder->investigation->name }}</h4>
                            <div class="flex items-center space-x-3 mt-1">
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                                    {{ $labOrder->priority === 'stat' ? 'bg-red-100 text-red-800' : 
                                       ($labOrder->priority === 'urgent' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ strtoupper($labOrder->priority) }}
                                </span>
                                <span class="text-sm text-gray-600">
                                    <i class="fas fa-calendar mr-1"></i>{{ $labOrder->ordered_at->format('M d, H:i') }}
                                </span>
                            </div>
                        </div>
                        <button type="button" onclick="toggleTest({{ $index }})" class="text-gray-500 hover:text-gray-700">
                            <i id="toggle-icon-{{ $index }}" class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    @if($labOrder->clinical_notes)
                        <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-800"><strong>Clinical Notes:</strong> {{ $labOrder->clinical_notes }}</p>
                        </div>
                    @endif
                </div>
                
                <!-- Test Content -->
                <div id="test-content-{{ $index }}" class="px-6 py-4">
                    <input type="hidden" name="orders[{{ $index }}][lab_order_id]" value="{{ $labOrder->id }}">
                    
                    <!-- Test Location -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Test Location *</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="orders[{{ $index }}][test_location]" value="indoor" 
                                       class="mr-2 text-medical-blue" checked required>
                                <span class="text-sm">Indoor Lab</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="orders[{{ $index }}][test_location]" value="outdoor" 
                                       class="mr-2 text-medical-blue" required>
                                <span class="text-sm">External Lab</span>
                            </label>
                        </div>
                    </div>
                    
                    @if($labOrder->investigation->parameters && is_object($labOrder->investigation->parameters) && $labOrder->investigation->parameters->count() > 0)
                        <!-- Parameter-based Results -->
                        <div class="mb-4">
                            <h5 class="text-sm font-medium text-gray-700 mb-3">Test Parameters</h5>
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
                                                    <input type="hidden" name="orders[{{ $index }}][parameters][{{ $paramIndex }}][parameter_id]" value="{{ $parameter->id }}">
                                                    <input type="text" name="orders[{{ $index }}][parameters][{{ $paramIndex }}][value]" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-medical-blue" 
                                                           required>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <input type="text" name="orders[{{ $index }}][parameters][{{ $paramIndex }}][unit]" 
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
                        </div>
                    @else
                        <!-- Text-based Results -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Test Result *</label>
                            <textarea name="orders[{{ $index }}][result_text]" rows="4" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" 
                                      placeholder="Enter test results..." required></textarea>
                        </div>
                    @endif
                    
                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                        <textarea name="orders[{{ $index }}][notes]" rows="2" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" 
                                  placeholder="Optional notes or interpretation..."></textarea>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Submit Actions -->
    <div class="flex justify-between items-center mt-8 p-6 bg-white rounded-lg shadow-sm">
        <a href="{{ route('lab-results.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to Results
        </a>
        <div class="flex space-x-4">
            <button type="button" onclick="resetForm()" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                <i class="fas fa-undo mr-2"></i>Reset Form
            </button>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Save All Results
            </button>
        </div>
    </div>
</form>

<script>
function toggleTest(index) {
    const content = document.getElementById(`test-content-${index}`);
    const icon = document.getElementById(`toggle-icon-${index}`);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
    } else {
        content.style.display = 'none';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
    }
}

function resetForm() {
    if (confirm('Reset all entered data?')) {
        document.getElementById('batch-results-form').reset();
    }
}

// Auto-save functionality (optional)
let autoSaveTimer;
function autoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        const formData = new FormData(document.getElementById('batch-results-form'));
        localStorage.setItem('lab-results-draft', JSON.stringify(Object.fromEntries(formData)));
    }, 2000);
}

// Add auto-save listeners
document.querySelectorAll('input, textarea, select').forEach(element => {
    element.addEventListener('input', autoSave);
});

// Load draft on page load
document.addEventListener('DOMContentLoaded', function() {
    const draft = localStorage.getItem('lab-results-draft');
    if (draft && confirm('Load previously saved draft?')) {
        const data = JSON.parse(draft);
        Object.keys(data).forEach(key => {
            const element = document.querySelector(`[name="${key}"]`);
            if (element) {
                if (element.type === 'radio') {
                    document.querySelector(`[name="${key}"][value="${data[key]}"]`).checked = true;
                } else {
                    element.value = data[key];
                }
            }
        });
    }
});

// Clear draft on successful submit
document.getElementById('batch-results-form').addEventListener('submit', function() {
    localStorage.removeItem('lab-results-draft');
});
</script>
@endif
@endsection