@php
    $statusColors = [
        'registered' => 'bg-blue-100 text-blue-800',
        'triaged' => 'bg-red-100 text-red-800',
        'vitals_recorded' => 'bg-green-100 text-green-800',
        'admitted' => 'bg-purple-100 text-purple-800',
        'with_doctor' => 'bg-indigo-100 text-indigo-800',
        'discharged' => 'bg-orange-100 text-orange-800',
        'completed' => 'bg-gray-100 text-gray-800',
    ];
    $backLink = \App\Support\VisitWorkflowBackLink::resolve($visit, request('from'));
@endphp
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-medical-blue rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-clipboard-list text-white"></i>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-gray-800">{{ $visit->visit_no }}</h3>
                    <p class="text-sm text-gray-600">{{ $visit->patient->name }} • {{ strtoupper($visit->visit_type) }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @if($workflowData['show_opd_ui'] ?? false)
                    @include('admin.visits.workflow.opd._queue-priority')
                @endif
                <span class="px-3 py-1 text-sm rounded-full {{ $statusColors[$visit->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                </span>
                <a href="{{ route('visits.print', $visit) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-medical-blue text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-print mr-2"></i>{{ $workflowData['print_label'] }}
                </a>
                <a href="{{ $backLink['url'] }}" data-landmark="workflow-back-to-list" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to {{ $backLink['label'] }}
                </a>
            </div>
        </div>
        @isset($headerExtra)
            <div class="mt-4 pt-4 border-t border-gray-100">
                {!! $headerExtra !!}
            </div>
        @endisset
    </div>
</div>
