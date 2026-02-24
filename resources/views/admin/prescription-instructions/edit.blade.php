@extends('admin.layout')

@section('title', 'Edit Prescription Instruction')
@section('page-title', 'Edit Prescription Instruction')
@section('page-description', 'Update prescription instruction')

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('prescription-instructions.update', $prescriptionInstruction) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Title (Optional)
                </label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    value="{{ old('title', $prescriptionInstruction->title) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue @error('title') border-red-500 @enderror"
                    placeholder="e.g., Take with food"
                >
                @error('title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                    Category
                </label>
                <select 
                    name="category" 
                    id="category" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue @error('category') border-red-500 @enderror"
                >
                    <option value="">Select Category</option>
                    <option value="frequency" {{ old('category', $prescriptionInstruction->category) === 'frequency' ? 'selected' : '' }}>Frequency</option>
                    <option value="meal" {{ old('category', $prescriptionInstruction->category) === 'meal' ? 'selected' : '' }}>Meal</option>
                    <option value="time" {{ old('category', $prescriptionInstruction->category) === 'time' ? 'selected' : '' }}>Time</option>
                    <option value="duration" {{ old('category', $prescriptionInstruction->category) === 'duration' ? 'selected' : '' }}>Duration</option>
                    <option value="conditional" {{ old('category', $prescriptionInstruction->category) === 'conditional' ? 'selected' : '' }}>Conditional</option>
                    <option value="injection" {{ old('category', $prescriptionInstruction->category) === 'injection' ? 'selected' : '' }}>Injection</option>
                </select>
                @error('category')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="instruction" class="block text-sm font-medium text-gray-700 mb-2">
                    Instruction <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="instruction" 
                    id="instruction" 
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue @error('instruction') border-red-500 @enderror"
                    placeholder="Enter detailed instruction..."
                    required
                >{{ old('instruction', $prescriptionInstruction->instruction) }}</textarea>
                @error('instruction')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        value="1"
                        {{ old('is_active', $prescriptionInstruction->is_active) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue"
                    >
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex items-center space-x-3">
                <button type="submit" class="bg-medical-blue text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update Instruction
                </button>
                <a href="{{ route('prescription-instructions.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
