@extends('admin.layout')

@section('title', 'Outstanding Bills Report')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Outstanding Bills Report</h1>
            <p class="text-gray-600 mt-1">Unpaid and partially paid bills with aging analysis</p>
        </div>
        <button onclick="window.print()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex items-end gap-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Aging Period</label>
            <select name="aging" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="all" {{ $agingPeriod == 'all' ? 'selected' : '' }}>All Outstanding</option>
                <option value="30" {{ $agingPeriod == '30' ? 'selected' : '' }}>30+ Days Old</option>
                <option value="60" {{ $agingPeriod == '60' ? 'selected' : '' }}>60+ Days Old</option>
                <option value="90" {{ $agingPeriod == '90' ? 'selected' : '' }}>90+ Days Old</option>
            </select>
        </div>
        <button type="submit" class="bg-medical-blue text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Outstanding</p>
                <p class="text-2xl font-bold text-red-600">₨{{ number_format($summary['total_outstanding'], 2) }}</p>
            </div>
            <div class="bg-red-100 rounded-full p-3">
                <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Bills</p>
                <p class="text-2xl font-bold text-gray-800">{{ $summary['total_bills'] }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Partially Paid</p>
                <p class="text-2xl font-bold text-orange-600">{{ $summary['partially_paid'] }}</p>
            </div>
            <div class="bg-orange-100 rounded-full p-3">
                <i class="fas fa-hourglass-half text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Unpaid</p>
                <p class="text-2xl font-bold text-red-600">{{ $summary['unpaid'] }}</p>
            </div>
            <div class="bg-red-100 rounded-full p-3">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Aging Analysis -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <h3 class="text-sm font-medium text-gray-700 mb-2">0-30 Days</h3>
        <p class="text-2xl font-bold text-gray-800">₨{{ number_format($agingAnalysis['0-30']['amount'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $agingAnalysis['0-30']['count'] }} bills</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
        <h3 class="text-sm font-medium text-gray-700 mb-2">31-60 Days</h3>
        <p class="text-2xl font-bold text-gray-800">₨{{ number_format($agingAnalysis['31-60']['amount'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $agingAnalysis['31-60']['count'] }} bills</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
        <h3 class="text-sm font-medium text-gray-700 mb-2">61-90 Days</h3>
        <p class="text-2xl font-bold text-gray-800">₨{{ number_format($agingAnalysis['61-90']['amount'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $agingAnalysis['61-90']['count'] }} bills</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-700">
        <h3 class="text-sm font-medium text-gray-700 mb-2">90+ Days</h3>
        <p class="text-2xl font-bold text-gray-800">₨{{ number_format($agingAnalysis['90+']['amount'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $agingAnalysis['90+']['count'] }} bills</p>
    </div>
</div>

<!-- Top 10 Patients with Outstanding -->
@if($patientOutstanding->count() > 0)
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Top 10 Patients with Outstanding</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bills</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outstanding</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oldest Bill</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($patientOutstanding as $patient)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $patient['patient']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $patient['patient']->patient_id }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $patient['bills'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                        ₨{{ number_format($patient['outstanding'], 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $patient['oldest_bill']->created_at->format('M d, Y') }}
                        <span class="text-xs">({{ $patient['oldest_bill']->created_at->diffForHumans() }})</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Outstanding Bills Details -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Outstanding Bills Details</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outstanding</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($bills as $bill)
                @php
                    $outstanding = $bill->total_amount - $bill->paid_amount;
                    $daysOld = $bill->created_at->diffInDays(now());
                @endphp
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('bills.show', $bill) }}" class="text-medical-blue hover:underline">
                            #{{ str_pad($bill->id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $bill->created_at->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $bill->patient->name }}</div>
                        <div class="text-xs text-gray-500">{{ $bill->patient->patient_id }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ₨{{ number_format($bill->total_amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                        ₨{{ number_format($bill->paid_amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                        ₨{{ number_format($outstanding, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $daysOld <= 30 ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $daysOld > 30 && $daysOld <= 60 ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ $daysOld > 60 && $daysOld <= 90 ? 'bg-red-100 text-red-800' : '' }}
                            {{ $daysOld > 90 ? 'bg-red-200 text-red-900' : '' }}">
                            {{ $daysOld }} days
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $bill->payment_status === 'unpaid' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $bill->payment_status === 'partial' ? 'bg-orange-100 text-orange-800' : '' }}">
                            {{ ucfirst($bill->payment_status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-check-circle text-4xl text-green-300 mb-4"></i>
                        <p>No outstanding bills found!</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($bills->count() > 0)
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-6 py-4 text-right text-sm text-gray-900">Total Outstanding:</td>
                    <td class="px-6 py-4 text-sm text-red-600">₨{{ number_format($summary['total_outstanding'], 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}
</style>
@endpush
@endsection
