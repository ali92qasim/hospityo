@extends('admin.layout')

@section('title', 'Shift Swap Requests')
@section('page-title', 'Shift Swap Requests')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Shift Swap Requests</h3>
                <p class="text-sm text-gray-600">Review and manage shift swap requests between employees</p>
            </div>
            <a href="{{ route('hr.shifts.roster') }}" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to Roster
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Swap Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester's Shift</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target's Shift</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($swapRequests ?? [] as $request)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($request->requester->photo ?? false)
                                <img src="{{ asset('storage/' . $request->requester->photo) }}" alt="{{ $request->requester->full_name }}" class="w-8 h-8 rounded-full object-cover mr-2">
                            @else
                                <div class="w-8 h-8 bg-medical-blue rounded-full flex items-center justify-center text-white text-xs font-medium mr-2">
                                    {{ strtoupper(substr($request->requester->first_name, 0, 1) . substr($request->requester->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="text-sm font-medium text-gray-900">{{ $request->requester->full_name }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($request->target->photo ?? false)
                                <img src="{{ asset('storage/' . $request->target->photo) }}" alt="{{ $request->target->full_name }}" class="w-8 h-8 rounded-full object-cover mr-2">
                            @else
                                <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center text-white text-xs font-medium mr-2">
                                    {{ strtoupper(substr($request->target->first_name, 0, 1) . substr($request->target->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="text-sm font-medium text-gray-900">{{ $request->target->full_name }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ \Carbon\Carbon::parse($request->swap_date)->format('M d, Y') }}
                        <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($request->swap_date)->format('l') }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($request->requesterShift)
                            <div class="flex items-center gap-1.5">
                                <span class="inline-block w-3 h-3 rounded-full border border-gray-200" style="background-color: {{ $request->requesterShift->color }};"></span>
                                <span class="text-sm text-gray-900">{{ $request->requesterShift->name }}</span>
                            </div>
                        @else
                            <span class="text-sm text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($request->targetShift)
                            <div class="flex items-center gap-1.5">
                                <span class="inline-block w-3 h-3 rounded-full border border-gray-200" style="background-color: {{ $request->targetShift->color }};"></span>
                                <span class="text-sm text-gray-900">{{ $request->targetShift->name }}</span>
                            </div>
                        @else
                            <span class="text-sm text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 max-w-[200px] truncate" title="{{ $request->reason }}">
                            {{ $request->reason ?? '—' }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusBadges = [
                                'pending'  => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                            ];
                            $badge = $statusBadges[$request->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $badge }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium">
                        @if($request->status === 'pending')
                            <div class="flex items-center gap-2">
                                <form action="{{ route('hr.shifts.approve-swap', $request) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition-colors"
                                            onclick="return confirm('Approve this shift swap request?')">
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                </form>
                                <form action="{{ route('hr.shifts.reject-swap', $request) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors"
                                            onclick="return confirm('Reject this shift swap request?')">
                                        <i class="fas fa-times mr-1"></i>Reject
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-exchange-alt text-4xl mb-4 text-gray-300"></i>
                        <p>No shift swap requests found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(($swapRequests ?? collect())->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $swapRequests->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
