@extends('admin.layout')

@section('title', 'Investigations - Laboratory Information System')
@section('page-title', 'Investigations')
@section('page-description', 'Manage investigation definitions')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="flex space-x-4">
    </div>
    <div class="flex items-center space-x-2">
        <a href="{{ asset('templates/investigations-template.csv') }}" download class="text-gray-500 hover:text-medical-blue px-3 py-2 border border-gray-300 rounded-lg text-sm" title="Download Template">
            <i class="fas fa-download mr-1"></i>Template
        </a>
        <button type="button" onclick="document.getElementById('import-file').click()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
            <i class="fas fa-file-upload mr-2"></i>Import CSV
        </button>
        <a href="{{ route('investigations.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Add Test
        </a>
    </div>
    <form id="import-form" action="{{ route('investigations.import') }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
        <input type="file" id="import-file" name="file" accept=".csv" onchange="if(this.files.length){if(confirm('Import '+this.files[0].name+'? Existing tests with same code will be updated.')){this.closest('form').submit();}else{this.value='';}}">
    </form>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
    <p class="text-sm font-medium text-green-800"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}</p>
</div>
@endif

@if(session('import_pending'))
<script>
(function () {
    // Write import tracking keys — poller in import-poller.js picks these up
    localStorage.setItem('investigationImportKey',       @json(session('import_cache_key')));
    localStorage.setItem('investigationImportStatusUrl', @json(route('investigations.import-status')));
    localStorage.setItem('investigationImportIndexUrl',  window.location.href);
    // Expiry: 25 min — shorter than the 30-min server cache TTL
    localStorage.setItem('investigationImportExpiry',    String(Date.now() + 25 * 60 * 1000));
})();
</script>
@endif

@if(session('import_errors') && count(session('import_errors')) > 0)
<div class="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
    <p class="text-sm font-medium text-yellow-800 mb-2"><i class="fas fa-exclamation-triangle mr-1"></i>Some rows had issues:</p>
    <ul class="text-xs text-yellow-700 space-y-1 max-h-32 overflow-y-auto">
        @foreach(session('import_errors') as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<table class="investigations-table w-full invisible">
    <thead>
    <tr>
        <th>Code</th>
        <th>Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>TAT</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
    </thead>
</table>
@vite(['resources/js/pagination.js'])
@endsection
