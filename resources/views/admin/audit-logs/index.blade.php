@extends('admin.layout')

@section('title', 'Audit Logs - Hospityo')
@section('page-title', 'Audit Logs')
@section('page-description', 'System activity and change tracking')

@section('content')
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-6 border-b border-gray-200">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Event</label>
                <select name="event" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Events</option>
                    <option value="created" {{ request('event') == 'created' ? 'selected' : '' }}>Created</option>
                    <option value="updated" {{ request('event') == 'updated' ? 'selected' : '' }}>Updated</option>
                    <option value="deleted" {{ request('event') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="{{ route('audit-logs.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-medical-blue text-white flex items-center justify-center text-sm font-medium">
                                    {{ substr($log->user->name ?? 'System', 0, 1) }}
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $log->user->name ?? 'System' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $log->event_badge_color }}-100 text-{{ $log->event_badge_color }}-800">
                                {{ ucfirst($log->event) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ class_basename($log->auditable_type) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->ip_address }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('audit-logs.show', $log) }}" class="text-medical-blue hover:text-blue-700">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                            <p>No audit logs found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection