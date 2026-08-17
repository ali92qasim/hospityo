@extends('admin.layout')

@section('title', 'Edit Imaging Study - Hospital Management System')
@section('page-title', 'Edit Imaging Study')
@section('page-description', 'Update imaging study information')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Edit Imaging Study</h3>
                <a href="{{ route('imaging.studies.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Imaging Studies
                </a>
            </div>
        </div>
        <form action="{{ route('imaging.studies.update', $imagingStudy) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Study Code *</label>
                        <input type="text" name="code" value="{{ old('code', $imagingStudy->code) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Study Name *</label>
                        <input type="text" name="name" value="{{ old('name', $imagingStudy->name) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('description', $imagingStudy->description) }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                            @foreach(\App\Models\ImagingStudy::categories() as $category)
                                <option value="{{ $category }}" {{ old('category', $imagingStudy->category) == $category ? 'selected' : '' }}>{{ ucwords(str_replace('-', ' ', $category)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price ({{ currency_symbol() }}) *</label>
                        <input type="number" name="price" value="{{ old('price', $imagingStudy->price) }}" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Turnaround Time</label>
                        <input type="text" name="turnaround_time" value="{{ old('turnaround_time', $imagingStudy->turnaround_time) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                    <textarea name="instructions" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('instructions', $imagingStudy->instructions) }}</textarea>
                </div>
            </div>
            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('imaging.studies.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">Update Study</button>
            </div>
        </form>
    </div>
</div>
@endsection
