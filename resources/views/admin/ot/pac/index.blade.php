@extends('admin.layout')

@section('title', 'PAC Requests')
@section('page-title', 'Pre-Anaesthesia Checkups')
@section('page-description', 'Manage pre-anaesthesia clearance requests')

@section('content')
<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <h1 class="text-2xl font-bold text-gray-800">PAC Requests</h1>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

{{-- Filters --}}
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">All</option>
                @foreach(['pending', 'cleared', 'not_cleared', 'requires_further_evaluation'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
            <i class="fas fa-filter mr-1"></i>Filter
        </button>
        <a href="{{ route('ot.pac.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Clear</a>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Surgery</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anaesthetist</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ASA Grade</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($checkups as $pac)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pac->patient?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        <div>{{ $pac->surgery?->procedure_name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $pac->surgery?->surgery_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $pac->anaesthetist?->name ?? 'Unassigned' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $pac->asa_grade ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @php
                            $statusColors = [
                                'pending'    => 'bg-yellow-100 text-yellow-800',
                                'cleared'    => 'bg-green-100 text-green-800',
                                'not_cleared'=> 'bg-red-100 text-red-800',
                                'requires_further_evaluation' => 'bg-orange-100 text-orange-800',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $statusColors[$pac->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $pac->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pac->created_at?->format('d M Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('ot.pac.show', $pac) }}" class="text-medical-blue hover:text-blue-700" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-clipboard-check text-4xl text-gray-300 mb-3"></i>
                        <p>No PAC requests found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($checkups->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $checkups->links() }}
    </div>
    @endif
</div>
@endsection
