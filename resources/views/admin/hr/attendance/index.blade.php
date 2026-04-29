@extends('admin.layout')

@section('title', 'Daily Attendance')
@section('page-title', 'Daily Attendance')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Staff</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-medical-blue"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Present</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['present'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-check text-green-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Absent</p>
                <p class="text-2xl font-bold text-red-600">{{ $stats['absent'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-times text-red-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Late</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['late'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">On Leave</p>
                <p class="text-2xl font-bold text-blue-600">{{ $stats['on_leave'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-minus text-blue-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Not Marked</p>
                <p class="text-2xl font-bold text-gray-500">{{ $stats['not_marked'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-question-circle text-gray-500"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4">
        <form action="{{ route('hr.attendance.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <input type="date" name="date" value="{{ $date }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
            </div>
            <div>
                <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">All Departments</option>
                    @foreach($departments ?? [] as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply
                </button>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('hr.attendance.mark', ['date' => $date]) }}" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-center">
                    <i class="fas fa-clipboard-check mr-1"></i>Mark Attendance
                </a>
                <a href="{{ route('hr.attendance.monthly') }}" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-center">
                    <i class="fas fa-calendar-alt mr-1"></i>Monthly View
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Table -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Attendance for {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</h3>
                <p class="text-sm text-gray-600">Total: {{ count($attendances ?? []) }} records</p>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check In</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check Out</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Worked Hours</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($attendances ?? [] as $attendance)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($attendance->employee->photo)
                                <img src="{{ asset('storage/' . $attendance->employee->photo) }}" alt="{{ $attendance->employee->full_name }}" class="w-10 h-10 rounded-full object-cover mr-3">
                            @else
                                <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                    {{ strtoupper(substr($attendance->employee->first_name, 0, 1) . substr($attendance->employee->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $attendance->employee->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $attendance->employee->employee_no }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $attendance->employee->department->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($attendance->shift ?? '—') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('h:i A') : '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('h:i A') : '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        @if($attendance->check_in && $attendance->check_out)
                            @php
                                $workedMinutes = \Carbon\Carbon::parse($attendance->check_in)->diffInMinutes(\Carbon\Carbon::parse($attendance->check_out));
                                $hours = floor($workedMinutes / 60);
                                $minutes = $workedMinutes % 60;
                            @endphp
                            {{ $hours }}h {{ $minutes }}m
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusBadges = [
                                'present'  => 'bg-green-100 text-green-800',
                                'absent'   => 'bg-red-100 text-red-800',
                                'late'     => 'bg-yellow-100 text-yellow-800',
                                'half_day' => 'bg-orange-100 text-orange-800',
                                'on_leave' => 'bg-blue-100 text-blue-800',
                                'holiday'  => 'bg-purple-100 text-purple-800',
                            ];
                            $badge = $statusBadges[$attendance->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $badge }}">
                            {{ ucwords(str_replace('_', ' ', $attendance->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $attendance->notes ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-clipboard-list text-4xl mb-4 text-gray-300"></i>
                        <p>No attendance records for this date</p>
                        <a href="{{ route('hr.attendance.mark', ['date' => $date]) }}" class="mt-2 inline-block text-medical-blue hover:underline">
                            Mark attendance now
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
