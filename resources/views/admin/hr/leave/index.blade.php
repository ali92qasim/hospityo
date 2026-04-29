@extends('admin.layout')

@section('title', 'Leave Requests')
@section('page-title', 'Leave Requests')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Pending</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-hourglass-half text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Approved This Month</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['approved_this_month'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total This Month</p>
                <p class="text-2xl font-bold text-blue-600">{{ $stats['total_this_month'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-4">
        <form action="{{ route('hr.leave.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <select name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    <option value="">All Employees</option>
                    @foreach($employees ?? [] as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->full_name }}
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
                <a href="{{ route('hr.leave.create') }}" class="block w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-center">
                    <i class="fas fa-plus mr-2"></i>New Leave Request
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Leave Requests Table -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Leave Requests</h3>
                <p class="text-sm text-gray-600">Total: {{ $leaveRequests->total() ?? 0 }} requests</p>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Half Day</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($leaveRequests ?? [] as $request)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($request->employee->photo)
                                <img src="{{ asset('storage/' . $request->employee->photo) }}" alt="{{ $request->employee->full_name }}" class="w-10 h-10 rounded-full object-cover mr-3">
                            @else
                                <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                    {{ strtoupper(substr($request->employee->first_name, 0, 1) . substr($request->employee->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $request->employee->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $request->employee->department->name ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $request->leaveType->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ \Carbon\Carbon::parse($request->start_date)->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ \Carbon\Carbon::parse($request->end_date)->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $request->total_days }}</td>
                    <td class="px-6 py-4">
                        @if($request->is_half_day)
                            <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">
                                {{ ucfirst($request->half_day_type ?? 'Half Day') }}
                            </span>
                        @else
                            <span class="text-sm text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusBadges = [
                                'pending'   => 'bg-yellow-100 text-yellow-800',
                                'approved'  => 'bg-green-100 text-green-800',
                                'rejected'  => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800',
                            ];
                            $badge = $statusBadges[$request->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $badge }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $request->approvedBy->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm font-medium">
                        @if($request->status === 'pending')
                            <div class="flex flex-col gap-2">
                                <form action="{{ route('hr.leave.approve', $request) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm font-medium" onclick="return confirm('Approve this leave request?')">
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                </form>
                                <div x-data="{ showReject: false }">
                                    <button @click="showReject = !showReject" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                        <i class="fas fa-times mr-1"></i>Reject
                                    </button>
                                    <div x-show="showReject" x-cloak class="mt-2">
                                        <form action="{{ route('hr.leave.reject', $request) }}" method="POST">
                                            @csrf
                                            <input type="text" name="rejection_reason" placeholder="Reason..."
                                                   class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-medical-blue focus:border-transparent mb-1" required>
                                            <button type="submit" class="px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                                                Confirm Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @elseif($request->status === 'approved')
                            <form action="{{ route('hr.leave.cancel', $request) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-orange-600 hover:text-orange-800 text-sm font-medium" onclick="return confirm('Cancel this approved leave?')">
                                    <i class="fas fa-ban mr-1"></i>Cancel
                                </button>
                            </form>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-calendar-times text-4xl mb-4 text-gray-300"></i>
                        <p>No leave requests found</p>
                        <a href="{{ route('hr.leave.create') }}" class="mt-2 inline-block text-medical-blue hover:underline">
                            Create a new leave request
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($leaveRequests->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $leaveRequests->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
