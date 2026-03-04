@extends('admin.layout')

@section('title', 'Audit Log Details - Hospityo')
@section('page-title', 'Audit Log Details')
@section('page-description', 'View detailed audit information')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Event Information</h3>
            <a href="{{ route('audit-logs.index') }}" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to Logs
            </a>
        </div>

        <div class="p-6 space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $auditLog->event_badge_color }}-100 text-{{ $auditLog->event_badge_color }}-800">
                        {{ ucfirst($auditLog->event) }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Timestamp</label>
                    <p class="text-gray-900">{{ $auditLog->created_at->format('Y-m-d H:i:s') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <p class="text-gray-900">{{ $auditLog->user->name ?? 'System' }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                    <p class="text-gray-900">{{ $auditLog->ip_address }}</p>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <p class="text-gray-900">{{ $auditLog->auditable_type }} (ID: {{ $auditLog->auditable_id }})</p>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">User Agent</label>
                    <p class="text-gray-600 text-sm">{{ $auditLog->user_agent }}</p>
                </div>
            </div>

            @if($auditLog->event === 'updated' && $auditLog->old_values && $auditLog->new_values)
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-4">Changes</h4>
                    <div class="space-y-4">
                        @foreach($auditLog->new_values as $key => $newValue)
                            @if(isset($auditLog->old_values[$key]) && $auditLog->old_values[$key] != $newValue)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="font-medium text-gray-700 mb-2">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">Old Value</div>
                                            <div class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">
                                                {{ is_array($auditLog->old_values[$key]) ? json_encode($auditLog->old_values[$key]) : $auditLog->old_values[$key] }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">New Value</div>
                                            <div class="text-sm text-green-600 bg-green-50 px-3 py-2 rounded">
                                                {{ is_array($newValue) ? json_encode($newValue) : $newValue }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if($auditLog->event === 'created' && $auditLog->new_values)
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-4">Created Data</h4>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                        @foreach($auditLog->new_values as $key => $value)
                            <div class="flex border-b border-gray-200 pb-2 last:border-0">
                                <div class="w-1/3 font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                <div class="w-2/3 text-gray-900">{{ is_array($value) ? json_encode($value) : ($value ?? 'N/A') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($auditLog->event === 'deleted' && $auditLog->old_values)
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-4">Deleted Data</h4>
                    <div class="bg-red-50 rounded-lg p-4 space-y-3">
                        @foreach($auditLog->old_values as $key => $value)
                            <div class="flex border-b border-red-100 pb-2 last:border-0">
                                <div class="w-1/3 font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                <div class="w-2/3 text-gray-900">{{ is_array($value) ? json_encode($value) : ($value ?? 'N/A') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection