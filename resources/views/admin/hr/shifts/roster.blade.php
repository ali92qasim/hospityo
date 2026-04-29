@extends('admin.layout')

@section('title', 'Duty Roster')
@section('page-title', 'Duty Roster')

@section('content')
@php
    $weekStartDate = \Carbon\Carbon::parse($weekStart);
    $weekEndDate = $weekStartDate->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $prevWeek = $weekStartDate->copy()->subWeek()->format('Y-m-d');
    $nextWeek = $weekStartDate->copy()->addWeek()->format('Y-m-d');

    $days = [];
    for ($i = 0; $i < 7; $i++) {
        $days[] = $weekStartDate->copy()->addDays($i);
    }
@endphp

<!-- Week Navigation & Filters -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Week Navigation -->
            <div class="flex items-center gap-3">
                <a href="{{ route('hr.shifts.roster', ['week_start' => $prevWeek]) }}"
                   class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left mr-1"></i>Previous Week
                </a>
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ $weekStartDate->format('M d') }} - {{ $weekEndDate->format('M d, Y') }}
                    </h3>
                </div>
                <a href="{{ route('hr.shifts.roster', ['week_start' => $nextWeek]) }}"
                   class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Next Week<i class="fas fa-chevron-right ml-1"></i>
                </a>
            </div>

            <!-- Department Filter & Actions -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <form action="{{ route('hr.shifts.roster') }}" method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="week_start" value="{{ $weekStartDate->format('Y-m-d') }}">
                    <select name="department_id" onchange="this.form.submit()"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <option value="">All Departments</option>
                        @foreach($departments ?? [] as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <form action="{{ route('hr.shifts.auto-generate') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="week_start" value="{{ $weekStartDate->format('Y-m-d') }}">
                    <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                            onclick="return confirm('This will auto-generate the roster for this week. Existing assignments may be overwritten. Continue?')">
                        <i class="fas fa-magic mr-2"></i>Auto Generate
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Shift Legend -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4">
        <div class="flex flex-wrap gap-4 text-sm">
            @foreach($shifts as $shift)
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded-full border border-gray-200" style="background-color: {{ $shift->color }};"></span>
                    <span class="text-gray-600">{{ $shift->name }}</span>
                </div>
            @endforeach
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded-full bg-gray-300 border border-gray-200"></span>
                <span class="text-gray-600">Off Day</span>
            </div>
        </div>
    </div>
</div>

<!-- Roster Grid -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Weekly Duty Roster</h3>
                <p class="text-sm text-gray-600">Assign shifts to employees for the week</p>
            </div>
        </div>
    </div>

    <form action="{{ route('hr.shifts.store-roster') }}" method="POST">
        @csrf
        <input type="hidden" name="week_start" value="{{ $weekStartDate->format('Y-m-d') }}">
        <input type="hidden" name="department_id" value="{{ request('department_id') }}">

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 min-w-[200px]">
                            Employee
                        </th>
                        @foreach($days as $day)
                            <th class="px-2 py-3 text-center text-xs font-medium {{ $day->isSunday() ? 'text-red-500' : 'text-gray-500' }} uppercase tracking-wider min-w-[160px]">
                                <div>{{ $day->format('D') }}</div>
                                <div class="text-xs font-normal {{ $day->isSunday() ? 'text-red-400' : 'text-gray-400' }}">{{ $day->format('M d') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($employees ?? [] as $employee)
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
                        @foreach($days as $day)
                            @php
                                $dateKey = $day->format('Y-m-d');
                                $rosterEntry = $rosters[$employee->id][$dateKey] ?? null;
                                $currentShiftId = $rosterEntry->shift_id ?? null;
                                $isOff = $rosterEntry->is_off ?? false;
                            @endphp
                            <td class="px-2 py-3 text-center {{ $isOff ? 'bg-gray-100' : '' }}">
                                <div class="space-y-1">
                                    <select name="roster[{{ $employee->id }}][{{ $dateKey }}][shift_id]"
                                            class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-medical-blue focus:border-transparent roster-shift-select"
                                            data-employee="{{ $employee->id }}" data-date="{{ $dateKey }}"
                                            {{ $isOff ? 'disabled' : '' }}>
                                        <option value="">— Select —</option>
                                        @foreach($shifts as $shift)
                                            <option value="{{ $shift->id }}" {{ $currentShiftId == $shift->id ? 'selected' : '' }}
                                                    data-color="{{ $shift->color }}">
                                                ● {{ $shift->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label class="flex items-center justify-center gap-1 cursor-pointer">
                                        <input type="checkbox" name="roster[{{ $employee->id }}][{{ $dateKey }}][is_off]" value="1"
                                               {{ $isOff ? 'checked' : '' }}
                                               class="w-3.5 h-3.5 text-gray-500 border-gray-300 rounded focus:ring-gray-400 roster-off-checkbox"
                                               data-employee="{{ $employee->id }}" data-date="{{ $dateKey }}">
                                        <span class="text-xs text-gray-500">Off</span>
                                    </label>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 1 + count($days) }}" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-calendar-alt text-4xl mb-4 text-gray-300"></i>
                            <p>No employees found</p>
                            @if(request('department_id'))
                                <p class="text-sm mt-1">Try selecting a different department</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(($employees ?? collect())->count() > 0)
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-save mr-2"></i>
                Save Roster
            </button>
        </div>
        @endif
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle shift select when Off checkbox is toggled
        document.querySelectorAll('.roster-off-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                const employeeId = this.dataset.employee;
                const date = this.dataset.date;
                const select = document.querySelector(
                    `.roster-shift-select[data-employee="${employeeId}"][data-date="${date}"]`
                );
                const cell = this.closest('td');

                if (this.checked) {
                    select.disabled = true;
                    select.value = '';
                    cell.classList.add('bg-gray-100');
                } else {
                    select.disabled = false;
                    cell.classList.remove('bg-gray-100');
                }
            });
        });

        // Re-enable disabled selects before form submission so values are sent
        document.querySelector('form[action*="store-roster"]').addEventListener('submit', function () {
            this.querySelectorAll('select:disabled').forEach(function (select) {
                select.disabled = false;
            });
        });
    });
</script>
@endpush
@endsection
