@extends('admin.layout')

@section('title', 'Supplier Details - Hospital Management System')
@section('page-title', 'Supplier Details')
@section('page-description', 'View supplier information and transaction history')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Supplier Header -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-medical-blue rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-truck text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">{{ $supplier->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $supplier->contact_person }}</p>
                        <span class="px-2 py-1 text-xs rounded-full {{ $supplier->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($supplier->status) }}
                        </span>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                    <a href="{{ route('suppliers.index') }}" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Suppliers
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Summary Stats -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-chart-line text-blue-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-blue-600">Total Transactions</p>
                            <p class="text-2xl font-semibold text-blue-800">{{ $totalTransactions }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-dollar-sign text-green-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-green-600">Total Value</p>
                            <p class="text-2xl font-semibold text-green-800">₨{{ number_format($totalValue, 2) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-handshake text-purple-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-purple-600">Payment Terms</p>
                            <p class="text-lg font-semibold text-purple-800">{{ $supplier->payment_terms ?: 'Not specified' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-800">Contact Information</h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="text-gray-900">{{ $supplier->email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Phone</label>
                        <p class="text-gray-900">{{ $supplier->phone }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Tax Number</label>
                        <p class="text-gray-900">{{ $supplier->tax_number ?: 'Not provided' }}</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Address</label>
                        <p class="text-gray-900">{{ $supplier->address }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Location</label>
                        <p class="text-gray-900">{{ $supplier->city }}, {{ $supplier->country }}</p>
                    </div>
                    @if($supplier->notes)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Notes</label>
                        <p class="text-gray-900">{{ $supplier->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-800">Recent Transactions</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentTransactions as $transaction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $transaction->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $transaction->medicine->name }}</div>
                                <div class="text-xs text-gray-500">{{ $transaction->medicine->generic_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $transaction->quantity }} {{ $transaction->medicine->unit }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₨{{ number_format($transaction->total_cost, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $transaction->reference_no ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No transactions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection