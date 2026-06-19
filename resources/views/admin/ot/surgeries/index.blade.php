@extends('admin.layout')

@section('title', 'Surgeries - Operation Theatre')
@section('page-title', 'Surgeries')
@section('page-description', 'Schedule and manage surgeries')

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
@vite(['resources/css/ot-surgeries.css'])
@endpush

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Surgery Calendar</h3>
                <p class="text-sm text-gray-600">Click on a date to schedule surgery or click on existing surgeries to view details</p>
            </div>
            <div class="flex space-x-3">
                <select id="theatre-filter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">
                    <option value="">All Theatres</option>
                    @foreach($theatres as $theatre)
                    <option value="{{ $theatre->id }}">{{ $theatre->name }}</option>
                    @endforeach
                </select>
                <button onclick="openSurgeryModal()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center text-sm">
                    <i class="fas fa-plus mr-2"></i>Schedule Surgery
                </button>
            </div>
        </div>
    </div>

    <div class="px-6 pt-4 pb-2">
        <div class="flex flex-wrap gap-4 items-center text-xs text-gray-600">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-blue-500"></span>Scheduled</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-yellow-500"></span>In Progress</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-green-500"></span>Completed</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-red-500"></span>Cancelled</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-orange-500"></span>Postponed</span>
        </div>
    </div>

    <div class="p-6">
        <div id="surgery-calendar"></div>
    </div>
</div>

<!-- Schedule Surgery Modal -->
<div id="surgeryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800" id="surgery-modal-title">Schedule Surgery</h3>
                    <button onclick="closeSurgeryModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <form id="surgeryForm" class="p-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                        <select name="patient_id" id="surgery_patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                            <option value="">Select Patient</option>
                            @foreach($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->patient_no }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lead Surgeon *</label>
                        <select name="doctor_id" id="surgery_doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                            <option value="">Select Surgeon</option>
                            @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Procedure Name *</label>
                        <input type="text" name="procedure_name" id="surgery_procedure" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="e.g. Appendectomy" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Surgery Type *</label>
                        <select name="surgery_type" id="surgery_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                            <option value="elective">Elective</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Scheduled Date & Time *</label>
                        <input type="text" name="scheduled_datetime" id="surgery_datetime" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="YYYY-MM-DD HH:MM" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Operation Theatre</label>
                        <select name="operation_theatre_id" id="surgery_theatre" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                            <option value="">Select Theatre</option>
                            @foreach($theatres as $theatre)
                            <option value="{{ $theatre->id }}">{{ $theatre->name }} ({{ ucfirst($theatre->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Anesthesia Type</label>
                        <select name="anesthesia_type" id="surgery_anesthesia" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                            <option value="">Select</option>
                            <option value="general">General</option>
                            <option value="local">Local</option>
                            <option value="spinal">Spinal</option>
                            <option value="epidural">Epidural</option>
                            <option value="sedation">Sedation</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pre-Op Diagnosis</label>
                        <textarea name="pre_op_diagnosis" id="surgery_diagnosis" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-6 sticky bottom-0 bg-white pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeSurgeryModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-calendar-check mr-2"></i>Schedule Surgery
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
@vite(['resources/js/ot-surgeries.js'])
@endpush
@endsection
