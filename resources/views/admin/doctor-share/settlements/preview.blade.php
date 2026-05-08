@extends('admin.layout')

@section('title', 'New Settlement')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">New Settlement</h1>
    <a href="{{ route('doctor-share.settlements.index') }}" class="text-medical-blue hover:text-blue-700 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i>Back to Settlements
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        {{ session('error') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="{{ route('doctor-share.settlements.preview') }}" class="flex flex-wrap gap-4 items-end">
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
            <input type="date" name="date_from" required
                   value="{{ request('date_from') }}"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
            <input type="date" name="date_to" required
                   value="{{ request('date_to') }}"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-1"></i>Preview
            </button>
        </div>
    </form>
</div>

@if(!$hasItems && (request('date_from') || request('date_to')))
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg mb-6">
        No eligible pending items found for the selected scope and date range.
    </div>
@endif

@if($hasItems)
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Share Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collected Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($eligibleItems as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $item->doctor->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $item->bill->bill_number ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ currency_symbol() }}{{ number_format($item->share_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ currency_symbol() }}{{ number_format($item->allocations()->sum('amount'), 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="2" class="px-6 py-3 text-sm font-medium text-gray-700">
                            Total: {{ $eligibleItems->count() }} items
                        </td>
                        <td colspan="2" class="px-6 py-3 text-sm font-medium text-gray-700">
                            {{ currency_symbol() }}{{ number_format($eligibleItems->sum('share_amount'), 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('doctor-share.settlements.store') }}" class="flex items-center gap-4">
        @csrf
        <input type="hidden" name="doctor_id" value="{{ request('doctor_id') }}">
        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
        <button type="submit" class="bg-medical-green text-white px-6 py-2 rounded-lg hover:bg-green-700">
            Confirm Settlement
        </button>
        <a href="{{ route('doctor-share.settlements.index') }}" class="text-gray-500 hover:text-gray-700">
            Cancel
        </a>
    </form>
@endif
@endsection
