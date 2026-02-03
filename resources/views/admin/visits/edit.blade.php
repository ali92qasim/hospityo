@extends('admin.layout')

@section('title', 'Edit Visit - Hospital Management System')
@section('page-title', 'Edit Visit')
@section('page-description', 'Update visit information')

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
                        <option value="low" {{ old('priority', $visit->priority) == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', $visit->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority', $visit->priority) == 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority', $visit->priority) == 'critical' ? 'selected' : '' }}>Critical</option>
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

                <!-- Dates -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visit Date & Time *</label>
                    <input type="datetime-local" name="visit_datetime" value="{{ old('visit_datetime', $visit->visit_datetime->format('Y-m-d\TH:i')) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Discharge Date & Time</label>
                    <input type="datetime-local" name="discharge_datetime" value="{{ old('discharge_datetime', $visit->discharge_datetime?->format('Y-m-d\TH:i')) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                </div>

                <!-- Room & Bed -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room Number</label>
                    <input type="text" name="room_no" value="{{ old('room_no', $visit->room_no) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bed Number</label>
                    <input type="text" name="bed_no" value="{{ old('bed_no', $visit->bed_no) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Total Charges ($)</label>
                    <input type="number" name="total_charges" value="{{ old('total_charges', $visit->total_charges) }}" min="0" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                </div>

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
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('chief_complaint', $visit->chief_complaint) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Diagnosis</label>
                    <textarea name="diagnosis" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('diagnosis', $visit->diagnosis) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Treatment</label>
                    <textarea name="treatment" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('treatment', $visit->treatment) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                    <textarea name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('notes', $visit->notes) }}</textarea>
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
@endsection