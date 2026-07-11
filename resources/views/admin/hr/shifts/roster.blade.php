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

@php
    $hospitalName = setting('hospital_name', config('app.name', 'Hospital Management System'));
    $hospitalLogo = setting('hospital_logo', '');
    $hospitalAddress = setting('hospital_address', '');
    $hospitalPhone = setting('hospital_phone', '');
    $hospitalEmail = setting('hospital_email', '');
    $printDeptName = request('department_id')
        ? optional(($departments ?? collect())->firstWhere('id', (int) request('department_id')))->name
        : null;
@endphp

<!-- Print letterhead (visible only when printing) -->
<div class="hidden print:block roster-print-header">
    <div class="roster-print-brand">
        @if($hospitalLogo)
            <img src="{{ asset('storage/' . $hospitalLogo) }}" alt="{{ $hospitalName }}" class="roster-print-logo">
        @endif
        <div class="roster-print-brand-text">
            <h1 class="roster-print-hospital">{{ $hospitalName }}</h1>
            @if($hospitalAddress)
                <p class="roster-print-meta">{{ $hospitalAddress }}</p>
            @endif
            @if($hospitalPhone || $hospitalEmail)
                <p class="roster-print-meta">
                    @if($hospitalPhone)<span>Tel: {{ $hospitalPhone }}</span>@endif
                    @if($hospitalPhone && $hospitalEmail)<span class="roster-print-sep">·</span>@endif
                    @if($hospitalEmail)<span>{{ $hospitalEmail }}</span>@endif
                </p>
            @endif
        </div>
    </div>

    <div class="roster-print-doc">
        <h2 class="roster-print-title">Duty Roster</h2>
        <div class="roster-print-details">
            <span><strong>Period:</strong> {{ $label }}</span>
            @if($printDeptName)
                <span><strong>Department:</strong> {{ $printDeptName }}</span>
            @else
                <span><strong>Department:</strong> All</span>
            @endif
            <span><strong>Printed:</strong> {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>
</div>

@php
    $navQuery = fn ($anchor) => array_filter([
        'period' => $period,
        'week_start' => $anchor->format('Y-m-d'),
        'month' => $period === 'monthly' ? $anchor->format('Y-m-01') : null,
        'start_date' => $period === 'custom' ? $anchor->format('Y-m-d') : null,
        'end_date' => $period === 'custom'
            ? $anchor->copy()->addDays(max(0, $startDate->diffInDays($endDate)))->format('Y-m-d')
            : null,
        'department_id' => request('department_id'),
    ]);

    if ($period === 'custom') {
        $rangeLabel = $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y');
        $rangeSub = ($startDate->diffInDays($endDate) + 1) . ' days';
    } elseif ($period === 'monthly') {
        $rangeLabel = $startDate->format('F Y');
        $rangeSub = 'Monthly';
    } else {
        $rangeLabel = $startDate->format('d M') . ' – ' . $endDate->format('d M Y');
        $rangeSub = 'Weekly';
    }
@endphp

<!-- Period Navigation & Filters -->
<div class="bg-white rounded-lg shadow-sm mb-6 print:hidden overflow-hidden">
    <div class="p-4 space-y-4">
        {{-- Row 1: Period navigation --}}
        <div class="flex items-center gap-2 sm:gap-3">
            <a href="{{ route('hr.shifts.roster', $navQuery($prev)) }}"
               class="inline-flex items-center justify-center shrink-0 h-10 w-10 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors"
               title="Previous period" aria-label="Previous period">
                <i class="fas fa-chevron-left"></i>
            </a>

            <div class="flex-1 min-w-0 flex justify-center">
                <div class="inline-flex max-w-full items-center gap-2 rounded-xl bg-gray-50 border border-gray-200 px-3 sm:px-4 py-2">
                    <i class="fas fa-calendar-alt text-medical-blue shrink-0"></i>
                    <div class="min-w-0 text-center">
                        <p class="text-sm sm:text-base font-semibold text-gray-800 leading-snug break-words">
                            {{ $rangeLabel }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $rangeSub }}</p>
                    </div>
                </div>
            </div>

            <a href="{{ route('hr.shifts.roster', $navQuery($next)) }}"
               class="inline-flex items-center justify-center shrink-0 h-10 w-10 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors"
               title="Next period" aria-label="Next period">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        {{-- Row 2: Filters --}}
        <form action="{{ route('hr.shifts.roster') }}" method="GET"
              class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-3 items-end">
            <input type="hidden" name="week_start" value="{{ $startDate->format('Y-m-d') }}">

            <div class="xl:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">View</label>
                <select name="period" onchange="this.form.submit()"
                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom dates</option>
                </select>
            </div>

            <div class="xl:col-span-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
                <select name="department_id" onchange="this.form.submit()"
                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">All Departments</option>
                    @foreach($departments ?? [] as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($period === 'monthly')
                <div class="xl:col-span-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Month</label>
                    <input type="month" name="month" value="{{ $startDate->format('Y-m') }}"
                           onchange="this.form.submit()"
                           class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                </div>
            @elseif($period === 'custom')
                <div class="sm:col-span-2 xl:col-span-5">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Date range</label>
                    <div class="flex flex-col sm:flex-row items-stretch gap-2">
                        <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                               class="w-full min-w-0 h-10 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <span class="hidden sm:inline-flex items-center justify-center text-xs text-gray-400 shrink-0 px-1">to</span>
                        <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                               class="w-full min-w-0 h-10 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <button type="submit"
                                class="h-10 px-4 shrink-0 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition-colors">
                            Apply
                        </button>
                    </div>
                </div>
            @endif
        </form>

        {{-- Row 3: Actions --}}
        <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100">
            <button type="button"
                    onclick="window.print()"
                    class="inline-flex items-center justify-center h-10 px-4 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-print mr-2 text-gray-500"></i>Print
            </button>

            <form action="{{ route('hr.shifts.auto-generate') }}" method="POST" class="inline-flex">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <input type="hidden" name="week_start" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                <input type="hidden" name="month" value="{{ $startDate->format('Y-m-01') }}">
                <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                <button type="submit"
                        class="inline-flex items-center justify-center h-10 px-4 bg-medical-blue text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                        onclick="return confirm('This will auto-generate the roster for this period. Existing assignments may be overwritten. Continue?')">
                    <i class="fas fa-magic mr-2"></i>Auto Generate
                </button>
            </form>
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
<div class="hidden print:block roster-print-legend">
    <strong>Shift timings:</strong>
    @foreach($shifts as $shift)
        <span>{{ $shift->name }}: {{ $shift->time_range }}</span>@if(!$loop->last)<span class="roster-print-sep">·</span>@endif
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

<!-- Print footer -->
<div class="hidden print:block roster-print-footer">
    <div class="roster-print-sign">
        <div class="roster-print-sign-line"></div>
        Prepared by
    </div>
    <div class="roster-print-sign">
        <div class="roster-print-sign-line"></div>
        Approved by
    </div>
    <div>
        {{ $hospitalName }}
        @if($hospitalPhone)
            · {{ $hospitalPhone }}
        @endif
    </div>
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
    /* Screen: keep print-only blocks hidden (Tailwind handles this) */

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
            color: #111827 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .lg\:ml-64 {
            margin-left: 0 !important;
        }

        main {
            padding: 0 !important;
        }

        .shadow-sm { box-shadow: none !important; }
        .rounded-lg { border-radius: 0 !important; }
        .overflow-x-auto { overflow: visible !important; }

        @page {
            size: A4 landscape;
            margin: 10mm 8mm 12mm;
        }

        /* ── Letterhead ── */
        .roster-print-header {
            display: block !important;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .roster-print-brand {
            display: flex !important;
            align-items: center;
            gap: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #111827;
        }

        .roster-print-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .roster-print-brand-text {
            min-width: 0;
            flex: 1;
        }

        .roster-print-hospital {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
            color: #111827;
        }

        .roster-print-meta {
            margin: 2px 0 0;
            font-size: 10px;
            line-height: 1.35;
            color: #4b5563;
        }

        .roster-print-sep {
            margin: 0 4px;
            color: #9ca3af;
        }

        .roster-print-doc {
            margin-top: 8px;
            display: flex !important;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .roster-print-title {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #111827;
        }

        .roster-print-details {
            display: flex !important;
            flex-wrap: wrap;
            gap: 4px 14px;
            font-size: 10px;
            color: #374151;
        }

        .roster-print-legend {
            display: block !important;
            margin: 0 0 8px;
            padding: 5px 8px;
            font-size: 9px;
            line-height: 1.4;
            color: #374151;
            background: #f9fafb !important;
            border: 1px solid #e5e7eb;
        }

        .roster-print-footer {
            display: flex !important;
            justify-content: space-between;
            align-items: flex-end;
            gap: 24px;
            margin-top: 18px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #4b5563;
            page-break-inside: avoid;
        }

        .roster-print-sign {
            min-width: 160px;
            text-align: center;
        }

        .roster-print-sign-line {
            border-top: 1px solid #6b7280;
            margin-bottom: 4px;
            height: 28px;
        }

        .roster-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .roster-table th,
        .roster-table td {
            border: 1px solid #d1d5db;
            padding: 5px 4px;
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
