@extends('admin.layout')

@section('title', 'Appointment Calendar - Hospital Management System')
@section('page-title', 'Appointment Calendar')
@section('page-description', 'Schedule and manage appointments')

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
@vite(['resources/css/appointments-calendar.css'])
@endpush

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Appointment Calendar</h3>
                <p class="text-sm text-gray-600">Click on a date to schedule new appointment or click on existing appointments to edit</p>
            </div>
            <div class="flex space-x-4">
                <select id="doctor-filter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                    <option value="">All Doctors</option>
                    @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }} - {{ $doctor->specialization }}</option>
                    @endforeach
                </select>
                <button onclick="openAppointmentModal()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    New Appointment
                </button>
            </div>
        </div>
    </div>

    <div class="p-6">
        <div id="calendar"></div>
    </div>
</div>

<!-- Appointment Modal -->
<div id="appointmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800" id="modal-title">Schedule Appointment</h3>
                    <button onclick="closeAppointmentModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <form id="appointmentForm" class="p-6">
                @csrf
                <input type="hidden" id="appointment_id" name="appointment_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                        <select name="patient_id" id="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                            <option value="">Select Patient</option>
                            @foreach($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->patient_no }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Doctor *</label>
                        <select name="doctor_id" id="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }} - {{ $doctor->specialization }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="status-field" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no_show">No Show</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Date & Time *</label>
                        <input type="text" name="appointment_datetime" id="appointment_datetime" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="YYYY-MM-DD HH:MM" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Visit</label>
                        <textarea name="reason" id="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" id="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-6 sticky bottom-0 bg-white pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeAppointmentModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i><span id="submit-text">Schedule Appointment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
@vite(['resources/js/appointments-calendar.js'])
@endpush
@endsection
