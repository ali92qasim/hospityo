@extends('admin.layout')

@section('title', 'System Settings')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-hospital mr-2 text-medical-blue"></i>Hospital Information
    </h2>
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
        @csrf
        
        <!-- Hospital Logo -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Hospital Logo</label>
            <div class="flex items-center space-x-6">
                <div class="shrink-0">
                    @if(cache('settings.hospital_logo'))
                        <img class="h-16 w-16 object-cover rounded-lg" src="{{ asset('storage/' . cache('settings.hospital_logo')) }}" alt="Hospital Logo">
                    @else
                        <div class="h-16 w-16 bg-gray-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-hospital text-gray-400 text-xl"></i>
                        </div>
                    @endif
                </div>
                <label class="block">
                    <span class="sr-only">Choose logo</span>
                    <input type="file" name="hospital_logo" accept="image/*" 
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-medical-blue file:text-white hover:file:bg-blue-700">
                </label>
            </div>
            @error('hospital_logo')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label for="hospital_name" class="block text-sm font-medium text-gray-700 mb-2">Hospital Name</label>
                <input type="text" id="hospital_name" name="hospital_name" 
                       value="{{ old('hospital_name', cache('settings.hospital_name', 'Hospital Management System')) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                       required>
                @error('hospital_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label for="hospital_address" class="block text-sm font-medium text-gray-700 mb-2">Hospital Address</label>
                <textarea id="hospital_address" name="hospital_address" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                          required>{{ old('hospital_address', cache('settings.hospital_address', '123 Medical Street, Healthcare City')) }}</textarea>
                @error('hospital_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="hospital_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="text" id="hospital_phone" name="hospital_phone" 
                       value="{{ old('hospital_phone', cache('settings.hospital_phone', '+92-XXX-XXXXXXX')) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                       required>
                @error('hospital_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="hospital_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" id="hospital_email" name="hospital_email" 
                       value="{{ old('hospital_email', cache('settings.hospital_email', 'info@hospital.com')) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                       required>
                @error('hospital_email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <h3 class="text-md font-semibold text-gray-800 mt-8 mb-4">
            <i class="fas fa-cogs mr-2 text-medical-blue"></i>System Preferences
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                <select id="currency" name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="PKR" {{ old('currency', cache('settings.currency', 'PKR')) == 'PKR' ? 'selected' : '' }}>Pakistani Rupee (₨)</option>
                    <option value="USD" {{ old('currency', cache('settings.currency')) == 'USD' ? 'selected' : '' }}>US Dollar ($)</option>
                    <option value="EUR" {{ old('currency', cache('settings.currency')) == 'EUR' ? 'selected' : '' }}>Euro (€)</option>
                    <option value="GBP" {{ old('currency', cache('settings.currency')) == 'GBP' ? 'selected' : '' }}>British Pound (£)</option>
                </select>
                @error('currency')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                <select id="timezone" name="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="Asia/Karachi" {{ old('timezone', cache('settings.timezone', 'Asia/Karachi')) == 'Asia/Karachi' ? 'selected' : '' }}>Asia/Karachi (PKT)</option>
                    <option value="UTC" {{ old('timezone', cache('settings.timezone')) == 'UTC' ? 'selected' : '' }}>UTC</option>
                    <option value="America/New_York" {{ old('timezone', cache('settings.timezone')) == 'America/New_York' ? 'selected' : '' }}>America/New_York (EST)</option>
                    <option value="Europe/London" {{ old('timezone', cache('settings.timezone')) == 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT)</option>
                </select>
                @error('timezone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="date_format" class="block text-sm font-medium text-gray-700 mb-2">Date Format</label>
                <select id="date_format" name="date_format" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="d/m/Y" {{ old('date_format', cache('settings.date_format', 'd/m/Y')) == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                    <option value="m/d/Y" {{ old('date_format', cache('settings.date_format')) == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                    <option value="Y-m-d" {{ old('date_format', cache('settings.date_format')) == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                    <option value="M d, Y" {{ old('date_format', cache('settings.date_format')) == 'M d, Y' ? 'selected' : '' }}>Mon DD, YYYY</option>
                </select>
                @error('date_format')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="time_format" class="block text-sm font-medium text-gray-700 mb-2">Time Format</label>
                <select id="time_format" name="time_format" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="H:i" {{ old('time_format', cache('settings.time_format', 'H:i')) == 'H:i' ? 'selected' : '' }}>24 Hour (HH:MM)</option>
                    <option value="h:i A" {{ old('time_format', cache('settings.time_format')) == 'h:i A' ? 'selected' : '' }}>12 Hour (HH:MM AM/PM)</option>
                </select>
                @error('time_format')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex justify-end mt-8">
            <button type="submit" class="bg-medical-blue text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Save Settings
            </button>
        </div>
    </form>
</div>
@endsection