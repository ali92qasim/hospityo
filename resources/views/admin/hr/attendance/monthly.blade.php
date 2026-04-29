@extends('admin.layout')

@section('title', 'Monthly Attendance Report')
@section('page-title', 'Monthly Attendance Report')

@section('content')
<!-- Filter Bar -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4">
        <form action="{{ route('hr.attendance.monthly') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ ($year ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ ($month ?? now()->month) == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                    @endforeach
                </select>
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
            <div>
                <a href="{{ route('hr.attendance.index') }}" class="block w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-center">
                    <i class="fas fa-arrow-left mr-2"></i>Daily View
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Legend -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4">
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 bg-green-100 text-green-800 rounded flex items-center justify-center text-xs font-bold">P</span>
                <span class="text-gray-600">Present</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 bg-red-100 text-red-800 rounded flex items-center justify-center text-xs font-bold">A</span>
                <span class="text-gray-600">Absent</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 bg-yellow-100 text-yellow-800 rounded flex items-center justify-center text-xs font-bold">L</span>
                <span class="text-gray-600">Late</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 bg-orange-100 text-orange-800 rounded flex items-center justify-center text-xs font-bold">H</span>
                <span class="text-gray-600">Half Day</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 bg-blue-100 text-blue-800 rounded flex items-center justify-center text-xs font-bold">LV</span>
                <span class="text-gray-600">On Leave</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 bg-purple-100 text-purple-800 rounded flex items-center justify-center text-xs font-bold">HL</span>
                <span class="text-gray-600">Holiday</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 bg-gray-100 text-gray-400 rounded flex items-center justify-center text-xs font-bold">—</span>
                <span class="text-gray-600">No Record</span>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Attendance Table -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">
            {{ \Carbon\Carbon::create($year ?? now()->year, $month ?? now()->month)->format('F Y') }} Attendance
        </h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 min-w-[180px]">Employee</th>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $dayDate = \Carbon\Carbon::create($year ?? now()->year, $month ?? now()->month, $d);
                            $isWeekend = $dayDate->isWeekend();
                        @endphp
                        <th class="px-1 py-3 text-center text-xs font-medium {{ $isWeekend ? 'text-red-400' : 'text-gray-500' }} uppercase tracking-wider min-w-[32px]">
                            {{ $d }}
                        </th>
                    @endfor
                    <th class="px-3 py-3 text-center text-xs font-medium text-green-600 uppercase tracking-wider bg-green-50">P</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-red-600 uppercase tracking-wider bg-red-50">A</th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-yellow-600 uppercase tracking-wider bg-yellow-50">L</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees ?? [] as $employee)
                @php
                    $empAttendances = $attendances[$employee->id] ?? [];
                    $totalPresent = 0;
                    $totalAbsent = 0;
                    $totalLate = 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 sticky left-0 bg-white z-10">
                        <div class="flex items-center">
                            @if($employee->photo)
                                <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="w-7 h-7 rounded-full object-cover mr-2">
                            @else
                                <div class="w-7 h-7 bg-medical-blue rounded-full flex items-center justify-center text-white text-xs font-medium mr-2">
                                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="truncate">{{ $employee->full_name }}</span>
                        </div>
                    </td>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $dayRecord = $empAttendances[$d] ?? null;
                            $cellClass = 'bg-gray-50 text-gray-400';
                            $cellText = '—';

                            if ($dayRecord) {
                                switch($dayRecord) {
                                    case 'present':
                                        $cellClass = 'bg-green-100 text-green-800';
                                        $cellText = 'P';
                                        $totalPresent++;
                                        break;
                                    case 'absent':
                                        $cellClass = 'bg-red-100 text-red-800';
                                        $cellText = 'A';
                                        $totalAbsent++;
                                        break;
                                    case 'late':
                                        $cellClass = 'bg-yellow-100 text-yellow-800';
                                        $cellText = 'L';
                                        $totalLate++;
                                        break;
                                    case 'half_day':
                                        $cellClass = 'bg-orange-100 text-orange-800';
                                        $cellText = 'H';
                                        break;
                                    case 'on_leave':
                                        $cellClass = 'bg-blue-100 text-blue-800';
                                        $cellText = 'LV';
                                        break;
                                    case 'holiday':
                                        $cellClass = 'bg-purple-100 text-purple-800';
                                        $cellText = 'HL';
                                        break;
                                }
                            }
                        @endphp
                        <td class="px-1 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded text-xs font-bold {{ $cellClass }}">
                                {{ $cellText }}
                            </span>
                        </td>
                    @endfor
                    <td class="px-3 py-3 text-center text-sm font-semibold text-green-700 bg-green-50">{{ $totalPresent }}</td>
                    <td class="px-3 py-3 text-center text-sm font-semibold text-red-700 bg-red-50">{{ $totalAbsent }}</td>
                    <td class="px-3 py-3 text-center text-sm font-semibold text-yellow-700 bg-yellow-50">{{ $totalLate }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $daysInMonth + 4 }}" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-calendar-alt text-4xl mb-4 text-gray-300"></i>
                        <p>No attendance data for this month</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
