@extends('admin.layout')

@section('title', 'PAC Request — ' . $surgery->surgery_number)
@section('page-title', 'Pre-Anaesthesia Checkup')

@push('scripts')
@vite(['resources/js/pac-form.js'])
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('ot.pac.store', $surgery) }}" method="POST" id="pac-form">
        @csrf

        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
        @endif

        {{-- Surgery Info Banner --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-800">{{ $surgery->surgery_number }} — {{ $surgery->procedure_name }}</p>
                    <p class="text-xs text-blue-600">Patient: {{ $surgery->patient?->name }} · Surgeon: Dr. {{ $surgery->doctor?->name }} · Date: {{ $surgery->scheduled_date?->format('d M Y') }}</p>
                </div>
                <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 capitalize">{{ $surgery->surgery_type }}</span>
            </div>
        </div>

        {{-- Assignment --}}
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800"><i class="fas fa-user-md mr-2 text-medical-blue"></i>Assignment</h4>
            </div>
            <div class="p-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assign Anaesthetist</label>
                    <select name="anaesthetist_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-medical-blue focus:border-medical-blue">
                        <option value="">Auto-assign on clearance</option>
                        @foreach($anaesthetists as $user)
                            <option value="{{ $user->id }}" {{ old('anaesthetist_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Patient Condition --}}
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800"><i class="fas fa-heartbeat mr-2 text-red-500"></i>Patient Condition</h4>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ASA Grade</label>
                    <select name="asa_grade" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        @foreach(['ASA I', 'ASA II', 'ASA III', 'ASA IV', 'ASA V', 'ASA VI'] as $grade)
                            <option value="{{ $grade }}" {{ old('asa_grade') == $grade ? 'selected' : '' }}>{{ $grade }}</option>
                        @endforeach
                    </select>
                    @error('asa_grade') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mallampati Class</label>
                    <select name="mallampati_class" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        @foreach(['I', 'II', 'III', 'IV'] as $mc)
                            <option value="{{ $mc }}" {{ old('mallampati_class') == $mc ? 'selected' : '' }}>Class {{ $mc }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medical History</label>
                    <textarea name="medical_history" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Relevant past medical history (diabetes, hypertension, etc.)">{{ old('medical_history') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Medications</label>
                    <textarea name="current_medications" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Ongoing medications">{{ old('current_medications') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Allergies</label>
                    <textarea name="allergies" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Drug/food/latex allergies">{{ old('allergies') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Airway Assessment</label>
                    <textarea name="airway_assessment" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Neck mobility, mouth opening, dental status">{{ old('airway_assessment') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cardiovascular Status</label>
                    <textarea name="cardiovascular_status" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('cardiovascular_status') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Respiratory Status</label>
                    <textarea name="respiratory_status" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('respiratory_status') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Renal / Hepatic Status</label>
                    <textarea name="renal_hepatic_status" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('renal_hepatic_status') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Vitals --}}
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800"><i class="fas fa-thermometer-half mr-2 text-green-500"></i>Vitals at PAC</h4>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Blood Pressure</label>
                    <input type="text" name="blood_pressure" value="{{ old('blood_pressure') }}" placeholder="120/80"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heart Rate (bpm)</label>
                    <input type="text" name="heart_rate" value="{{ old('heart_rate') }}" placeholder="72"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SpO2 (%)</label>
                    <input type="text" name="spo2" value="{{ old('spo2') }}" placeholder="98"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                    <input type="text" name="weight_kg" value="{{ old('weight_kg') }}" placeholder="70"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
            </div>
        </div>

        {{-- Investigations & Plan --}}
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800"><i class="fas fa-flask mr-2 text-purple-500"></i>Investigations & Anaesthesia Plan</h4>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Investigations Reviewed</label>
                    <textarea name="investigations_reviewed" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="CBC, ECG, chest X-ray, blood sugar, RFTs etc.">{{ old('investigations_reviewed') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proposed Anaesthesia Type</label>
                    <select name="proposed_anaesthesia_type" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        @foreach(['general', 'regional', 'local', 'sedation'] as $t)
                            <option value="{{ $t }}" {{ old('proposed_anaesthesia_type') == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fasting Instructions</label>
                    <input type="text" name="fasting_instructions" value="{{ old('fasting_instructions') }}" placeholder="NPO for 6 hours"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Premedication</label>
                    <textarea name="premedication" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Tab. Alprazolam 0.5mg night before">{{ old('premedication') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Special Precautions</label>
                    <textarea name="special_precautions" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Difficult intubation, diabetic management, etc.">{{ old('special_precautions') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end space-x-4">
            <a href="{{ route('ot.surgeries.show', $surgery) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
                <i class="fas fa-paper-plane mr-2"></i>Submit PAC Request
            </button>
        </div>
    </form>
</div>
@endsection
