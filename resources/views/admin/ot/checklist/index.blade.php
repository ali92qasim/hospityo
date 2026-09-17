@extends('admin.layout')

@section('title', 'Surgical Checklists')
@section('page-title', 'Surgical Safety Checklists')
@section('page-description', 'Pending surgeries that still need checklist action')

@section('content')
<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <h1 class="text-2xl font-bold text-gray-800">Checklist</h1>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

{{-- Table --}}
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Surgery number / procedure</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Surgery status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Checklist status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($surgeries as $surgery)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $surgery->patient?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        <div>{{ $surgery->procedure_name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $surgery->surgery_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @php
                            $surgeryStatusColors = [
                                'scheduled'   => 'bg-yellow-100 text-yellow-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $surgeryStatusColors[$surgery->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $surgery->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @php
                            $checklist = $surgery->surgicalChecklist;
                            $checklistStatus = $checklist?->status;
                            $checklistStatusColors = [
                                'incomplete'    => 'bg-yellow-100 text-yellow-800',
                                'sign_in_done'  => 'bg-blue-100 text-blue-800',
                                'time_out_done' => 'bg-indigo-100 text-indigo-800',
                                'completed'     => 'bg-green-100 text-green-800',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $checklistStatusColors[$checklistStatus] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $checklistStatus ? ucfirst(str_replace('_', ' ', $checklistStatus)) : 'Not started' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('ot.checklist.show', $surgery) }}" class="text-medical-blue hover:text-blue-700" title="Open">
                            Open
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                        <p>No surgeries with pending checklist action.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($surgeries->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $surgeries->links() }}
    </div>
    @endif
</div>
@endsection
