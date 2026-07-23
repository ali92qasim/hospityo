@extends('admin.layout')

@section('title', 'Medicine Brands')

@section('content')
<div id="medicine-brands-index"
     data-import-pending="{{ session('import_pending') ? '1' : '0' }}"
     data-import-cache-key="{{ session('import_cache_key') }}"
     data-import-status-url="{{ route('medicine-brands.import-status') }}"
     data-import-index-url="{{ route('medicine-brands.index') }}"
     data-import-expiry="{{ session('import_pending') ? (string) (now()->addMinutes(25)->timestamp * 1000) : '' }}">

<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <h1 class="text-2xl font-bold text-gray-800">Medicine Brands</h1>
    <div class="flex items-center flex-wrap gap-2">
        @if($templateUrl = import_template_url('medicine-brands-template'))
        <a href="{{ $templateUrl }}" download
           class="text-gray-500 hover:text-medical-blue px-3 py-2 border border-gray-300 rounded-lg text-sm"
           title="Download import template">
            <i class="fas fa-download mr-1"></i>Template
        </a>
        @endif

        @can('manage pharmacy')
        <button type="button"
                data-medicine-brand-import-trigger
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm flex items-center">
            <i class="fas fa-file-upload mr-2"></i>Import CSV / Excel
        </button>
        @endcan

        <a href="{{ route('medicine-brands.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center text-sm">
            <i class="fas fa-plus mr-2"></i>Add Brand
        </a>
    </div>
</div>

@can('manage pharmacy')
<form data-medicine-brand-import-form
      action="{{ route('medicine-brands.import') }}"
      method="POST"
      enctype="multipart/form-data"
      class="hidden">
    @csrf
    <input type="file"
           data-medicine-brand-import-file
           name="file"
           accept=".csv,.xlsx,.xls,.txt">
</form>
@endcan

<div id="medicine-brand-import-progress" class="hidden mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center">
    <svg class="animate-spin h-4 w-4 text-blue-600 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
    </svg>
    <span class="text-sm text-blue-800 font-medium">Importing medicine brands… this may take a moment.</span>
</div>

<div id="medicine-brand-import-result" class="hidden mb-4"></div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ $errors->first() }}
    </div>
@endif

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4">
        <input 
            type="text" 
            name="search" 
            value="{{ request('search') }}" 
            placeholder="Search by name..." 
            class="px-3 py-2 border border-gray-300 rounded-lg flex-1 min-w-[200px]"
        >
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
        <a href="{{ route('medicine-brands.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
            <i class="fas fa-times mr-2"></i>Clear
        </a>
    </form>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicines</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($brands as $brand)
                <tr>
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $brand->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $brand->description ? Str::limit($brand->description, 60) : '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                            {{ $brand->medicines_count ?? $brand->medicines()->count() }} medicines
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $brand->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $brand->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('medicine-brands.show', $brand) }}" class="text-blue-600 hover:text-blue-800" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('medicine-brands.edit', $brand) }}" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('medicine-brands.destroy', $brand) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this brand?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-copyright text-4xl text-gray-300 mb-4"></i>
                        <p>No brands found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $brands->links() }}
    </div>
</div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/medicine-brands-index.js'])
@endpush
