@extends('admin.layout')

@section('title', 'Patient History - Hospital Management System')
@section('page-title', 'Patient History')
@section('page-description', 'Complete medical history')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Patient Header -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-medical-blue rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">{{ $patient->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $patient->patient_no }} • {{ ucfirst($patient->gender) }}, {{ $patient->age }} years</p>
                        <p class="text-sm text-gray-500">{{ $patient->phone }}</p>
                    </div>
                </div>
                <a href="{{ route('patients.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Patients
                </a>
            </div>
        </div>
        
        <!-- Summary Stats -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-blue-600">Total Visits</p>
                            <p class="text-2xl font-semibold text-blue-800">{{ $visits->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-prescription text-green-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-green-600">Prescriptions</p>
                            <p class="text-2xl font-semibold text-green-800">{{ $prescriptions->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-flask text-purple-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-purple-600">Lab Tests</p>
                            <p class="text-2xl font-semibold text-purple-800">{{ $labOrders->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-orange-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-bed text-orange-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-orange-600">Admissions</p>
                            <p class="text-2xl font-semibold text-orange-800">{{ $admissions->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Visit Details -->
    @if($latestVisit)
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-800">Latest Visit Details</h4>
            <p class="text-sm text-gray-600">{{ $latestVisit->visit_datetime->format('M d, Y h:i A') }}</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Visit Information -->
                <div class="space-y-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h5 class="font-medium text-blue-800 mb-2">Visit Information</h5>
                        <div class="space-y-2 text-sm">
                            <div><span class="text-blue-600">Visit No:</span> {{ $latestVisit->visit_no }}</div>
                            <div><span class="text-blue-600">Type:</span> {{ strtoupper($latestVisit->visit_type) }}</div>
                            <div><span class="text-blue-600">Status:</span> 
                                @php
                                    $statusColors = [
                                        'registered' => 'bg-blue-100 text-blue-800',
                                        'vitals_recorded' => 'bg-green-100 text-green-800',
                                        'with_doctor' => 'bg-purple-100 text-purple-800',
                                        'completed' => 'bg-gray-100 text-gray-800',
                                        'admitted' => 'bg-purple-100 text-purple-800',
                                        'discharged' => 'bg-orange-100 text-orange-800'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$latestVisit->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $latestVisit->status)) }}
                                </span>
                            </div>
                            @if($latestVisit->doctor)
                                <div><span class="text-blue-600">Doctor:</span> Dr. {{ $latestVisit->doctor->name }}</div>
                                <div><span class="text-blue-600">Specialization:</span> {{ $latestVisit->doctor->specialization }}</div>
                            @endif
                        </div>
                    </div>
                    
                    @if($latestVisit->vitalSigns)
                    <div class="bg-green-50 rounded-lg p-4">
                        <h5 class="font-medium text-green-800 mb-2">Vital Signs</h5>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            @if($latestVisit->vitalSigns->blood_pressure)
                                <div><span class="text-green-600">BP:</span> {{ $latestVisit->vitalSigns->blood_pressure }}</div>
                            @endif
                            @if($latestVisit->vitalSigns->temperature)
                                <div><span class="text-green-600">Temp:</span> {{ $latestVisit->vitalSigns->temperature }}°F</div>
                            @endif
                            @if($latestVisit->vitalSigns->pulse_rate)
                                <div><span class="text-green-600">Pulse:</span> {{ $latestVisit->vitalSigns->pulse_rate }} bpm</div>
                            @endif
                            @if($latestVisit->vitalSigns->spo2)
                                <div><span class="text-green-600">SpO<sub>2</sub>:</span> {{ $latestVisit->vitalSigns->spo2 }}%</div>
                            @endif
                            @if($latestVisit->vitalSigns->bsr)
                                <div><span class="text-green-600">BSR:</span> {{ $latestVisit->vitalSigns->bsr }}%</div>
                            @endif
                            @if($latestVisit->vitalSigns->weight)
                                <div><span class="text-green-600">Weight:</span> {{ $latestVisit->vitalSigns->weight }} kg</div>
                            @endif
                            @if($latestVisit->vitalSigns->height)
                                <div><span class="text-green-600">Height:</span> {{ $latestVisit->vitalSigns->height }} ft</div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                
                <!-- Consultation Details -->
                <div class="space-y-4">
                    @if($latestVisit->consultation)
                    <div class="bg-purple-50 rounded-lg p-4">
                        <h5 class="font-medium text-purple-800 mb-2">Consultation</h5>
                        <div class="space-y-2 text-sm">
                            @if($latestVisit->consultation->presenting_complaints)
                                <div><span class="text-purple-600">Complaints:</span> {{ $latestVisit->consultation->presenting_complaints }}</div>
                            @endif
                            @if($latestVisit->consultation->examination)
                                <div><span class="text-purple-600">Examination:</span> {{ $latestVisit->consultation->examination }}</div>
                            @endif
                            @if($latestVisit->consultation->provisional_diagnosis_conditions && count($latestVisit->consultation->provisional_diagnosis_conditions) > 0)
                                <div>
                                    <span class="text-purple-600">Conditions:</span>
                                    @php
                                        $conditionLabels = [
                                            'dm' => 'DM',
                                            'htn' => 'HTN',
                                            'ihd' => 'IHD',
                                            'asthma' => 'Asthma'
                                        ];
                                        $selectedLabels = array_map(fn($c) => $conditionLabels[$c] ?? $c, $latestVisit->consultation->provisional_diagnosis_conditions);
                                    @endphp
                                    {{ implode(', ', $selectedLabels) }}
                                </div>
                            @endif
                            @if($latestVisit->consultation->provisional_diagnosis)
                                <div><span class="text-purple-600">Diagnosis:</span> {{ $latestVisit->consultation->provisional_diagnosis }}</div>
                            @endif
                            @if($latestVisit->consultation->allergies && $latestVisit->consultation->allergies->count() > 0)
                                <div>
                                    <span class="text-purple-600">Allergies:</span>
                                    <span class="text-red-600 font-medium">{{ $latestVisit->consultation->allergies->pluck('name')->join(', ') }}</span>
                                </div>
                            @endif
                            @if($latestVisit->consultation->treatment)
                                <div><span class="text-purple-600">Treatment:</span> {{ $latestVisit->consultation->treatment }}</div>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    @if($latestVisit->prescriptions->count() > 0)
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <h5 class="font-medium text-yellow-800 mb-2">Prescriptions</h5>
                        <div class="space-y-2">
                            @foreach($latestVisit->prescriptions as $prescription)
                                <div class="text-sm">
                                    <div class="font-medium text-yellow-700">Prescription #{{ $prescription->id }}</div>
                                    @foreach($prescription->items as $item)
                                        <div class="text-yellow-600 ml-2">
                                            • {{ $item->medicine->name }} - {{ $item->quantity }} {{ $item->medicine->unit }} ({{ $item->dosage }})
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    @if($latestVisit->labOrders->count() > 0)
                    <div class="bg-indigo-50 rounded-lg p-4">
                        <h5 class="font-medium text-indigo-800 mb-2">Lab Tests</h5>
                        <div class="space-y-1">
                            @foreach($latestVisit->labOrders as $labOrder)
                                <div class="text-sm text-indigo-600">
                                    • {{ $labOrder->investigation->name }} 
                                    <span class="text-xs">({{ ucfirst($labOrder->status) }})</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-200">
                <a href="{{ route('visits.workflow', $latestVisit) }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-eye mr-2"></i>View Full Visit Details
                </a>
            </div>
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 text-center">
            <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No visits found for this patient</p>
        </div>
    </div>
    @endif

    <!-- Recent Visits -->
    @if($visits->count() > 0)
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-800">Recent Visits</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Visit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diagnosis</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($visits->take(5) as $visit)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $visit->visit_no }}</div>
                                <div class="text-xs text-gray-500">{{ $visit->visit_datetime->format('M d, Y h:i A') }}</div>
                                <div class="text-xs text-gray-400">{{ strtoupper($visit->visit_type) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($visit->doctor)
                                    <div class="text-sm text-gray-900">Dr. {{ $visit->doctor->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $visit->doctor->specialization }}</div>
                                @else
                                    <span class="text-sm text-gray-400">Not assigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($visit->consultation && ($visit->consultation->provisional_diagnosis_conditions || $visit->consultation->provisional_diagnosis))
                                    @if($visit->consultation->provisional_diagnosis_conditions && count($visit->consultation->provisional_diagnosis_conditions) > 0)
                                        @php
                                            $conditionLabels = ['dm' => 'DM', 'htn' => 'HTN', 'ihd' => 'IHD', 'asthma' => 'Asthma'];
                                            $selectedLabels = array_map(fn($c) => $conditionLabels[$c] ?? $c, $visit->consultation->provisional_diagnosis_conditions);
                                        @endphp
                                        <div class="text-xs text-gray-600 mb-1">{{ implode(', ', $selectedLabels) }}</div>
                                    @endif
                                    @if($visit->consultation->provisional_diagnosis)
                                        <div class="text-sm text-gray-900">{{ Str::limit($visit->consultation->provisional_diagnosis, 50) }}</div>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-400">No diagnosis</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'registered' => 'bg-blue-100 text-blue-800',
                                        'vitals_recorded' => 'bg-green-100 text-green-800',
                                        'with_doctor' => 'bg-purple-100 text-purple-800',
                                        'completed' => 'bg-gray-100 text-gray-800',
                                        'admitted' => 'bg-purple-100 text-purple-800',
                                        'discharged' => 'bg-orange-100 text-orange-800'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$visit->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('visits.workflow', $visit) }}" class="text-medical-blue hover:text-blue-700 text-sm">
                                    <i class="fas fa-eye mr-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection