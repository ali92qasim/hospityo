@extends('admin.layout')

@section('title', 'Inventory Management - Hospital Management System')
@section('page-title', 'Inventory Management')
@section('page-description', 'Track stock movements and manage medicine inventory')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="flex space-x-4">
        <select onchange="filterTransactions()" id="type-filter" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Transactions</option>
            <option value="stock_in" {{ request('type') == 'stock_in' ? 'selected' : '' }}>Stock In</option>
            <option value="stock_out" {{ request('type') == 'stock_out' ? 'selected' : '' }}>Stock Out</option>
        </select>
        
        <select onchange="filterTransactions()" id="medicine-filter" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Medicines</option>
            @foreach($medicines as $medicine)
                <option value="{{ $medicine->id }}" {{ request('medicine_id') == $medicine->id ? 'selected' : '' }}>
                    {{ $medicine->name }}
                </option>
            @endforeach
        </select>
    </div>
    
    <div class="flex space-x-3">
        <a href="{{ route('inventory.low-stock') }}" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>Low Stock
        </a>
        <a href="{{ route('inventory.expiring') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
            <i class="fas fa-clock mr-2"></i>Expiring
        </a>
        <a href="{{ route('inventory.stock-out') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
            <i class="fas fa-minus mr-2"></i>Stock Out
        </a>
        <a href="{{ route('inventory.stock-in') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Stock In
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier/Reason</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($transactions as $transaction)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $transaction->created_at->format('M d, Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $transaction->medicine->name }}</div>
                        <div class="text-xs text-gray-500">{{ $transaction->medicine->generic_name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $transaction->type === 'stock_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $transaction->type === 'stock_in' ? 'Stock In' : 'Stock Out' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $transaction->quantity }} {{ $transaction->medicine->unit }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($transaction->type === 'stock_in')
                            ₨{{ number_format($transaction->total_cost, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $transaction->supplier }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $transaction->reference_no ?: '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $transaction->user->name }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">No transactions found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $transactions->links() }}

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