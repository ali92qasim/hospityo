@extends('admin.layout')

@section('title', 'Doctor Share Reports')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Doctor Share Reports</h1>
    <a href="{{ route('doctor-share.reports.print') . '?' . http_build_query(request()->query()) }}"
       target="_blank"
       class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
        <i class="fas fa-print mr-2"></i>Print Report
    </a>
</div>

{{-- Filter bar --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('doctor-share.reports.index') }}" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Doctor</label>
            <select name="doctor_id" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All Doctors</option>
                @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                        {{ $doctor->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bill Type</label>
            <select name="bill_type" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All Types</option>
                <option value="opd" {{ request('bill_type') == 'opd' ? 'selected' : '' }}>OPD</option>
                <option value="ipd" {{ request('bill_type') == 'ipd' ? 'selected' : '' }}>IPD</option>
                <option value="investigation" {{ request('bill_type') == 'investigation' ? 'selected' : '' }}>Investigation</option>
                <option value="emergency" {{ request('bill_type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
            @if(request('doctor_id') || request('date_from') || request('date_to') || request('bill_type'))
                <a href="{{ route('doctor-share.reports.index') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2">
                    Clear
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Summary table --}}
<div class="bg-white rounded-lg shadow mb-6">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Earned</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Collected</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pending</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Settled</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($summary as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $row->doctor->name ?? '— Global —' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ currency_symbol() }}{{ number_format($row->total_earned, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ currency_symbol() }}{{ number_format($row->total_collected, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ currency_symbol() }}{{ number_format($row->total_pending, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ currency_symbol() }}{{ number_format($row->total_settled, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        No data for the selected filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Detail section --}}
<div class="bg-white rounded-lg shadow mt-6">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Share Item Detail</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Share Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collected</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($details as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $item->doctor->name ?? '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $item->bill->bill_number ?? '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($item->bill && $item->bill->bill_date)
                            {{ $item->bill->bill_date->format('M d, Y') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ currency_symbol() }}{{ number_format($item->base_amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ currency_symbol() }}{{ number_format($item->share_amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ currency_symbol() }}{{ number_format($item->allocations_sum_amount ?? 0, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($item->status === 'pending')
                            <span class="text-xs px-2 py-1 rounded capitalize bg-yellow-100 text-yellow-800">{{ $item->status }}</span>
                        @elseif($item->status === 'settled')
                            <span class="text-xs px-2 py-1 rounded capitalize bg-green-100 text-green-800">{{ $item->status }}</span>
                        @elseif($item->status === 'voided')
                            <span class="text-xs px-2 py-1 rounded capitalize bg-red-100 text-red-800">{{ $item->status }}</span>
                        @else
                            <span class="text-xs px-2 py-1 rounded capitalize bg-gray-100 text-gray-800">{{ $item->status }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        No share items found for the selected filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $details->links() }}
    </div>
</div>
@endsection
