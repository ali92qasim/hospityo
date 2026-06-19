@extends('admin.layout')

@section('title', 'Sterilization — ' . $sterilization->log_number)
@section('page-title', 'Sterilization Details')

@push('scripts')
@vite(['resources/js/sterilization-form.js'])
@endpush

@section('content')
<div class="max-w-3xl mx-auto">

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

    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $sterilization->log_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ \App\Models\SterilizationLog::METHODS[$sterilization->method] ?? $sterilization->method }}
                    · {{ \App\Models\SterilizationLog::TARGET_TYPES[$sterilization->target_type] ?? $sterilization->target_type }}
                </p>
            </div>
            @php
                $sc = [
                    'scheduled'   => 'bg-yellow-100 text-yellow-800',
                    'in_progress' => 'bg-blue-100 text-blue-800',
                    'completed'   => 'bg-green-100 text-green-800',
                    'failed'      => 'bg-red-100 text-red-800',
                ];
            @endphp
            <span class="px-3 py-1 text-sm rounded-full {{ $sc[$sterilization->status] ?? 'bg-gray-100' }}">
                {{ ucfirst(str_replace('_', ' ', $sterilization->status)) }}
            </span>
        </div>
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs font-medium text-gray-500">Target</p>
                <p class="text-gray-800">
                    @if($sterilization->target_type === 'theatre')
                        {{ $sterilization->theatre?->name ?? '—' }}
                    @elseif($sterilization->target_type === 'individual_instrument')
                        {{ $sterilization->consumable?->name ?? '—' }}
                    @else
                        {{ $sterilization->instrument_set_name ?? '—' }}
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Cycle Number</p>
                <p class="text-gray-800">{{ $sterilization->cycle_number ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Temperature</p>
                <p class="text-gray-800">{{ $sterilization->temperature ? $sterilization->temperature . '°C' : '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Duration</p>
                <p class="text-gray-800">{{ $sterilization->duration_minutes ? $sterilization->duration_minutes . ' min' : '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Chemical Indicator</p>
                @if($sterilization->chemical_indicator_result)
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $sterilization->chemical_indicator_result === 'pass' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($sterilization->chemical_indicator_result) }}
                    </span>
                @else
                    <p class="text-gray-400">Pending</p>
                @endif
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Biological Indicator</p>
                @if($sterilization->biological_indicator_result)
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $sterilization->biological_indicator_result === 'pass' ? 'bg-green-100 text-green-800' : ($sterilization->biological_indicator_result === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ ucfirst($sterilization->biological_indicator_result) }}
                    </span>
                @else
                    <p class="text-gray-400">Pending</p>
                @endif
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Performed By</p>
                <p class="text-gray-800">{{ $sterilization->performedByUser?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Verified By</p>
                @if($sterilization->verifiedByUser)
                    <p class="text-green-700"><i class="fas fa-check-circle mr-1"></i>{{ $sterilization->verifiedByUser->name }} · {{ $sterilization->verified_at?->format('d M H:i') }}</p>
                @else
                    <p class="text-gray-400">Not yet verified</p>
                @endif
            </div>
            @if($sterilization->notes)
            <div class="md:col-span-2">
                <p class="text-xs font-medium text-gray-500">Notes</p>
                <p class="text-gray-800">{{ $sterilization->notes }}</p>
            </div>
            @endif
            @if($sterilization->failure_reason)
            <div class="md:col-span-2">
                <p class="text-xs font-medium text-red-500">Failure Reason</p>
                <p class="text-red-700">{{ $sterilization->failure_reason }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    @if($sterilization->status === 'scheduled')
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Actions</h4>
        <div class="flex gap-2">
            <form action="{{ route('ot.sterilization.start', $sterilization) }}" method="POST">
                @csrf
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-play mr-1"></i>Start Sterilization
                </button>
            </form>
            <button type="button" id="btn-fail-scheduled" class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 text-sm">
                <i class="fas fa-times mr-1"></i>Mark Failed
            </button>
        </div>
        <div id="form-fail-scheduled" class="hidden mt-4 border-t pt-4">
            <form action="{{ route('ot.sterilization.fail', $sterilization) }}" method="POST">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Failure Reason *</label>
                <textarea name="failure_reason" rows="2" required class="w-full border-gray-300 rounded-lg text-sm mb-3"></textarea>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm">Confirm Failed</button>
            </form>
        </div>
    </div>
    @endif

    @if($sterilization->status === 'in_progress')
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Complete Sterilization</h4>
        <form action="{{ route('ot.sterilization.complete', $sterilization) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chemical Indicator *</label>
                    <select name="chemical_indicator_result" required class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        <option value="pass">Pass</option>
                        <option value="fail">Fail</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Biological Indicator *</label>
                    <select name="biological_indicator_result" required class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        <option value="pass">Pass</option>
                        <option value="fail">Fail</option>
                        <option value="pending">Pending (incubating)</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border-gray-300 rounded-lg text-sm"></textarea>
                </div>
            </div>
            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 text-sm">
                <i class="fas fa-check mr-2"></i>Complete
            </button>
        </form>
    </div>
    @endif

    @if($sterilization->status === 'completed' && !$sterilization->verified_by)
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Verification (Dual Sign-Off)</h4>
        <p class="text-sm text-gray-600 mb-3">A second person must verify this sterilization was performed correctly.</p>
        <form action="{{ route('ot.sterilization.verify', $sterilization) }}" method="POST">
            @csrf
            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 text-sm">
                <i class="fas fa-user-check mr-2"></i>Verify & Sign Off
            </button>
        </form>
    </div>
    @endif

    <div class="flex justify-between">
        <a href="{{ route('ot.sterilization.index') }}" class="text-gray-600 hover:text-gray-800 text-sm">← Back to Logs</a>
    </div>
</div>
@endsection
