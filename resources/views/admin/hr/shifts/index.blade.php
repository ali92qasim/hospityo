@extends('admin.layout')

@section('title', 'Shifts')
@section('page-title', 'Shifts')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Shifts</h3>
                <p class="text-sm text-gray-600">Manage work shifts and schedules</p>
            </div>
            <a href="{{ route('hr.shifts.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add Shift
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Range</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Working Hours</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grace Minutes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignments</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($shifts as $shift)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $shift->name }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700 font-mono">{{ $shift->code }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $shift->working_hours }} hrs</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $shift->break_duration }} min</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $shift->grace_minutes }} min</td>
                    <td class="px-6 py-4">
                        <span class="inline-block w-4 h-4 rounded-full border border-gray-200" style="background-color: {{ $shift->color }};" title="{{ $shift->color }}"></span>
                    </td>
                    <td class="px-6 py-4">
                        @if($shift->is_overnight)
                            <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">
                                <i class="fas fa-moon mr-1"></i>Overnight
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-sun mr-1"></i>Day
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $shift->duty_rosters_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-sm font-medium space-x-2">
                        <a href="{{ route('hr.shifts.edit', $shift) }}" class="text-medical-green hover:text-green-700">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('hr.shifts.destroy', $shift) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this shift?')">
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
                    <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-clock text-4xl mb-4 text-gray-300"></i>
                        <p>No shifts found</p>
                        <a href="{{ route('hr.shifts.create') }}" class="mt-2 inline-block text-medical-blue hover:underline">
                            Create your first shift
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
