@extends('admin.layout')

@section('title', 'Users Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Users Management</h1>
    <a href="{{ route('users.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
        <i class="fas fa-plus mr-2"></i>Add User
    </a>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roles</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($users as $user)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $user->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $user->email }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @forelse($user->roles as $role)
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ $role->name }}</span>
                            @empty
                                <span class="text-gray-400 text-sm">No roles</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($user->email_verified_at)
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Verified</span>
                        @else
                            <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Unverified</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('users.show', $user) }}" class="text-medical-blue hover:text-blue-700 mr-3"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('users.edit', $user) }}" class="text-medical-blue hover:text-blue-700 mr-3"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
