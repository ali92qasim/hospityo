@extends('admin.layout')

@section('title', 'Imaging Reports - Hospital Management System')
@section('page-title', 'Imaging Reports')
@section('page-description', 'View and manage imaging study reports')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-semibold text-gray-800">Imaging Reports</h3>
            </div>
        </div>
    </div>

    <div class="p-6 border-b border-gray-200 bg-gray-50">
        <form method="GET" class="flex items-center space-x-4">
            <div class="flex-1">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="final" {{ request('status') === 'final' ? 'selected' : '' }}>Final</option>
                    <option value="amended" {{ request('status') === 'amended' ? 'selected' : '' }}>Amended</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Study</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Radiologist</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reported</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($results as $result)
                @php
                    $study = $result->imagingOrder?->items->first()?->imagingStudy ?? $result->investigationOrder?->items->first()?->investigation;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $result->imagingOrder->patient->name ?? $result->investigationOrder->patient->name }}</div>
                        <div class="text-xs text-gray-500">{{ $result->imagingOrder->patient->phone ?? $result->investigationOrder->patient->phone }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $study?->name ?? 'Unknown study' }}</div>
                        <div class="text-xs text-gray-500">Order #{{ $result->imagingOrder->order_number ?? $result->investigationOrder->order_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst(str_replace('-', ' ', $study?->category ?? 'imaging')) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $result->radiologist->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                            {{ $result->status === 'final' ? 'bg-green-100 text-green-800' :
                               ($result->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                            {{ ucfirst($result->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $result->reported_at ? $result->reported_at->format('M d, Y') : 'Not finalized' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('radiology-results.show', $result) }}" class="text-medical-blue hover:text-blue-700" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            @can('edit radiology results')
                            <a href="{{ route('radiology-results.edit', $result) }}" class="text-yellow-600 hover:text-yellow-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <i class="fas fa-file-image text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">No imaging reports found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($results->hasPages())
    <div class="p-6 border-t border-gray-200">
        {{ $results->links() }}
    </div>
    @endif
</div>
@endsection
