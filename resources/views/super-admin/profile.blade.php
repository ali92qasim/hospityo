@extends('super-admin.layout')
@section('title', 'Profile')
@section('page-title', 'Profile Settings')
@section('page-description', 'Manage your account')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        {{-- Profile Information --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Profile Information</h2>
            <form method="POST" action="{{ route('super-admin.profile.update') }}">
                @csrf @method('PATCH')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h2>
            <form method="POST" action="{{ route('super-admin.profile.password') }}">
                @csrf @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" id="current_password" name="current_password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" id="password" name="password"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-red-600 text-white px-4 py-2.5 rounded-lg hover:bg-red-700 text-sm">
                        <i class="fas fa-key mr-2"></i>Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Profile Summary --}}
    <div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Account Summary</h3>
            <div class="text-center mb-4">
                <div class="w-20 h-20 bg-gray-900 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <h4 class="font-medium text-gray-900">{{ $user->name }}</h4>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <span class="inline-block mt-2 text-xs px-3 py-1 bg-gray-900 text-white rounded-full">Super Admin</span>
            </div>
            <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                <div class="flex justify-between">
                    <span class="text-gray-500">Member Since</span>
                    <span class="text-gray-900">{{ $user->created_at->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Last Updated</span>
                    <span class="text-gray-900">{{ $user->updated_at->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
