<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
    <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
        <option value="">Select Patient</option>
        @foreach($patients as $patient)
        <option value="{{ $patient->id }}" {{ (old('patient_id') == $patient->id || request('patient_id') == $patient->id) ? 'selected' : '' }}>
            {{ $patient->name }} ({{ $patient->patient_no }}) — {{ $patient->phone }}
        </option>
        @endforeach
    </select>
</div>
