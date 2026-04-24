@extends('admin.layout')

@section('title', 'Profit & Loss Statement')
@section('page-title', 'Profit & Loss Statement')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    {{-- Date Range Filter --}}
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <form method="GET" action="{{ route('accounting.profit-loss') }}" class="flex flex-col sm:flex-row gap-3 items-end">
            <div>
                <label for="from" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input type="date" name="from" id="from" value="{{ $from }}"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
            </div>
            <div>
                <label for="to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" name="to" id="to" value="{{ $to }}"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
            </div>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm min-h-[42px]">
                <i class="fas fa-filter mr-1"></i> Apply
            </button>
        </form>
    </div>

    <div class="p-4 sm:p-6 space-y-6">
        {{-- Revenue Section --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider border-b-2 border-purple-500 pb-2 mb-3">Revenue</h3>
            <table class="w-full">
                <tbody>
                    @foreach($revenue as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-sm text-gray-700 pl-4">{{ $item['account']->code }} — {{ $item['account']->name }}</td>
                        <td class="py-2 text-sm text-right text-gray-900 pr-4">{{ format_currency($item['balance']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-300">
                        <td class="py-2 text-sm font-semibold text-gray-800 pl-4">Total Revenue</td>
                        <td class="py-2 text-sm font-semibold text-right text-gray-900 pr-4">{{ format_currency($totalRevenue) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Expenses Section --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider border-b-2 border-orange-500 pb-2 mb-3">Expenses</h3>
            <table class="w-full">
                <tbody>
                    @foreach($expenses as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-sm text-gray-700 pl-4">{{ $item['account']->code }} — {{ $item['account']->name }}</td>
                        <td class="py-2 text-sm text-right text-gray-900 pr-4">{{ format_currency($item['balance']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-300">
                        <td class="py-2 text-sm font-semibold text-gray-800 pl-4">Total Expenses</td>
                        <td class="py-2 text-sm font-semibold text-right text-gray-900 pr-4">{{ format_currency($totalExpenses) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Net Income --}}
        <div class="border-t-2 border-gray-800 pt-4">
            <div class="flex justify-between items-center px-4">
                <span class="text-base font-bold text-gray-900">Net Income</span>
                <span class="text-base font-bold {{ $netIncome >= 0 ? 'text-green-700' : 'text-red-600' }}">
                    {{ format_currency(abs($netIncome)) }}{{ $netIncome < 0 ? ' (Loss)' : '' }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection