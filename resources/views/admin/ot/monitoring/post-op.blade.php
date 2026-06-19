@extends('admin.layout')

@section('title', 'Post-Op Monitoring — ' . $surgery->surgery_number)
@section('page-title', 'Post-Operative Monitoring')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm font-medium text-blue-800">{{ $surgery->surgery_number }} — {{ $surgery->procedure_name }}</p>
                <p class="text-xs text-blue-600">Patient: {{ $surgery->patient?->name }}</p>
            </div>
            <a href="{{ route('ot.surgeries.show', $surgery) }}" class="text-sm text-blue-700 hover:text-blue-900"><i class="fas fa-arrow-left mr-1"></i>Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>
    @endif

    {{-- Previous entries --}}
    @if($surgery->postOpMonitoring->count() > 0)
    <div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700">Monitoring History ({{ $surgery->postOpMonitoring->count() }} entries)</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-gray-500">Time</th>
                        <th class="px-3 py-2 text-left text-gray-500">Phase</th>
                        <th class="px-3 py-2 text-left text-gray-500">AVPU</th>
                        <th class="px-3 py-2 text-left text-gray-500">BP</th>
                        <th class="px-3 py-2 text-left text-gray-500">HR</th>
                        <th class="px-3 py-2 text-left text-gray-500">SpO2</th>
                        <th class="px-3 py-2 text-left text-gray-500">Pain</th>
                        <th class="px-3 py-2 text-left text-gray-500">N/V</th>
                        <th class="px-3 py-2 text-left text-gray-500">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($surgery->postOpMonitoring as $entry)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $entry->recorded_at?->format('d M H:i') }}</td>
                        <td class="px-3 py-2"><span class="px-1.5 py-0.5 rounded text-xs {{ $entry->phase === 'pacu' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ strtoupper($entry->phase) }}</span></td>
                        <td class="px-3 py-2 capitalize">{{ $entry->consciousness_level ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $entry->blood_pressure ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $entry->heart_rate ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $entry->spo2 ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $entry->pain_score ? $entry->pain_score . '/10' : '—' }}</td>
                        <td class="px-3 py-2 capitalize">{{ $entry->nausea_vomiting ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-400">{{ $entry->recordedByUser?->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Add New Entry --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Add Monitoring Entry</h4>
        <form action="{{ route('ot.monitoring.store-post-op', $surgery) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Time *</label>
                    <input type="datetime-local" name="recorded_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Phase *</label>
                    <select name="phase" required class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="pacu">PACU (Recovery Room)</option>
                        <option value="ward">Ward</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Consciousness (AVPU)</label>
                    <select name="consciousness_level" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        <option value="alert">Alert</option>
                        <option value="verbal">Responds to Verbal</option>
                        <option value="pain">Responds to Pain</option>
                        <option value="unresponsive">Unresponsive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Blood Pressure</label>
                    <input type="text" name="blood_pressure" class="w-full border-gray-300 rounded-lg text-sm" placeholder="120/80">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Heart Rate</label>
                    <input type="text" name="heart_rate" class="w-full border-gray-300 rounded-lg text-sm" placeholder="72">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">SpO2 (%)</label>
                    <input type="text" name="spo2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="98">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Resp. Rate</label>
                    <input type="text" name="respiratory_rate" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Temperature</label>
                    <input type="text" name="temperature" class="w-full border-gray-300 rounded-lg text-sm" placeholder="36.5">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Pain Score (0-10)</label>
                    <input type="text" name="pain_score" class="w-full border-gray-300 rounded-lg text-sm" placeholder="3">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Nausea/Vomiting</label>
                    <select name="nausea_vomiting" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        <option value="none">None</option>
                        <option value="mild">Mild</option>
                        <option value="moderate">Moderate</option>
                        <option value="severe">Severe</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Wound Status</label>
                    <input type="text" name="wound_status" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Dry, oozing, etc.">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Drain Output</label>
                    <input type="text" name="drain_output" class="w-full border-gray-300 rounded-lg text-sm" placeholder="50ml serous">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Medications Given</label>
                    <input type="text" name="medications_given" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Paracetamol 1g IV, Ondansetron 4mg IV">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border-gray-300 rounded-lg text-sm"></textarea>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-plus mr-1"></i>Add Entry
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
