@php
    $queuePriority = $workflowData['queue_priority'] ?? 'medium';
    $priorityColors = [
        'low' => 'bg-gray-100 text-gray-800',
        'medium' => 'bg-blue-100 text-blue-800',
        'high' => 'bg-orange-100 text-orange-800',
        'critical' => 'bg-red-100 text-red-800',
    ];
@endphp
<span class="px-2 py-1 text-xs rounded-full {{ $priorityColors[$queuePriority] ?? 'bg-gray-100 text-gray-800' }}">
    {{ ucfirst($queuePriority) }} Queue Priority
</span>
