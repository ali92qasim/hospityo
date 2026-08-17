@extends('admin.layout')

@section('title', 'Create Imaging Study - Hospital Management System')
@section('page-title', 'Create Imaging Study')
@section('page-description', 'Add a new imaging study')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('imaging.studies.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Study Code</label>
                <input type="text" name="code" value="{{ old('code') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Study Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Category</option>
                    @foreach(\App\Models\ImagingStudy::categories() as $category)
                        <option value="{{ $category }}" @selected(old('category') === $category)>{{ ucwords(str_replace('-', ' ', $category)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price ({{ currency_symbol() }})</label>
                <input type="number" name="price" min="0" step="0.01" value="{{ old('price') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Turnaround Time</label>
                <input type="text" name="turnaround_time" value="{{ old('turnaround_time') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="e.g. 24 hours">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ old('description') }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                <textarea name="instructions" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ old('instructions') }}</textarea>
            </div>
        </div>
        <div class="flex justify-end space-x-4 mt-6">
            <a href="{{ route('imaging.studies.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create Study
            </button>
        </div>
    </form>
</div>
@endsection
