@extends('admin.layout')

@section('title', 'Add New Doctor - Hospital Management System')
@section('page-title', 'Add New Doctor')
@section('page-description', 'Register a new medical professional')

@push('styles')
@vite(['resources/css/doctors-form.css'])
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Doctor Information</h3>
                <a href="{{ route('doctors.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Doctors
                </a>
            </div>
        </div>

        <form action="{{ route('doctors.store') }}" method="POST" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="md:col-span-2">
                    <h4 class="text-md font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-md mr-2 text-medical-green"></i>
                        Personal Information
                    </h4>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                    <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea name="address" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('address') }}</textarea>
                </div>

                <!-- Professional Information -->
                <div class="md:col-span-2 mt-6">
                    <h4 class="text-md font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-stethoscope mr-2 text-medical-blue"></i>
                        Professional Information
                    </h4>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Specialization *</label>
                    <input type="text" name="specialization" value="{{ old('specialization') }}"
                           placeholder="e.g., Cardiology, Neurology"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Qualification *</label>
                    <input type="text" name="qualification" value="{{ old('qualification') }}"
                           placeholder="e.g., MBBS, MD"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">PMDC Number</label>
                    <input type="text" name="pmdc_number" value="{{ old('pmdc_number') }}"
                           placeholder="e.g., 12345-A"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Experience (Years) *</label>
                    <input type="number" name="experience_years" value="{{ old('experience_years') }}" min="0" max="50"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Consultation Fee *</label>
                    <input type="number" name="consultation_fee" value="{{ old('consultation_fee') }}" min="0" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                    <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="">Select Department</option>
                        @foreach(\App\Models\Department::where('status', 'active')->get() as $department)
                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Schedule Information -->
                <div class="md:col-span-2 mt-6">
                    <h4 class="text-md font-medium text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock mr-2 text-yellow-500"></i>
                        Schedule Information
                    </h4>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Shift Start *</label>
                    <input type="text" name="shift_start" value="{{ old('shift_start') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           placeholder="HH:MM"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Shift End *</label>
                    <input type="text" name="shift_end" value="{{ old('shift_end') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                           placeholder="HH:MM"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Available Days</label>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                        <label class="flex items-center">
                            <input type="checkbox" name="available_days[]" value="{{ $day }}"
                                   {{ in_array($day, old('available_days', [])) ? 'checked' : '' }}
                                   class="mr-2 text-medical-blue">
                            <span class="text-sm">{{ $day }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('doctors.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Save Doctor
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
@vite(['resources/js/doctors-form.js'])
@endpush
@endsection
