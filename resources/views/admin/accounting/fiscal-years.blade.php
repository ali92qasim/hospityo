@extends('admin.layout')

@section('title', 'Fiscal Years')
@section('page-title', 'Fiscal Years')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Fiscal Year Periods</h3>
        <p class="text-sm text-gray-600 mt-1">Manage accounting periods. Closing a fiscal year permanently locks it from new entries.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Closed By</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($fiscalYears as $fy)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $fy->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $fy->start_date->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $fy->end_date->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-sm text-center">
                        @if($fy->is_closed)
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Closed</span>
                        @elseif($fy->is_active)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        @if($fy->is_closed && $fy->closed_at)
                            {{ \App\Models\User::find($fy->closed_by)?->name ?? '—' }}
                            <span class="text-xs text-gray-400 block">{{ $fy->closed_at->format('M d, Y h:i A') }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-center">
                        @if(!$fy->is_closed)
                            <a href="{{ route('accounting.fiscal-years.pre-close', $fy) }}"
                               class="text-red-600 hover:text-red-800 text-xs font-medium">
                                <i class="fas fa-lock mr-1"></i>Close Period
                            </a>
                        @else
                            <span class="text-xs text-gray-400">Locked</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
