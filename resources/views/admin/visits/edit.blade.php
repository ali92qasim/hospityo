@extends('admin.layout')

@section('title', 'Edit Visit - Hospital Management System')
@section('page-title', 'Edit Visit')
@section('page-description', 'Update visit information')

@push('styles')
@vite(['resources/css/visits-form.css'])
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Edit Visit: {{ $visit->visit_no }}</h3>
                <a href="{{ route('visits.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Visits
                </a>
            </div>
        </div>

        <form action="{{ route('visits.update', $visit) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Visit Info -->
                <div class="md:col-span-2">
                    <h4 class="text-md font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clipboard-list mr-2 text-purple-500"></i>
                        Visit Information
                    </h4>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visit Number</label>
                    <input type="text" value="{{ $visit->visit_no }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" 
                           readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="active" {{ old('status', $visit->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ old('status', $visit->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="discharged" {{ old('status', $visit->status) == 'discharged' ? 'selected' : '' }}>Discharged</option>
                        <option value="transferred" {{ old('status', $visit->status) == 'transferred' ? 'selected' : '' }}>Transferred</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visit Type *</label>
                    <select name="visit_type" id="visit_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="opd" {{ old('visit_type', $visit->visit_type) == 'opd' ? 'selected' : '' }}>OPD</option>
                        <option value="ipd" {{ old('visit_type', $visit->visit_type) == 'ipd' ? 'selected' : '' }}>IPD</option>
                        <option value="emergency" {{ old('visit_type', $visit->visit_type) == 'emergency' ? 'selected' : '' }}>Emergency</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority *</label>
                    <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @php $selectedPriority = old('priority', $visitAdmin['display_priority']); @endphp
                        <option value="low" {{ $selectedPriority == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ $selectedPriority == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ $selectedPriority == 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ $selectedPriority == 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>

                <!-- Patient & Doctor -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                    <select name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ old('patient_id', $visit->patient_id) == $patient->id ? 'selected' : '' }}>
                            {{ $patient->name }} ({{ $patient->patient_no }})
                        </option>
                        @endforeach
                    </select>
                </div>

                @if($visitAdmin['requires_doctor'])
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Doctor *</label>
                    <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" {{ old('doctor_id', $visit->doctor_id) == $doctor->id ? 'selected' : '' }}>
                            Dr. {{ $doctor->name }} - {{ $doctor->specialization }} ({{ $doctor->department->name ?? 'No Department' }})
                        </option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                        Attending doctors for IPD visits are managed through the visit workflow care team.
                    </p>
                </div>
                @endif

                <!-- Dates -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visit Date & Time *</label>
                    <input type="text" name="visit_datetime" value="{{ old('visit_datetime', $visit->visit_datetime->format('Y-m-d H:i')) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                           placeholder="YYYY-MM-DD HH:MM"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Closed At</label>
                    <input type="text" name="closed_at" value="{{ old('closed_at', $visit->closed_at?->format('Y-m-d H:i')) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           placeholder="YYYY-MM-DD HH:MM">
                </div>

                @if($visitAdmin['bed_number'] || $visitAdmin['ward_name'])
                <div class="md:col-span-2">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm text-gray-700">
                        @if($visitAdmin['ward_name'])
                            <div><span class="font-medium">Ward:</span> {{ $visitAdmin['ward_name'] }}</div>
                        @endif
                        @if($visitAdmin['bed_number'])
                            <div><span class="font-medium">Bed:</span> {{ $visitAdmin['bed_number'] }}</div>
                        @endif
                    </div>
                </div>
                @endif

                @if($visitAdmin['total_charges'] > 0)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Total Charges (from bills)</label>
                    <input type="text" value="{{ format_currency($visitAdmin['total_charges']) }}" readonly
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                @endif

                <!-- Medical Information -->
                <div class="md:col-span-2 mt-6">
                    <h4 class="text-md font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-stethoscope mr-2 text-red-500"></i>
                        Medical Information
                    </h4>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chief Complaint</label>
                    <textarea name="chief_complaint" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('chief_complaint', $visitAdmin['chief_complaint']) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Diagnosis</label>
                    <textarea name="diagnosis" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('diagnosis', $visitAdmin['diagnosis']) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Treatment</label>
                    <textarea name="treatment" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('treatment', $visitAdmin['treatment']) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Clinical Notes</label>
                    <textarea name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('notes', $visitAdmin['clinical_notes']) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('visits.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Update Visit
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
@vite(['resources/js/visits-form.js'])
@endpush
@endsection