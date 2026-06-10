@extends('admin.layout')

@section('title', 'Daily Cash Register')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Daily Cash Register</h1>
            <p class="text-gray-600 mt-1">Cash flow summary — inflows, outflows & closing balance</p>
        </div>
        <button onclick="window.print()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 no-print">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>
</div>

<!-- Date Range Filter -->
<div class="bg-white rounded-lg shadow p-4 mb-6 no-print">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}" max="{{ today()->format('Y-m-d') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}" max="{{ today()->format('Y-m-d') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
        </div>
        <div>
            <button type="submit" class="w-full bg-medical-blue text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Generate Report
            </button>
        </div>
    </form>
</div>

<!-- Cash Flow Summary (Opening → Inflow → Outflow → Closing) -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Cash Flow Summary</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="text-center p-4 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Opening Balance</p>
            <p class="text-2xl font-bold text-gray-800">{{ format_currency($summary['opening_balance']) }}</p>
        </div>
        <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
            <p class="text-xs font-medium text-green-600 uppercase mb-1"><i class="fas fa-arrow-down mr-1"></i>Total Inflows</p>
            <p class="text-2xl font-bold text-green-700">+ {{ format_currency($summary['total_inflows']) }}</p>
        </div>
        <div class="text-center p-4 bg-red-50 rounded-lg border border-red-200">
            <p class="text-xs font-medium text-red-600 uppercase mb-1"><i class="fas fa-arrow-up mr-1"></i>Total Outflows</p>
            <p class="text-2xl font-bold text-red-700">- {{ format_currency($summary['total_outflows']) }}</p>
        </div>
        <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-xs font-medium text-blue-600 uppercase mb-1">Closing Balance</p>
            <p class="text-2xl font-bold text-blue-800">{{ format_currency($summary['closing_balance']) }}</p>
        </div>
    </div>
</div>

<!-- Billing & Collections Overview -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500">Bills Created</p>
        <p class="text-xl font-bold text-gray-800">{{ $summary['total_bills'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500">Total Billed</p>
        <p class="text-xl font-bold text-gray-800">{{ format_currency($summary['total_billed']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500">Total Collected</p>
        <p class="text-xl font-bold text-green-600">{{ format_currency($summary['total_collected']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500">Outstanding</p>
        <p class="text-xl font-bold text-red-600">{{ format_currency($summary['total_outstanding']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500">Discounts Given</p>
        <p class="text-xl font-bold text-yellow-600">{{ format_currency($summary['total_discount']) }}</p>
    </div>
</div>

<!-- Payment Method Breakdown -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Collection by Payment Method</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Cash</span>
                <i class="fas fa-money-bill-wave text-green-600"></i>
            </div>
            <p class="text-xl font-bold text-gray-800">{{ format_currency($summary['cash_payments']) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $payments->where('payment_method', 'cash')->count() }} transactions</p>
        </div>
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Card</span>
                <i class="fas fa-credit-card text-blue-600"></i>
            </div>
            <p class="text-xl font-bold text-gray-800">{{ format_currency($summary['card_payments']) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $payments->where('payment_method', 'card')->count() }} transactions</p>
        </div>
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Insurance</span>
                <i class="fas fa-shield-alt text-purple-600"></i>
            </div>
            <p class="text-xl font-bold text-gray-800">{{ format_currency($summary['insurance_payments']) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $payments->where('payment_method', 'insurance')->count() }} transactions</p>
        </div>
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Other</span>
                <i class="fas fa-ellipsis-h text-gray-600"></i>
            </div>
            <p class="text-xl font-bold text-gray-800">{{ format_currency($summary['other_payments']) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $payments->whereNotIn('payment_method', ['cash', 'card', 'insurance'])->count() }} transactions</p>
        </div>
    </div>
</div>

<!-- Inflows — Payment Transactions -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-arrow-down text-green-500 mr-2"></i>Inflows — Payments Received
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($payments as $payment)
                <tr>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                        {{ $payment->payment_date?->format('d M') ?? $payment->created_at->format('d M') }}
                        <span class="text-xs text-gray-400 ml-1">{{ $payment->created_at->format('h:i A') }}</span>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                        <a href="{{ route('bills.show', $payment->bill_id) }}" class="text-medical-blue hover:underline">
                            {{ $payment->bill?->bill_number ?? '#' . $payment->bill_id }}
                        </a>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $payment->bill?->patient?->name ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                        <span class="px-2 py-0.5 text-xs rounded-full
                            {{ $payment->payment_method === 'cash' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $payment->payment_method === 'card' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $payment->payment_method === 'insurance' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ !in_array($payment->payment_method, ['cash', 'card', 'insurance']) ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ ucfirst($payment->payment_method ?? 'other') }}
                        </span>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-green-700 text-right">
                        + {{ format_currency($payment->amount) }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">{{ $payment->reference_number ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No payments received in this period</td>
                </tr>
                @endforelse
            </tbody>
            @if($payments->count() > 0)
            <tfoot class="bg-green-50 font-semibold">
                <tr>
                    <td colspan="4" class="px-6 py-3 text-right text-sm text-gray-700">Total Inflows:</td>
                    <td class="px-6 py-3 text-right text-sm text-green-700">+ {{ format_currency($summary['total_inflows']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<!-- Outflows — Expenses -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-arrow-up text-red-500 mr-2"></i>Outflows — Expenses & Payments Out
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entry #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
<<<<<<< HEAD
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
=======
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Debited To</th>
>>>>>>> 99bc01c (Fix: daily cash register now showing the account that was debited and readme file updated adding project information)
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($outflowLines as $line)
                <tr>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $line->journalEntry?->entry_date?->format('d M Y') ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $line->journalEntry?->entry_number ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-gray-700">{{ $line->journalEntry?->description ?? $line->narration ?? '—' }}</td>
<<<<<<< HEAD
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $line->account?->code }} — {{ $line->account?->name }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-red-700 text-right">
                        - {{ format_currency($line->credit) }}
=======
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $line->account?->name ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-red-700 text-right">
                        - {{ format_currency($line->debit) }}
>>>>>>> 99bc01c (Fix: daily cash register now showing the account that was debited and readme file updated adding project information)
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No outflows recorded in this period</td>
                </tr>
                @endforelse
            </tbody>
            @if($outflowLines->count() > 0)
            <tfoot class="bg-red-50 font-semibold">
                <tr>
                    <td colspan="4" class="px-6 py-3 text-right text-sm text-gray-700">Total Outflows:</td>
                    <td class="px-6 py-3 text-right text-sm text-red-700">- {{ format_currency($summary['total_outflows']) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<!-- Bills Created in Period -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Bills Created in Period</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Discount</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Due</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($bills as $bill)
                <tr>
                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                        <a href="{{ route('bills.show', $bill) }}" class="text-medical-blue hover:underline">{{ $bill->bill_number }}</a>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $bill->patient?->name ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-right text-gray-900">{{ format_currency($bill->total_amount) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-right text-yellow-700">{{ $bill->discount_amount > 0 ? format_currency($bill->discount_amount) : '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-right text-green-700">{{ format_currency($bill->paid_amount) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-right text-red-700">{{ $bill->due_amount > 0 ? format_currency($bill->due_amount) : '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-center">
                        <span class="px-2 py-0.5 text-xs rounded-full
                            {{ $bill->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $bill->status === 'partial' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ !in_array($bill->status, ['paid', 'partial']) ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($bill->status ?? 'pending') }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">No bills created in this period</td>
                </tr>
                @endforelse
            </tbody>
            @if($bills->count() > 0)
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="2" class="px-6 py-3 text-right text-sm text-gray-700">Totals:</td>
                    <td class="px-6 py-3 text-right text-sm text-gray-900">{{ format_currency($summary['total_billed']) }}</td>
                    <td class="px-6 py-3 text-right text-sm text-yellow-700">{{ format_currency($summary['total_discount']) }}</td>
                    <td class="px-6 py-3 text-right text-sm text-green-700">{{ format_currency($summary['total_collected']) }}</td>
                    <td class="px-6 py-3 text-right text-sm text-red-700">{{ format_currency($summary['total_outstanding']) }}</td>
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
    .no-print, aside, header, .ml-64, nav, button { display: none !important; }
    body { margin: 0 !important; padding: 0 !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    main { margin: 0 !important; padding: 20px !important; }
    @page { margin: 1cm; }
}
</style>
@endpush
@endsection
