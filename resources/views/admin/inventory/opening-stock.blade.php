@extends('admin.layout')

@section('title', 'Opening Stock Import')

@section('content')
<div id="opening-stock-index"
     data-import-locked="{{ $locked ? '1' : '0' }}"
     @if(session('import_pending'))
     data-import-pending="1"
     data-import-cache-key="{{ session('import_cache_key') }}"
     data-import-status-url="{{ route('inventory.opening-stock.import-status') }}"
     @endif>

<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Opening Stock Import</h1>
            <p class="text-sm text-gray-500 mt-1">One-time bulk upload of initial medicine stock levels.</p>
        </div>
        <a href="{{ route('inventory.index') }}"
           class="text-sm text-medical-blue hover:text-blue-700 inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
        </a>
    </div>
</div>

@if($locked)
<div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-5">
    <div class="flex items-start gap-3">
        <span class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
            <i class="fas fa-lock text-green-700"></i>
        </span>
        <div>
            <h2 class="text-base font-semibold text-green-900">Opening stock import completed</h2>
            <p class="text-sm text-green-800 mt-1">
                This clinic has already recorded opening stock. The bulk import is locked to prevent duplicate initial balances.
            </p>
            <ul class="mt-3 text-sm text-green-800 space-y-1">
                @if($importedAt)
                    <li><span class="font-medium">Imported on:</span> {{ $importedAt }}</li>
                @endif
                @if($importedBy)
                    <li><span class="font-medium">Imported by:</span> {{ $importedBy }}</li>
                @endif
                @if($batchCount)
                    <li><span class="font-medium">Batches recorded:</span> {{ number_format($batchCount) }}</li>
                @endif
            </ul>
            <p class="text-sm text-green-700 mt-3">
                Use <a href="{{ route('inventory.stock-in') }}" class="underline font-medium">Stock In</a> for ongoing purchases and adjustments.
            </p>
        </div>
    </div>
</div>
@else
<div class="mb-6 grid gap-4 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-lg border border-blue-100 bg-blue-50 p-5">
        <h2 class="text-sm font-semibold text-blue-900 uppercase tracking-wide">Before you import</h2>
        <ol class="mt-3 space-y-2 text-sm text-blue-900 list-decimal list-inside">
            <li>Import medicine catalog, categories, brands, and units first.</li>
            <li>Ensure each medicine has <strong>Manage Stock</strong> enabled.</li>
            <li>Download the template and fill one row per batch (same SKU can appear on multiple rows).</li>
            <li>Upload once — opening stock import cannot be repeated after completion.</li>
        </ol>
    </div>
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-5">
        <h2 class="text-sm font-semibold text-amber-900 uppercase tracking-wide">Important</h2>
        <p class="mt-3 text-sm text-amber-900">
            All rows are validated before anything is saved. If any row has errors, the entire import is rejected.
        </p>
    </div>
</div>

<div class="mb-6 flex flex-wrap items-center gap-2">
    @if($templateUrl = import_template_url('opening-stock-template'))
    <a href="{{ $templateUrl }}" download
       class="text-gray-600 hover:text-medical-blue px-4 py-2 border border-gray-300 rounded-lg text-sm inline-flex items-center">
        <i class="fas fa-download mr-2"></i>Template
    </a>
    @endif

    @canany(['manage pharmacy', 'manage inventory'])
    <button type="button"
            data-opening-stock-import-trigger
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm inline-flex items-center">
        <i class="fas fa-file-upload mr-2"></i>Import Opening Stock
    </button>
    @endcanany
</div>

@canany(['manage pharmacy', 'manage inventory'])
<form data-opening-stock-import-form
      action="{{ route('inventory.opening-stock.import') }}"
      method="POST"
      enctype="multipart/form-data"
      class="hidden">
    @csrf
    <input type="file"
           data-opening-stock-import-file
           name="file"
           accept=".csv,.xlsx,.xls,.txt">
</form>
@endcanany
@endif

<div id="opening-stock-import-result" class="hidden mb-4"></div>

@if(session('error'))
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
    <i class="fas fa-times-circle mr-1"></i>{{ session('error') }}
</div>
@endif

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-sm font-semibold text-gray-800">Template columns</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-white">
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                    <th class="px-5 py-3">Column</th>
                    <th class="px-5 py-3">Required</th>
                    <th class="px-5 py-3">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-700">
                <tr><td class="px-5 py-3 font-mono text-xs">sku</td><td class="px-5 py-3">Yes</td><td class="px-5 py-3">Medicine SKU (must exist, manage stock enabled)</td></tr>
                <tr><td class="px-5 py-3 font-mono text-xs">quantity</td><td class="px-5 py-3">Yes</td><td class="px-5 py-3">Whole number in the unit below</td></tr>
                <tr><td class="px-5 py-3 font-mono text-xs">unit_abbreviation</td><td class="px-5 py-3">Yes</td><td class="px-5 py-3">Must match a unit abbreviation (e.g. TAB, INJ)</td></tr>
                <tr><td class="px-5 py-3 font-mono text-xs">unit_cost</td><td class="px-5 py-3">Yes</td><td class="px-5 py-3">Cost per entered unit (not base unit)</td></tr>
                <tr><td class="px-5 py-3 font-mono text-xs">batch_no</td><td class="px-5 py-3">Yes</td><td class="px-5 py-3">Batch or lot number</td></tr>
                <tr><td class="px-5 py-3 font-mono text-xs">expiry_date</td><td class="px-5 py-3">Yes</td><td class="px-5 py-3">YYYY-MM-DD</td></tr>
                <tr><td class="px-5 py-3 font-mono text-xs">supplier</td><td class="px-5 py-3">No</td><td class="px-5 py-3">Defaults to Opening Balance</td></tr>
                <tr><td class="px-5 py-3 font-mono text-xs">reference_no</td><td class="px-5 py-3">No</td><td class="px-5 py-3">Defaults to OPENING-YYYY-MM</td></tr>
                <tr><td class="px-5 py-3 font-mono text-xs">notes</td><td class="px-5 py-3">No</td><td class="px-5 py-3">Defaults to Opening stock import</td></tr>
            </tbody>
        </table>
    </div>
</div>

</div>

@vite(['resources/js/opening-stock-index.js'])
@endsection
