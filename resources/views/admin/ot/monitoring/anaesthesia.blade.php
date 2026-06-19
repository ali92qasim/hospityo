@extends('admin.layout')

@section('title', 'Anaesthesia Record — ' . $surgery->surgery_number)
@section('page-title', 'Anaesthesia Record')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm font-medium text-blue-800">{{ $surgery->surgery_number }} — {{ $surgery->procedure_name }}</p>
        <p class="text-xs text-blue-600">Patient: {{ $surgery->patient?->name }} · Surgeon: Dr. {{ $surgery->doctor?->name }}</p>
    </div>

    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>
    @endif

    @php $record = $surgery->anaesthesiaRecord; @endphp

    <form action="{{ route('ot.monitoring.store-anaesthesia', $surgery) }}" method="POST">
        @csrf

        {{-- Anaesthetist & Type --}}
        <div class="bg-white rounded-lg shadow-sm mb-6 p-6">
            <h4 class="text-md font-medium text-gray-800 mb-4"><i class="fas fa-user-md mr-2 text-purple-500"></i>Anaesthesia Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Anaesthetist *</label>
                    <select name="anaesthetist_id" required class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        @foreach($anaesthetists as $u)
                            <option value="{{ $u->id }}" {{ old('anaesthetist_id', $record?->anaesthetist_id) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="anaesthesia_type" required class="w-full border-gray-300 rounded-lg text-sm">
                        @foreach(['general', 'regional', 'local', 'sedation', 'combined'] as $t)
                            <option value="{{ $t }}" {{ old('anaesthesia_type', $record?->anaesthesia_type) == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Airway Management</label>
                    <select name="airway_management" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        @foreach(['ETT', 'LMA', 'facemask', 'tracheostomy'] as $a)
                            <option value="{{ $a }}" {{ old('airway_management', $record?->airway_management) == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ETT Size</label>
                    <input type="text" name="ett_size" value="{{ old('ett_size', $record?->ett_size) }}" class="w-full border-gray-300 rounded-lg text-sm" placeholder="e.g. 7.5">
                </div>
            </div>
        </div>

        {{-- Drugs --}}
        <div class="bg-white rounded-lg shadow-sm mb-6 p-6">
            <h4 class="text-md font-medium text-gray-800 mb-4"><i class="fas fa-pills mr-2 text-green-500"></i>Drugs & Agents</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Induction Agent</label>
                    <input type="text" name="induction_agent" value="{{ old('induction_agent', $record?->induction_agent) }}" class="w-full border-gray-300 rounded-lg text-sm" placeholder="e.g. Propofol">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Induction Dose</label>
                    <input type="text" name="induction_dose" value="{{ old('induction_dose', $record?->induction_dose) }}" class="w-full border-gray-300 rounded-lg text-sm" placeholder="e.g. 200mg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Agent</label>
                    <input type="text" name="maintenance_agent" value="{{ old('maintenance_agent', $record?->maintenance_agent) }}" class="w-full border-gray-300 rounded-lg text-sm" placeholder="e.g. Sevoflurane">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Muscle Relaxant</label>
                    <input type="text" name="muscle_relaxant" value="{{ old('muscle_relaxant', $record?->muscle_relaxant) }}" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reversal Agent</label>
                    <input type="text" name="reversal_agent" value="{{ old('reversal_agent', $record?->reversal_agent) }}" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Regional Technique</label>
                    <input type="text" name="regional_technique" value="{{ old('regional_technique', $record?->regional_technique) }}" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Spinal L3-4, etc.">
                </div>
            </div>
        </div>

        {{-- Fluid & Output --}}
        <div class="bg-white rounded-lg shadow-sm mb-6 p-6">
            <h4 class="text-md font-medium text-gray-800 mb-4"><i class="fas fa-tint mr-2 text-blue-500"></i>Fluids & Output</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IV Fluids</label>
                    <textarea name="iv_fluids" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('iv_fluids', $record?->iv_fluids) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Est. Blood Loss (ml)</label>
                    <input type="number" name="estimated_blood_loss_ml" value="{{ old('estimated_blood_loss_ml', $record?->estimated_blood_loss_ml) }}" min="0" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Urine Output (ml)</label>
                    <input type="number" name="urine_output_ml" value="{{ old('urine_output_ml', $record?->urine_output_ml) }}" min="0" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
            </div>
        </div>

        {{-- Timings & Events --}}
        <div class="bg-white rounded-lg shadow-sm mb-6 p-6">
            <h4 class="text-md font-medium text-gray-800 mb-4"><i class="fas fa-clock mr-2 text-yellow-500"></i>Timings & Events</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Induction Time</label>
                    <input type="datetime-local" name="induction_time" value="{{ old('induction_time', $record?->induction_time?->format('Y-m-d\TH:i')) }}" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Intubation Time</label>
                    <input type="datetime-local" name="intubation_time" value="{{ old('intubation_time', $record?->intubation_time?->format('Y-m-d\TH:i')) }}" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Extubation Time</label>
                    <input type="datetime-local" name="extubation_time" value="{{ old('extubation_time', $record?->extubation_time?->format('Y-m-d\TH:i')) }}" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Intra-op Medications</label>
                    <textarea name="intra_op_medications" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('intra_op_medications', $record?->intra_op_medications) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Intra-op Events/Complications</label>
                    <textarea name="intra_op_events" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('intra_op_events', $record?->intra_op_events) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Recovery --}}
        <div class="bg-white rounded-lg shadow-sm mb-6 p-6">
            <h4 class="text-md font-medium text-gray-800 mb-4"><i class="fas fa-heartbeat mr-2 text-red-500"></i>Recovery</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recovery Status</label>
                    <select name="recovery_status" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        @foreach(['awake', 'drowsy', 'intubated'] as $rs)
                            <option value="{{ $rs }}" {{ old('recovery_status', $record?->recovery_status) == $rs ? 'selected' : '' }}>{{ ucfirst($rs) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pain Management Plan</label>
                    <textarea name="pain_management_plan" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('pain_management_plan', $record?->pain_management_plan) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Post-Op Instructions</label>
                    <textarea name="post_op_instructions" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('post_op_instructions', $record?->post_op_instructions) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ route('ot.surgeries.show', $surgery) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
                <i class="fas fa-save mr-2"></i>{{ $record ? 'Update' : 'Save' }} Record
            </button>
        </div>
    </form>
</div>
@endsection
