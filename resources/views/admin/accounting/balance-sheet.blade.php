@extends('admin.layout')

@section('title', 'Balance Sheet')
@section('page-title', 'Balance Sheet')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    {{-- As-of Date Filter --}}
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <form method="GET" action="{{ route('accounting.balance-sheet') }}" class="flex flex-col sm:flex-row gap-3 items-end">
            <div>
                <label for="as_of" class="block text-sm font-medium text-gray-700 mb-1">As of Date</label>
                <input type="date" name="as_of" id="as_of" value="{{ $asOf }}"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
            </div>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm min-h-[42px]">
                <i class="fas fa-filter mr-1"></i> Apply
            </button>
        </form>
    </div>

    <div class="p-4 sm:p-6 space-y-6">
        {{-- Assets --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider border-b-2 border-green-500 pb-2 mb-3">Assets</h3>
            <table class="w-full">
                <tbody>
                    @foreach($assets as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-sm text-gray-700 pl-4">{{ $item['account']->code }} — {{ $item['account']->name }}</td>
                        <td class="py-2 text-sm text-right text-gray-900 pr-4">{{ format_currency($item['balance']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-300">
                        <td class="py-2 text-sm font-semibold text-gray-800 pl-4">Total Assets</td>
                        <td class="py-2 text-sm font-semibold text-right text-gray-900 pr-4">{{ format_currency($totalAssets) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Liabilities --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider border-b-2 border-red-500 pb-2 mb-3">Liabilities</h3>
            <table class="w-full">
                <tbody>
                    @foreach($liabilities as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-sm text-gray-700 pl-4">{{ $item['account']->code }} — {{ $item['account']->name }}</td>
                        <td class="py-2 text-sm text-right text-gray-900 pr-4">{{ format_currency($item['balance']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-300">
                        <td class="py-2 text-sm font-semibold text-gray-800 pl-4">Total Liabilities</td>
                        <td class="py-2 text-sm font-semibold text-right text-gray-900 pr-4">{{ format_currency($totalLiabilities) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Equity --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider border-b-2 border-blue-500 pb-2 mb-3">Equity</h3>
            <table class="w-full">
                <tbody>
                    @foreach($equity as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-sm text-gray-700 pl-4">{{ $item['account']->code }} — {{ $item['account']->name }}</td>
                        <td class="py-2 text-sm text-right text-gray-900 pr-4">{{ format_currency($item['balance']) }}</td>
                    </tr>
                    @endforeach
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-sm text-gray-700 pl-4 italic">Retained Earnings</td>
                        <td class="py-2 text-sm text-right text-gray-900 pr-4">{{ format_currency($retainedEarnings) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-300">
                        <td class="py-2 text-sm font-semibold text-gray-800 pl-4">Total Equity</td>
                        <td class="py-2 text-sm font-semibold text-right text-gray-900 pr-4">{{ format_currency($totalEquity) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Accounting Equation --}}
        <div class="border-t-2 border-gray-800 pt-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-2 text-sm">
                    <div class="text-center">
                        <div class="text-gray-500">Total Assets</div>
                        <div class="text-base font-bold text-gray-900">{{ format_currency($totalAssets) }}</div>
                    </div>
                    <div class="text-gray-400 text-lg font-bold">=</div>
                    <div class="text-center">
                        <div class="text-gray-500">Total Liabilities</div>
                        <div class="text-base font-bold text-gray-900">{{ format_currency($totalLiabilities) }}</div>
                    </div>
                    <div class="text-gray-400 text-lg font-bold">+</div>
                    <div class="text-center">
                        <div class="text-gray-500">Total Equity</div>
                        <div class="text-base font-bold text-gray-900">{{ format_currency($totalEquity) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection