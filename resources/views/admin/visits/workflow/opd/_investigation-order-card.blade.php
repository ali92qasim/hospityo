@php
    $investigation = $orderItem->labTest ?? $orderItem->imagingStudy ?? $orderItem->investigation;
    $isImaging = $orderItem instanceof \App\Models\ImagingOrderItem || $orderItem->imaging_study_id;
    $isPending = $tone === 'pending';
    $cardClass = $isPending
        ? 'bg-yellow-50 border-l-4 border-yellow-500'
        : 'bg-green-50 border-l-4 border-green-500';
@endphp

<div class="{{ $cardClass }} rounded-lg p-4 shadow-sm">
    <div class="flex justify-between items-start mb-3">
        <div class="flex-1 min-w-0">
            <h6 class="text-base font-semibold text-gray-900 truncate mb-2">{{ $investigation->name ?? 'Unknown' }}</h6>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium bg-gray-100 text-gray-700">
                    {{ ucfirst(str_replace('-', ' ', $investigation->category ?? '')) }}
                </span>
                @if($isPending)
                    <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                        {{ $orderItem->priority === 'stat' ? 'bg-red-600 text-white' :
                           ($orderItem->priority === 'urgent' ? 'bg-orange-600 text-white' : 'bg-blue-600 text-white') }}">
                        {{ strtoupper($orderItem->priority) }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium bg-green-200 text-green-900">
                        <i class="fas fa-check-circle mr-1"></i>
                        Reported
                    </span>
                @endif
            </div>
            <p class="text-xs text-gray-600">
                <i class="fas fa-calendar-alt mr-1"></i>
                {{ $orderItem->order->ordered_at->format('M d, h:i A') }}
            </p>
        </div>
    </div>

    @if($isPending && $orderItem->clinical_notes)
        <div class="bg-white rounded p-2 mb-3 text-xs text-gray-700">
            <i class="fas fa-notes-medical text-yellow-600 mr-1"></i>
            {{ Str::limit($orderItem->clinical_notes, 60) }}
        </div>
    @endif

    @if($isPending)
        <div class="mt-3 pt-3 border-t border-yellow-200">
            @if($isImaging)
                @if(\App\Models\ModuleRegistry::allows(\App\Models\Tenant::current(), auth()->user(), 'imaging'))
                <a href="{{ route('radiology-results.create', $orderItem->order) }}"
                   class="inline-flex items-center px-3 py-2 bg-medical-blue text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all w-full justify-center">
                    <i class="fas fa-file-medical mr-2"></i>
                    Enter Imaging Report
                </a>
                @endif
            @elseif(\App\Models\ModuleRegistry::allows(\App\Models\Tenant::current(), auth()->user(), 'laboratory'))
                <a href="{{ route('lab-orders.results.create', $orderItem) }}"
                   class="inline-flex items-center px-3 py-2 bg-medical-blue text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all w-full justify-center">
                    <i class="fas fa-plus mr-2"></i>
                    Enter Lab Result
                </a>
            @endif
        </div>
    @endif

    @if(! $isPending && $orderItem->result)
        @if($isImaging
            ? \App\Models\ModuleRegistry::allows(\App\Models\Tenant::current(), auth()->user(), 'imaging')
            : \App\Models\ModuleRegistry::allows(\App\Models\Tenant::current(), auth()->user(), 'laboratory'))
        <div class="mt-3 pt-3 border-t border-green-200 flex gap-2">
            <a href="{{ route('lab-results.report', $orderItem->result) }}"
               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-all">
                <i class="fas fa-file-medical mr-2"></i>View
            </a>
        </div>
        @endif
    @endif
</div>
