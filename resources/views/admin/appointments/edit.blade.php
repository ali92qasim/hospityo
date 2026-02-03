@extends('admin.layout')

@section('title', 'Edit Appointment - Hospital Management System')
@section('page-title', 'Edit Appointment')
@section('page-description', 'Update appointment information')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Edit Appointment: {{ $appointment->appointment_no }}</h3>
                <p class="text-sm text-gray-600">Click on the appointment or date to modify details</p>
            </div>
            <div class="flex space-x-4">
                <select id="doctor-filter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                    <option value="">All Doctors</option>
                    @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }} - {{ $doctor->specialization }}</option>
                    @endforeach
                </select>
                <a href="{{ route('appointments.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to List
                </a>
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
                    <h3 class="text-lg font-semibold text-gray-800" id="modal-title">Edit Appointment</h3>
                    <button onclick="closeAppointmentModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <form id="appointmentForm" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" id="appointment_id" name="appointment_id" value="{{ $appointment->id }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Number</label>
                        <input type="text" value="{{ $appointment->appointment_no }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                            <option value="scheduled" {{ $appointment->status == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="completed" {{ $appointment->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $appointment->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="no_show" {{ $appointment->status == 'no_show' ? 'selected' : '' }}>No Show</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                        <select name="patient_id" id="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                            <option value="">Select Patient</option>
                            @foreach($patients as $patient)
                            <option value="{{ $patient->id }}" {{ $appointment->patient_id == $patient->id ? 'selected' : '' }}>
                                {{ $patient->name }} ({{ $patient->patient_no }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Doctor *</label>
                        <select name="doctor_id" id="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ $appointment->doctor_id == $doctor->id ? 'selected' : '' }}>
                                Dr. {{ $doctor->name }} - {{ $doctor->specialization }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Date & Time *</label>
                        <input type="text" name="appointment_datetime" id="appointment_datetime" 
                               value="{{ $appointment->appointment_datetime->format('Y-m-d H:i') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" 
                               placeholder="Select date and time" required readonly>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Visit</label>
                        <textarea name="reason" id="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ $appointment->reason }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" id="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ $appointment->notes }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-6 sticky bottom-0 bg-white pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeAppointmentModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Update Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* Select2 custom styling */
.select2-container {
    width: 100% !important;
    box-sizing: border-box;
}
.select2-container--default .select2-selection--single {
    height: 42px;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    box-sizing: border-box;
    overflow: hidden;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px;
    padding-left: 12px;
    padding-right: 50px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.select2-container--default .select2-selection--single .select2-selection__clear {
    position: absolute;
    right: 28px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 16px;
    line-height: 1;
    width: 16px;
    height: 16px;
    text-align: center;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #6b7280 transparent transparent transparent;
    border-style: solid;
    border-width: 5px 4px 0 4px;
    height: 0;
    left: 50%;
    margin-left: -4px;
    margin-top: -2px;
    position: absolute;
    top: 50%;
    width: 0;
}
.select2-dropdown {
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #0066CC;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #0066CC;
    box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.2);
    outline: none;
}
.select2-container * {
    box-sizing: border-box;
}
/* Flatpickr custom styling */
.flatpickr-calendar {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}
.flatpickr-day.selected {
    background: #0066CC;
    border-color: #0066CC;
}
.flatpickr-day:hover {
    background: #e5f3ff;
}
.flatpickr-time input:hover {
    background: #f8fafc;
}
/* Calendar event hover effects */
.fc-event {
    cursor: pointer !important;
    transition: all 0.2s ease;
}
.fc-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}
/* Calendar date hover */
.fc-daygrid-day:hover {
    cursor: pointer;
}
/* Modal overflow fix */
#appointmentModal {
    overflow-y: auto;
}
#appointmentModal .bg-white {
    max-height: 90vh;
    overflow-y: auto;
}
</style>
<script src="{{ asset('js/appointments/edit-calendar.js') }}"></script>
@endsection