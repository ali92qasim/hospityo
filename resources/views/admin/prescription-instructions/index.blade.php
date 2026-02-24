@extends('admin.layout')

@section('title', 'Prescription Instructions')
@section('page-title', 'Prescription Instructions')
@section('page-description', 'Manage prescription instructions')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div></div>
    <a href="{{ route('prescription-instructions.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>Add Instruction
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Instruction</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($instructions as $instruction)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($instruction->category)
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($instruction->category) }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ Str::limit($instruction->instruction, 100) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full {{ $instruction->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $instruction->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('prescription-instructions.edit', $instruction) }}" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('prescription-instructions.destroy', $instruction) }}" method="POST" class="inline" onsubmit="return confirm('Delete this instruction?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No instructions found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $instructions->links() }}
@endsection
