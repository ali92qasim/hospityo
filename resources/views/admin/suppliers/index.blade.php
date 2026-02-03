@extends('admin.layout')

@section('title', 'Suppliers - Hospital Management System')
@section('page-title', 'Suppliers')
@section('page-description', 'Manage medicine suppliers and vendors')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="flex space-x-4">
        <input type="text" id="search-input" placeholder="Search suppliers..." 
               value="{{ request('search') }}" 
               class="px-3 py-2 border border-gray-300 rounded-lg w-64">
        
        <select onchange="filterSuppliers()" id="status-filter" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    
    <a href="{{ route('suppliers.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>Add Supplier
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($suppliers as $supplier)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $supplier->name }}</div>
                        <div class="text-xs text-gray-500">{{ $supplier->contact_person }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $supplier->email }}</div>
                        <div class="text-xs text-gray-500">{{ $supplier->phone }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $supplier->city }}</div>
                        <div class="text-xs text-gray-500">{{ $supplier->country }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $supplier->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($supplier->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('suppliers.show', $supplier) }}" class="text-blue-600 hover:text-blue-800 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="text-yellow-600 hover:text-yellow-800 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No suppliers found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $suppliers->links() }}

<script>
let searchTimeout;
document.getElementById('search-input').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        filterSuppliers();
    }, 500);
});

function filterSuppliers() {
    const search = document.getElementById('search-input').value;
    const status = document.getElementById('status-filter').value;
    const url = new URL(window.location);
    
    if (search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');
    
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    
    window.location = url;
}
</script>
@endsection