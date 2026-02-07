@extends('admin.layout')

@section('title', 'Lab Test Details - Laboratory Information System')
@section('page-title', 'Lab Test Details')
@section('page-description', 'View lab test information and parameters')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $labTest->name }}</h1>
        <p class="text-gray-600 mt-1">{{ $labTest->description ?? 'No description available' }}</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('lab-tests.edit', $labTest) }}" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-edit mr-2"></i>Edit Test
        </a>
        <a href="{{ route('lab-tests.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to Tests
        </a>
    </div>
</div>

<!-- Test Information -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Test Information</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Test Name</label>
                <p class="text-gray-900">{{ $labTest->name }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Test Code</label>
                <p class="text-gray-900">{{ $labTest->code ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <p class="text-gray-900">{{ $labTest->category ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                <p class="text-gray-900">₨{{ number_format($labTest->price ?? 0, 2) }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <p class="text-gray-900">{{ $labTest->description ?? 'No description available' }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Test Parameters -->
@if($labTest->load('parameters') && $labTest->parameters->isNotEmpty())
<div class="bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Test Parameters ({{ $labTest->parameters->count() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parameter Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference Range</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($labTest->parameters as $parameter)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                            {{ $parameter->parameter_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                            {{ $parameter->unit ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                            {{ is_array($parameter->reference_ranges) ? ($parameter->reference_ranges['range'] ?? '-') : ($parameter->reference_ranges ?? '-') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium bg-blue-100 text-blue-800">
                                {{ $parameter->data_type ?? 'Numeric' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Test Parameters</h3>
    </div>
    <div class="p-6 text-center text-gray-500">
        <i class="fas fa-flask text-3xl mb-3"></i>
        <p>This test has no specific parameters. Results will be entered as free text.</p>
    </div>
</div>
@endif
@endsection
