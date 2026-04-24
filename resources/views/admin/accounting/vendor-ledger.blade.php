@extends('admin.layout')

@section('title', 'Vendor Ledger')
@section('page-title', 'Vendor Ledger')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    {{-- Supplier Filter --}}
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <form method="GET" action="{{ route('accounting.vendor-ledger') }}" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1 w-full sm:w-auto">
                <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                <select name="supplier_id" id="supplier_id"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm min-h-[42px]">
                <i class="fas fa-filter mr-1"></i> Apply
            </button>
        </form>
    </div>

    {{-- Ledger Table --}}
    @if($supplierId)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry #</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Narration</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php $runningBalance = 0; @endphp
                    @forelse($entries as $entry)
                        @php $runningBalance += $entry->debit - $entry->credit; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 lg:px-6 py-3 text-sm text-gray-900">{{ $entry->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-medical-blue font-medium">{{ $entry->journalEntry->entry_number ?? '—' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-gray-700">{{ $entry->narration ?? '—' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">{{ $entry->debit > 0 ? format_currency($entry->debit) : '' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">{{ $entry->credit > 0 ? format_currency($entry->credit) : '' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-right font-medium {{ $runningBalance >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                {{ format_currency(abs($runningBalance)) }}{{ $runningBalance < 0 ? ' CR' : ' DR' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-truck text-4xl mb-4 text-gray-300"></i>
                                <p>No ledger entries found for this supplier.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($entries->count())
                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                    <tr>
                        <td colspan="3" class="px-4 lg:px-6 py-3 text-sm font-semibold text-gray-800">Totals</td>
                        <td class="px-4 lg:px-6 py-3 text-sm text-right font-semibold text-gray-900">{{ format_currency($entries->sum('debit')) }}</td>
                        <td class="px-4 lg:px-6 py-3 text-sm text-right font-semibold text-gray-900">{{ format_currency($entries->sum('credit')) }}</td>
                        <td class="px-4 lg:px-6 py-3 text-sm text-right font-bold {{ $runningBalance >= 0 ? 'text-green-700' : 'text-red-600' }}">
                            {{ format_currency(abs($runningBalance)) }}{{ $runningBalance < 0 ? ' CR' : ' DR' }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    @else
        <div class="px-6 py-12 text-center text-gray-500">
            <i class="fas fa-truck text-4xl mb-4 text-gray-300"></i>
            <p>Select a supplier to view their ledger</p>
        </div>
    @endif
</div>
@endsection