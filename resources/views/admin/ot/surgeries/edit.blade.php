@extends('admin.layout')

@section('title', 'Edit Surgery — ' . $surgery->surgery_number)
@section('page-title', 'Edit Surgery')

@push('scripts')
@vite(['resources/js/ot-scheduling.js'])
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('ot.surgeries.update', $surgery) }}" method="POST" data-conflict-url="{{ route('ot.check-conflicts') }}" data-surgery-id="{{ $surgery->id }}" data-team-index="{{ $surgery->teamMembers->count() }}">
        @csrf @method('PUT')

        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
        @endif

        {{-- Patient & Surgeon --}}
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800"><i class="fas fa-user-injured mr-2 text-medical-blue"></i>Patient & Surgeon</h4>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient *</label>
                    <select name="patient_id" required class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}" {{ old('patient_id', $surgery->patient_id) == $patient->id ? 'selected' : '' }}>{{ $patient->name }} — {{ $patient->patient_no }}</option>
                        @endforeach
                    </select>
                    @error('patient_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lead Surgeon *</label>
                    <select name="doctor_id" required class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select Surgeon</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ old('doctor_id', $surgery->doctor_id) == $doctor->id ? 'selected' : '' }}>Dr. {{ $doctor->name }}</option>
                        @endforeach
                    </select>
                    @error('doctor_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Procedure Details --}}
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800"><i class="fas fa-procedures mr-2 text-medical-blue"></i>Procedure Details</h4>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Procedure Name *</label>
                    <input type="text" name="procedure_name" value="{{ old('procedure_name', $surgery->procedure_name) }}" required
                        class="w-full border-gray-300 rounded-lg text-sm">
                    @error('procedure_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Surgery Type *</label>
                    <select name="surgery_type" required class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="elective" {{ old('surgery_type', $surgery->surgery_type) == 'elective' ? 'selected' : '' }}>Elective</option>
                        <option value="emergency" {{ old('surgery_type', $surgery->surgery_type) == 'emergency' ? 'selected' : '' }}>Emergency</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Anesthesia Type</label>
                    <select name="anesthesia_type" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        @foreach(['general', 'local', 'spinal', 'epidural', 'sedation'] as $a)
                            <option value="{{ $a }}" {{ old('anesthesia_type', $surgery->anesthesia_type) == $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Procedure Code</label>
                    <input type="text" name="procedure_code" value="{{ old('procedure_code', $surgery->procedure_code) }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pre-Op Diagnosis</label>
                    <textarea name="pre_op_diagnosis" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('pre_op_diagnosis', $surgery->pre_op_diagnosis) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800"><i class="fas fa-calendar mr-2 text-medical-blue"></i>Schedule</h4>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                    <input type="date" name="scheduled_date" value="{{ old('scheduled_date', $surgery->scheduled_date?->format('Y-m-d')) }}" required
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                    <input type="time" name="scheduled_start_time" value="{{ old('scheduled_start_time', $surgery->scheduled_start_time) }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Time (Est.)</label>
                    <input type="time" name="scheduled_end_time" value="{{ old('scheduled_end_time', $surgery->scheduled_end_time) }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Theatre</label>
                    <select name="operation_theatre_id" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        @foreach($theatres as $theatre)
                            <option value="{{ $theatre->id }}" {{ old('operation_theatre_id', $surgery->operation_theatre_id) == $theatre->id ? 'selected' : '' }}>{{ $theatre->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Surgical Team --}}
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800"><i class="fas fa-user-md mr-2 text-medical-blue"></i>Surgical Team</h4>
            </div>
            <div class="p-6" id="teamSection">
                <div class="space-y-3" id="teamRows">
                    @foreach($surgery->teamMembers as $i => $member)
                    <div class="grid grid-cols-12 gap-3 team-row">
                        <div class="col-span-6">
                            <select name="team[{{ $i }}][user_id]" class="w-full border-gray-300 rounded-lg text-sm">
                                <option value="">Select</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ $member->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-4">
                            <select name="team[{{ $i }}][role]" class="w-full border-gray-300 rounded-lg text-sm">
                                @foreach(['assistant_surgeon', 'anesthetist', 'nurse', 'technician'] as $r)
                                    <option value="{{ $r }}" {{ $member->role == $r ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2 flex items-center">
                            <button type="button" onclick="removeTeamRow(this)" class="text-red-400 hover:text-red-600 text-sm"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" onclick="addTeamRow()" class="mt-3 text-sm text-medical-blue hover:text-blue-700">
                    <i class="fas fa-plus mr-1"></i>Add Team Member
                </button>
                {{-- Hidden template for user options --}}
                <select id="userOptionsTemplate" class="hidden">
                    <option value="">Select Team Member</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end space-x-4">
            <a href="{{ route('ot.surgeries.show', $surgery) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
                <i class="fas fa-save mr-2"></i>Update Surgery
            </button>
        </div>
    </form>
</div>

@endsection
