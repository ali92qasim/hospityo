@extends('admin.layout')

@section('title', 'Medicines Management')

@section('content')
<div id="medicines-index">

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 md:mb-6 gap-3">
    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Medicines Management</h1>
    <div class="flex items-center flex-wrap gap-2">
        @if($templateUrl = import_template_url('medicines-template'))
        <a href="{{ $templateUrl }}" download
           class="text-gray-500 hover:text-medical-blue px-3 py-2 border border-gray-300 rounded-lg text-sm"
           title="Download import template">
            <i class="fas fa-download mr-1"></i>Template
        </a>
        @endif

        @can('manage pharmacy')
        <button type="button"
                data-medicine-import-trigger
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm flex items-center justify-center btn-touch">
            <i class="fas fa-file-upload mr-2"></i>Import CSV / Excel
        </button>
        @endcan

        <a href="{{ route('medicines.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center justify-center btn-touch">
            <i class="fas fa-plus mr-2"></i>Add Medicine
        </a>
    </div>
</div>

@can('manage pharmacy')
<form data-medicine-import-form
      action="{{ route('medicines.import') }}"
      method="POST"
      enctype="multipart/form-data"
      class="hidden">
    @csrf
    <input type="file"
           data-medicine-import-file
           name="file"
           accept=".csv,.xlsx,.xls,.txt">
</form>
@endcan

@if(session('import_pending'))
<script>
(function () {
    localStorage.setItem('medicineImportKey',       @json(session('import_cache_key')));
    localStorage.setItem('medicineImportStatusUrl', @json(route('medicines.import-status')));
    localStorage.setItem('medicineImportIndexUrl',  window.location.href);
    localStorage.setItem('medicineImportExpiry',    String(Date.now() + 25 * 60 * 1000));
})();
</script>
@endif

<div id="medicine-import-result" class="hidden mb-4"></div>

@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ session('error') }}
    </div>
@endif

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
</div>
@vite(['resources/js/medicines-index.js'])
@endsection
