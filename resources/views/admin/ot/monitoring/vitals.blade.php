@extends('admin.layout')

@section('title', 'Intra-Op Vitals — ' . $surgery->surgery_number)
@section('page-title', 'Intra-Operative Vitals')

@push('scripts')
@vite(['resources/js/operative-vitals.js'])
@endpush

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

    {{-- Vitals Chart --}}
    <div class="bg-white rounded-lg shadow-sm mb-6 p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Vitals Trend</h4>
        <div id="vitals-chart" data-url="{{ route('ot.monitoring.vitals-data', $surgery) }}" class="h-64"></div>
        @if($surgery->operativeVitals->isEmpty())
            <p class="text-center text-gray-400 text-sm py-8">No vitals recorded yet. Add entries below.</p>
        @endif
    </div>

    {{-- Vitals Table --}}
    @if($surgery->operativeVitals->count() > 0)
    <div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-gray-500">Time</th>
                        <th class="px-3 py-2 text-left text-gray-500">BP</th>
                        <th class="px-3 py-2 text-left text-gray-500">HR</th>
                        <th class="px-3 py-2 text-left text-gray-500">SpO2</th>
                        <th class="px-3 py-2 text-left text-gray-500">EtCO2</th>
                        <th class="px-3 py-2 text-left text-gray-500">RR</th>
                        <th class="px-3 py-2 text-left text-gray-500">Temp</th>
                        <th class="px-3 py-2 text-left text-gray-500">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($surgery->operativeVitals as $v)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $v->recorded_at?->format('H:i') }}</td>
                        <td class="px-3 py-2">{{ $v->blood_pressure_systolic }}/{{ $v->blood_pressure_diastolic }}</td>
                        <td class="px-3 py-2">{{ $v->heart_rate ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $v->spo2 ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $v->etco2 ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $v->respiratory_rate ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $v->temperature ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $v->notes ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Add New Vitals Entry --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Record Vitals Entry</h4>
        <form action="{{ route('ot.monitoring.store-vitals', $surgery) }}" method="POST">
            @csrf
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Time *</label>
                    <input type="datetime-local" name="recorded_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Systolic</label>
                    <input type="text" name="blood_pressure_systolic" class="w-full border-gray-300 rounded-lg text-sm" placeholder="120">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Diastolic</label>
                    <input type="text" name="blood_pressure_diastolic" class="w-full border-gray-300 rounded-lg text-sm" placeholder="80">
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
                    <label class="block text-xs font-medium text-gray-500 mb-1">EtCO2</label>
                    <input type="text" name="etco2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="35">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Resp. Rate</label>
                    <input type="text" name="respiratory_rate" class="w-full border-gray-300 rounded-lg text-sm" placeholder="14">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Temp (°C)</label>
                    <input type="text" name="temperature" class="w-full border-gray-300 rounded-lg text-sm" placeholder="36.5">
                </div>
                <div class="col-span-2 md:col-span-4">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                    <input type="text" name="notes" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Optional notes">
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
