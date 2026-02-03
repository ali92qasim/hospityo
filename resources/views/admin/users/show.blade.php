@extends('admin.layout')

@section('title', 'User Details')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">User Details</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Name</label>
                    <p class="text-gray-900">{{ $user->name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Email</label>
                    <p class="text-gray-900">{{ $user->email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Status</label>
                    @if($user->email_verified_at)
                        <span class="bg-green-100 text-green-800 text-sm px-2 py-1 rounded">Verified</span>
                    @else
                        <span class="bg-yellow-100 text-yellow-800 text-sm px-2 py-1 rounded">Unverified</span>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Created</label>
                    <p class="text-gray-900">{{ $user->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Roles & Permissions</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Assigned Roles</label>
                    <div class="flex flex-wrap gap-2">
                        @forelse($user->roles as $role)
                            <span class="bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full">{{ $role->name }}</span>
                        @empty
                            <span class="text-gray-400">No roles assigned</span>
                        @endforelse
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">All Permissions</label>
                    <div class="flex flex-wrap gap-2">
                        @forelse($user->getAllPermissions() as $permission)
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">{{ $permission->name }}</span>
                        @empty
                            <span class="text-gray-400">No permissions</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end space-x-3 mt-6">
        <a href="{{ route('users.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Back</a>
        <a href="{{ route('users.edit', $user) }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">Edit User</a>
    </div>
</div>
@endsection