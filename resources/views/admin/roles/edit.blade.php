@extends('admin.layout')

@section('title', 'Edit Role')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Role</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf @method('PUT')
        
        <div class="mb-6">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Role Name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                   required>
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($permissions as $permission)
                    <label class="flex items-center">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                               {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue">
                        <span class="ml-2 text-sm text-gray-700">{{ $permission->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('roles.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Cancel</a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">Update Role</button>
        </div>
    </form>
</div>
@endsection