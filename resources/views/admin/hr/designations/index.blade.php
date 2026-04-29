@extends('admin.layout')

@section('title', 'Designations')
@section('page-title', 'Designations')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Designations</h3>
                <p class="text-sm text-gray-600">Manage employee designations and roles</p>
            </div>
            <a href="{{ route('hr.designations.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add Designation
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employees</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($designations as $designation)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $designation->name }}</div>
                        @if($designation->description)
                            <div class="text-xs text-gray-500">{{ Str::limit($designation->description, 60) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $categoryBadges = [
                                'medical'   => 'bg-blue-100 text-blue-800',
                                'nursing'   => 'bg-green-100 text-green-800',
                                'admin'     => 'bg-purple-100 text-purple-800',
                                'technical' => 'bg-orange-100 text-orange-800',
                                'support'   => 'bg-gray-100 text-gray-800',
                            ];
                            $categoryBadge = $categoryBadges[$designation->category] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $categoryBadge }}">
                            {{ ucfirst($designation->category) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $designation->employees_count }}</td>
                    <td class="px-6 py-4 text-sm font-medium space-x-2">
                        <a href="{{ route('hr.designations.edit', $designation) }}" class="text-medical-green hover:text-green-700">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('hr.designations.destroy', $designation) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this designation?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-id-badge text-4xl mb-4 text-gray-300"></i>
                        <p>No designations found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
