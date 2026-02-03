@extends('admin.layout')

@section('title', 'Register Visit - Hospital Management System')
@section('page-title', 'Register Visit')
@section('page-description', 'Register a new patient visit')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Visit Registration</h3>
                <a href="{{ route('visits.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Visits
                </a>
            </div>
        </div>

        <form action="{{ route('visits.store') }}" method="POST" class="p-6">
            @csrf
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                    <select name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ (old('patient_id') == $patient->id || request('patient_id') == $patient->id) ? 'selected' : '' }}>
                            {{ $patient->name }} ({{ $patient->patient_no }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visit Type *</label>
                    <select name="visit_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="">Select Visit Type</option>
                        <option value="opd" {{ old('visit_type') == 'opd' ? 'selected' : '' }}>OPD (Out Patient Department)</option>
                        <option value="ipd" {{ old('visit_type') == 'ipd' ? 'selected' : '' }}>IPD (In Patient Department)</option>
                        <option value="emergency" {{ old('visit_type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visit Date & Time *</label>
                    <input type="datetime-local" name="visit_datetime" value="{{ old('visit_datetime', now()->format('Y-m-d\TH:i')) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                           required>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('visits.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Register Visit
                </button>
            </div>
        </form>
    </div>
</div>
@endsection