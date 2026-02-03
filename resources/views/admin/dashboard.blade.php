@extends('admin.layout')

@section('title', 'Dashboard - Hospityo')
@section('page-title', 'Dashboard')
@section('page-description', 'Hospityo Overview')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-medical-blue rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-user-injured text-white text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Patients</p>
                <p class="text-2xl font-semibold text-gray-800">{{ \App\Models\Patient::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-medical-green rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-user-md text-white text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Doctors</p>
                <p class="text-2xl font-semibold text-gray-800">{{ \App\Models\Doctor::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-building text-white text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Departments</p>
                <p class="text-2xl font-semibold text-gray-800">{{ \App\Models\Department::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-clipboard-list text-white text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Visits</p>
                <p class="text-2xl font-semibold text-gray-800">{{ \App\Models\Visit::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-calendar-check text-white text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Appointments</p>
                <p class="text-2xl font-semibold text-gray-800">{{ \App\Models\Appointment::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-heartbeat text-white text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Emergency Cases</p>
                <p class="text-2xl font-semibold text-gray-800">0</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
    
    <!-- Patient Search -->
    <div class="mb-6">
        <div class="relative">
            <input type="text" 
                   id="patient-search" 
                   placeholder="Search patient by phone number..." 
                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <div id="search-loading" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                <i class="fas fa-spinner fa-spin text-gray-400"></i>
            </div>
        </div>
        
        <!-- Search Results -->
        <div id="search-results" class="mt-3 hidden">
            <!-- Patient Found -->
            <div id="patient-found" class="hidden bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <i class="fas fa-user-check text-green-600 mr-3"></i>
                        <div>
                            <p class="font-medium text-green-800" id="patient-name"></p>
                            <p class="text-sm text-green-600" id="patient-details"></p>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <a href="#" id="add-visit-btn" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fas fa-plus mr-2"></i>Add Visit
                    </a>
                    <a href="#" id="schedule-appointment-btn" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                        <i class="fas fa-calendar-plus mr-2"></i>Schedule Appointment
                    </a>
                </div>
            </div>
            
            <!-- Patient Not Found -->
            <div id="patient-not-found" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <i class="fas fa-user-plus text-yellow-600 mr-3"></i>
                        <div>
                            <p class="font-medium text-yellow-800">Patient not found</p>
                            <p class="text-sm text-yellow-600">No patient found with this phone number</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('patients.create') }}" id="add-patient-btn" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 text-sm">
                    <i class="fas fa-user-plus mr-2"></i>Add New Patient
                </a>
            </div>
        </div>
    </div>
    
    <!-- Default Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('patients.create') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-plus-circle text-medical-blue text-2xl mr-3"></i>
            <div>
                <p class="font-medium text-gray-800">Add New Patient</p>
                <p class="text-sm text-gray-600">Register a new patient</p>
            </div>
        </a>
        
        <a href="{{ route('patients.index') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-list text-medical-green text-2xl mr-3"></i>
            <div>
                <p class="font-medium text-gray-800">View All Patients</p>
                <p class="text-sm text-gray-600">Manage patient records</p>
            </div>
        </a>
        
        <a href="{{ route('appointments.create') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-calendar-plus text-yellow-500 text-2xl mr-3"></i>
            <div>
                <p class="font-medium text-gray-800">Schedule Appointment</p>
                <p class="text-sm text-gray-600">Book new appointment</p>
            </div>
        </a>
    </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('patient-search');
    const searchResults = document.getElementById('search-results');
    const searchLoading = document.getElementById('search-loading');
    const patientFound = document.getElementById('patient-found');
    const patientNotFound = document.getElementById('patient-not-found');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        const phone = this.value.trim();
        
        if (phone.length < 3) {
            hideResults();
            return;
        }
        
        // Show loading
        searchLoading.classList.remove('hidden');
        hideResults(); // Hide previous results
        
        // Debounce search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchPatient(phone);
        }, 500);
    });
    
    function searchPatient(phone) {
        fetch(`/api/patients/search?phone=${encodeURIComponent(phone)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            searchLoading.classList.add('hidden');
            
            if (data.found) {
                showPatientFound(data.patient);
            } else {
                showPatientNotFound();
            }
        })
        .catch(error => {
            searchLoading.classList.add('hidden');
            console.error('Search error:', error);
            showPatientNotFound();
        });
    }
    
    function showPatientFound(patient) {
        document.getElementById('patient-name').textContent = patient.name;
        document.getElementById('patient-details').textContent = `${patient.patient_no} • ${patient.phone}`;
        
        // Update action links with proper URLs
        const addVisitBtn = document.getElementById('add-visit-btn');
        const scheduleAppointmentBtn = document.getElementById('schedule-appointment-btn');
        
        if (addVisitBtn) {
            addVisitBtn.href = `{{ route('visits.create') }}?patient_id=${patient.id}`;
        }
        if (scheduleAppointmentBtn) {
            scheduleAppointmentBtn.href = `{{ route('appointments.create') }}?patient_id=${patient.id}`;
        }
        
        patientFound.classList.remove('hidden');
        patientNotFound.classList.add('hidden');
        searchResults.classList.remove('hidden');
    }
    
    function showPatientNotFound() {
        const phone = searchInput.value.trim();
        const addPatientBtn = document.getElementById('add-patient-btn');
        
        if (addPatientBtn) {
            addPatientBtn.href = `{{ route('patients.create') }}?phone=${encodeURIComponent(phone)}`;
        }
        
        patientFound.classList.add('hidden');
        patientNotFound.classList.remove('hidden');
        searchResults.classList.remove('hidden');
    }
    
    function hideResults() {
        searchResults.classList.add('hidden');
        searchLoading.classList.add('hidden');
    }
});
</script>