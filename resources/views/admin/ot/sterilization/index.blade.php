@extends('admin.layout')

@section('title', 'Sterilization Logs')
@section('page-title', 'Sterilization & Infection Control')
@section('page-description', 'Track and verify sterilization of theatres and instruments')

@section('content')
<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <h1 class="text-2xl font-bold text-gray-800">Sterilization Logs</h1>
    <div class="flex gap-2">
        @if($pendingCount > 0)
        <span class="bg-yellow-100 text-yellow-700 px-3 py-2 rounded-lg text-sm">
            <i class="fas fa-clock mr-1"></i>{{ $pendingCount }} Scheduled
        </span>
        @endif
        <a href="{{ route('ot.sterilization.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
            <i class="fas fa-plus mr-2"></i>New Sterilization
        </a>
    </div>
</div>

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

{{-- Filters --}}
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">All</option>
                @foreach(['scheduled', 'in_progress', 'completed', 'failed'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Method</label>
            <select name="method" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">All</option>
                @foreach(\App\Models\SterilizationLog::METHODS as $key => $label)
                    <option value="{{ $key }}" {{ request('method') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Target</label>
            <select name="target_type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">All</option>
                @foreach(\App\Models\SterilizationLog::TARGET_TYPES as $key => $label)
                    <option value="{{ $key }}" {{ request('target_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-filter mr-1"></i>Filter</button>
        <a href="{{ route('ot.sterilization.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Clear</a>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Log #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performed By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verified</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $log->log_number }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        @if($log->target_type === 'theatre')
                            <i class="fas fa-door-open text-gray-400 mr-1"></i>{{ $log->theatre?->name ?? 'Theatre' }}
                        @elseif($log->target_type === 'individual_instrument')
                            <i class="fas fa-tools text-gray-400 mr-1"></i>{{ $log->consumable?->name ?? 'Instrument' }}
                        @else
                            <i class="fas fa-briefcase-medical text-gray-400 mr-1"></i>{{ $log->instrument_set_name ?? 'Set' }}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ \App\Models\SterilizationLog::METHODS[$log->method] ?? $log->method }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @php
                            $sc = [
                                'scheduled'   => 'bg-yellow-100 text-yellow-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                'completed'   => 'bg-green-100 text-green-800',
                                'failed'      => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $sc[$log->status] ?? 'bg-gray-100' }}">
                            {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $log->performedByUser?->name ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($log->verified_by)
                            <i class="fas fa-check-circle text-green-500" title="Verified"></i>
                        @else
                            <i class="fas fa-minus-circle text-gray-300" title="Not verified"></i>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->created_at?->format('d M Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('ot.sterilization.show', $log) }}" class="text-medical-blue hover:text-blue-700"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-shield-virus text-4xl text-gray-300 mb-3"></i>
                        <p>No sterilization logs yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
