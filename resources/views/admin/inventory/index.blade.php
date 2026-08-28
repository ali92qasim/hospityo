@extends('admin.layout')

@section('title', 'Inventory Management - Hospital Management System')
@section('page-title', 'Inventory Management')
@section('page-description', 'Track stock movements and manage medicine inventory')

@section('content')
<div class="space-y-4 sm:space-y-6">
    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex flex-col sm:flex-row flex-wrap gap-3">
            <select onchange="filterTransactions()" id="type-filter" class="w-full sm:w-auto sm:min-w-[10rem] px-3 py-2.5 sm:py-2 text-base sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All Transactions</option>
                <option value="stock_in" {{ request('type') == 'stock_in' ? 'selected' : '' }}>Stock In</option>
                <option value="stock_out" {{ request('type') == 'stock_out' ? 'selected' : '' }}>Stock Out</option>
            </select>

            <select onchange="filterTransactions()" id="medicine-filter" class="w-full sm:flex-1 sm:min-w-[12rem] px-3 py-2.5 sm:py-2 text-base sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All Medicines</option>
                @foreach($medicines as $medicine)
                    <option value="{{ $medicine->id }}" {{ request('medicine_id') == $medicine->id ? 'selected' : '' }}>
                        {{ $medicine->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Actions --}}
    <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2 sm:gap-3">
        <a href="{{ route('inventory.low-stock') }}" class="inline-flex items-center justify-center px-3 py-2.5 sm:px-4 sm:py-2 text-sm bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-center">
            <i class="fas fa-exclamation-triangle sm:mr-2"></i><span class="hidden sm:inline">Low Stock</span><span class="sm:hidden ml-2">Low</span>
        </a>
        <a href="{{ route('inventory.expiring') }}" class="inline-flex items-center justify-center px-3 py-2.5 sm:px-4 sm:py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 text-center">
            <i class="fas fa-clock sm:mr-2"></i><span class="hidden sm:inline">Expiring</span><span class="sm:hidden ml-2">Expiry</span>
        </a>
        <a href="{{ route('inventory.stock-out') }}" class="inline-flex items-center justify-center px-3 py-2.5 sm:px-4 sm:py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 text-center">
            <i class="fas fa-minus sm:mr-2"></i><span class="hidden sm:inline">Stock Out</span><span class="sm:hidden ml-2">Out</span>
        </a>
        <a href="{{ route('inventory.stock-in') }}" class="inline-flex items-center justify-center px-3 py-2.5 sm:px-4 sm:py-2 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-center col-span-2 sm:col-span-1">
            <i class="fas fa-plus sm:mr-2"></i>Stock In
        </a>
    </div>

    {{-- Transactions table --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto -mx-px">
            <table class="min-w-[960px] w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">SKU</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Cost</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Supplier/Reason</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="block">{{ $transaction->created_at->format('M d, Y') }}</span>
                                <span class="block text-xs text-gray-500">{{ format_time($transaction->created_at) }}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900 max-w-[10rem] sm:max-w-none">
                                <div class="font-medium truncate">{{ $transaction->medicine->name }}</div>
                                @if($transaction->medicine->generic_name)
                                    <div class="text-xs text-gray-500 truncate">{{ $transaction->medicine->generic_name }}</div>
                                @endif
                                <div class="md:hidden text-xs text-gray-500 mt-1 font-mono">{{ $transaction->medicine->sku ?? '-' }}</div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <span class="text-xs font-mono text-gray-600">{{ $transaction->medicine->sku ?? '-' }}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full {{ $transaction->type === 'stock_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $transaction->type === 'stock_in' ? 'In' : 'Out' }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $transaction->quantity }} {{ $transaction->medicine->baseUnit?->abbreviation ?? '' }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden sm:table-cell">
                                @if($transaction->type === 'stock_in')
                                    {{ format_currency($transaction->total_cost) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900 hidden lg:table-cell max-w-[8rem] truncate">
                                {{ $transaction->supplier }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden xl:table-cell">
                                {{ $transaction->reference_no ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 sm:px-6 py-8 text-center text-gray-500">No transactions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-x-auto">
        {{ $transactions->links() }}
    </div>
</div>

<script>
function filterTransactions() {
    const type = document.getElementById('type-filter').value;
    const medicine = document.getElementById('medicine-filter').value;
    const url = new URL(window.location);

    if (type) url.searchParams.set('type', type);
    else url.searchParams.delete('type');

    if (medicine) url.searchParams.set('medicine_id', medicine);
    else url.searchParams.delete('medicine_id');

    window.location = url;
}
</script>
@endsection
