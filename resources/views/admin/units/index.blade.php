@extends('admin.layout')

@section('title', 'Units - Hospital Management System')
@section('page-title', 'Units')
@section('page-description', 'Manage medicine units and packaging')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-balance-scale text-blue-600 text-xl mr-3"></i>
            <div>
                <p class="text-sm text-blue-600">Total Units</p>
                <p class="text-2xl font-semibold text-blue-800">{{ $units->total() }}</p>
            </div>
        </div>
    </div>
    
    <a href="{{ route('units.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>Add Unit
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abbreviation</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Base Unit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conversion</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($units as $unit)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $unit->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $unit->abbreviation }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $unit->baseUnit ? $unit->baseUnit->name : 'Base Unit' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($unit->baseUnit)
                            1 {{ $unit->abbreviation }} = {{ $unit->conversion_factor }} {{ $unit->baseUnit->abbreviation }}
                        @else
                            Base Unit
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                            {{ ucfirst($unit->type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $unit->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $unit->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('units.edit', $unit) }}" class="text-yellow-600 hover:text-yellow-800 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('units.destroy', $unit) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
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
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No units found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $units->links() }}
@endsection