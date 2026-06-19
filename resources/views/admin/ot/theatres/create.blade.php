@extends('admin.layout')

@section('title', 'Add Operation Theatre')
@section('page-title', 'Add Operation Theatre')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">New Operation Theatre</h3>
        </div>
        <form action="{{ route('ot.theatres.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Theatre Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                        placeholder="e.g. OT-1">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="type" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                        <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>General</option>
                        <option value="cardiac" {{ old('type') == 'cardiac' ? 'selected' : '' }}>Cardiac</option>
                        <option value="ortho" {{ old('type') == 'ortho' ? 'selected' : '' }}>Orthopaedic</option>
                        <option value="ent" {{ old('type') == 'ent' ? 'selected' : '' }}>ENT</option>
                        <option value="ophthalmic" {{ old('type') == 'ophthalmic' ? 'selected' : '' }}>Ophthalmic</option>
                    </select>
                    @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Floor</label>
                    <input type="text" name="floor" value="{{ old('floor') }}"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                        placeholder="e.g. 2nd Floor">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                    placeholder="Equipment details, capacity, special features...">{{ old('notes') }}</textarea>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-medical-blue text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-save mr-2"></i>Create Theatre
                </button>
                <a href="{{ route('ot.theatres') }}" class="text-gray-600 hover:text-gray-800 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
