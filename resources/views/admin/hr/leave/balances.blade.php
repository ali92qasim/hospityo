@extends('admin.layout')

@section('title', 'Leave Balances')
@section('page-title', 'Leave Balances')

@section('content')
<!-- Year Filter -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4">
        <form action="{{ route('hr.leave.balances') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ ($year ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply
                </button>
            </div>
            <div>
                <a href="{{ route('hr.leave.index') }}" class="block w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-center">
                    <i class="fas fa-arrow-left mr-2"></i>Leave Requests
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Leave Balances Table -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Leave Balances — {{ $year ?? now()->year }}</h3>
        <p class="text-sm text-gray-600">Overview of leave entitlements and usage per employee</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th rowspan="2" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-r border-gray-200 min-w-[200px]">Employee</th>
                    @foreach($leaveTypes ?? [] as $type)
                        <th colspan="3" class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-r border-gray-200">
                            {{ $type->name }}
                        </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($leaveTypes ?? [] as $type)
                        <th class="px-2 py-2 text-center text-xs font-medium text-green-600 uppercase tracking-wider bg-green-50 border-b border-gray-200">Entitled</th>
                        <th class="px-2 py-2 text-center text-xs font-medium text-red-600 uppercase tracking-wider bg-red-50 border-b border-gray-200">Used</th>
                        <th class="px-2 py-2 text-center text-xs font-medium text-blue-600 uppercase tracking-wider bg-blue-50 border-b border-r border-gray-200">Remaining</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees ?? [] as $employee)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 border-r border-gray-200">
                        <div class="flex items-center">
                            @if($employee->photo)
                                <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="w-8 h-8 rounded-full object-cover mr-2">
                            @else
                                <div class="w-8 h-8 bg-medical-blue rounded-full flex items-center justify-center text-white text-xs font-medium mr-2">
                                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $employee->department->name ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    @foreach($leaveTypes ?? [] as $type)
                        @php
                            $balance = $balances[$employee->id][$type->id] ?? null;
                            $entitled = $balance->entitled_days ?? $type->default_days ?? 0;
                            $used = $balance->used_days ?? 0;
                            $remaining = $entitled - $used;
                            $percentage = $entitled > 0 ? ($remaining / $entitled) * 100 : 0;

                            if ($percentage > 50) {
                                $remainingClass = 'text-green-700 bg-green-50';
                            } elseif ($percentage >= 25) {
                                $remainingClass = 'text-yellow-700 bg-yellow-50';
                            } else {
                                $remainingClass = 'text-red-700 bg-red-50';
                            }
                        @endphp
                        <td class="px-2 py-4 text-center text-sm text-gray-700">{{ $entitled }}</td>
                        <td class="px-2 py-4 text-center text-sm text-gray-700">{{ $used }}</td>
                        <td class="px-2 py-4 text-center text-sm font-semibold border-r border-gray-200 {{ $remainingClass }}">{{ $remaining }}</td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 1 + (count($leaveTypes ?? []) * 3) }}" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-balance-scale text-4xl mb-4 text-gray-300"></i>
                        <p>No leave balance data available</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
