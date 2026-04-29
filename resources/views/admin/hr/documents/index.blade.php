@extends('admin.layout')

@section('title', 'Document Management')
@section('page-title', 'Document Management')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Documents</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-file-alt text-medical-blue text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Expired</p>
                <p class="text-2xl font-bold text-red-600">{{ $stats['expired'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Expiring in 30 Days</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['expiring_30'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Unverified</p>
                <p class="text-2xl font-bold text-orange-600">{{ $stats['unverified'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-question-circle text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Missing Mandatory</p>
                <p class="text-2xl font-bold text-red-600">{{ $stats['mandatory_missing'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-file-excel text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filter Pills & Action Buttons -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            @php
                $filters = [
                    'all' => ['label' => 'All', 'icon' => 'fas fa-list'],
                    'expired' => ['label' => 'Expired', 'icon' => 'fas fa-exclamation-circle'],
                    'expiring' => ['label' => 'Expiring Soon', 'icon' => 'fas fa-clock'],
                    'unverified' => ['label' => 'Unverified', 'icon' => 'fas fa-question-circle'],
                    'mandatory' => ['label' => 'Mandatory', 'icon' => 'fas fa-asterisk'],
                ];
            @endphp
            @foreach($filters as $key => $f)
                <a href="{{ route('hr.documents.index', ['filter' => $key]) }}"
                   class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $filter === $key ? 'bg-medical-blue text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="{{ $f['icon'] }} mr-1"></i>{{ $f['label'] }}
                </a>
            @endforeach
        </div>
        <div class="flex gap-2">
            <a href="{{ route('hr.documents.compliance') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm flex items-center">
                <i class="fas fa-chart-bar mr-2"></i>Compliance Report
            </a>
            <a href="{{ route('hr.documents.requirements') }}" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center">
                <i class="fas fa-clipboard-list mr-2"></i>Requirements
            </a>
        </div>
    </div>
</div>

<!-- Documents Table -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Documents</h3>
        <p class="text-sm text-gray-600">Total: {{ $documents->total() }} documents</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($documents as $document)
                <tr class="hover:bg-gray-50">
                    {{-- Employee --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($document->employee->photo)
                                <img src="{{ asset('storage/' . $document->employee->photo) }}" alt="{{ $document->employee->full_name }}" class="w-10 h-10 rounded-full object-cover mr-3">
                            @else
                                <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                    {{ strtoupper(substr($document->employee->first_name, 0, 1) . substr($document->employee->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $document->employee->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $document->employee->department->name ?? '—' }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- Document Title --}}
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $document->title }}</td>

                    {{-- Type Badge --}}
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            {{ strtoupper($document->document_type) }}
                        </span>
                    </td>

                    {{-- Document Number --}}
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $document->document_number ?? '—' }}</td>

                    {{-- Issue Date --}}
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $document->issue_date ? $document->issue_date->format('d M Y') : '—' }}
                    </td>

                    {{-- Expiry Date --}}
                    <td class="px-6 py-4">
                        @if($document->expiry_date)
                            @if($document->isExpired())
                                <span class="text-sm font-medium text-red-600">{{ $document->expiry_date->format('d M Y') }}</span>
                                <div class="text-xs text-red-500">Expired {{ abs($document->days_until_expiry) }} days ago</div>
                            @elseif($document->isExpiringSoon())
                                <span class="text-sm font-medium text-yellow-600">{{ $document->expiry_date->format('d M Y') }}</span>
                                <div class="text-xs text-yellow-500">{{ $document->days_until_expiry }} days left</div>
                            @else
                                <span class="text-sm font-medium text-green-600">{{ $document->expiry_date->format('d M Y') }}</span>
                                <div class="text-xs text-green-500">{{ $document->days_until_expiry }} days left</div>
                            @endif
                        @else
                            <span class="text-sm text-gray-400">—</span>
                        @endif
                    </td>

                    {{-- Verified Badge --}}
                    <td class="px-6 py-4">
                        @if($document->is_verified)
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Verified
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                                <i class="fas fa-times-circle mr-1"></i>Unverified
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4 text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            @if($document->is_verified)
                                <form action="{{ route('hr.documents.unverify', $document) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition-colors" title="Unverify">
                                        <i class="fas fa-undo mr-1"></i>Unverify
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('hr.documents.verify', $document) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors" title="Verify">
                                        <i class="fas fa-check mr-1"></i>Verify
                                    </button>
                                </form>
                            @endif

                            @if($document->file_path)
                                <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="px-3 py-1 text-xs bg-blue-100 text-blue-800 rounded-lg hover:bg-blue-200 transition-colors" title="Download">
                                    <i class="fas fa-download mr-1"></i>Download
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-file-alt text-4xl mb-4 text-gray-300"></i>
                        <p>No documents found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($documents->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $documents->links() }}
    </div>
    @endif
</div>
@endsection
