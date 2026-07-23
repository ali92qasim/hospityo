@extends('admin.layout')

@section('title', 'Medicine Categories')

@section('content')
<div id="medicine-categories-index"
     data-import-pending="{{ session('import_pending') ? '1' : '0' }}"
     data-import-cache-key="{{ session('import_cache_key') }}"
     data-import-status-url="{{ route('medicine-categories.import-status') }}"
     data-import-index-url="{{ route('medicine-categories.index') }}"
     data-import-expiry="{{ session('import_pending') ? (string) (now()->addMinutes(25)->timestamp * 1000) : '' }}">

<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <h1 class="text-2xl font-bold text-gray-800">Medicine Categories</h1>
    <div class="flex items-center flex-wrap gap-2">
        @if($templateUrl = import_template_url('medicine-categories-template'))
        <a href="{{ $templateUrl }}" download
           class="text-gray-500 hover:text-medical-blue px-3 py-2 border border-gray-300 rounded-lg text-sm"
           title="Download import template">
            <i class="fas fa-download mr-1"></i>Template
        </a>
        @endif

        @can('manage pharmacy')
        <button type="button"
                data-medicine-category-import-trigger
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm flex items-center">
            <i class="fas fa-file-upload mr-2"></i>Import CSV / Excel
        </button>
        @endcan

        <a href="{{ route('medicine-categories.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center text-sm">
            <i class="fas fa-plus mr-2"></i>Add Category
        </a>
    </div>
</div>

@can('manage pharmacy')
<form data-medicine-category-import-form
      action="{{ route('medicine-categories.import') }}"
      method="POST"
      enctype="multipart/form-data"
      class="hidden">
    @csrf
    <input type="file"
           data-medicine-category-import-file
           name="file"
           accept=".csv,.xlsx,.xls,.txt">
</form>
@endcan

<div id="medicine-category-import-progress" class="hidden mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center">
    <svg class="animate-spin h-4 w-4 text-blue-600 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
    </svg>
    <span class="text-sm text-blue-800 font-medium">Importing medicine categories… this may take a moment.</span>
</div>

<div id="medicine-category-import-result" class="hidden mb-4"></div>

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
            placeholder="Search by name or code..." 
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
        <a href="{{ route('medicine-categories.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
            <i class="fas fa-times mr-2"></i>Clear
        </a>
    </form>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicines</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($categories as $category)
                <tr>
                    <td class="px-6 py-4">
                        <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">{{ $category->code }}</span>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $category->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $category->description ? Str::limit($category->description, 50) : '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                            {{ $category->medicines_count ?? $category->medicines()->count() }} medicines
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('medicine-categories.show', $category) }}" class="text-blue-600 hover:text-blue-800" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('medicine-categories.edit', $category) }}" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('medicine-categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
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
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-folder-open text-4xl text-gray-300 mb-4"></i>
                        <p>No categories found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $categories->links() }}
    </div>
</div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/medicine-categories-index.js'])
@endpush
