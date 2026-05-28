@extends('admin.layout')

@section('title', 'Services Management')

@section('content')
<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <h1 class="text-2xl font-bold text-gray-800">Services Management</h1>
    <div class="flex items-center space-x-2">
        {{-- Template download --}}
        <a href="{{ asset('templates/services-template.csv') }}" download
           class="text-gray-500 hover:text-medical-blue px-3 py-2 border border-gray-300 rounded-lg text-sm"
           title="Download import template">
            <i class="fas fa-download mr-1"></i>Template
        </a>

        {{-- Import button --}}
        @can('create services')
        <button type="button" onclick="document.getElementById('service-import-file').click()"
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm flex items-center">
            <i class="fas fa-file-upload mr-2"></i>Import CSV / Excel
        </button>
        @endcan

        {{-- Add single service --}}
        @can('create services')
        <a href="{{ route('services.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center text-sm">
            <i class="fas fa-plus mr-2"></i>Add Service
        </a>
        @endcan
    </div>
</div>

{{-- Hidden import form — accepts CSV and Excel --}}
@can('create services')
<form id="service-import-form" action="{{ route('services.import') }}" method="POST"
      enctype="multipart/form-data" class="hidden">
    @csrf
    <input type="file" id="service-import-file" name="file"
           accept=".csv,.xlsx,.xls,.txt"
           onchange="
               if (this.files.length) {
                   var name = this.files[0].name;
                   var msg  = 'Import \'' + name + '\'?\n\n'
                            + 'New services will be created.\n'
                            + 'Existing services with the same code will be updated.\n\n'
                            + 'Tip: Both CSV and Excel files are accepted.';
                   if (confirm(msg)) {
                       this.closest('form').submit();
                   } else {
                       this.value = '';
                   }
               }
           ">
</form>
@endcan

{{-- Import in-progress banner --}}
<div id="import-progress-banner" class="hidden mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center">
    <svg class="animate-spin h-4 w-4 text-blue-600 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
    </svg>
    <span class="text-sm text-blue-800 font-medium">Importing services… this may take a moment.</span>
</div>

{{-- Import result banner (shown after polling completes) --}}
<div id="import-result-banner" class="hidden mb-4"></div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
    <p class="text-sm font-medium text-green-800"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}</p>
</div>
@endif

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
    <p class="text-sm font-medium text-red-800 mb-1"><i class="fas fa-exclamation-circle mr-1"></i>Please fix the following errors:</p>
    <ul class="text-xs text-red-700 list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($services as $service)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $service->code }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $service->name }}</div>
                        @if($service->description)
                            <div class="text-sm text-gray-500">{{ Str::limit($service->description, 50) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded capitalize">{{ str_replace('_', ' ', $service->category) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $service->department->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ format_currency($service->price) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($service->is_active)
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Active</span>
                        @else
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('services.edit', $service) }}" class="text-medical-blue hover:text-blue-700 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('services.destroy', $service) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No services found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $services->links() }}
    </div>
</div>

{{-- Import polling script --}}
@if(session('import_pending'))
<script>
(function () {
    var key        = @json(session('import_cache_key'));
    var statusUrl  = @json(route('services.import-status'));
    var indexUrl   = window.location.href;
    var expiry     = Date.now() + 25 * 60 * 1000; // 25 min

    var banner     = document.getElementById('import-progress-banner');
    var resultDiv  = document.getElementById('import-result-banner');

    if (banner) banner.classList.remove('hidden');

    function poll() {
        if (Date.now() > expiry) {
            showResult('warning', 'Import is taking longer than expected. Please refresh the page to check the result.');
            return;
        }

        fetch(statusUrl + '?key=' + encodeURIComponent(key), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'pending') {
                setTimeout(poll, 1500);
                return;
            }

            if (banner) banner.classList.add('hidden');

            if (data.status === 'done') {
                var msg = '<strong>' + data.created + '</strong> service(s) created, '
                        + '<strong>' + data.updated + '</strong> updated.';

                if (data.errors && data.errors.length > 0) {
                    msg += '<br><span class="font-medium mt-1 block">Warnings (' + data.errors.length + '):</span>'
                         + '<ul class="list-disc list-inside text-xs mt-1 space-y-0.5 max-h-32 overflow-y-auto">'
                         + data.errors.map(function(e) { return '<li>' + e + '</li>'; }).join('')
                         + '</ul>';
                    showResult('warning', msg);
                } else {
                    showResult('success', msg);
                }

                // Reload the table after a short delay so new services appear
                setTimeout(function() { window.location.href = indexUrl; }, 3000);

            } else if (data.status === 'failed') {
                showResult('error', data.message || 'Import failed. Please check your file and try again.');

            } else {
                // not_found — key expired or never written
                showResult('warning', 'Import status could not be determined. Please refresh the page.');
            }
        })
        .catch(function() {
            setTimeout(poll, 3000); // retry on network error
        });
    }

    function showResult(type, html) {
        if (!resultDiv) return;
        var styles = {
            success: 'bg-green-50 border-green-200 text-green-800',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
            error:   'bg-red-50 border-red-200 text-red-800',
        };
        var icons = {
            success: 'fa-check-circle',
            warning: 'fa-exclamation-triangle',
            error:   'fa-times-circle',
        };
        resultDiv.className = 'mb-4 border rounded-lg p-4 text-sm ' + (styles[type] || styles.warning);
        resultDiv.innerHTML = '<i class="fas ' + (icons[type] || icons.warning) + ' mr-1"></i>' + html;
        resultDiv.classList.remove('hidden');
    }

    poll();
})();
</script>
@endif

@endsection