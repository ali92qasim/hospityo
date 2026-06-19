@extends('admin.layout')

@section('title', 'Edit Operation Theatre')
@section('page-title', 'Edit Operation Theatre')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Edit — {{ $theatre->name }}</h3>
        </div>
        <form action="{{ route('ot.theatres.update', $theatre) }}" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Theatre Name *</label>
                    <input type="text" name="name" value="{{ old('name', $theatre->name) }}" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="type" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                        @foreach(['general', 'cardiac', 'ortho', 'ent', 'ophthalmic'] as $t)
                            <option value="{{ $t }}" {{ old('type', $theatre->type) == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="status" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                        @foreach(['available', 'occupied', 'maintenance'] as $s)
                            <option value="{{ $s }}" {{ old('status', $theatre->status) == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Floor</label>
                    <input type="text" name="floor" value="{{ old('floor', $theatre->floor) }}"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">{{ old('notes', $theatre->notes) }}</textarea>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $theatre->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-medical-blue text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-save mr-2"></i>Update Theatre
                </button>
                <a href="{{ route('ot.theatres') }}" class="text-gray-600 hover:text-gray-800 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
