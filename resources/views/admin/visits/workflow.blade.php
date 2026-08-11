@extends('admin.layout')

@section('title', 'Visit Workflow - Hospital Management System')
@section('page-title', 'Visit Workflow')
@section('page-description', 'Manage patient visit workflow')

@section('content')
<div id="visit-workflow" class="max-w-6xl mx-auto">
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
                    @if($workflowData['show_opd_ui'] ?? false)
                        @include('admin.visits.workflow.opd._queue-priority')
                    @endif
                    <a href="{{ route('visits.print', $visit) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-medical-blue text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-print mr-2"></i>{{ $workflowData['print_label'] }}
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
                    $steps = $workflowData['steps'];
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
                @if($workflowData['show_emergency_ui'] ?? false)
                    @include('admin.visits.workflow.emergency._tabs')
                @endif
                @if($workflowData['show_ipd_ui'] ?? false)
                    @include('admin.visits.workflow.ipd._tabs')
                @endif
                <button onclick="showTab('vitals')" id="vitals-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm {{ ($workflowData['vitals_tab_default_visible'] ?? false) ? 'border-medical-blue text-medical-blue' : 'border-transparent text-gray-500' }}">
                    <i class="fas fa-heartbeat mr-2"></i>Vital Signs
                </button>
                <button onclick="showTab('consultation')" id="consultation-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-stethoscope mr-2"></i>{{ $workflowData['consultation_label'] }}
                </button>
                <button onclick="showTab('prescription')" id="prescription-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-prescription mr-2"></i>Prescription
                </button>
                @if($workflowData['show_investigations'])
                    <button onclick="showTab('tests')" id="tests-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
                        <i class="fas fa-flask mr-2"></i>Investigations
                    </button>
                @endif
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Emergency Triage Tab -->
            @if($workflowData['show_emergency_ui'] ?? false)
            <div id="triage-content" class="tab-content">
                @include('admin.visits.workflow.emergency._triage')
            </div>
            @endif

            <!-- IPD Admission Tab -->
            @if($workflowData['show_ipd_ui'] ?? false)
            <div id="admission-content" class="tab-content">
                @include('admin.visits.workflow.ipd._admission')
            </div>
            @endif

            <!-- Vital Signs Tab -->
            <div id="vitals-content" class="tab-content {{ ($workflowData['vitals_tab_default_visible'] ?? false) ? '' : 'hidden' }}">
                @if(session('warning'))
                    <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg flex items-start">
                        <i class="fas fa-exclamation-triangle mr-3 mt-0.5"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-lg font-medium text-gray-800 mb-4">{{ ($workflowData['append_only_vitals'] ?? false) ? 'Record New Vital Signs' : 'Record Vital Signs' }}</h4>
                        <form action="{{ route('visits.vitals', $visit) }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Blood Pressure</label>
                                    <input type="text" name="blood_pressure" value="{{ ($workflowData['append_only_vitals'] ?? false) ? '' : old('blood_pressure', $visit->vitalSigns?->blood_pressure) }}" 
                                           placeholder="120/80" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Temperature (°F)</label>
                                    <input type="number" name="temperature" value="{{ ($workflowData['append_only_vitals'] ?? false) ? '' : old('temperature', $visit->vitalSigns?->temperature) }}" 
                                           step="0.1" placeholder="98.6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Pulse Rate (bpm)</label>
                                    <input type="number" name="pulse_rate" value="{{ ($workflowData['append_only_vitals'] ?? false) ? '' : old('pulse_rate', $visit->vitalSigns?->pulse_rate) }}" 
                                           placeholder="72" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SpO<sub>2</sub> (%)</label>
                                    <input type="number" name="spo2" value="{{ ($workflowData['append_only_vitals'] ?? false) ? '' : old('spo2', $visit->vitalSigns?->spo2) }}" 
                                           min="0" max="100" placeholder="98" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">BSR (%)</label>
                                    <input type="number" name="bsr" value="{{ ($workflowData['append_only_vitals'] ?? false) ? '' : old('bsr', $visit->vitalSigns?->bsr) }}" 
                                           step="0.01" min="0" placeholder="120" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                                    <input type="number" name="weight" value="{{ ($workflowData['append_only_vitals'] ?? false) ? '' : old('weight', $visit->vitalSigns?->weight) }}" 
                                           step="0.1" placeholder="70.5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Height (ft)</label>
                                    <input type="number" name="height" value="{{ ($workflowData['append_only_vitals'] ?? false) ? '' : old('height', $visit->vitalSigns?->height) }}" 
                                           step="0.01" placeholder="5.6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                    <p class="text-xs text-gray-500 mt-1">Example: 5.6 for 5'6"</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ ($workflowData['append_only_vitals'] ?? false) ? '' : old('notes', $visit->vitalSigns?->notes) }}</textarea>
                            </div>
                            <button type="submit" class="mt-4 bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-save mr-2"></i>{{ ($workflowData['append_only_vitals'] ?? false) ? 'Add Vital Signs' : 'Save Vital Signs' }}
                            </button>
                        </form>
                    </div>

                    <div>
                        @if($workflowData['append_only_vitals'] ?? false)
                            @include('admin.visits.workflow.ipd._vitals-history')
                        @elseif($workflowData['show_opd_ui'] ?? false)
                            @include('admin.visits.workflow.opd._vitals')
                        @elseif($workflowData['show_emergency_ui'] ?? false)
                            @include('admin.visits.workflow.emergency._vitals')
                        @endif
                    </div>
                </div>
            </div>

            @if($workflowData['show_ipd_ui'] ?? false)
                @include('admin.visits.partials.ipd-gpe-records')
                @include('admin.visits.partials.ipd-care-team')
            @endif

            <!-- Consultation Tab -->
            <div id="consultation-content" class="tab-content hidden">
                @if($workflowData['show_active_complaints'] ?? false)
                    @include('admin.visits.partials.ipd-active-complaints')
                @endif

                @if($workflowData['can_consult'])
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
                                <textarea name="history" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue mb-4" placeholder="Enter patient history...">{{ old('history', $visit->consultation?->history) }}</textarea>
                                <!-- Common Conditions -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Common Conditions</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">DM (Diabetes Mellitus)</label>
                                            <input type="text" name="diagnosis_dm" value="{{ old('diagnosis_dm', $visit->consultation?->diagnosis_dm) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter DM details">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">HTN (Hypertension)</label>
                                            <input type="text" name="diagnosis_htn" value="{{ old('diagnosis_htn', $visit->consultation?->diagnosis_htn) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter HTN details">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">IHD (Ischemic Heart Disease)</label>
                                            <input type="text" name="diagnosis_ihd" value="{{ old('diagnosis_ihd', $visit->consultation?->diagnosis_ihd) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter IHD details">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Asthma</label>
                                            <input type="text" name="diagnosis_asthma" value="{{ old('diagnosis_asthma', $visit->consultation?->diagnosis_asthma) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter Asthma details">
                                        </div>
                                    </div>
                                </div>
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
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Diagnosis</label>
                                    <textarea name="provisional_diagnosis" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter additional provisional diagnosis...">{{ old('provisional_diagnosis', $visit->consultation?->provisional_diagnosis) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Allergies Accordion -->
                        <div class="border border-gray-200 rounded-lg mb-4">
                            <button type="button" onclick="toggleAccordion('allergies')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                                <span class="font-medium text-gray-800">Allergies</span>
                                <i id="allergies-icon" class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                            <div id="allergies-content" class="hidden p-4">
                                <!-- Allergies Multi-Select -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Allergies</label>
                                    <select id="allergies-select" name="allergies[]" multiple class="w-full">
                                        @php
                                            $selectedAllergyIds = old('allergies', $visit->consultation?->allergies->pluck('name')->toArray() ?? []);
                                        @endphp
                                        @foreach($allergies->groupBy('category') as $category => $categoryAllergies)
                                            <optgroup label="{{ ucfirst($category) }} Allergies">
                                                @foreach($categoryAllergies as $allergy)
                                                    <option value="{{ $allergy->name }}" {{ in_array($allergy->name, $selectedAllergyIds) ? 'selected' : '' }}>
                                                        {{ $allergy->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        You can select multiple allergies or type to add custom ones
                                    </p>
                                </div>
                                
                                <!-- Additional Allergy Notes -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Information</label>
                                    <textarea name="allergy_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter additional allergy information, reactions, or severity...">{{ old('allergy_notes', $visit->consultation?->allergy_notes) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- GPE (General Physical Examination) Accordion -->
                        @if($workflowData['show_opd_ui'] ?? false)
                            @include('admin.visits.workflow.opd._consultation')
                        @elseif($workflowData['show_emergency_ui'] ?? false)
                            @include('admin.visits.workflow.emergency._care')
                        @endif

                        <!-- Next Visit Date -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Next Visit Date (Optional)</label>
                            <input type="text" id="next-visit-date" name="next_visit_date" value="{{ old('next_visit_date', $visit->consultation?->next_visit_date?->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Select date">
                        </div>

                        <div class="flex space-x-4">
                            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-save mr-2"></i>Save Consultation
                            </button>
                            @if(in_array($visit->status, ['with_doctor', 'triaged']) && ($workflowData['show_complete_visit_button'] ?? false))
                                <a href="{{ route('visits.complete', $visit) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                    <i class="fas fa-check mr-2"></i>Complete Visit
                                </a>
                            @endif
                        </div>
                    </form>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-user-md text-4xl text-gray-300 mb-4"></i>
                        @if($workflowData['care_team_consult_message'] ?? false)
                            <p class="text-gray-500">{{ $workflowData['care_team_consult_message'] }}</p>
                        @else
                            <p class="text-gray-500">Please assign a doctor first to start consultation.</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Prescription Tab -->
            <div id="prescription-content" class="tab-content hidden">
                @if($workflowData['can_prescribe'])
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
                                @if($workflowData['show_order_doctor_picker'] ?? false)
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Prescribing Doctor (from care team)</label>
                                        <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                            <option value="">Select Doctor</option>
                                            @foreach($visit->careTeam as $member)
                                                <option value="{{ $member->doctor_id }}">
                                                    Dr. {{ $member->doctor->name }} - {{ $member->doctor->specialization }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('doctor_id')
                                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                                <div id="prescription-items">
                                    <div class="prescription-item border border-gray-200 rounded-lg p-3 mb-3">
                                        <div class="flex items-start gap-3">
                                            <div class="flex-[2] min-w-0">
                                                <label class="block text-xs font-medium text-gray-500 mb-1">Medicine</label>
                                                <select name="medicines[0][medicine_id]" class="medicine-select w-full" required>
                                                    <option value="">Select Medicine</option>
                                                    @foreach($medicines ?? [] as $medicine)
                                                        <option value="{{ $medicine->id }}">{{ $medicine->name }}{{ $medicine->strength ? ' ('.$medicine->strength.')' : '' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex-[2] min-w-0">
                                                <label class="block text-xs font-medium text-gray-500 mb-1">Instruction</label>
                                                <select name="medicines[0][instruction_id]" class="instruction-select w-full">
                                                    <option value="">Select Instruction</option>
                                                    @php
                                                        $groupedInstructions = \App\Models\PrescriptionInstruction::active()
                                                            ->orderBy('category')
                                                            ->orderBy('instruction')
                                                            ->get()
                                                            ->groupBy('category');
                                                        $categoryLabels = [
                                                            'frequency' => 'تعدد (Frequency)',
                                                            'meal' => 'کھانا (Meal)',
                                                            'time' => 'وقت (Time)',
                                                            'duration' => 'مدت (Duration)',
                                                            'conditional' => 'شرطی (Conditional)',
                                                            'injection' => 'انجیکشن (Injection)',
                                                        ];
                                                    @endphp
                                                    @foreach(['frequency', 'meal', 'time', 'duration', 'conditional', 'injection'] as $category)
                                                        @if($groupedInstructions->has($category))
                                                            <optgroup label="{{ $categoryLabels[$category] }}">
                                                                @foreach($groupedInstructions[$category] as $instruction)
                                                                    <option value="{{ $instruction->id }}">{{ $instruction->instruction }}</option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endif
                                                    @endforeach
                                                    @if($groupedInstructions->has(''))
                                                        <optgroup label="دیگر (Other)">
                                                            @foreach($groupedInstructions[''] as $instruction)
                                                                <option value="{{ $instruction->id }}">{{ $instruction->instruction }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="w-20 flex-shrink-0">
                                                <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                                                <input type="number" name="medicines[0][quantity]" value="1" min="1" max="999"
                                                       class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                            </div>
                                            <div class="pt-5 flex-shrink-0">
                                                <button type="button" class="remove-item-btn p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="button" onclick="addPrescriptionItem()" class="mb-4 text-medical-blue hover:text-blue-700">
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
                        @if($workflowData['care_team_prescribe_message'] ?? false)
                            <p class="text-gray-500">{{ $workflowData['care_team_prescribe_message'] }}</p>
                        @else
                            <p class="text-gray-500">Please assign a doctor first to create prescriptions.</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Tests Tab -->
            @if($workflowData['show_investigations'])
            <div id="tests-content" class="tab-content hidden">
                @include('admin.visits.workflow.opd._investigations')
            </div>
            @endif
        </div>
    </div>
</div>

<script>
let activeTab = '{{ $workflowData['resolved_initial_tab'] ?? $workflowData['default_tab'] }}';
let itemIndex = 1;
let testRowIndex = 1;

function showTab(tabName) {
    activeTab = tabName;

    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });

    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-medical-blue', 'text-medical-blue');
        button.classList.add('border-transparent', 'text-gray-500');
    });

    const content = document.getElementById(tabName + '-content');
    if (content) {
        content.classList.remove('hidden');
    }

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
    // Handled by prescription-form.js
    if (typeof window.addPrescriptionItem === 'function') {
        window.addPrescriptionItem();
    }
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

    if (typeof window.initVisitWorkflowSelect2 === 'function') {
        window.initVisitWorkflowSelect2(newRow);
    }

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
    // Handled by prescription-form.js via delegated event
    const items = document.querySelectorAll('.prescription-item');
    if (items.length > 1) {
        button.closest('.prescription-item').remove();
    }
}

// Restore active tab on page load
document.addEventListener('DOMContentLoaded', function() {
    // Determine the correct starting tab based on visit status and completion
    let defaultTab = '{{ $workflowData['resolved_initial_tab'] ?? $workflowData['default_tab'] }}';
    
    // Restore tab after form submit, otherwise use workflow default
    if (typeof window.restoreVisitWorkflowTab === 'function') {
        window.restoreVisitWorkflowTab(defaultTab);
    } else {
        showTab(defaultTab);
    }

    // Initialize test count display
    updateTestCount();
});
</script>

@vite(['resources/css/visits-form.css', 'resources/css/visit-workflow-ipd.css', 'resources/js/visits-form.js', 'resources/js/visit-workflow-ipd.js', 'resources/js/visit-workflow-select2.js', 'resources/js/visit-workflow-admission.js', 'resources/js/prescription-form.js'])
@endsection