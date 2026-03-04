@extends('admin.layout')

@section('title', 'Dashboard - Hospityo')
@section('page-title', 'Dashboard')
@section('page-description', 'Hospityo Overview')

@section('content')
@if(auth()->user()->hasRole('Doctor') && isset($assignedPatients))
<!-- Doctor Dashboard -->
<div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 md:p-6 mb-4 md:mb-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <h3 class="text-base sm:text-lg font-semibold text-gray-800">Assigned Patients</h3>
        @if($totalAssigned > 5)
            <a href="{{ route('doctor.assignments') }}" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 text-sm text-center min-h-[44px] flex items-center justify-center">
                View All ({{ $totalAssigned }})
            </a>
        @endif
    </div>
    
    @if($assignedPatients->count() > 0)
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Type</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Time</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($assignedPatients as $visit)
                    <tr>
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $visit->patient->name }}</div>
                                <div class="text-sm text-gray-500">{{ $visit->patient->patient_no }}</div>
                            </div>
                        </td>
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($visit->visit_type === 'opd') bg-blue-100 text-blue-800
                                @elseif($visit->visit_type === 'ipd') bg-purple-100 text-purple-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ strtoupper($visit->visit_type) }}
                            </span>
                        </td>
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                            </span>
                        </td>
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $visit->visit_datetime->format('M d, Y H:i') }}
                        </td>
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2">
                                <a href="{{ route('visits.workflow', $visit) }}" class="text-medical-blue hover:text-blue-700">
                                    <i class="fas fa-stethoscope"></i> Consult
                                </a>
                                <form action="{{ route('visits.check-patient', $visit) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-700" 
                                            onclick="return confirm('Mark this patient as checked?')">
                                        <i class="fas fa-check"></i> Check
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Card View -->
        <div class="md:hidden space-y-3">
            @foreach($assignedPatients as $visit)
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <div class="flex justify-between items-start gap-2 mb-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-900 truncate">{{ $visit->patient->name }}</div>
                        <div class="text-xs text-gray-500">{{ $visit->patient->patient_no }}</div>
                    </div>
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full flex-shrink-0
                        @if($visit->visit_type === 'opd') bg-blue-100 text-blue-800
                        @elseif($visit->visit_type === 'ipd') bg-purple-100 text-purple-800
                        @else bg-red-100 text-red-800 @endif">
                        {{ strtoupper($visit->visit_type) }}
                    </span>
                </div>
                
                <div class="space-y-2 text-xs sm:text-sm mb-3">
                    <div class="flex justify-between gap-2">
                        <span class="text-gray-500">Status:</span>
                        <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                        </span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-gray-500">Visit Time:</span>
                        <span class="text-gray-900 text-right">{{ $visit->visit_datetime->format('M d, Y H:i') }}</span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <a href="{{ route('visits.workflow', $visit) }}" class="flex-1 bg-medical-blue text-white px-3 py-2.5 rounded-lg hover:bg-blue-700 text-center text-sm min-h-[44px] flex items-center justify-center">
                        <i class="fas fa-stethoscope mr-1"></i> Consult
                    </a>
                    <form action="{{ route('visits.check-patient', $visit) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full bg-green-600 text-white px-3 py-2.5 rounded-lg hover:bg-green-700 text-sm min-h-[44px]" 
                                onclick="return confirm('Mark this patient as checked?')">
                            <i class="fas fa-check mr-1"></i> Check
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8">
            <i class="fas fa-user-friends text-gray-400 text-4xl mb-4"></i>
            <p class="text-gray-500 text-sm">No patients assigned yet</p>
        </div>
    @endif
</div>
@endif

<div class="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6 mb-4 md:mb-8">
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5 md:p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-medical-blue rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-injured text-white text-lg sm:text-xl"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs sm:text-sm text-gray-600 truncate">Total Patients</p>
                <p class="text-xl sm:text-2xl font-semibold text-gray-800">{{ \App\Models\Patient::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5 md:p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-medical-green rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-md text-white text-lg sm:text-xl"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs sm:text-sm text-gray-600 truncate">Doctors</p>
                <p class="text-xl sm:text-2xl font-semibold text-gray-800">{{ \App\Models\Doctor::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5 md:p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-building text-white text-lg sm:text-xl"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs sm:text-sm text-gray-600 truncate">Departments</p>
                <p class="text-xl sm:text-2xl font-semibold text-gray-800">{{ \App\Models\Department::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5 md:p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clipboard-list text-white text-lg sm:text-xl"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs sm:text-sm text-gray-600 truncate">Visits</p>
                <p class="text-xl sm:text-2xl font-semibold text-gray-800">{{ \App\Models\Visit::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5 md:p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-check text-white text-lg sm:text-xl"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs sm:text-sm text-gray-600 truncate">Appointments</p>
                <p class="text-xl sm:text-2xl font-semibold text-gray-800">{{ \App\Models\Appointment::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5 md:p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-red-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-heartbeat text-white text-lg sm:text-xl"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs sm:text-sm text-gray-600 truncate">Emergency Cases</p>
                <p class="text-xl sm:text-2xl font-semibold text-gray-800">0</p>
            </div>
        </div>
    </div>
</div>

@if(!auth()->user()->hasRole('Doctor'))
<div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 md:p-6">
    <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
    
    <!-- Patient Search -->
    <div class="mb-6">
        <div class="relative">
            <input type="text" 
                   id="patient-search" 
                   placeholder="Search patient by phone number..." 
                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm sm:text-base">
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
            <div id="patient-found" class="hidden bg-green-50 border border-green-200 rounded-lg p-3 sm:p-4">
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-user-check text-green-600 flex-shrink-0"></i>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-green-800 text-sm sm:text-base truncate" id="patient-name"></p>
                        <p class="text-xs sm:text-sm text-green-600 truncate" id="patient-details"></p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <a href="#" id="add-visit-btn" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 text-sm text-center min-h-[44px] flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i>Add Visit
                    </a>
                    <a href="#" id="schedule-appointment-btn" class="bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm text-center min-h-[44px] flex items-center justify-center">
                        <i class="fas fa-calendar-plus mr-2"></i>Schedule Appointment
                    </a>
                    <a href="#" id="view-history-btn" class="bg-purple-600 text-white px-4 py-2.5 rounded-lg hover:bg-purple-700 text-sm text-center min-h-[44px] flex items-center justify-center">
                        <i class="fas fa-history mr-2"></i>View History
                    </a>
                </div>
            </div>
            
            <!-- Patient Not Found -->
            <div id="patient-not-found" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-3 sm:p-4">
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-user-plus text-yellow-600 flex-shrink-0"></i>
                    <div>
                        <p class="font-medium text-yellow-800 text-sm sm:text-base">Patient not found</p>
                        <p class="text-xs sm:text-sm text-yellow-600">No patient found with this phone number</p>
                    </div>
                </div>
                <a href="{{ route('patients.create') }}" id="add-patient-btn" class="bg-yellow-600 text-white px-4 py-2.5 rounded-lg hover:bg-yellow-700 text-sm inline-flex items-center justify-center min-h-[44px]">
                    <i class="fas fa-user-plus mr-2"></i>Add New Patient
                </a>
            </div>
        </div>
    </div>
    
    <!-- Default Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        <a href="{{ route('patients.create') }}" class="flex items-center p-3 sm:p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors min-h-[72px]">
            <i class="fas fa-plus-circle text-medical-blue text-2xl mr-3 flex-shrink-0"></i>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-800 text-sm sm:text-base truncate">Add New Patient</p>
                <p class="text-xs sm:text-sm text-gray-600 truncate">Register a new patient</p>
            </div>
        </a>
        
        <a href="{{ route('patients.index') }}" class="flex items-center p-3 sm:p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors min-h-[72px]">
            <i class="fas fa-list text-medical-green text-2xl mr-3 flex-shrink-0"></i>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-800 text-sm sm:text-base truncate">View All Patients</p>
                <p class="text-xs sm:text-sm text-gray-600 truncate">Manage patient records</p>
            </div>
        </a>
        
        <a href="{{ route('appointments.create') }}" class="flex items-center p-3 sm:p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors min-h-[72px]">
            <i class="fas fa-calendar-plus text-yellow-500 text-2xl mr-3 flex-shrink-0"></i>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-800 text-sm sm:text-base truncate">Schedule Appointment</p>
                <p class="text-xs sm:text-sm text-gray-600 truncate">Book new appointment</p>
            </div>
        </a>
    </div>
</div>
@endif
@endsection

@if(!auth()->user()->hasRole('Doctor'))
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
        const viewHistoryBtn = document.getElementById('view-history-btn');
        
        if (addVisitBtn) {
            addVisitBtn.href = `{{ route('visits.create') }}?patient_id=${patient.id}`;
        }
        if (scheduleAppointmentBtn) {
            scheduleAppointmentBtn.href = `{{ route('appointments.create') }}?patient_id=${patient.id}`;
        }
        if (viewHistoryBtn) {
            viewHistoryBtn.href = `/patients/${patient.id}/history`;
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
@endif

@section('title', 'Dashboard - Hospityo')
@section('page-title', 'Dashboard')
@section('page-description', 'Hospityo Overview')

@section('content')
@if(auth()->user()->hasRole('Doctor') && isset($assignedPatients))
<!-- Doctor Dashboard -->
<div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-3">
        <h3 class="text-lg font-semibold text-gray-800">Assigned Patients</h3>
        @if($totalAssigned > 5)
            <a href="{{ route('doctor.assignments') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm text-center btn-touch">
                View All ({{ $totalAssigned }})
            </a>
        @endif
    </div>
    
    @if($assignedPatients->count() > 0)
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($assignedPatients as $visit)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $visit->patient->name }}</div>
                                <div class="text-sm text-gray-500">{{ $visit->patient->patient_no }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($visit->visit_type === 'opd') bg-blue-100 text-blue-800
                                @elseif($visit->visit_type === 'ipd') bg-purple-100 text-purple-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ strtoupper($visit->visit_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $visit->visit_datetime->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('visits.workflow', $visit) }}" class="text-medical-blue hover:text-blue-700">
                                    <i class="fas fa-stethoscope"></i> Consult
                                </a>
                                <form action="{{ route('visits.check-patient', $visit) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-700 ml-2" 
                                            onclick="return confirm('Mark this patient as checked?')">
                                        <i class="fas fa-check"></i> Check
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Card View -->
        <div class="md:hidden space-y-4">
            @foreach($assignedPatients as $visit)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="text-sm font-medium text-gray-900">{{ $visit->patient->name }}</div>
                        <div class="text-xs text-gray-500">{{ $visit->patient->patient_no }}</div>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        @if($visit->visit_type === 'opd') bg-blue-100 text-blue-800
                        @elseif($visit->visit_type === 'ipd') bg-purple-100 text-purple-800
                        @else bg-red-100 text-red-800 @endif">
                        {{ strtoupper($visit->visit_type) }}
                    </span>
                </div>
                
                <div class="space-y-2 text-sm mb-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status:</span>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Visit Time:</span>
                        <span class="text-gray-900">{{ $visit->visit_datetime->format('M d, Y H:i') }}</span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <a href="{{ route('visits.workflow', $visit) }}" class="flex-1 bg-medical-blue text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-center text-sm btn-touch">
                        <i class="fas fa-stethoscope mr-1"></i> Consult
                    </a>
                    <form action="{{ route('visits.check-patient', $visit) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 text-sm btn-touch" 
                                onclick="return confirm('Mark this patient as checked?')">
                            <i class="fas fa-check mr-1"></i> Check
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8">
            <i class="fas fa-user-friends text-gray-400 text-4xl mb-4"></i>
            <p class="text-gray-500">No patients assigned yet</p>
        </div>
    @endif
</div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
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

@if(!auth()->user()->hasRole('Doctor'))
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
                    <a href="#" id="view-history-btn" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm">
                        <i class="fas fa-history mr-2"></i>View History
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('patients.create') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors btn-touch">
            <i class="fas fa-plus-circle text-medical-blue text-2xl mr-3 flex-shrink-0"></i>
            <div>
                <p class="font-medium text-gray-800">Add New Patient</p>
                <p class="text-sm text-gray-600">Register a new patient</p>
            </div>
        </a>
        
        <a href="{{ route('patients.index') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors btn-touch">
            <i class="fas fa-list text-medical-green text-2xl mr-3 flex-shrink-0"></i>
            <div>
                <p class="font-medium text-gray-800">View All Patients</p>
                <p class="text-sm text-gray-600">Manage patient records</p>
            </div>
        </a>
        
        <a href="{{ route('appointments.create') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors btn-touch">
            <i class="fas fa-calendar-plus text-yellow-500 text-2xl mr-3 flex-shrink-0"></i>
            <div>
                <p class="font-medium text-gray-800">Schedule Appointment</p>
                <p class="text-sm text-gray-600">Book new appointment</p>
            </div>
        </a>
    </div>
</div>
@endif
@endsection

@if(!auth()->user()->hasRole('Doctor'))
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
        const viewHistoryBtn = document.getElementById('view-history-btn');
        
        if (addVisitBtn) {
            addVisitBtn.href = `{{ route('visits.create') }}?patient_id=${patient.id}`;
        }
        if (scheduleAppointmentBtn) {
            scheduleAppointmentBtn.href = `{{ route('appointments.create') }}?patient_id=${patient.id}`;
        }
        if (viewHistoryBtn) {
            viewHistoryBtn.href = `/patients/${patient.id}/history`;
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
@endif