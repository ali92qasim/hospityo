@extends('admin.layout')

@section('title', 'Purchase Order Details - Hospital Management System')
@section('page-title', 'Purchase Order Details')
@section('page-description', 'View purchase order information and items')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Purchase Order Header -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800">{{ $purchase->po_number }}</h3>
                    <p class="text-sm text-gray-600">Created by {{ $purchase->user->name }} on {{ $purchase->created_at->format('M d, Y') }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'approved' => 'bg-blue-100 text-blue-800',
                            'received' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800'
                        ];
                    @endphp
                    <span class="px-3 py-1 text-sm rounded-full {{ $statusColors[$purchase->status] }}">
                        {{ ucfirst($purchase->status) }}
                    </span>
                    <a href="{{ route('purchases.index') }}" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Order Information -->
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Supplier</label>
                        <p class="text-gray-900 font-medium">{{ $purchase->supplier->name }}</p>
                        <p class="text-sm text-gray-600">{{ $purchase->supplier->contact_person }}</p>
                        <p class="text-sm text-gray-600">{{ $purchase->supplier->email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Order Date</label>
                        <p class="text-gray-900">{{ $purchase->order_date->format('M d, Y') }}</p>
                    </div>
                    @if($purchase->expected_delivery)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Expected Delivery</label>
                        <p class="text-gray-900">{{ $purchase->expected_delivery->format('M d, Y') }}</p>
                    </div>
                    @endif
                </div>
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600">Subtotal:</span>
                            <span class="text-gray-900">₨{{ number_format($purchase->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600">Tax (17%):</span>
                            <span class="text-gray-900">₨{{ number_format($purchase->tax_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                            <span class="font-medium text-gray-900">Total:</span>
                            <span class="font-bold text-lg text-gray-900">₨{{ number_format($purchase->total_amount, 2) }}</span>
                        </div>
                    </div>
                    
                    @if($purchase->notes)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Notes</label>
                        <p class="text-gray-900">{{ $purchase->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    @if(in_array($purchase->status, ['pending', 'approved']))
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h4 class="text-lg font-medium text-gray-800 mb-4">Actions</h4>
        <div class="flex space-x-4">
            @if($purchase->status === 'pending')
                <form action="{{ route('purchases.approve', $purchase) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-check mr-2"></i>Approve Order
                    </button>
                </form>
            @endif
            
            @if($purchase->status === 'approved')
                <form action="{{ route('purchases.receive', $purchase) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-truck mr-2"></i>Mark as Received
                    </button>
                </form>
            @endif
            
            <form action="{{ route('purchases.cancel', $purchase) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                @csrf
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                    <i class="fas fa-times mr-2"></i>Cancel Order
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Order Items -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-800">Order Items</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Price</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($purchase->items as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $item->medicine->name }}</div>
                                <div class="text-xs text-gray-500">{{ $item->medicine->generic_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $item->quantity }} {{ $item->medicine->unit }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₨{{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₨{{ number_format($item->total_price, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection