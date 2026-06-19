@extends('admin.layout')

@section('title', 'Surgery Details — ' . $surgery->surgery_number)
@section('page-title', 'Surgery Details')

@push('scripts')
@vite(['resources/js/ot-surgery-show.js'])
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Header with actions --}}
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $surgery->surgery_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $surgery->procedure_name }} · {{ ucfirst($surgery->surgery_type) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($surgery->status === 'scheduled')
                    <form action="{{ route('ot.surgeries.start', $surgery) }}" method="POST" class="inline"
                          onsubmit="return confirm('Start this surgery? The OT will be marked as occupied.')">
                        @csrf
                        <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 text-sm">
                            <i class="fas fa-play mr-1"></i>Start Surgery
                        </button>
                    </form>
                    <button type="button" id="postpone-btn" class="bg-orange-100 text-orange-700 px-4 py-2 rounded-lg hover:bg-orange-200 text-sm">
                        <i class="fas fa-clock mr-1"></i>Postpone
                    </button>
                    <a href="{{ route('ot.surgeries.edit', $surgery) }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-sm">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                @endif

                @if($surgery->status === 'in_progress')
                    <button type="button" id="complete-btn"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                        <i class="fas fa-check mr-1"></i>Complete Surgery
                    </button>
                @endif

                @if(!in_array($surgery->status, ['completed', 'cancelled']))
                    <button type="button" id="cancel-btn"
                            class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 text-sm">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                @endif
            </div>
        </div>

        {{-- Status badge --}}
        <div class="px-6 pb-4">
            @php
                $statusColors = [
                    'scheduled'    => 'bg-blue-100 text-blue-800',
                    'in_progress'  => 'bg-yellow-100 text-yellow-800',
                    'completed'    => 'bg-green-100 text-green-800',
                    'cancelled'    => 'bg-red-100 text-red-800',
                    'postponed'    => 'bg-orange-100 text-orange-800',
                ];
            @endphp
            <span class="px-3 py-1 text-sm rounded-full {{ $statusColors[$surgery->status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ ucfirst(str_replace('_', ' ', $surgery->status)) }}
            </span>
            @if($surgery->getDurationMinutes())
                <span class="text-sm text-gray-500 ml-3"><i class="fas fa-clock mr-1"></i>{{ $surgery->getDurationMinutes() }} minutes</span>
            @endif
        </div>
    </div>

    {{-- PAC Status --}}
    @if($surgery->status === 'scheduled' || $surgery->status === 'postponed')
    <div class="bg-white rounded-lg shadow-sm mb-6 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-clipboard-check text-lg text-purple-500"></i>
                <div>
                    <p class="text-sm font-medium text-gray-800">Pre-Anaesthesia Checkup (PAC)</p>
                    @if($surgery->pacCheckup)
                        @php
                            $pacColors = [
                                'pending'    => 'text-yellow-600',
                                'cleared'    => 'text-green-600',
                                'not_cleared'=> 'text-red-600',
                                'requires_further_evaluation' => 'text-orange-600',
                            ];
                        @endphp
                        <p class="text-xs {{ $pacColors[$surgery->pacCheckup->status] ?? 'text-gray-500' }}">
                            Status: {{ ucfirst(str_replace('_', ' ', $surgery->pacCheckup->status)) }}
                            @if($surgery->pacCheckup->cleared_at) · Cleared {{ $surgery->pacCheckup->cleared_at->diffForHumans() }} @endif
                        </p>
                    @else
                        <p class="text-xs text-gray-500">Not yet requested — required before surgery can start</p>
                    @endif
                </div>
            </div>
            <div>
                @if($surgery->pacCheckup)
                    <a href="{{ route('ot.pac.show', $surgery->pacCheckup) }}" class="text-sm text-medical-blue hover:text-blue-700">
                        <i class="fas fa-eye mr-1"></i>View PAC
                    </a>
                @else
                    <a href="{{ route('ot.pac.create', $surgery) }}" class="bg-purple-600 text-white px-3 py-1.5 rounded-lg hover:bg-purple-700 text-sm">
                        <i class="fas fa-plus mr-1"></i>Request PAC
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Surgical Safety Checklist --}}
    <div class="bg-white rounded-lg shadow-sm mb-6 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-shield-alt text-lg text-green-600"></i>
                <div>
                    <p class="text-sm font-medium text-gray-800">WHO Surgical Safety Checklist</p>
                    @if($surgery->surgicalChecklist)
                        @php
                            $clStatus = $surgery->surgicalChecklist->status;
                            $clColors = [
                                'incomplete'    => 'text-yellow-600',
                                'sign_in_done'  => 'text-blue-600',
                                'time_out_done' => 'text-indigo-600',
                                'completed'     => 'text-green-600',
                            ];
                        @endphp
                        <p class="text-xs {{ $clColors[$clStatus] ?? 'text-gray-500' }}">
                            Status: {{ ucfirst(str_replace('_', ' ', $clStatus)) }}
                        </p>
                    @else
                        <p class="text-xs text-gray-500">Not yet started — Sign In required before surgery start</p>
                    @endif
                </div>
            </div>
            <a href="{{ route('ot.checklist.show', $surgery) }}" class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 text-sm">
                <i class="fas fa-clipboard-list mr-1"></i>Open Checklist
            </a>
        </div>
    </div>
    @endif

    {{-- Complete Surgery Form (hidden) --}}
    <div id="complete-form" class="bg-white rounded-lg shadow-sm mb-6 hidden">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-md font-medium text-gray-800">Complete Surgery — Post-Op Notes</h4>
        </div>
        <form action="{{ route('ot.surgeries.complete', $surgery) }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Post-Op Diagnosis</label>
                <textarea name="post_op_diagnosis" rows="2" class="w-full border-gray-300 rounded-lg text-sm"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Procedure Notes</label>
                <textarea name="procedure_notes" rows="3" class="w-full border-gray-300 rounded-lg text-sm"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Complications</label>
                <textarea name="complications" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="None if uneventful"></textarea>
            </div>
            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 text-sm">
                <i class="fas fa-check mr-2"></i>Mark Completed
            </button>
        </form>
    </div>

    {{-- Cancel Surgery Form (hidden) --}}
    <div id="cancel-form" class="bg-white rounded-lg shadow-sm mb-6 hidden">
        <form action="{{ route('ot.surgeries.cancel', $surgery) }}" method="POST" class="p-6">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Cancellation *</label>
            <textarea name="cancelled_reason" rows="2" required class="w-full border-gray-300 rounded-lg text-sm mb-3"
                      placeholder="Why is this surgery being cancelled?"></textarea>
            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 text-sm">
                <i class="fas fa-times mr-2"></i>Confirm Cancel
            </button>
        </form>
    </div>

    {{-- Postpone Surgery Form (hidden) --}}
    @if($surgery->status === 'scheduled')
    <div id="postpone-form" class="bg-white rounded-lg shadow-sm mb-6 hidden">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-md font-medium text-gray-800"><i class="fas fa-clock mr-2 text-orange-500"></i>Postpone Surgery</h4>
        </div>
        <form action="{{ route('ot.surgeries.postpone', $surgery) }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Postponement *</label>
                <textarea name="postponed_reason" rows="2" required class="w-full border-gray-300 rounded-lg text-sm"
                          placeholder="Why is this surgery being postponed?"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Tentative Date (optional)</label>
                <input type="date" name="new_date" class="border-gray-300 rounded-lg text-sm"
                       min="{{ now()->addDay()->format('Y-m-d') }}">
                <p class="text-xs text-gray-500 mt-1">Leave empty if the new date is not yet decided.</p>
            </div>
            <button type="submit" class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 text-sm">
                <i class="fas fa-clock mr-2"></i>Confirm Postpone
            </button>
        </form>
    </div>
    @endif

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Patient --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Patient</h4>
            <p class="text-lg font-medium text-gray-900">{{ $surgery->patient?->name ?? '—' }}</p>
            <p class="text-sm text-gray-600">{{ $surgery->patient?->patient_no }} · {{ $surgery->patient?->phone }}</p>
        </div>

        {{-- Surgeon --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Lead Surgeon</h4>
            <p class="text-lg font-medium text-gray-900">Dr. {{ $surgery->doctor?->name ?? '—' }}</p>
            <p class="text-sm text-gray-600">{{ $surgery->doctor?->specialization ?? '' }}</p>
        </div>

        {{-- Schedule --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Schedule</h4>
            <p class="text-sm text-gray-700"><strong>Date:</strong> {{ $surgery->scheduled_date?->format('d M Y') }}</p>
            <p class="text-sm text-gray-700"><strong>Time:</strong> {{ $surgery->scheduled_start_time ?? '—' }} — {{ $surgery->scheduled_end_time ?? '—' }}</p>
            @if($surgery->actual_start_time)
            <p class="text-sm text-gray-700 mt-2"><strong>Actual Start:</strong> {{ $surgery->actual_start_time->format('d M Y H:i') }}</p>
            @endif
            @if($surgery->actual_end_time)
            <p class="text-sm text-gray-700"><strong>Actual End:</strong> {{ $surgery->actual_end_time->format('d M Y H:i') }}</p>
            @endif
        </div>

        {{-- Theatre & Anesthesia --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Theatre & Anesthesia</h4>
            <p class="text-sm text-gray-700"><strong>Theatre:</strong> {{ $surgery->operationTheatre?->name ?? 'Not assigned' }}</p>
            <p class="text-sm text-gray-700"><strong>Anesthesia:</strong> {{ ucfirst($surgery->anesthesia_type ?? 'Not specified') }}</p>
        </div>
    </div>

    {{-- Diagnosis & Notes --}}
    @if($surgery->pre_op_diagnosis || $surgery->post_op_diagnosis || $surgery->procedure_notes || $surgery->complications)
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Clinical Notes</h4>
        @if($surgery->pre_op_diagnosis)
        <div class="mb-3">
            <p class="text-xs font-medium text-gray-500">Pre-Op Diagnosis</p>
            <p class="text-sm text-gray-800">{{ $surgery->pre_op_diagnosis }}</p>
        </div>
        @endif
        @if($surgery->post_op_diagnosis)
        <div class="mb-3">
            <p class="text-xs font-medium text-gray-500">Post-Op Diagnosis</p>
            <p class="text-sm text-gray-800">{{ $surgery->post_op_diagnosis }}</p>
        </div>
        @endif
        @if($surgery->procedure_notes)
        <div class="mb-3">
            <p class="text-xs font-medium text-gray-500">Procedure Notes</p>
            <p class="text-sm text-gray-800">{{ $surgery->procedure_notes }}</p>
        </div>
        @endif
        @if($surgery->complications)
        <div>
            <p class="text-xs font-medium text-red-500">Complications</p>
            <p class="text-sm text-red-700">{{ $surgery->complications }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Surgical Team --}}
    @if($surgery->teamMembers->count() > 0)
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Surgical Team</h4>
        <div class="space-y-2">
            @foreach($surgery->teamMembers as $member)
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <span class="text-sm text-gray-900">{{ $member->user?->name ?? '—' }}</span>
                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700 capitalize">{{ str_replace('_', ' ', $member->role) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Cancelled Reason --}}
    @if($surgery->status === 'cancelled' && $surgery->cancelled_reason)
    <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
        <h4 class="text-sm font-semibold text-red-600 uppercase mb-2">Cancellation Reason</h4>
        <p class="text-sm text-red-800">{{ $surgery->cancelled_reason }}</p>
    </div>
    @endif

    {{-- Postponed Reason --}}
    @if($surgery->status === 'postponed' && $surgery->postponed_reason)
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-6 mb-6">
        <h4 class="text-sm font-semibold text-orange-600 uppercase mb-2">Postponement Reason</h4>
        <p class="text-sm text-orange-800">{{ $surgery->postponed_reason }}</p>
    </div>
    @endif

    {{-- Consumable Usage --}}
    @if(in_array($surgery->status, ['in_progress', 'completed']))
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-boxes text-lg text-indigo-500"></i>
                <div>
                    <p class="text-sm font-medium text-gray-800">Consumables Used</p>
                    <p class="text-xs text-gray-500">{{ $surgery->consumableUsages?->count() ?? 0 }} item(s) recorded</p>
                </div>
            </div>
            <a href="{{ route('ot.consumables.usage', $surgery) }}" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700 text-sm">
                <i class="fas fa-plus-circle mr-1"></i>Record Usage
            </a>
        </div>
    </div>

    {{-- Operative Monitoring --}}
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Operative Monitoring</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <a href="{{ route('ot.monitoring.anaesthesia', $surgery) }}" class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-purple-50 transition-colors">
                <i class="fas fa-syringe text-purple-500"></i>
                <div>
                    <p class="text-sm font-medium text-gray-800">Anaesthesia Record</p>
                    <p class="text-xs text-gray-500">{{ $surgery->anaesthesiaRecord ? 'Recorded' : 'Not yet recorded' }}</p>
                </div>
            </a>
            <a href="{{ route('ot.monitoring.vitals', $surgery) }}" class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-red-50 transition-colors">
                <i class="fas fa-heartbeat text-red-500"></i>
                <div>
                    <p class="text-sm font-medium text-gray-800">Intra-Op Vitals</p>
                    <p class="text-xs text-gray-500">{{ $surgery->operativeVitals?->count() ?? 0 }} entries</p>
                </div>
            </a>
            <a href="{{ route('ot.monitoring.post-op', $surgery) }}" class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-green-50 transition-colors">
                <i class="fas fa-bed text-green-500"></i>
                <div>
                    <p class="text-sm font-medium text-gray-800">Post-Op Monitoring</p>
                    <p class="text-xs text-gray-500">{{ $surgery->postOpMonitoring?->count() ?? 0 }} entries</p>
                </div>
            </a>
        </div>
    </div>
    @endif

    <div class="flex justify-end">
        <a href="{{ route('ot.surgeries.index') }}" class="text-gray-600 hover:text-gray-800 text-sm">← Back to Surgeries</a>
    </div>
</div>
@endsection
