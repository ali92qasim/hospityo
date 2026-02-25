@extends('admin.layout')

@section('title', 'View Medicine Brand')

@section('content')
<div class="max-w-5xl">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $medicineBrand->name }}</h1>
            <p class="text-gray-600 mt-1">Brand Details</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('medicine-brands.edit', $medicineBrand) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('medicine-brands.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <!-- Brand Information -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Brand Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Brand Name</label>
                <p class="text-lg font-medium text-gray-900">{{ $medicineBrand->name }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                <p>
                    <span class="px-3 py-1 text-sm rounded-full {{ $medicineBrand->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $medicineBrand->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-500 mb-1">Description</label>
                <p class="text-gray-700">{{ $medicineBrand->description ?? 'No description provided' }}</p>
            </div>
        </div>
    </div>

    <!-- Associated Medicines -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800">
                Associated Medicines 
                <span class="text-sm font-normal text-gray-500">({{ $medicineBrand->medicines->count() }})</span>
            </h2>
        </div>

        @if($medicineBrand->medicines->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($medicineBrand->medicines as $medicine)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $medicine->name }}</div>
                                <div class="text-sm text-gray-500">{{ $medicine->strength }} - {{ $medicine->dosage_form }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $medicine->category ? $medicine->category->name : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="{{ $medicine->isLowStock() ? 'text-red-600 font-medium' : 'text-gray-700' }}">
                                    {{ $medicine->getCurrentStock() }} {{ $medicine->baseUnit->name ?? '' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full {{ $medicine->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($medicine->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-pills text-4xl text-gray-300 mb-4"></i>
                <p>No medicines with this brand yet</p>
            </div>
        @endif
    </div>
</div>
@endsection
