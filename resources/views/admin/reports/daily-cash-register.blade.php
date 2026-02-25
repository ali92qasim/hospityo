@extends('admin.layout')

@section('title', 'Daily Cash Register')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Daily Cash Register</h1>
            <p class="text-gray-600 mt-1">Daily collection and payment summary</p>
        </div>
        <button onclick="window.print()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>
</div>

<!-- Date Filter -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex items-end gap-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Date</label>
            <input type="date" name="date" value="{{ $date }}" max="{{ today()->format('Y-m-d') }}" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
        </div>
        <button type="submit" class="bg-medical-blue text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-search mr-2"></i>View Report
        </button>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
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
                <p class="text-sm text-gray-600">Total Amount</p>
                <p class="text-2xl font-bold text-gray-800">₨{{ number_format($summary['total_amount'], 2) }}</p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Collected</p>
                <p class="text-2xl font-bold text-green-600">₨{{ number_format($summary['total_paid'], 2) }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Outstanding</p>
                <p class="text-2xl font-bold text-red-600">₨{{ number_format($summary['total_outstanding'], 2) }}</p>
            </div>
            <div class="bg-red-100 rounded-full p-3">
                <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Payment Method Breakdown -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Payment Method Breakdown</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Cash</span>
                    <i class="fas fa-money-bill-wave text-green-600"></i>
                </div>
                <p class="text-xl font-bold text-gray-800">₨{{ number_format($summary['cash_payments'], 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $payments->where('payment_method', 'cash')->count() }} transactions</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Card</span>
                    <i class="fas fa-credit-card text-blue-600"></i>
                </div>
                <p class="text-xl font-bold text-gray-800">₨{{ number_format($summary['card_payments'], 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $payments->where('payment_method', 'card')->count() }} transactions</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Insurance</span>
                    <i class="fas fa-shield-alt text-purple-600"></i>
                </div>
                <p class="text-xl font-bold text-gray-800">₨{{ number_format($summary['insurance_payments'], 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $payments->where('payment_method', 'insurance')->count() }} transactions</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Other</span>
                    <i class="fas fa-ellipsis-h text-gray-600"></i>
                </div>
                <p class="text-xl font-bold text-gray-800">₨{{ number_format($summary['other_payments'], 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $payments->whereNotIn('payment_method', ['cash', 'card', 'insurance'])->count() }} transactions</p>
            </div>
        </div>
    </div>
</div>

<!-- Payment Transactions -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Payment Transactions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($payments as $payment)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $payment->created_at->format('h:i A') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <a href="{{ route('bills.show', $payment->bill_id) }}" class="text-medical-blue hover:underline">
                            #{{ str_pad($payment->bill_id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $payment->bill->patient->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $payment->payment_method === 'cash' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $payment->payment_method === 'card' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $payment->payment_method === 'insurance' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ !in_array($payment->payment_method, ['cash', 'card', 'insurance']) ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ ucfirst($payment->payment_method) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ₨{{ number_format($payment->amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $payment->reference_number ?? '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                        <p>No payments recorded for this date</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($payments->count() > 0)
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="4" class="px-6 py-4 text-right text-sm text-gray-900">Total Collected:</td>
                    <td class="px-6 py-4 text-sm text-gray-900">₨{{ number_format($summary['total_paid'], 2) }}</td>
                    <td></td>
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
