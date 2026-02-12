@extends('admin.layout')

@section('title', 'Visit Workflow - Hospital Management System')
@section('page-title', 'Visit Workflow')
@section('page-description', 'Manage patient visit workflow')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Visit Header -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-medical-blue rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-clipboard-list text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">{{ $visit->visit_no }}</h3>
                        <p class="text-sm text-gray-600">{{ $visit->patient->name }} • {{ strtoupper($visit->visit_type) }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @php
                        $statusColors = [
                            'registered' => 'bg-blue-100 text-blue-800',
                            'triaged' => 'bg-red-100 text-red-800',
                            'vitals_recorded' => 'bg-green-100 text-green-800',
                            'admitted' => 'bg-purple-100 text-purple-800',
                            'with_doctor' => 'bg-indigo-100 text-indigo-800',
                            'discharged' => 'bg-orange-100 text-orange-800',
                            'completed' => 'bg-gray-100 text-gray-800'
                        ];
                    @endphp
                    <span class="px-3 py-1 text-sm rounded-full {{ $statusColors[$visit->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                    </span>
                    <a href="{{ route('visits.print', $visit) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-medical-blue text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-print mr-2"></i>Print Report
                    </a>
                    <a href="{{ route('visits.index') }}" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Visits
                    </a>
                </div>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="p-6">
            <div class="flex items-center justify-between">
                @php
                    $steps = match($visit->visit_type) {
                        'opd' => [
                            'registered' => 'Registration',
                            'vitals_recorded' => 'Vital Signs',
                            'with_doctor' => 'Consultation',
                            'completed' => 'Completed'
                        ],
                        'ipd' => [
                            'registered' => 'Registration',
                            'vitals_recorded' => 'Vital Signs',
                            'admitted' => 'Admitted',
                            'with_doctor' => 'Treatment',
                            'discharged' => 'Discharged'
                        ],
                        'emergency' => [
                            'registered' => 'Registration',
                            'triaged' => 'Triaged',
                            'vitals_recorded' => 'Vital Signs',
                            'with_doctor' => 'Emergency Care',
                            'completed' => 'Completed'
                        ]
                    };
                    $currentStep = array_search($visit->status, array_keys($steps));
                @endphp
                @foreach($steps as $status => $label)
                    @php
                        $stepIndex = array_search($status, array_keys($steps));
                        $isCompleted = $stepIndex <= $currentStep;
                        $isCurrent = $status === $visit->status;
                    @endphp
                    <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $isCompleted ? 'bg-medical-blue text-white' : 'bg-gray-200 text-gray-500' }}">
                                @if($isCompleted && !$isCurrent)
                                    <i class="fas fa-check text-xs"></i>
                                @else
                                    {{ $stepIndex + 1 }}
                                @endif
                            </div>
                            <span class="ml-2 text-sm {{ $isCurrent ? 'font-medium text-medical-blue' : ($isCompleted ? 'text-gray-700' : 'text-gray-500') }}">
                                {{ $label }}
                            </span>
                        </div>
                        @if(!$loop->last)
                            <div class="flex-1 h-0.5 mx-4 {{ $stepIndex < $currentStep ? 'bg-medical-blue' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Workflow Tabs -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                @if($visit->visit_type === 'emergency')
                    <button onclick="showTab('triage')" id="triage-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-medical-blue text-medical-blue">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Triage
                    </button>
                @endif
                @if($visit->visit_type === 'ipd')
                    <button onclick="showTab('admission')" id="admission-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm {{ $visit->visit_type === 'ipd' ? 'border-medical-blue text-medical-blue' : 'border-transparent text-gray-500' }}">
                        <i class="fas fa-bed mr-2"></i>Admission
                    </button>
                @endif
                <button onclick="showTab('vitals')" id="vitals-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm {{ $visit->visit_type !== 'emergency' && $visit->visit_type !== 'ipd' ? 'border-medical-blue text-medical-blue' : 'border-transparent text-gray-500' }}">
                    <i class="fas fa-heartbeat mr-2"></i>Vital Signs
                </button>
                <button onclick="showTab('consultation')" id="consultation-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-stethoscope mr-2"></i>{{ $visit->visit_type === 'emergency' ? 'Emergency Care' : 'Consultation' }}
                </button>
                <button onclick="showTab('prescription')" id="prescription-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-prescription mr-2"></i>Prescription
                </button>
                @if($visit->visit_type !== 'emergency')
                    <button onclick="showTab('tests')" id="tests-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
                        <i class="fas fa-flask mr-2"></i>Tests & Results
                    </button>
                @endif
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Emergency Triage Tab -->
            @if($visit->visit_type === 'emergency')
            <div id="triage-content" class="tab-content">
                @if(!$visit->triage)
                    <form action="{{ route('visits.triage', $visit) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Priority Level</label>
                                <select name="priority_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                    <option value="">Select Priority</option>
                                    <option value="critical" class="text-red-600">Critical - Immediate</option>
                                    <option value="urgent" class="text-orange-600">Urgent - 15 mins</option>
                                    <option value="less_urgent" class="text-yellow-600">Less Urgent - 60 mins</option>
                                    <option value="non_urgent" class="text-green-600">Non-Urgent - 120 mins</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pain Scale (0-10)</label>
                                <input type="number" name="pain_scale" min="0" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Chief Complaint</label>
                                <input type="text" name="chief_complaint" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Triage Notes</label>
                                <textarea name="triage_notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="mt-4 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Complete Triage
                        </button>
                    </form>
                @else
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <h4 class="text-lg font-medium text-red-800 mb-4">Triage Completed</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-red-600">Priority:</span>
                                <span class="ml-2 px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">{{ ucfirst(str_replace('_', ' ', $visit->triage->priority_level)) }}</span>
                            </div>
                            <div>
                                <span class="text-sm text-red-600">Pain Scale:</span>
                                <span class="ml-2 font-medium">{{ $visit->triage->pain_scale ?? 'N/A' }}/10</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-sm text-red-600">Chief Complaint:</span>
                                <p class="mt-1">{{ $visit->triage->chief_complaint }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            @endif

            <!-- IPD Admission Tab -->
            @if($visit->visit_type === 'ipd')
            <div id="admission-content" class="tab-content {{ $visit->visit_type === 'ipd' ? '' : 'hidden' }}">
                @if(!$visit->admission)
                    <div class="space-y-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-4">Select Bed for Admission</h4>
                        
                        <form action="{{ route('visits.admit', $visit) }}" method="POST" id="admission-form">
                            @csrf
                            <input type="hidden" name="bed_id" id="selected-bed-id">
                            
                            <!-- Ward Filter -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Ward</label>
                                <select id="ward-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                    <option value="">All Wards</option>
                                    @foreach($availableBeds->groupBy('ward.name') as $wardName => $beds)
                                        <option value="{{ $wardName }}">{{ $wardName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Bed Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6" id="bed-grid">
                                @foreach($availableBeds ?? [] as $bed)
                                    <div class="bed-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-medical-blue transition-colors" 
                                         data-bed-id="{{ $bed->id }}" 
                                         data-ward="{{ $bed->ward->name }}">
                                        <div class="text-center">
                                            <div class="w-12 h-12 mx-auto mb-2 bg-green-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-bed text-green-600 text-lg"></i>
                                            </div>
                                            <div class="font-medium text-gray-800">{{ $bed->bed_number }}</div>
                                            <div class="text-xs text-gray-500 mb-1">{{ $bed->ward->name }}</div>
                                            <div class="text-xs font-medium text-medical-blue">{{ ucfirst($bed->bed_type) }}</div>
                                            <div class="text-xs text-gray-600 mt-1">₨{{ number_format($bed->daily_rate, 0) }}/day</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <!-- Selected Bed Info -->
                            <div id="selected-bed-info" class="hidden bg-medical-light border border-medical-blue rounded-lg p-4 mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-bed text-medical-blue mr-2"></i>
                                    <span class="font-medium text-medical-blue">Selected: </span>
                                    <span id="selected-bed-details" class="ml-2"></span>
                                </div>
                            </div>
                            
                            <!-- Admission Notes -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Admission Notes</label>
                                <textarea name="admission_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter admission notes..."></textarea>
                            </div>
                            
                            <button type="submit" id="admit-btn" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <i class="fas fa-bed mr-2"></i>Admit Patient
                            </button>
                        </form>
                    </div>
                    
                    <script>
                        // Bed selection functionality
                        document.addEventListener('DOMContentLoaded', function() {
                            const bedCards = document.querySelectorAll('.bed-card');
                            const selectedBedId = document.getElementById('selected-bed-id');
                            const selectedBedInfo = document.getElementById('selected-bed-info');
                            const selectedBedDetails = document.getElementById('selected-bed-details');
                            const admitBtn = document.getElementById('admit-btn');
                            const wardFilter = document.getElementById('ward-filter');
                            
                            // Bed selection
                            bedCards.forEach(card => {
                                card.addEventListener('click', function() {
                                    // Remove previous selection
                                    bedCards.forEach(c => {
                                        c.classList.remove('border-medical-blue', 'bg-medical-light');
                                        c.classList.add('border-gray-200');
                                    });
                                    
                                    // Select current bed
                                    this.classList.remove('border-gray-200');
                                    this.classList.add('border-medical-blue', 'bg-medical-light');
                                    
                                    // Update form
                                    const bedId = this.dataset.bedId;
                                    const bedNumber = this.querySelector('.font-medium').textContent;
                                    const wardName = this.dataset.ward;
                                    const bedType = this.querySelector('.text-medical-blue').textContent;
                                    const dailyRate = this.querySelector('.text-gray-600').textContent;
                                    
                                    selectedBedId.value = bedId;
                                    selectedBedDetails.textContent = `${bedNumber} - ${wardName} (${bedType}) - ${dailyRate}`;
                                    selectedBedInfo.classList.remove('hidden');
                                    admitBtn.disabled = false;
                                });
                            });
                            
                            // Ward filter
                            wardFilter.addEventListener('change', function() {
                                const selectedWard = this.value;
                                
                                bedCards.forEach(card => {
                                    if (selectedWard === '' || card.dataset.ward === selectedWard) {
                                        card.style.display = 'block';
                                    } else {
                                        card.style.display = 'none';
                                    }
                                });
                            });
                        });
                    </script>
                @else
                    <div class="space-y-6">
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                            <h4 class="text-lg font-medium text-purple-800 mb-4">Patient Admitted</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-purple-600">Ward:</span>
                                    <span class="ml-2 font-medium">{{ $visit->admission->bed->ward->name }}</span>
                                </div>
                                <div>
                                    <span class="text-sm text-purple-600">Bed:</span>
                                    <span class="ml-2 font-medium">{{ $visit->admission->bed->bed_number }}</span>
                                </div>
                                <div>
                                    <span class="text-sm text-purple-600">Admitted:</span>
                                    <span class="ml-2">{{ $visit->admission->admission_date->format('M d, Y h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        @if($visit->admission->status === 'active')
                        <form action="{{ route('visits.discharge', $visit) }}" method="POST">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Discharge Summary</label>
                                    <textarea name="discharge_summary" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Discharge Notes</label>
                                    <textarea name="discharge_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue"></textarea>
                                </div>
                                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Discharge Patient
                                </button>
                            </div>
                        </form>
                        @endif
                    </div>
                @endif
            </div>
            @endif

            <!-- Vital Signs Tab -->
            <div id="vitals-content" class="tab-content {{ $visit->visit_type === 'opd' ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-lg font-medium text-gray-800 mb-4">{{ $visit->visit_type === 'ipd' ? 'Record New Vital Signs' : 'Record Vital Signs' }}</h4>
                        <form action="{{ route('visits.vitals', $visit) }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Blood Pressure</label>
                                    <input type="text" name="blood_pressure" value="{{ $visit->visit_type === 'ipd' ? '' : old('blood_pressure', $visit->vitalSigns?->blood_pressure) }}" 
                                           placeholder="120/80" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Temperature (°F)</label>
                                    <input type="number" name="temperature" value="{{ $visit->visit_type === 'ipd' ? '' : old('temperature', $visit->vitalSigns?->temperature) }}" 
                                           step="0.1" placeholder="98.6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Pulse Rate (bpm)</label>
                                    <input type="number" name="pulse_rate" value="{{ $visit->visit_type === 'ipd' ? '' : old('pulse_rate', $visit->vitalSigns?->pulse_rate) }}" 
                                           placeholder="72" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Respiratory Rate</label>
                                    <input type="number" name="respiratory_rate" value="{{ $visit->visit_type === 'ipd' ? '' : old('respiratory_rate', $visit->vitalSigns?->respiratory_rate) }}" 
                                           placeholder="16" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                                    <input type="number" name="weight" value="{{ $visit->visit_type === 'ipd' ? '' : old('weight', $visit->vitalSigns?->weight) }}" 
                                           step="0.1" placeholder="70.5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Height (cm)</label>
                                    <input type="number" name="height" value="{{ $visit->visit_type === 'ipd' ? '' : old('height', $visit->vitalSigns?->height) }}" 
                                           step="0.1" placeholder="175" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ $visit->visit_type === 'ipd' ? '' : old('notes', $visit->vitalSigns?->notes) }}</textarea>
                            </div>
                            <button type="submit" class="mt-4 bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-save mr-2"></i>{{ $visit->visit_type === 'ipd' ? 'Add Vital Signs' : 'Save Vital Signs' }}
                            </button>
                        </form>
                    </div>

                    <div>
                        @if($visit->visit_type === 'ipd')
                            <!-- Doctor Assignment for IPD -->
                            <h4 class="text-lg font-medium text-gray-800 mb-4">Doctor Assignment</h4>
                            @if(!$visit->doctor_id)
                                <form action="{{ route('visits.assign-doctor', $visit) }}" method="POST" class="mb-6">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Assign Doctor</label>
                                        <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                            <option value="">Select Doctor</option>
                                            @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }} - {{ $doctor->specialization }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                        <i class="fas fa-user-md mr-2"></i>Assign Doctor
                                    </button>
                                </form>
                            @else
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                        <div>
                                            <p class="font-medium text-green-800">Dr. {{ $visit->doctor->name }}</p>
                                            <p class="text-sm text-green-600">{{ $visit->doctor->specialization }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Vital Signs History -->
                            <h4 class="text-lg font-medium text-gray-800 mb-4">Vital Signs History</h4>
                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                @forelse($visit->allVitalSigns as $vital)
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div class="flex justify-between items-start mb-3">
                                            <h5 class="font-medium text-blue-800">{{ $vital->created_at->format('M d, Y h:i A') }}</h5>
                                            <span class="text-xs text-blue-600">{{ $vital->user?->name ?? 'Unknown' }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            @if($vital->blood_pressure)
                                                <div><span class="text-blue-600">BP:</span> {{ $vital->blood_pressure }}</div>
                                            @endif
                                            @if($vital->temperature)
                                                <div><span class="text-blue-600">Temp:</span> {{ $vital->temperature }}°F</div>
                                            @endif
                                            @if($vital->pulse_rate)
                                                <div><span class="text-blue-600">Pulse:</span> {{ $vital->pulse_rate }} bpm</div>
                                            @endif
                                            @if($vital->respiratory_rate)
                                                <div><span class="text-blue-600">Resp:</span> {{ $vital->respiratory_rate }}</div>
                                            @endif
                                            @if($vital->weight)
                                                <div><span class="text-blue-600">Weight:</span> {{ $vital->weight }} kg</div>
                                            @endif
                                            @if($vital->height)
                                                <div><span class="text-blue-600">Height:</span> {{ $vital->height }} cm</div>
                                            @endif
                                        </div>
                                        @if($vital->notes)
                                            <div class="mt-2 pt-2 border-t border-blue-200">
                                                <p class="text-sm text-blue-700">{{ $vital->notes }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-gray-500 text-center py-4">No vital signs recorded yet.</p>
                                @endforelse
                            </div>
                        @else
                            <h4 class="text-lg font-medium text-gray-800 mb-4">Doctor Assignment</h4>
                            @if(!$visit->doctor_id)
                                <form action="{{ route('visits.assign-doctor', $visit) }}" method="POST">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Assign Doctor</label>
                                        <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                            <option value="">Select Doctor</option>
                                            @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }} - {{ $doctor->specialization }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                        <i class="fas fa-user-md mr-2"></i>Assign Doctor
                                    </button>
                                </form>
                            @else
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                        <div>
                                            <p class="font-medium text-green-800">Dr. {{ $visit->doctor->name }}</p>
                                            <p class="text-sm text-green-600">{{ $visit->doctor->specialization }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <!-- Consultation Tab -->
            <div id="consultation-content" class="tab-content hidden">
                @if($visit->doctor_id)
                    <form action="{{ route('visits.consultation', $visit) }}" method="POST">
                        @csrf
                        
                        <!-- Presenting Complaints Accordion -->
                        <div class="border border-gray-200 rounded-lg mb-4">
                            <button type="button" onclick="toggleAccordion('complaints')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                                <span class="font-medium text-gray-800">Presenting Complaints</span>
                                <i id="complaints-icon" class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                            <div id="complaints-content" class="hidden p-4">
                                <textarea name="presenting_complaints" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter presenting complaints...">{{ old('presenting_complaints', $visit->consultation?->presenting_complaints) }}</textarea>
                            </div>
                        </div>

                        <!-- History Accordion -->
                        <div class="border border-gray-200 rounded-lg mb-4">
                            <button type="button" onclick="toggleAccordion('history')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                                <span class="font-medium text-gray-800">History</span>
                                <i id="history-icon" class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                            <div id="history-content" class="hidden p-4">
                                <textarea name="history" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter patient history...">{{ old('history', $visit->consultation?->history) }}</textarea>
                            </div>
                        </div>

                        <!-- Examination Accordion -->
                        <div class="border border-gray-200 rounded-lg mb-4">
                            <button type="button" onclick="toggleAccordion('examination')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                                <span class="font-medium text-gray-800">Examination</span>
                                <i id="examination-icon" class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                            <div id="examination-content" class="hidden p-4">
                                <textarea name="examination" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter examination findings...">{{ old('examination', $visit->consultation?->examination) }}</textarea>
                            </div>
                        </div>

                        <!-- Provisional Diagnosis Accordion -->
                        <div class="border border-gray-200 rounded-lg mb-4">
                            <button type="button" onclick="toggleAccordion('diagnosis')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                                <span class="font-medium text-gray-800">Provisional Diagnosis</span>
                                <i id="diagnosis-icon" class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                            <div id="diagnosis-content" class="hidden p-4">
                                <textarea name="provisional_diagnosis" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter provisional diagnosis...">{{ old('provisional_diagnosis', $visit->consultation?->provisional_diagnosis) }}</textarea>
                            </div>
                        </div>

                        <!-- Treatment Accordion -->
                        <div class="border border-gray-200 rounded-lg mb-4">
                            <button type="button" onclick="toggleAccordion('treatment')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                                <span class="font-medium text-gray-800">Treatment</span>
                                <i id="treatment-icon" class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                            <div id="treatment-content" class="hidden p-4">
                                <textarea name="treatment" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter treatment plan...">{{ old('treatment', $visit->consultation?->treatment) }}</textarea>
                            </div>
                        </div>

                        <!-- Next Visit Date -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Next Visit Date (Optional)</label>
                            <input type="date" name="next_visit_date" value="{{ old('next_visit_date', $visit->consultation?->next_visit_date?->format('Y-m-d')) }}" min="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                        </div>

                        <div class="flex space-x-4">
                            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-save mr-2"></i>Save Consultation
                            </button>
                            @if(in_array($visit->status, ['with_doctor', 'triaged']) && $visit->visit_type !== 'ipd')
                                <a href="{{ route('visits.complete', $visit) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                    <i class="fas fa-check mr-2"></i>Complete Visit
                                </a>
                            @endif
                        </div>
                    </form>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-user-md text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Please assign a doctor first to start consultation.</p>
                    </div>
                @endif
            </div>

            <!-- Prescription Tab -->
            <div id="prescription-content" class="tab-content hidden">
                @if($visit->doctor_id)
                    <div class="space-y-6">
                        @if($visit->prescriptions->count() > 0)
                            <div>
                                <h4 class="text-lg font-medium text-gray-800 mb-4">Existing Prescriptions</h4>
                                @foreach($visit->prescriptions as $prescription)
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-3">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <h5 class="font-medium text-green-800">Prescription #{{ $prescription->id }}</h5>
                                                <p class="text-sm text-green-600">{{ $prescription->created_at->format('M d, Y h:i A') }}</p>
                                            </div>
                                            <span class="px-2 py-1 text-xs rounded-full {{ $prescription->status === 'dispensed' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($prescription->status) }}
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($prescription->items as $item)
                                                <div class="flex justify-between items-center text-sm">
                                                    <div>
                                                        <span class="font-medium">{{ $item->medicine->name }}</span>
                                                        <span class="text-gray-600">- {{ $item->quantity }} {{ $item->medicine->unit }}</span>
                                                    </div>
                                                    <span class="text-gray-500">{{ $item->dosage }}</span>
                                                </div>
                                                @if($item->instructions)
                                                    <p class="text-xs text-gray-600 ml-2">{{ $item->instructions }}</p>
                                                @endif
                                            @endforeach
                                        </div>
                                        @if($prescription->notes)
                                            <div class="mt-3 pt-3 border-t border-green-200">
                                                <p class="text-sm text-green-700"><strong>Notes:</strong> {{ $prescription->notes }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h4 class="text-lg font-medium text-gray-800 mb-4">Create New Prescription</h4>
                            <form action="{{ route('visits.prescription', $visit) }}" method="POST" id="prescription-form">
                                @csrf
                                <div id="prescription-items">
                                    <div class="prescription-item border border-gray-200 rounded-lg p-4 mb-4">
                                        <div class="grid grid-cols-1 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Medicine</label>
                                                <select name="medicines[0][medicine_id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                                    <option value="">Select Medicine</option>
                                                    @foreach($medicines ?? [] as $medicine)
                                                        <option value="{{ $medicine->id }}">{{ $medicine->name }} ({{ $medicine->strength }}) - Stock: {{ $medicine->stock_quantity }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                                    <input type="number" name="medicines[0][quantity]" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Dosage</label>
                                                    <input type="text" name="medicines[0][dosage]" placeholder="1 tablet twice daily" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                                                <input type="text" name="medicines[0][instructions]" placeholder="Take after meals" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                            </div>
                                        </div>
                                        <button type="button" onclick="removeItem(this)" class="mt-3 text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash mr-1"></i>Remove Medicine
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="button" onclick="addItem()" class="mb-4 text-medical-blue hover:text-blue-700">
                                    <i class="fas fa-plus mr-1"></i>Add Another Medicine
                                </button>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prescription Notes</label>
                                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Additional notes or instructions..."></textarea>
                                </div>
                                
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                                    <i class="fas fa-prescription mr-2"></i>Create Prescription
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-user-md text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Please assign a doctor first to create prescriptions.</p>
                    </div>
                @endif
            </div>

            <!-- Tests Tab (OPD & IPD only) -->
            @if($visit->visit_type !== 'emergency')
            <div id="tests-content" class="tab-content hidden">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                    <!-- Test Orders Section -->
                    <div class="xl:col-span-8">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-medium text-gray-800">Test Orders</h4>
                            <span class="text-sm text-gray-500">{{ $visit->testOrders->count() }} orders</span>
                        </div>
                        
                        @if($visit->doctor_id)
                            <!-- Test Orders Cards -->
                            <div class="max-h-96 overflow-y-auto space-y-3 mb-6">
                                @forelse($visit->testOrders as $testOrder)
                                    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow">
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="flex-1">
                                                <h5 class="font-medium text-gray-800 mb-1">{{ $testOrder->labTest?->name ?? 'Unknown Test' }}</h5>
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span class="px-2 py-1 text-xs rounded-full font-medium
                                                        {{ $testOrder->priority === 'stat' ? 'bg-red-100 text-red-800' : 
                                                           ($testOrder->priority === 'urgent' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                                        {{ strtoupper($testOrder->priority) }}
                                                    </span>
                                                    <span class="text-xs text-gray-500">Qty: {{ $testOrder->quantity ?? 1 }}</span>
                                                </div>
                                                @if($testOrder->clinical_notes)
                                                    <p class="text-sm text-gray-600 bg-gray-50 rounded p-2">{{ $testOrder->clinical_notes }}</p>
                                                @endif
                                            </div>
                                            <form action="{{ route('test-orders.remove', $testOrder) }}" method="POST" class="ml-3">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 p-1" 
                                                        onclick="return confirm('Remove this test order?')" title="Remove test">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg p-8 text-center">
                                        <i class="fas fa-flask text-gray-400 text-3xl mb-3"></i>
                                        <p class="text-gray-500">No test orders yet</p>
                                    </div>
                                @endforelse
                            </div>

                            <!-- Add Test Order Form -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center">
                                        <i class="fas fa-plus-circle text-medical-blue mr-2"></i>
                                        <h5 class="font-semibold text-gray-800">Order Lab Tests</h5>
                                    </div>
                                    <span class="text-xs text-gray-500">Select multiple tests to order at once</span>
                                </div>
                                
                                <form action="{{ route('visits.order-multiple-lab-tests', $visit) }}" method="POST" id="lab-tests-form">
                                    @csrf
                                    
                                    <!-- Dynamic Test Table -->
                                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <h6 class="text-sm font-medium text-gray-700 flex items-center">
                                                <i class="fas fa-flask text-gray-500 mr-2"></i>
                                                Test Selection
                                            </h6>
                                            <button type="button" onclick="addTestRow()" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-medical-blue bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors">
                                                <i class="fas fa-plus mr-1"></i>Add Test
                                            </button>
                                        </div>
                                        
                                        <div class="overflow-x-auto">
                                            <table class="w-full" id="tests-table">
                                                <thead>
                                                    <tr class="text-xs font-semibold text-gray-600 uppercase tracking-wider border-b-2 border-gray-200">
                                                        <th class="text-left py-3 pr-4">Lab Test</th>
                                                        <th class="text-center py-3 px-3 w-16">Qty</th>
                                                        <th class="text-center py-3 px-3 w-24">Priority</th>
                                                        <th class="text-left py-3 px-3">Clinical Notes</th>
                                                        <th class="w-10"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="test-rows">
                                                    <tr class="test-row border-b border-gray-100 hover:bg-gray-25">
                                                        <td class="py-3 pr-4">
                                                            <select name="tests[0][lab_test_id]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" required>
                                                                <option value="">Select test...</option>
                                                                @foreach($labTests as $test)
                                                                <option value="{{ $test->id }}">
                                                                    {{ $test->name }} - ₨{{ number_format($test->price, 0) }}
                                                                </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="py-3 px-3 text-center">
                                                            <input type="number" name="tests[0][quantity]" value="1" min="1" max="10" class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" required>
                                                        </td>
                                                        <td class="py-3 px-3">
                                                            <select name="tests[0][priority]" class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors priority-select" required>
                                                                <option value="routine" data-badge="bg-blue-100 text-blue-800">Routine</option>
                                                                <option value="urgent" data-badge="bg-yellow-100 text-yellow-800">Urgent</option>
                                                                <option value="stat" data-badge="bg-red-100 text-red-800">STAT</option>
                                                            </select>
                                                        </td>
                                                        <td class="py-3 px-3">
                                                            <input type="text" name="tests[0][clinical_notes]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" placeholder="Optional notes...">
                                                        </td>
                                                        <td class="py-3 text-center">
                                                            <button type="button" onclick="removeTestRow(this)" class="text-red-500 hover:text-red-700 p-1 rounded transition-colors" style="display: none;" title="Remove test">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Test Count Display -->
                                        <div class="mt-3 pt-3 border-t border-gray-200">
                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                <span id="test-count">1 test selected</span>
                                                <span>Use "Add Test" to select multiple tests</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Single Submit Button -->
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <button type="submit" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-medical-blue text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm">
                                            <i class="fas fa-flask mr-2"></i>
                                            Order Lab Tests
                                        </button>
                                        <button type="button" onclick="resetForm()" class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-200 transition-all duration-200">
                                            <i class="fas fa-undo mr-2"></i>
                                            Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                                    <p class="text-yellow-800">Doctor must be assigned to order tests.</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Results Section -->
                    <div class="xl:col-span-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-medium text-gray-800">Lab Results</h4>
                            <span class="text-sm text-gray-500">{{ $visit->labOrders->count() }} tests</span>
                        </div>
                        
                        @php
                            $pendingOrders = $visit->labOrders->whereIn('status', ['ordered', 'collected', 'testing']);
                            $completedOrders = $visit->labOrders->whereIn('status', ['verified', 'reported']);
                        @endphp
                        
                        <div class="space-y-6">
                            <!-- Pending Tests -->
                            @if($pendingOrders->count() > 0)
                                <section aria-labelledby="pending-tests-heading">
                                    <div class="flex flex-col sm:flex-row sm:items-center mb-4 gap-2">
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 bg-yellow-500 rounded-full mr-3 animate-pulse" aria-hidden="true"></div>
                                            <h5 id="pending-tests-heading" class="text-base font-semibold text-gray-900">Pending Tests</h5>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full font-medium" aria-label="{{ $pendingOrders->count() }} pending tests">{{ $pendingOrders->count() }}</span>
                                    </div>
                                    <div class="max-h-64 overflow-y-auto space-y-3" role="list" aria-label="Pending lab tests">
                                        @foreach($pendingOrders as $labOrder)
                                            <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200" role="listitem">
                                                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-3 gap-3">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex flex-col sm:flex-row sm:items-center mb-3 gap-2">
                                                            <h6 class="text-base font-semibold text-gray-900 truncate">{{ $labOrder->labTest->name }}</h6>
                                                            @php
                                                                $statusConfig = [
                                                                    'ordered' => ['label' => 'Pending', 'bg' => 'bg-gray-200', 'text' => 'text-gray-800', 'icon' => 'fas fa-clock'],
                                                                    'collected' => ['label' => 'Sample Collected', 'bg' => 'bg-blue-200', 'text' => 'text-blue-900', 'icon' => 'fas fa-vial'],
                                                                    'testing' => ['label' => 'In Progress', 'bg' => 'bg-yellow-200', 'text' => 'text-yellow-900', 'icon' => 'fas fa-spinner']
                                                                ];
                                                                $config = $statusConfig[$labOrder->status] ?? $statusConfig['ordered'];
                                                            @endphp
                                                            <span class="inline-flex items-center px-2.5 py-1 text-sm rounded-full font-medium {{ $config['bg'] }} {{ $config['text'] }}" aria-label="Status: {{ $config['label'] }}">
                                                                <i class="{{ $config['icon'] }} mr-1.5 text-xs" aria-hidden="true"></i>
                                                                {{ $config['label'] }}
                                                            </span>
                                                        </div>
                                                        <div class="flex flex-wrap items-center gap-2 mb-3">
                                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                                                                {{ $labOrder->priority === 'stat' ? 'bg-red-600 text-white' : 
                                                                   ($labOrder->priority === 'urgent' ? 'bg-orange-600 text-white' : 'bg-blue-600 text-white') }}" 
                                                                  aria-label="Priority: {{ strtoupper($labOrder->priority) }}">
                                                                @if($labOrder->priority === 'stat')
                                                                    <i class="fas fa-exclamation-triangle mr-1.5" aria-hidden="true"></i>STAT
                                                                @elseif($labOrder->priority === 'urgent')
                                                                    <i class="fas fa-clock mr-1.5" aria-hidden="true"></i>URGENT
                                                                @else
                                                                    <i class="fas fa-calendar mr-1.5" aria-hidden="true"></i>ROUTINE
                                                                @endif
                                                            </span>
                                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                                                                {{ $labOrder->test_location === 'indoor' ? 'bg-green-200 text-green-900' : 'bg-purple-200 text-purple-900' }}" 
                                                                  aria-label="Location: {{ $labOrder->test_location === 'indoor' ? 'Indoor Lab' : 'External Lab' }}">
                                                                <i class="fas {{ $labOrder->test_location === 'indoor' ? 'fa-building' : 'fa-external-link-alt' }} mr-1.5" aria-hidden="true"></i>
                                                                {{ $labOrder->test_location === 'indoor' ? 'Indoor Lab' : 'External Lab' }}
                                                            </span>
                                                        </div>
                                                        <p class="text-sm text-gray-600 flex items-center">
                                                            <i class="fas fa-calendar-alt mr-2 text-gray-400" aria-hidden="true"></i>
                                                            <span class="sr-only">Ordered on </span>{{ $labOrder->ordered_at->format('M d, Y h:i A') }}
                                                        </p>
                                                    </div>
                                                </div>
                                                @if($labOrder->clinical_notes)
                                                    <div class="bg-white rounded-lg p-3 mt-3 border border-yellow-200">
                                                        <div class="flex items-start">
                                                            <i class="fas fa-notes-medical text-yellow-600 mr-2 mt-0.5 flex-shrink-0" aria-hidden="true"></i>
                                                            <div>
                                                                <span class="sr-only">Clinical notes: </span>
                                                                <p class="text-sm text-gray-700 leading-relaxed">{{ $labOrder->clinical_notes }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                <!-- Inline Actions for Pending Indoor Tests -->
                                                @if($labOrder->test_location === 'indoor')
                                                    <div class="mt-4 pt-3 border-t border-yellow-200">
                                                        <a href="{{ route('lab-results.create', ['lab_order' => $labOrder->id]) }}" 
                                                           class="inline-flex items-center px-4 py-2 bg-medical-blue text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200"
                                                           aria-label="Add result for {{ $labOrder->labTest->name }}">
                                                            <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                                                            Add Result
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                            
                            <!-- Completed Tests -->
                            @if($completedOrders->count() > 0)
                                <section aria-labelledby="completed-tests-heading">
                                    <div class="flex flex-col sm:flex-row sm:items-center mb-4 gap-2">
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3" aria-hidden="true"></div>
                                            <h5 id="completed-tests-heading" class="text-base font-semibold text-gray-900">Completed Tests</h5>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs bg-green-100 text-green-800 rounded-full font-medium" aria-label="{{ $completedOrders->count() }} completed tests">{{ $completedOrders->count() }}</span>
                                    </div>
                                    <div class="max-h-64 overflow-y-auto space-y-3" role="list" aria-label="Completed lab tests">
                                        @foreach($completedOrders as $labOrder)
                                            <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200" role="listitem">
                                                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-3 gap-3">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex flex-col sm:flex-row sm:items-center mb-3 gap-2">
                                                            <h6 class="text-base font-semibold text-gray-900 truncate">{{ $labOrder->labTest->name }}</h6>
                                                            <span class="inline-flex items-center px-2.5 py-1 text-sm rounded-full font-medium bg-green-200 text-green-900" aria-label="Status: Reported">
                                                                <i class="fas fa-check-circle mr-1.5 text-xs" aria-hidden="true"></i>
                                                                Reported
                                                            </span>
                                                        </div>
                                                        <div class="flex flex-wrap items-center gap-2 mb-3">
                                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                                                                {{ $labOrder->priority === 'stat' ? 'bg-red-600 text-white' : 
                                                                   ($labOrder->priority === 'urgent' ? 'bg-orange-600 text-white' : 'bg-blue-600 text-white') }}" 
                                                                  aria-label="Priority: {{ strtoupper($labOrder->priority) }}">
                                                                @if($labOrder->priority === 'stat')
                                                                    <i class="fas fa-exclamation-triangle mr-1.5" aria-hidden="true"></i>STAT
                                                                @elseif($labOrder->priority === 'urgent')
                                                                    <i class="fas fa-clock mr-1.5" aria-hidden="true"></i>URGENT
                                                                @else
                                                                    <i class="fas fa-calendar mr-1.5" aria-hidden="true"></i>ROUTINE
                                                                @endif
                                                            </span>
                                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                                                                {{ $labOrder->test_location === 'indoor' ? 'bg-green-200 text-green-900' : 'bg-purple-200 text-purple-900' }}" 
                                                                  aria-label="Location: {{ $labOrder->test_location === 'indoor' ? 'Indoor Lab' : 'External Lab' }}">
                                                                <i class="fas {{ $labOrder->test_location === 'indoor' ? 'fa-building' : 'fa-external-link-alt' }} mr-1.5" aria-hidden="true"></i>
                                                                {{ $labOrder->test_location === 'indoor' ? 'Indoor Lab' : 'External Lab' }}
                                                            </span>
                                                        </div>
                                                        <p class="text-sm text-gray-600 flex items-center">
                                                            <i class="fas fa-calendar-alt mr-2 text-gray-400" aria-hidden="true"></i>
                                                            <span class="sr-only">Ordered on </span>{{ $labOrder->ordered_at->format('M d, Y h:i A') }}
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                @if($labOrder->clinical_notes)
                                                    <div class="bg-white rounded-lg p-3 mb-3 border border-green-200">
                                                        <div class="flex items-start">
                                                            <i class="fas fa-notes-medical text-green-600 mr-2 mt-0.5 flex-shrink-0" aria-hidden="true"></i>
                                                            <div>
                                                                <span class="sr-only">Clinical notes: </span>
                                                                <p class="text-sm text-gray-700 leading-relaxed">{{ $labOrder->clinical_notes }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                @if($labOrder->test_location === 'indoor')
                                                    @if($labOrder->result)
                                                        <!-- Indoor Lab Results with Parameters -->
                                                        <div class="bg-white border border-green-200 rounded-lg p-3 mt-3">
                                                            <div class="flex items-center justify-between mb-2">
                                                                <h6 class="text-sm font-medium text-green-800">Test Results</h6>
                                                                <span class="text-xs text-green-600">{{ $labOrder->result->reported_at?->format('M d, h:i A') ?? 'Recently reported' }}</span>
                                                            </div>
                                                            
                                                            @if($labOrder->resultItems && $labOrder->resultItems->count() > 0)
                                                                <!-- Parameter-based Results -->
                                                                <div class="space-y-2">
                                                                    @foreach($labOrder->resultItems as $item)
                                                                        <div class="flex justify-between items-center py-1 border-b border-green-100 last:border-0">
                                                                            <span class="text-sm font-medium text-gray-700">{{ $item->parameter_name ?? 'Parameter' }}</span>
                                                                            <div class="text-right">
                                                                                <span class="text-sm font-semibold text-gray-800">{{ $item->value ?? 'N/A' }}</span>
                                                                                @if($item->unit)
                                                                                    <span class="text-xs text-gray-500 ml-1">{{ $item->unit }}</span>
                                                                                @endif
                                                                                @if($item->reference_range)
                                                                                    <div class="text-xs text-gray-500">Ref: {{ $item->reference_range }}</div>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @elseif($labOrder->result->result_text)
                                                                <!-- Text-based Results -->
                                                                <div class="bg-gray-50 rounded p-2">
                                                                    <p class="text-sm text-gray-700">{{ $labOrder->result->result_text }}</p>
                                                                </div>
                                                            @else
                                                                <!-- No Results Available -->
                                                                <div class="bg-gray-50 rounded p-2 text-center">
                                                                    <p class="text-sm text-gray-500">Results pending</p>
                                                                </div>
                                                            @endif
                                                            
                                                            @if($labOrder->result->notes)
                                                                <div class="mt-2 pt-2 border-t border-green-200">
                                                                    <p class="text-xs text-gray-600"><strong>Notes:</strong> {{ $labOrder->result->notes }}</p>
                                                                </div>
                                                            @endif
                                                            
                                                            <div class="mt-4 flex flex-col sm:flex-row sm:justify-end gap-3">
                                                                <a href="{{ route('lab-results.report', $labOrder->result) }}" 
                                                                   class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200"
                                                                   aria-label="View report for {{ $labOrder->labTest->name }}">
                                                                    <i class="fas fa-file-medical mr-2" aria-hidden="true"></i>View Report
                                                                </a>
                                                                <a href="{{ route('lab-results.report', $labOrder->result) }}?print=1" 
                                                                   target="_blank"
                                                                   class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200"
                                                                   aria-label="Print report for {{ $labOrder->labTest->name }} (opens in new tab)">
                                                                    <i class="fas fa-print mr-2" aria-hidden="true"></i>Print
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <!-- No Results Yet -->
                                                        <div class="bg-gray-50 border border-gray-200 rounded p-3 mt-3 text-center">
                                                            <i class="fas fa-clock text-gray-400 mb-2"></i>
                                                            <p class="text-sm text-gray-500">Results pending</p>
                                                        </div>
                                                    @endif
                                                @elseif($labOrder->test_location === 'outdoor')
                                                    <!-- External Lab Placeholder -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-3">
                                                        <div class="flex items-start">
                                                            <i class="fas fa-external-link-alt text-blue-600 mr-3 mt-1"></i>
                                                            <div class="flex-1">
                                                                <h6 class="text-sm font-medium text-blue-800 mb-2">External Lab Test</h6>
                                                                <div class="space-y-2 text-sm text-blue-700">
                                                                    <div class="grid grid-cols-2 gap-4">
                                                                        <div>
                                                                            <span class="font-medium">Test:</span> {{ $labOrder->labTest->name }}
                                                                        </div>
                                                                        <div>
                                                                            <span class="font-medium">Priority:</span> 
                                                                            <span class="px-1 py-0.5 text-xs rounded
                                                                                {{ $labOrder->priority === 'stat' ? 'bg-red-100 text-red-800' : 
                                                                                   ($labOrder->priority === 'urgent' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                                                                                {{ strtoupper($labOrder->priority) }}
                                                                            </span>
                                                                        </div>
                                                                        <div class="col-span-2">
                                                                            <span class="font-medium">Ordered:</span> {{ $labOrder->ordered_at->format('M d, Y h:i A') }}
                                                                        </div>
                                                                    </div>
                                                                    @if($labOrder->clinical_notes)
                                                                        <div class="pt-2 border-t border-blue-200">
                                                                            <span class="font-medium">Clinical Notes:</span>
                                                                            <p class="mt-1 text-blue-600">{{ $labOrder->clinical_notes }}</p>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="mt-3 p-2 bg-blue-100 rounded text-center">
                                                                    <p class="text-sm font-medium text-blue-800">
                                                                        <i class="fas fa-clock mr-1"></i>
                                                                        Results will be uploaded when available
                                                                    </p>
                                                                </div>
                                                                @if($labOrder->result)
                                                                    <div class="mt-2">
                                                                        <a href="{{ route('lab-results.report', $labOrder->result) }}" 
                                                                           class="inline-flex items-center text-sm text-medical-blue hover:text-blue-700 font-medium">
                                                                            <i class="fas fa-file-medical mr-1"></i>View Uploaded Report
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                            
                            <!-- Empty State -->
                            @if($visit->labOrders->count() === 0)
                                <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg p-8 text-center" role="status" aria-live="polite">
                                    <i class="fas fa-clipboard-list text-gray-400 text-3xl mb-3" aria-hidden="true"></i>
                                    <p class="text-gray-500">No lab tests ordered yet</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
let activeTab = '{{ $visit->visit_type === "emergency" ? "triage" : ($visit->visit_type === "ipd" ? "admission" : "vitals") }}';
let itemIndex = 1;
let testRowIndex = 1;

function showTab(tabName) {
    activeTab = tabName;
    localStorage.setItem('activeTab', tabName);
    
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-medical-blue', 'text-medical-blue');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    document.getElementById(tabName + '-content').classList.remove('hidden');
    
    const activeTabButton = document.getElementById(tabName + '-tab');
    if (activeTabButton) {
        activeTabButton.classList.remove('border-transparent', 'text-gray-500');
        activeTabButton.classList.add('border-medical-blue', 'text-medical-blue');
    }
}

function toggleAccordion(section) {
    const content = document.getElementById(section + '-content');
    const icon = document.getElementById(section + '-icon');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function addItem() {
    const container = document.getElementById('prescription-items');
    const firstItem = container.querySelector('.prescription-item');
    const medicineSelect = firstItem.querySelector('select');
    const medicineOptions = medicineSelect.innerHTML;
    
    const newItem = document.createElement('div');
    newItem.className = 'prescription-item border border-gray-200 rounded-lg p-3 mb-3';
    newItem.innerHTML = `
        <div class="grid grid-cols-1 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                <select name="medicines[${itemIndex}][medicine_id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm" required>
                    ${medicineOptions}
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input type="number" name="medicines[${itemIndex}][quantity]" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                    <input type="text" name="medicines[${itemIndex}][dosage]" placeholder="1 tablet twice daily" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm" required>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                <input type="text" name="medicines[${itemIndex}][instructions]" placeholder="Take after meals" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">
            </div>
        </div>
        <button type="button" onclick="removeItem(this)" class="mt-2 text-red-600 hover:text-red-800 text-sm">
            <i class="fas fa-trash mr-1"></i>Remove
        </button>
    `;
    container.appendChild(newItem);
    itemIndex++;
}

function addTestRow() {
    const tbody = document.getElementById('test-rows');
    const firstRow = tbody.querySelector('.test-row');
    const testSelect = firstRow.querySelector('select[name*="lab_test_id"]');
    const testOptions = testSelect.innerHTML;
    
    const newRow = document.createElement('tr');
    newRow.className = 'test-row border-b border-gray-100 hover:bg-gray-25';
    newRow.innerHTML = `
        <td class="py-3 pr-4">
            <select name="tests[${testRowIndex}][lab_test_id]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" required>
                ${testOptions}
            </select>
        </td>
        <td class="py-3 px-3 text-center">
            <input type="number" name="tests[${testRowIndex}][quantity]" value="1" min="1" max="10" class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" required>
        </td>
        <td class="py-3 px-3">
            <select name="tests[${testRowIndex}][priority]" class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors priority-select" required>
                <option value="routine" data-badge="bg-blue-100 text-blue-800">Routine</option>
                <option value="urgent" data-badge="bg-yellow-100 text-yellow-800">Urgent</option>
                <option value="stat" data-badge="bg-red-100 text-red-800">STAT</option>
            </select>
        </td>
        <td class="py-3 px-3">
            <input type="text" name="tests[${testRowIndex}][clinical_notes]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" placeholder="Optional notes...">
        </td>
        <td class="py-3 text-center">
            <button type="button" onclick="removeTestRow(this)" class="text-red-500 hover:text-red-700 p-1 rounded transition-colors" title="Remove test">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    testRowIndex++;
    
    updateRemoveButtons();
    updateTestCount();
}

function removeTestRow(button) {
    const rows = document.querySelectorAll('.test-row');
    if (rows.length > 1) {
        button.closest('.test-row').remove();
        updateRemoveButtons();
        updateTestCount();
    }
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.test-row');
    rows.forEach((row, index) => {
        const removeBtn = row.querySelector('button[onclick*="removeTestRow"]');
        if (removeBtn) {
            removeBtn.style.display = rows.length > 1 ? 'block' : 'none';
        }
    });
}

function updateTestCount() {
    const rows = document.querySelectorAll('.test-row');
    const count = rows.length;
    const countElement = document.getElementById('test-count');
    if (countElement) {
        countElement.textContent = `${count} test${count !== 1 ? 's' : ''} selected`;
    }
}

function resetForm() {
    // Reset the form
    document.getElementById('lab-tests-form').reset();
    
    // Remove extra rows, keep only the first one
    const tbody = document.getElementById('test-rows');
    const rows = tbody.querySelectorAll('.test-row');
    for (let i = 1; i < rows.length; i++) {
        rows[i].remove();
    }
    
    // Reset the first row
    const firstRow = tbody.querySelector('.test-row');
    firstRow.querySelector('select[name*="lab_test_id"]').selectedIndex = 0;
    firstRow.querySelector('input[name*="quantity"]').value = 1;
    firstRow.querySelector('select[name*="priority"]').selectedIndex = 0;
    firstRow.querySelector('input[name*="clinical_notes"]').value = '';
    
    updateRemoveButtons();
    updateTestCount();
}

function removeItem(button) {
    const items = document.querySelectorAll('.prescription-item');
    if (items.length > 1) {
        button.closest('.prescription-item').remove();
    }
}

// Restore active tab on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTab = localStorage.getItem('activeTab');
    if (savedTab && document.getElementById(savedTab + '-tab')) {
        showTab(savedTab);
    } else {
        showTab(activeTab);
    }
    
    // Initialize test count display
    updateTestCount();
});
</script>
@endsection