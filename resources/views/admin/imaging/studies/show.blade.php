@extends('admin.layout')

@section('title', 'Imaging Study Details - Hospital Management System')
@section('page-title', 'Imaging Study Details')
@section('page-description', 'View imaging study information')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $imagingStudy->name }}</h1>
        <p class="text-gray-600 mt-1">{{ $imagingStudy->description ?? 'No description available' }}</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('imaging.studies.edit', $imagingStudy) }}" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-edit mr-2"></i>Edit Study
        </a>
        <a href="{{ route('imaging.studies.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to Studies
        </a>
    </div>
</div>
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Study Code</label>
            <p class="text-gray-900">{{ $imagingStudy->code }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <p class="text-gray-900">{{ ucwords(str_replace('-', ' ', $imagingStudy->category)) }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
            <p class="text-gray-900">{{ currency_symbol() }}{{ number_format($imagingStudy->price, 2) }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Turnaround Time</label>
            <p class="text-gray-900">{{ $imagingStudy->turnaround_time ?? 'N/A' }}</p>
        </div>
    </div>
</div>
@endsection
