@extends('admin.layout')

@section('title', 'Profile Settings')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Profile Settings</h1>
</div>

@if(session('status') === 'profile-updated')
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i>Profile updated successfully.
    </div>
@endif

@if(session('status') === 'password-updated')
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i>Password updated successfully.
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <!-- Profile Information -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Profile Information</h2>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PATCH')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @if($user->isDirty('email') && !$user->email_verified_at)
                            <p class="mt-1 text-sm text-yellow-600">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Your email address is unverified.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>

        @if($doctor)
        <!-- Doctor Professional Information -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-stethoscope mr-2 text-medical-blue"></i>Professional Information
            </h2>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PATCH')

                {{-- Hidden user fields to pass through --}}
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $doctor->phone) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                        <input type="text" name="specialization" value="{{ old('specialization', $doctor->specialization) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                               placeholder="e.g., Cardiology">
                        @error('specialization') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Qualification</label>
                        <input type="text" name="qualification" value="{{ old('qualification', $doctor->qualification) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                               placeholder="e.g., MBBS, MD">
                        @error('qualification') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PMDC Number</label>
                        <input type="text" name="pmdc_number" value="{{ old('pmdc_number', $doctor->pmdc_number) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                               placeholder="e.g., 12345-A">
                        @error('pmdc_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Experience (Years)</label>
                        <input type="number" name="experience_years" value="{{ old('experience_years', $doctor->experience_years) }}" min="0" max="60"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @error('experience_years') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Consultation Fee</label>
                        <input type="number" name="consultation_fee" value="{{ old('consultation_fee', $doctor->consultation_fee) }}" min="0" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @error('consultation_fee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Shift Start</label>
                        <input type="time" name="shift_start" value="{{ old('shift_start', $doctor->shift_start) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @error('shift_start') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Shift End</label>
                        <input type="time" name="shift_end" value="{{ old('shift_end', $doctor->shift_end) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @error('shift_end') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Available Days</label>
                        <div class="flex flex-wrap gap-4">
                            @php $currentDays = old('available_days', $doctor->available_days ?? []); @endphp
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                            <label class="flex items-center">
                                <input type="checkbox" name="available_days[]" value="{{ $day }}"
                                       {{ in_array($day, $currentDays) ? 'checked' : '' }}
                                       class="mr-2 text-medical-blue rounded">
                                <span class="text-sm text-gray-700">{{ $day }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea name="address" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('address', $doctor->address) }}</textarea>
                        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Update Professional Info
                    </button>
                </div>
            </form>
        </div>
        @endif

        <!-- Change Password -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h2>
            <form method="POST" action="{{ route('password.update') }}">
                @csrf @method('PUT')
                
                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                               required>
                        @error('current_password', 'updatePassword')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" id="password" name="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                               required>
                        @error('password', 'updatePassword')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                               required>
                        @error('password_confirmation', 'updatePassword')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                        <i class="fas fa-key mr-2"></i>Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div>
        <!-- Profile Summary -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Profile Summary</h3>
            <div class="text-center mb-4">
                <div class="w-20 h-20 bg-medical-blue rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-user text-white text-2xl"></i>
                </div>
                <h4 class="font-medium text-gray-900">{{ $user->name }}</h4>
                <p class="text-sm text-gray-600">{{ $user->email }}</p>
            </div>
            
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Member Since:</span>
                    <span class="text-gray-900">{{ $user->created_at->format('M Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Email Status:</span>
                    @if($user->email_verified_at)
                        <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Verified</span>
                    @else
                        <span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>Unverified</span>
                    @endif
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Roles:</span>
                    <div class="text-right">
                        @forelse($user->roles as $role)
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mb-1 inline-block">{{ $role->name }}</span>
                        @empty
                            <span class="text-gray-400">No roles</span>
                        @endforelse
                    </div>
                </div>
                @if($doctor)
                <div class="flex justify-between">
                    <span class="text-gray-600">Specialization:</span>
                    <span class="text-gray-900">{{ $doctor->specialization ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Doctor No:</span>
                    <span class="text-gray-900">{{ $doctor->doctor_no }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Account Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Account Actions</h3>
            <div class="space-y-3">
                @if(!$user->email_verified_at)
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="w-full bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 text-sm">
                        <i class="fas fa-envelope mr-2"></i>Resend Email Verification
                    </button>
                </form>
                @endif
                
                <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Are you sure? This action cannot be undone.')">
                    @csrf @method('DELETE')
                    <input type="password" name="password" placeholder="Enter password to confirm" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-2 text-sm" required>
                    <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm">
                        <i class="fas fa-trash mr-2"></i>Delete Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection