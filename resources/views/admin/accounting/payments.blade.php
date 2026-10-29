@extends('admin.layout')

@section('title', 'Payments')
@section('page-title', 'Payments')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    {{-- Filter Bar --}}
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-3 mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Payments</h3>
        </div>
        <form method="GET" action="{{ route('accounting.payments') }}" class="flex flex-col sm:flex-row gap-3 items-end">
            <div>
                <label for="payment_type" class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label>
                <select name="payment_type" id="payment_type"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">All</option>
                    <option value="income" @selected(request('payment_type') === 'income')>Income</option>
                    <option value="expense" @selected(request('payment_type') === 'expense')>Expense</option>
                </select>
            </div>
            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <select name="payment_method" id="payment_method"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">All</option>
                    <option value="cash" @selected(request('payment_method') === 'cash')>Cash</option>
                    <option value="bank" @selected(request('payment_method') === 'bank')>Bank</option>
                </select>
            </div>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm min-h-[42px]">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                    <th class="px-4 lg:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Type</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($payments as $payment)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">{{ format_currency($payment->amount) }}</td>
                    <td class="px-4 lg:px-6 py-3 text-sm text-gray-900">{{ $payment->payment_date->format('Y-m-d') }}</td>
                    <td class="px-4 lg:px-6 py-3 text-center">
                        @if($payment->payment_type === 'income')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-arrow-down mr-1"></i> Income
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-arrow-up mr-1"></i> Expense
                            </span>
                        @endif
                    </td>
                    <td class="px-4 lg:px-6 py-3 text-sm text-gray-700">
                        {{ $payment->payment_method === 'cash' ? 'Cash' : 'Bank' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-money-bill-wave text-4xl mb-4 text-gray-300"></i>
                        <p>No payments found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-200">
        {{ $payments->links() }}
    </div>
    @endif
</div>
@endsection
