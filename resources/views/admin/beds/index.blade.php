@extends('admin.layout')

@section('title', 'Beds Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Beds Management</h1>
    @can('create beds')
    <a href="{{ route('beds.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
        <i class="fas fa-plus mr-2"></i>Add Bed
    </a>
    @endcan
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bed Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ward</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Daily Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($beds as $bed)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $bed->bed_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $bed->ward->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ ucfirst($bed->bed_type) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ format_currency($bed->daily_rate) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'available' => 'bg-green-100 text-green-800',
                                'occupied' => 'bg-red-100 text-red-800',
                                'maintenance' => 'bg-yellow-100 text-yellow-800'
                            ];
                        @endphp
                        @canany(['change bed status', 'edit beds'])
                            @if(in_array($bed->status, ['available', 'maintenance'], true))
                                <form method="POST" action="{{ route('beds.update-status', $bed) }}" class="inline-flex items-center gap-2" data-bed-status-form>
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="text-xs border-gray-300 rounded-md py-1 pl-2 pr-6">
                                        <option value="available" @selected($bed->status === 'available')>Available</option>
                                        <option value="maintenance" @selected($bed->status === 'maintenance')>Maintenance</option>
                                    </select>
                                    <button type="submit" class="text-xs text-medical-blue hover:text-blue-700 font-medium">
                                        Update
                                    </button>
                                </form>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$bed->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($bed->status) }}
                                </span>
                            @endif
                        @else
                            <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$bed->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($bed->status) }}
                            </span>
                        @endcanany
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        @can('edit beds')
                        <a href="{{ route('beds.edit', $bed) }}" class="text-medical-blue hover:text-blue-700 mr-3" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endcan
                        @can('delete beds')
                        <form method="POST" action="{{ route('beds.destroy', $bed) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700" data-confirm="Are you sure?" data-confirm-variant="danger" data-confirm-text="Delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $beds->links() }}
    </div>
</div>
@endsection
