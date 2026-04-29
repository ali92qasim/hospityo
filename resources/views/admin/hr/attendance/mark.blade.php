@extends('admin.layout')

@section('title', 'Mark Attendance')
@section('page-title', 'Mark Attendance')

@section('content')
<div class="mb-6">
    <form action="{{ route('hr.attendance.store-daily') }}" method="POST">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar text-medical-blue"></i>
                            <span class="text-lg font-semibold text-gray-800">{{ \Carbon\Carbon::parse($date)->format('l, M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="mark-all-present" class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-check-double mr-1"></i>Mark All Present
                        </button>
                        <button type="button" id="mark-all-absent" class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-times-circle mr-1"></i>Mark All Absent
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Filter -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <select id="department-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                            <option value="">All Departments</option>
                            @foreach($departments ?? [] as $department)
                                <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Employee Attendance</h3>
                <p class="text-sm text-gray-600">Total: {{ count($employees ?? []) }} employees</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check In</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check Out</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($employees ?? [] as $employee)
                        @php
                            $existingRecord = $existing[$employee->id] ?? null;
                            $existingTime = $existingTimes->firstWhere('employee_id', $employee->id) ?? null;
                        @endphp
                        <tr class="hover:bg-gray-50 employee-row" data-department="{{ $employee->department_id }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    @if($employee->photo)
                                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="w-10 h-10 rounded-full object-cover mr-3">
                                    @else
                                        <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $employee->employee_no }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $employee->department->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <select name="attendance[{{ $employee->id }}][status]"
                                        class="status-select w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                                    <option value="present" {{ ($existingRecord ?? '') == 'present' ? 'selected' : '' }}>Present</option>
                                    <option value="absent" {{ ($existingRecord ?? '') == 'absent' ? 'selected' : '' }}>Absent</option>
                                    <option value="late" {{ ($existingRecord ?? '') == 'late' ? 'selected' : '' }}>Late</option>
                                    <option value="half_day" {{ ($existingRecord ?? '') == 'half_day' ? 'selected' : '' }}>Half Day</option>
                                    <option value="on_leave" {{ ($existingRecord ?? '') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                                    <option value="holiday" {{ ($existingRecord ?? '') == 'holiday' ? 'selected' : '' }}>Holiday</option>
                                </select>
                            </td>
                            <td class="px-6 py-4">
                                <input type="time" name="attendance[{{ $employee->id }}][check_in]"
                                       value="{{ $existingTime->check_in ?? '' }}"
                                       class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                            </td>
                            <td class="px-6 py-4">
                                <input type="time" name="attendance[{{ $employee->id }}][check_out]"
                                       value="{{ $existingTime->check_out ?? '' }}"
                                       class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                            </td>
                            <td class="px-6 py-4">
                                <select name="attendance[{{ $employee->id }}][shift]"
                                        class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                                    <option value="morning" {{ ($existingTime->shift ?? $employee->default_shift ?? '') == 'morning' ? 'selected' : '' }}>Morning</option>
                                    <option value="evening" {{ ($existingTime->shift ?? $employee->default_shift ?? '') == 'evening' ? 'selected' : '' }}>Evening</option>
                                    <option value="night" {{ ($existingTime->shift ?? $employee->default_shift ?? '') == 'night' ? 'selected' : '' }}>Night</option>
                                </select>
                            </td>
                            <td class="px-6 py-4">
                                <input type="text" name="attendance[{{ $employee->id }}][notes]"
                                       value="{{ $existingTime->notes ?? '' }}"
                                       placeholder="Notes..."
                                       class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                <p>No active employees found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end space-x-4 mt-6">
            <a href="{{ route('hr.attendance.index', ['date' => $date]) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-save mr-2"></i>
                Save Attendance
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Mark All Present
    document.getElementById('mark-all-present').addEventListener('click', function() {
        document.querySelectorAll('.status-select').forEach(function(select) {
            const row = select.closest('.employee-row');
            if (row && !row.classList.contains('hidden')) {
                select.value = 'present';
            }
        });
    });

    // Mark All Absent
    document.getElementById('mark-all-absent').addEventListener('click', function() {
        document.querySelectorAll('.status-select').forEach(function(select) {
            const row = select.closest('.employee-row');
            if (row && !row.classList.contains('hidden')) {
                select.value = 'absent';
            }
        });
    });

    // Department Filter
    document.getElementById('department-filter').addEventListener('change', function() {
        const departmentId = this.value;
        document.querySelectorAll('.employee-row').forEach(function(row) {
            if (!departmentId || row.dataset.department === departmentId) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    });
</script>
@endpush
