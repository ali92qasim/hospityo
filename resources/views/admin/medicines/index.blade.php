@extends('admin.layout')

@section('title', 'Medicines Management')

@section('content')
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 md:mb-6 gap-3">
    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Medicines Management</h1>
    <a href="{{ route('medicines.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center justify-center btn-touch">
        <i class="fas fa-plus mr-2"></i>Add Medicine
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
        <select name="category_id" class="px-3 py-2 border border-gray-300 rounded-lg flex-1 sm:flex-initial">
            <option value="">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg flex-1 sm:flex-initial">
            <option value="">All Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <label class="flex items-center btn-touch">
            <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} class="mr-2">
            Low Stock Only
        </label>
        <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 btn-touch">Filter</button>
        <a href="{{ route('medicines.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 text-center btn-touch">Clear</a>
    </form>
</div>

<div class="bg-white rounded-lg shadow">
    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
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
                            <div class="text-xs text-gray-400">{{ $medicine->brand->name }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-mono text-gray-700 bg-gray-100 px-2 py-1 rounded inline-block">
                            {{ $medicine->sku }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $medicine->category ? $medicine->category->name : '-' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($medicine->manage_stock)
                            <div class="text-sm font-medium {{ $medicine->isLowStock() ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $medicine->getCurrentStock() }} {{ $medicine->baseUnit->name ?? '' }}
                            </div>
                            @if($medicine->isLowStock())
                                <div class="text-xs text-red-500">Low Stock (Reorder: {{ $medicine->reorder_level }})</div>
                            @endif
                        @else
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-ban mr-1"></i>Not Managed
                            </div>
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

    <!-- Mobile Card View -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($medicines as $medicine)
        <div class="p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-3">
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-gray-900 truncate">{{ $medicine->name }}</div>
                    <div class="text-sm text-gray-500">{{ $medicine->strength }} - {{ $medicine->dosage_form }}</div>
                    @if($medicine->brand)
                        <div class="text-xs text-gray-400">{{ $medicine->brand->name }}</div>
                    @endif
                </div>
                <span class="px-2 py-1 text-xs rounded-full ml-2 flex-shrink-0 {{ $medicine->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ ucfirst($medicine->status) }}
                </span>
            </div>
            
            <div class="space-y-2 text-sm mb-3">
                <div class="flex justify-between">
                    <span class="text-gray-500">SKU:</span>
                    <span class="font-mono text-gray-700 bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $medicine->sku }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Category:</span>
                    <span class="text-gray-900">{{ $medicine->category ? $medicine->category->name : '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Stock:</span>
                    @if($medicine->manage_stock)
                        <span class="font-medium {{ $medicine->isLowStock() ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $medicine->getCurrentStock() }} {{ $medicine->baseUnit->name ?? '' }}
                            @if($medicine->isLowStock())
                                <span class="text-xs">(Low)</span>
                            @endif
                        </span>
                    @else
                        <span class="text-gray-500">
                            <i class="fas fa-ban mr-1"></i>Not Managed
                        </span>
                    @endif
                </div>
            </div>
            
            <div class="flex gap-2">
                <a href="{{ route('medicines.edit', $medicine) }}" class="flex-1 bg-medical-blue text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-center text-sm btn-touch">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <form method="POST" action="{{ route('medicines.destroy', $medicine) }}" class="flex-1">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 text-sm btn-touch" onclick="return confirm('Are you sure?')">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center text-gray-500">
            <i class="fas fa-pills text-4xl mb-4 text-gray-300"></i>
            <p>No medicines found</p>
        </div>
        @endforelse
    </div>

    <div class="px-4 md:px-6 py-4 border-t border-gray-200">
        {{ $medicines->links() }}
    </div>
</div>
@endsection