@extends('super-admin.layout')

@section('title', 'Site Settings')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Site Settings</h3>
            <p class="text-sm text-gray-500 mt-1">Manage contact information displayed on the public website.</p>
        </div>
        <form action="{{ route('super-admin.site-settings.update') }}" method="POST" class="p-6">
            @csrf @method('PUT')

            <div class="space-y-5">
                @foreach($fields as $key => $label)
                <div>
                    <label for="{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                    @if($key === 'office_address')
                        <textarea name="{{ $key }}" id="{{ $key }}" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">{{ old($key, $settings[$key] ?? '') }}</textarea>
                    @else
                        <input type="text" name="{{ $key }}" id="{{ $key }}" value="{{ old($key, $settings[$key] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">
                    @endif
                    @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                @endforeach
            </div>

            <div class="flex justify-end mt-8 pt-6 border-t">
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-save mr-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
