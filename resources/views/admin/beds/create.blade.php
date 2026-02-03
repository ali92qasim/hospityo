@extends('admin.layout')

@section('title', 'Add Bed')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Add Bed</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('beds.store') }}">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="bed_number" class="block text-sm font-medium text-gray-700 mb-2">Bed Number</label>
                <input type="text" id="bed_number" name="bed_number" value="{{ old('bed_number') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                @error('bed_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="ward_id" class="block text-sm font-medium text-gray-700 mb-2">Ward</label>
                <select id="ward_id" name="ward_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="">Select Ward</option>
                    @foreach($wards as $ward)
                        <option value="{{ $ward->id }}" {{ old('ward_id') == $ward->id ? 'selected' : '' }}>
                            {{ $ward->name }}
                        </option>
                    @endforeach
                </select>
                @error('ward_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="bed_type" class="block text-sm font-medium text-gray-700 mb-2">Bed Type</label>
                <select id="bed_type" name="bed_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="">Select Type</option>
                    <option value="general" {{ old('bed_type') == 'general' ? 'selected' : '' }}>General</option>
                    <option value="private" {{ old('bed_type') == 'private' ? 'selected' : '' }}>Private</option>
                    <option value="icu" {{ old('bed_type') == 'icu' ? 'selected' : '' }}>ICU</option>
                    <option value="emergency" {{ old('bed_type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                </select>
                @error('bed_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="daily_rate" class="block text-sm font-medium text-gray-700 mb-2">Daily Rate (₨)</label>
                <input type="number" id="daily_rate" name="daily_rate" value="{{ old('daily_rate') }}" min="0" step="0.01"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                @error('daily_rate')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="available" {{ old('status', 'available') == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="occupied" {{ old('status') == 'occupied' ? 'selected' : '' }}>Occupied</option>
                    <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex justify-end space-x-4 mt-6">
            <a href="{{ route('beds.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Save Bed
            </button>
        </div>
    </form>
</div>
@endsection