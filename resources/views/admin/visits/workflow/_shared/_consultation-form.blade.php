@if($workflowData['show_active_complaints'] ?? false)
    @include('admin.visits.partials.ipd-active-complaints')
@endif

@if($workflowData['can_consult'])
    <form action="{{ route('visits.consultation', $visit) }}" method="POST" @if($workflowData['show_ipd_ui'] ?? false) data-save-tab="consultation" @endif>
        @csrf

        @php $expandPrimary = $expandPrimary ?? false; @endphp

        <div class="border border-gray-200 rounded-lg mb-4">
            <button type="button" onclick="toggleAccordion('complaints')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                <span class="font-medium text-gray-800">Presenting Complaints</span>
                <i id="complaints-icon" class="fas fa-chevron-{{ $expandPrimary ? 'up' : 'down' }} text-gray-500"></i>
            </button>
            <div id="complaints-content" class="{{ $expandPrimary ? 'p-4' : 'hidden p-4' }}">
                <textarea name="presenting_complaints" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter presenting complaints...">{{ old('presenting_complaints', $visit->consultation?->presenting_complaints) }}</textarea>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg mb-4">
            <button type="button" onclick="toggleAccordion('history')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                <span class="font-medium text-gray-800">History</span>
                <i id="history-icon" class="fas fa-chevron-down text-gray-500"></i>
            </button>
            <div id="history-content" class="hidden p-4">
                <textarea name="history" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue mb-4" placeholder="Enter patient history...">{{ old('history', $visit->consultation?->history) }}</textarea>
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

        <div class="border border-gray-200 rounded-lg mb-4">
            <button type="button" onclick="toggleAccordion('examination')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                <span class="font-medium text-gray-800">Examination</span>
                <i id="examination-icon" class="fas fa-chevron-down text-gray-500"></i>
            </button>
            <div id="examination-content" class="hidden p-4">
                <textarea name="examination" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter examination findings...">{{ old('examination', $visit->consultation?->examination) }}</textarea>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg mb-4">
            <button type="button" onclick="toggleAccordion('diagnosis')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                <span class="font-medium text-gray-800">Provisional Diagnosis</span>
                <i id="diagnosis-icon" class="fas fa-chevron-{{ $expandPrimary ? 'up' : 'down' }} text-gray-500"></i>
            </button>
            <div id="diagnosis-content" class="{{ $expandPrimary ? 'p-4' : 'hidden p-4' }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Diagnosis</label>
                    <textarea name="provisional_diagnosis" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter additional provisional diagnosis...">{{ old('provisional_diagnosis', $visit->consultation?->provisional_diagnosis) }}</textarea>
                </div>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg mb-4">
            <button type="button" onclick="toggleAccordion('allergies')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
                <span class="font-medium text-gray-800">Allergies</span>
                <i id="allergies-icon" class="fas fa-chevron-down text-gray-500"></i>
            </button>
            <div id="allergies-content" class="hidden p-4">
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Information</label>
                    <textarea name="allergy_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter additional allergy information, reactions, or severity...">{{ old('allergy_notes', $visit->consultation?->allergy_notes) }}</textarea>
                </div>
            </div>
        </div>

        @if($workflowData['show_opd_ui'] ?? false)
            @include('admin.visits.workflow.opd._consultation')
        @elseif($workflowData['show_emergency_ui'] ?? false)
            @include('admin.visits.workflow.emergency._care')
        @endif

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Next Visit Date (Optional)</label>
            <input type="text" id="next-visit-date" name="next_visit_date" value="{{ old('next_visit_date', $visit->consultation?->next_visit_date?->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Select date">
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Save {{ $workflowData['consultation_label'] ?? 'Consultation' }}
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
