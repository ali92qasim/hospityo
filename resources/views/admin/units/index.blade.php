@extends('admin.layout')

@section('title', 'Units - Hospital Management System')
@section('page-title', 'Units')
@section('page-description', 'Manage medicine units and packaging')

@section('content')
<div id="units-index"
     data-import-pending="{{ session('import_pending') ? '1' : '0' }}"
     data-import-cache-key="{{ session('import_cache_key') }}"
     data-import-status-url="{{ route('units.import-status') }}"
     data-import-index-url="{{ route('units.index') }}"
     data-import-expiry="{{ session('import_pending') ? (string) (now()->addMinutes(25)->timestamp * 1000) : '' }}">

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-6 gap-4">
    <div class="flex items-center flex-wrap gap-2">
        @if($templateUrl = import_template_url('medicine-units-template'))
        <a href="{{ $templateUrl }}" download
           class="text-gray-500 hover:text-medical-blue px-3 py-2 border border-gray-300 rounded-lg text-sm inline-flex items-center"
           title="Download import template">
            <i class="fas fa-download mr-1"></i>Template
        </a>
        @endif

        @can('manage pharmacy')
        <button type="button"
                data-unit-import-trigger
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm inline-flex items-center">
            <i class="fas fa-file-upload mr-2"></i>Import CSV / Excel
        </button>
        @endcan

        <a href="{{ route('units.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 inline-flex items-center text-sm">
            <i class="fas fa-plus mr-2"></i>Add Unit
        </a>
    </div>
</div>

@can('manage pharmacy')
<form data-unit-import-form
      action="{{ route('units.import') }}"
      method="POST"
      enctype="multipart/form-data"
      class="hidden">
    @csrf
    <input type="file"
           data-unit-import-file
           name="file"
           accept=".csv,.xlsx,.xls,.txt">
</form>
@endcan

<div id="unit-import-progress" class="hidden mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center">
    <svg class="animate-spin h-4 w-4 text-blue-600 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
    </svg>
    <span class="text-sm text-blue-800 font-medium">Importing units… this may take a moment.</span>
</div>

<div id="unit-import-result" class="hidden mb-4"></div>

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

<div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
    <p class="font-medium text-gray-800">Import tips</p>
    <ul class="mt-2 space-y-1 list-disc list-inside">
        <li>Import base units first (rows without parentheses in the name).</li>
        <li>Put the base unit abbreviation in parentheses, e.g. <span class="font-mono text-xs">10 UNITS (MISC.)</span> or <span class="font-mono text-xs">INJ PACKING 10 (INJ)</span>.</li>
        <li>Natt Brothers exports with <span class="font-mono text-xs">Name</span> and <span class="font-mono text-xs">Short name</span> columns are supported directly.</li>
    </ul>
</div>

<table class="units-table w-full invisible">
    <thead>
        <tr>
            <th>Name</th>
            <th>Abbreviation</th>
            <th>Base Unit</th>
            <th>Conversion</th>
            <th>Type</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
</table>

</div>

@vite(['resources/js/units-index.js'])
@endsection
