@extends('admin.layout')

@section('title', 'Leave Types')
@section('page-title', 'Leave Types')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Leave Types</h3>
                <p class="text-sm text-gray-600">Manage leave type configurations</p>
            </div>
            <a href="{{ route('hr.leave-types.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add Leave Type
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Days</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carry Forward</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requires Document</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requests</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($leaveTypes ?? [] as $type)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $type->name }}</div>
                        @if($type->description)
                            <div class="text-xs text-gray-500">{{ Str::limit($type->description, 50) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700 font-mono">{{ $type->code }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $type->default_days }}</td>
                    <td class="px-6 py-4">
                        @if($type->is_paid)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Yes</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">No</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($type->is_carry_forward)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Yes</span>
                            @if($type->max_carry_forward_days)
                                <span class="text-xs text-gray-500 ml-1">(max {{ $type->max_carry_forward_days }}d)</span>
                            @endif
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">No</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($type->requires_document)
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Yes</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">No</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $type->requests_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-sm font-medium space-x-2">
                        <a href="{{ route('hr.leave-types.edit', $type) }}" class="text-medical-blue hover:text-blue-700">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('hr.leave-types.destroy', $type) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this leave type?')">
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
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-list-alt text-4xl mb-4 text-gray-300"></i>
                        <p>No leave types configured</p>
                        <a href="{{ route('hr.leave-types.create') }}" class="mt-2 inline-block text-medical-blue hover:underline">
                            Add your first leave type
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
