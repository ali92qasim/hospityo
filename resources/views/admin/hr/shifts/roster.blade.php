@extends('admin.layout')

@section('title', 'Duty Roster')
@section('page-title', 'Duty Roster')

@section('content')
@php
    $period = $period ?? request('period', 'weekly'); // weekly | monthly | custom

    $startDate = \Carbon\Carbon::parse($weekStart);
    $endDate = \Carbon\Carbon::parse($weekEnd);

    if ($period === 'monthly') {
        $label = $startDate->format('F Y');
        $prev = $startDate->copy()->subMonth()->startOfMonth();
        $next = $startDate->copy()->addMonth()->startOfMonth();
    } elseif ($period === 'custom') {
        $label = $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y');
        $spanDays = max(0, $startDate->diffInDays($endDate));
        $prev = $startDate->copy()->subDays($spanDays + 1);
        $next = $startDate->copy()->addDays($spanDays + 1);
    } else {
        $label = $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
        $prev = $startDate->copy()->subWeek();
        $next = $startDate->copy()->addWeek();
    }

    $days = [];
    for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
        $days[] = $d->copy();
    }
@endphp

<!-- Print header (visible only when printing) -->
<div class="hidden print:block mb-4">
    <h1 class="text-xl font-bold text-center">Duty Roster</h1>
    <p class="text-center text-sm text-gray-700">{{ $label }}</p>
    @if(request('department_id'))
        @php $deptName = optional(($departments ?? collect())->firstWhere('id', (int) request('department_id')))->name; @endphp
        @if($deptName)
            <p class="text-center text-xs text-gray-600">Department: {{ $deptName }}</p>
        @endif
    @endif
</div>

<!-- Period Navigation & Filters -->
<div class="bg-white rounded-lg shadow-sm mb-6 print:hidden">
    <div class="p-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Navigation -->
            <div class="flex items-center gap-3">
                <a href="{{ route('hr.shifts.roster', array_filter([
                    'period' => $period,
                    'week_start' => $prev->format('Y-m-d'),
                    'month' => $period === 'monthly' ? $prev->format('Y-m-01') : null,
                    'start_date' => $period === 'custom' ? $prev->format('Y-m-d') : null,
                    'end_date' => $period === 'custom' ? $prev->copy()->addDays(max(0, $startDate->diffInDays($endDate)))->format('Y-m-d') : null,
                    'department_id' => request('department_id'),
                ])) }}"
                   class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left mr-1"></i>Previous
                </a>
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ $label }}
                    </h3>
                </div>
                <a href="{{ route('hr.shifts.roster', array_filter([
                    'period' => $period,
                    'week_start' => $next->format('Y-m-d'),
                    'month' => $period === 'monthly' ? $next->format('Y-m-01') : null,
                    'start_date' => $period === 'custom' ? $next->format('Y-m-d') : null,
                    'end_date' => $period === 'custom' ? $next->copy()->addDays(max(0, $startDate->diffInDays($endDate)))->format('Y-m-d') : null,
                    'department_id' => request('department_id'),
                ])) }}"
                   class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Next<i class="fas fa-chevron-right ml-1"></i>
                </a>
            </div>

            <!-- Filters & Actions -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <form action="{{ route('hr.shifts.roster') }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 print:hidden">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <input type="hidden" name="week_start" value="{{ $startDate->format('Y-m-d') }}">
                    @if($period === 'monthly')
                        <input type="month" name="month" value="{{ $startDate->format('Y-m') }}"
                               onchange="this.form.submit()"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    @elseif($period === 'custom')
                        <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <button type="submit" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition-colors">
                            Apply
                        </button>
                    @endif

                    <select name="period" onchange="this.form.submit()"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom dates</option>
                    </select>

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

                <div class="flex items-center gap-2 print:hidden">
                    <button type="button"
                            onclick="window.print()"
                            class="w-full sm:w-auto px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-black transition-colors">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                </div>

                <form action="{{ route('hr.shifts.auto-generate') }}" method="POST" class="inline print:hidden">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <input type="hidden" name="week_start" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                    <input type="hidden" name="month" value="{{ $startDate->format('Y-m-01') }}">
                    <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                            onclick="return confirm('This will auto-generate the roster for this period. Existing assignments may be overwritten. Continue?')">
                        <i class="fas fa-magic mr-2"></i>Auto Generate
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Shift Legend -->
<div class="bg-white rounded-lg shadow-sm mb-6 print:hidden">
    <div class="p-4">
        <div class="flex flex-wrap gap-4 text-sm">
            @foreach($shifts as $shift)
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded-full border border-gray-200" style="background-color: {{ $shift->color }};"></span>
                    <span class="text-gray-600">{{ $shift->name }} ({{ $shift->time_range }})</span>
                </div>
            @endforeach
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded-full bg-gray-300 border border-gray-200"></span>
                <span class="text-gray-600">Off Day</span>
            </div>
        </div>
    </div>
</div>

<!-- Print shift timing legend -->
<div class="hidden print:block mb-3 text-xs">
    <strong>Shift timings:</strong>
    @foreach($shifts as $shift)
        <span class="mr-3">{{ $shift->name }}: {{ $shift->time_range }}</span>
    @endforeach
</div>

<!-- Roster Grid -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200 print:hidden">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Duty Roster</h3>
                <p class="text-sm text-gray-600">
                    {{ $period === 'monthly' ? 'Assign shifts for the month' : ($period === 'custom' ? 'Assign shifts for selected dates' : 'Assign shifts to employees for the week') }}
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('hr.shifts.store-roster') }}" method="POST">
        @csrf
        <input type="hidden" name="period" value="{{ $period }}">
        <input type="hidden" name="week_start" value="{{ $startDate->format('Y-m-d') }}">
        <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
        <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
        <input type="hidden" name="month" value="{{ $startDate->format('Y-m-01') }}">
        <input type="hidden" name="department_id" value="{{ request('department_id') }}">

        <div class="overflow-x-auto">
            <table class="w-full roster-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 min-w-[220px]">
                            Doctor / Employee
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
                    @php
                        $doctor = $employee->doctor;
                        $displayName = $doctor?->name ?: $employee->full_name;
                        $pmdc = $doctor?->pmdc_number;
                        $registration = $pmdc ?: ($doctor?->doctor_no);
                        $doctorTimings = null;
                        if ($doctor?->shift_start && $doctor?->shift_end) {
                            $doctorTimings = \Carbon\Carbon::parse($doctor->shift_start)->format('h:i A')
                                . ' — '
                                . \Carbon\Carbon::parse($doctor->shift_end)->format('h:i A');
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 sticky left-0 bg-white z-10">
                            <div class="flex items-start">
                                @if($employee->photo)
                                    <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $displayName }}" class="w-7 h-7 rounded-full object-cover mr-2 print:hidden">
                                @else
                                    <div class="w-7 h-7 bg-medical-blue rounded-full flex items-center justify-center text-white text-xs font-medium mr-2 print:hidden">
                                        {{ strtoupper(substr($employee->first_name ?? $displayName, 0, 1) . substr($employee->last_name ?? '', 0, 1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">{{ $displayName }}</div>
                                    @if($pmdc)
                                        <div class="text-xs text-gray-600">PMDC: {{ $pmdc }}</div>
                                    @elseif($registration)
                                        <div class="text-xs text-gray-600">Reg No: {{ $registration }}</div>
                                    @elseif($doctor)
                                        <div class="text-xs text-gray-500">PMDC: —</div>
                                    @endif
                                    @if($doctorTimings)
                                        <div class="text-xs text-gray-600">Timings: {{ $doctorTimings }}</div>
                                    @endif
                                    @if($employee->department)
                                        <div class="text-xs text-gray-400 print:hidden">{{ $employee->department->name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @foreach($days as $day)
                            @php
                                $dateKey = $day->format('Y-m-d');
                                $rosterEntry = $rosters[$employee->id][$dateKey] ?? null;
                                $currentShiftId = $rosterEntry->shift_id ?? null;
                                $assignedShift = $rosterEntry->shift ?? null;
                                $isOff = (bool) ($rosterEntry->is_off_day ?? false);
                            @endphp
                            <td class="px-2 py-3 text-center {{ $isOff ? 'bg-gray-100' : '' }}">
                                <div class="space-y-1 print:hidden">
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

                                {{-- Print-only cell content --}}
                                <div class="hidden print:block text-xs leading-tight">
                                    @if($isOff)
                                        <strong>Off</strong>
                                    @elseif($assignedShift)
                                        <div class="font-semibold">{{ $assignedShift->name }}</div>
                                        <div>{{ $assignedShift->time_range }}</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
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
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end print:hidden">
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

@push('styles')
<style>
    @media print {
        /* Hide app chrome */
        #sidebar,
        #mobile-overlay,
        header,
        .print\:hidden,
        nav,
        aside {
            display: none !important;
        }

        .hidden.print\:block,
        .print\:block {
            display: block !important;
        }

        body {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Expand main content full width when sidebar is hidden */
        .lg\:ml-64 {
            margin-left: 0 !important;
        }

        main {
            padding: 0 !important;
        }

        .shadow-sm { box-shadow: none !important; }
        .rounded-lg { border-radius: 0 !important; }
        .overflow-x-auto { overflow: visible !important; }

        .roster-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .roster-table th,
        .roster-table td {
            border: 1px solid #d1d5db;
            padding: 6px 4px;
            vertical-align: top;
        }

        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        .sticky { position: static !important; }
    }
</style>
@endpush
@endsection
