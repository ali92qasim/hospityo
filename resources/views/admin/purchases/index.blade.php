@extends('admin.layout')

@section('title', 'Purchase Orders - Hospital Management System')
@section('page-title', 'Purchase Orders')
@section('page-description', 'Manage medicine purchase orders')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="flex space-x-4">
        <select onchange="filterOrders()" id="status-filter" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
        
        <select onchange="filterOrders()" id="supplier-filter" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Suppliers</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>
    </div>
    
    <a href="{{ route('purchases.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>New Purchase Order
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($orders as $order)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $order->po_number }}</div>
                        <div class="text-xs text-gray-500">{{ $order->user->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $order->supplier->name }}</div>
                        <div class="text-xs text-gray-500">{{ $order->supplier->contact_person }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $order->order_date->format('M d, Y') }}</div>
                        @if($order->expected_delivery)
                            <div class="text-xs text-gray-500">Expected: {{ $order->expected_delivery->format('M d, Y') }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ₨{{ number_format($order->total_amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-blue-100 text-blue-800',
                                'received' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800'
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$order->status] }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('purchases.show', $order) }}" class="text-blue-600 hover:text-blue-800 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        
                        @if($order->status === 'pending')
                            <form action="{{ route('purchases.approve', $order) }}" method="POST" class="inline mr-2">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        @endif
                        
                        @if($order->status === 'approved')
                            <form action="{{ route('purchases.receive', $order) }}" method="POST" class="inline mr-2">
                                @csrf
                                <button type="submit" class="text-purple-600 hover:text-purple-800" title="Receive">
                                    <i class="fas fa-truck"></i>
                                </button>
                            </form>
                        @endif
                        
                        @if(in_array($order->status, ['pending', 'approved']))
                            <form action="{{ route('purchases.cancel', $order) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this order?')">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Cancel">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No purchase orders found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $orders->links() }}

<script>
function filterOrders() {
    const status = document.getElementById('status-filter').value;
    const supplier = document.getElementById('supplier-filter').value;
    const url = new URL(window.location);
    
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    
    if (supplier) url.searchParams.set('supplier_id', supplier);
    else url.searchParams.delete('supplier_id');
    
    window.location = url;
}
</script>
@endsection