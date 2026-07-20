@extends('admin.layout')

@section('title', 'Medicines Management')

@section('content')
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 md:mb-6 gap-3">
    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Medicines Management</h1>
    <a href="{{ route('medicines.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center justify-center btn-touch">
        <i class="fas fa-plus mr-2"></i>Add Medicine
    </a>
</div>

<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
        <select id="medicine-category-filter" class="px-3 py-2 border border-gray-300 rounded-lg flex-1 sm:flex-initial">
            <option value="">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
        <select id="medicine-status-filter" class="px-3 py-2 border border-gray-300 rounded-lg flex-1 sm:flex-initial">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <label class="flex items-center btn-touch">
            <input type="checkbox" id="medicine-low-stock-filter" value="1" class="mr-2">
            Low Stock Only
        </label>
        <button type="button" id="apply-medicine-filters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 btn-touch">Filter</button>
        <button type="button" id="clear-medicine-filters" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 text-center btn-touch">Clear</button>
    </div>
</div>

<table class="medicines-table w-full invisible">
    <thead>
        <tr>
            <th>Medicine</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
</table>
@vite(['resources/js/medicines-index.js'])
@endsection
