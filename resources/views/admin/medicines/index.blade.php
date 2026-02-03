@extends('admin.layout')

@section('title', 'Medicines Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Medicines Management</h1>
    <a href="{{ route('medicines.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
        <i class="fas fa-plus mr-2"></i>Add Medicine
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4">
        <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <label class="flex items-center">
            <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} class="mr-2">
            Low Stock Only
        </label>
        <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Filter</button>
        <a href="{{ route('medicines.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">Clear</a>
    </form>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($medicines as $medicine)
                <tr>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $medicine->name }}</div>
                        <div class="text-sm text-gray-500">{{ $medicine->strength }} - {{ $medicine->dosage_form }}</div>
                        @if($medicine->brand)
                            <div class="text-xs text-gray-400">{{ $medicine->brand }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $medicine->category }}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium {{ $medicine->isLowStock() ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $medicine->stock_quantity }}
                        </div>
                        @if($medicine->isLowStock())
                            <div class="text-xs text-red-500">Low Stock</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">₨{{ number_format($medicine->unit_price, 2) }}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm {{ $medicine->isExpired() ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $medicine->expiry_date->format('M d, Y') }}
                        </div>
                        @if($medicine->isExpired())
                            <div class="text-xs text-red-500">Expired</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $medicine->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($medicine->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <a href="{{ route('medicines.edit', $medicine) }}" class="text-medical-blue hover:text-blue-700 mr-3" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('medicines.destroy', $medicine) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700" onclick="return confirm('Are you sure?')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $medicines->links() }}
    </div>
</div>
@endsection