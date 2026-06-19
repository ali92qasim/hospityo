@extends('admin.layout')

@section('title', 'Surgical Checklist — ' . $surgery->surgery_number)
@section('page-title', 'WHO Surgical Safety Checklist')
@section('page-description', $surgery->procedure_name . ' — ' . $surgery->patient?->name)

@push('scripts')
@vite(['resources/js/surgical-checklist.js'])
@endpush

@section('content')
<div class="max-w-4xl mx-auto" id="checklist-container"
     data-toggle-url="{{ route('ot.checklist.toggle-item', ['item' => '__ITEM_ID__']) }}"
     data-complete-phase-url="{{ route('ot.checklist.complete-phase', $checklist) }}"
     data-csrf="{{ csrf_token() }}">

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

    {{-- Surgery Info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-blue-800">{{ $surgery->surgery_number }} — {{ $surgery->procedure_name }}</p>
                <p class="text-xs text-blue-600">Patient: {{ $surgery->patient?->name }} · Surgeon: Dr. {{ $surgery->doctor?->name }} · {{ $surgery->scheduled_date?->format('d M Y') }}</p>
            </div>
            <a href="{{ route('ot.surgeries.show', $surgery) }}" class="text-sm text-blue-700 hover:text-blue-900">
                <i class="fas fa-arrow-left mr-1"></i>Back to Surgery
            </a>
        </div>
    </div>

    {{-- Overall Status --}}
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-shield-alt text-lg text-green-600"></i>
                <div>
                    <p class="text-sm font-medium text-gray-800">Checklist Status</p>
                    @php
                        $statusLabels = [
                            'incomplete'     => ['Incomplete', 'text-yellow-600'],
                            'sign_in_done'   => ['Sign In Complete', 'text-blue-600'],
                            'time_out_done'  => ['Time Out Complete', 'text-indigo-600'],
                            'completed'      => ['All Phases Complete', 'text-green-600'],
                        ];
                        $sl = $statusLabels[$checklist->status] ?? ['Unknown', 'text-gray-600'];
                    @endphp
                    <p class="text-xs {{ $sl[1] }} font-medium">{{ $sl[0] }}</p>
                </div>
            </div>
            @if($checklist->status === 'completed')
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">
                    <i class="fas fa-check-circle mr-1"></i>Cleared
                </span>
            @endif
        </div>
    </div>

    {{-- Phase 1: Sign In --}}
    @php $signInProgress = $checklist->getPhaseProgress('sign_in'); @endphp
    <div class="bg-white rounded-lg shadow-sm mb-6" id="phase-sign_in">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                <h4 class="text-md font-medium text-gray-800">Sign In</h4>
                <span class="text-xs text-gray-500">(Before induction of anaesthesia)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500" id="progress-sign_in">{{ $signInProgress['checked'] }}/{{ $signInProgress['total'] }}</span>
                @if($checklist->sign_in_completed_at)
                    <span class="bg-green-100 text-green-700 px-2 py-0.5 text-xs rounded-full"><i class="fas fa-check mr-1"></i>Done</span>
                @elseif($signInProgress['checked'] === $signInProgress['total'])
                    <button type="button" class="complete-phase-btn bg-blue-600 text-white px-3 py-1 rounded-lg text-xs hover:bg-blue-700" data-phase="sign_in">
                        <i class="fas fa-check-double mr-1"></i>Confirm Phase
                    </button>
                @endif
            </div>
        </div>
        <div class="p-4 space-y-2">
            @foreach($phases['sign_in'] as $item)
            <label class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer checklist-item" data-item-id="{{ $item->id }}">
                <input type="checkbox" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500 checklist-checkbox"
                       data-item-id="{{ $item->id }}" {{ $item->is_checked ? 'checked' : '' }}
                       {{ $checklist->sign_in_completed_at ? 'disabled' : '' }}>
                <div class="flex-1">
                    <span class="text-sm text-gray-800 {{ $item->is_checked ? 'line-through text-gray-400' : '' }}">{{ $item->label }}</span>
                    @if($item->checked_at)
                        <p class="text-xs text-gray-400 mt-0.5">Checked by {{ $item->checkedByUser?->name ?? 'Unknown' }} · {{ $item->checked_at->format('H:i') }}</p>
                    @endif
                </div>
            </label>
            @endforeach
        </div>
    </div>

    {{-- Phase 2: Time Out --}}
    @php $timeOutProgress = $checklist->getPhaseProgress('time_out'); @endphp
    <div class="bg-white rounded-lg shadow-sm mb-6" id="phase-time_out">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                <h4 class="text-md font-medium text-gray-800">Time Out</h4>
                <span class="text-xs text-gray-500">(Before skin incision)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500" id="progress-time_out">{{ $timeOutProgress['checked'] }}/{{ $timeOutProgress['total'] }}</span>
                @if($checklist->time_out_completed_at)
                    <span class="bg-green-100 text-green-700 px-2 py-0.5 text-xs rounded-full"><i class="fas fa-check mr-1"></i>Done</span>
                @elseif($timeOutProgress['checked'] === $timeOutProgress['total'] && $checklist->sign_in_completed_at)
                    <button type="button" class="complete-phase-btn bg-indigo-600 text-white px-3 py-1 rounded-lg text-xs hover:bg-indigo-700" data-phase="time_out">
                        <i class="fas fa-check-double mr-1"></i>Confirm Phase
                    </button>
                @endif
            </div>
        </div>
        <div class="p-4 space-y-2">
            @foreach($phases['time_out'] as $item)
            <label class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer checklist-item" data-item-id="{{ $item->id }}">
                <input type="checkbox" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 checklist-checkbox"
                       data-item-id="{{ $item->id }}" {{ $item->is_checked ? 'checked' : '' }}
                       {{ $checklist->time_out_completed_at ? 'disabled' : '' }}>
                <div class="flex-1">
                    <span class="text-sm text-gray-800 {{ $item->is_checked ? 'line-through text-gray-400' : '' }}">{{ $item->label }}</span>
                    @if($item->checked_at)
                        <p class="text-xs text-gray-400 mt-0.5">Checked by {{ $item->checkedByUser?->name ?? 'Unknown' }} · {{ $item->checked_at->format('H:i') }}</p>
                    @endif
                </div>
            </label>
            @endforeach
        </div>
    </div>

    {{-- Phase 3: Sign Out --}}
    @php $signOutProgress = $checklist->getPhaseProgress('sign_out'); @endphp
    <div class="bg-white rounded-lg shadow-sm mb-6" id="phase-sign_out">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-green-100 text-green-700 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                <h4 class="text-md font-medium text-gray-800">Sign Out</h4>
                <span class="text-xs text-gray-500">(Before patient leaves operating room)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500" id="progress-sign_out">{{ $signOutProgress['checked'] }}/{{ $signOutProgress['total'] }}</span>
                @if($checklist->sign_out_completed_at)
                    <span class="bg-green-100 text-green-700 px-2 py-0.5 text-xs rounded-full"><i class="fas fa-check mr-1"></i>Done</span>
                @elseif($signOutProgress['checked'] === $signOutProgress['total'] && $checklist->time_out_completed_at)
                    <button type="button" class="complete-phase-btn bg-green-600 text-white px-3 py-1 rounded-lg text-xs hover:bg-green-700" data-phase="sign_out">
                        <i class="fas fa-check-double mr-1"></i>Confirm Phase
                    </button>
                @endif
            </div>
        </div>
        <div class="p-4 space-y-2">
            @foreach($phases['sign_out'] as $item)
            <label class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer checklist-item" data-item-id="{{ $item->id }}">
                <input type="checkbox" class="mt-0.5 rounded text-green-600 focus:ring-green-500 checklist-checkbox"
                       data-item-id="{{ $item->id }}" {{ $item->is_checked ? 'checked' : '' }}
                       {{ $checklist->sign_out_completed_at ? 'disabled' : '' }}>
                <div class="flex-1">
                    <span class="text-sm text-gray-800 {{ $item->is_checked ? 'line-through text-gray-400' : '' }}">{{ $item->label }}</span>
                    @if($item->checked_at)
                        <p class="text-xs text-gray-400 mt-0.5">Checked by {{ $item->checkedByUser?->name ?? 'Unknown' }} · {{ $item->checked_at->format('H:i') }}</p>
                    @endif
                </div>
            </label>
            @endforeach
        </div>
    </div>
</div>
@endsection
