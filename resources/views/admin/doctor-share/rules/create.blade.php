@extends('admin.layout')

@section('title', 'Create Share Rule')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Create Share Rule</h1>
    <a href="{{ route('doctor-share.rules.index') }}" class="text-gray-500 hover:text-gray-700 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i>Back to Rules
    </a>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('doctor-share.rules.store') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="doctor_id" class="block text-sm font-medium text-gray-700 mb-2">Doctor</label>
                <select id="doctor_id" name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">— Global Default (no doctor) —</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                            {{ $doctor->name }}
                        </option>
                    @endforeach
                </select>
                @error('doctor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="applies_to" class="block text-sm font-medium text-gray-700 mb-2">Applies To</label>
                <select id="applies_to" name="applies_to" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="all" {{ old('applies_to') == 'all' ? 'selected' : '' }}>All</option>
                    <option value="opd" {{ old('applies_to') == 'opd' ? 'selected' : '' }}>Opd</option>
                    <option value="ipd" {{ old('applies_to') == 'ipd' ? 'selected' : '' }}>Ipd</option>
                    <option value="investigation" {{ old('applies_to') == 'investigation' ? 'selected' : '' }}>Investigation</option>
                    <option value="emergency" {{ old('applies_to') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                </select>
                @error('applies_to')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="service_id" class="block text-sm font-medium text-gray-700 mb-2">Service</label>
                <select id="service_id" name="service_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">— None —</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                            {{ $service->name }}
                        </option>
                    @endforeach
                </select>
                @error('service_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="investigation_id" class="block text-sm font-medium text-gray-700 mb-2">Investigation</label>
                <select id="investigation_id" name="investigation_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">— None —</option>
                    @foreach($investigations as $investigation)
                        <option value="{{ $investigation->id }}" {{ old('investigation_id') == $investigation->id ? 'selected' : '' }}>
                            {{ $investigation->name }}
                        </option>
                    @endforeach
                </select>
                @error('investigation_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Share Type</label>
                <div class="flex gap-6 mt-1">
                    <label class="flex items-center">
                        <input type="radio" name="share_type" value="percentage"
                               {{ old('share_type', 'percentage') == 'percentage' ? 'checked' : '' }}
                               class="mr-2 text-medical-blue focus:ring-medical-blue">
                        Percentage
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="share_type" value="fixed"
                               {{ old('share_type', 'percentage') == 'fixed' ? 'checked' : '' }}
                               class="mr-2 text-medical-blue focus:ring-medical-blue">
                        Fixed
                    </label>
                </div>
                @error('share_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="share_value" class="block text-sm font-medium text-gray-700 mb-2">Share Value</label>
                <input type="number" id="share_value" name="share_value" step="0.01" min="0.01"
                       value="{{ old('share_value') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                @error('share_value')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('notes') }}</textarea>
                @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
            <a href="{{ route('doctor-share.rules.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                Cancel
            </a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Create Rule
            </button>
        </div>
    </form>
</div>
@endsection
