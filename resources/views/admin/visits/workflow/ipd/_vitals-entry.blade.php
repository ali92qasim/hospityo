@php
    $compact = $compact ?? false;
@endphp

<div @class([
    'rounded-lg p-4 border',
    'border-blue-200 bg-blue-50' => ! $compact,
    'border-blue-100 bg-blue-50/80' => $compact,
]) data-vital-entry="{{ $vital->id }}">
    <div class="flex justify-between items-start mb-3">
        <span @class([
            'font-medium text-blue-800' => ! $compact,
            'text-xs font-semibold uppercase text-blue-700' => $compact,
        ])>
            @if($compact)
                Vitals
            @else
                {{ $vital->created_at->format('M d, Y h:i A') }}
            @endif
        </span>
        <span class="text-xs text-blue-600">
            @if($compact)
                {{ $vital->created_at->format('M d, Y h:i A') }}
            @else
                {{ $vital->user?->name ?? 'Unknown' }}
            @endif
        </span>
    </div>
    <div @class([
        'grid grid-cols-2 gap-2 text-sm text-blue-900',
        'md:grid-cols-4' => $compact,
    ])>
        @if($vital->blood_pressure)
            <span><span class="text-blue-600">BP:</span> {{ $vital->blood_pressure }}</span>
        @endif
        @if($vital->temperature)
            <span><span class="text-blue-600">Temp:</span> {{ $vital->temperature }}°F</span>
        @endif
        @if($vital->pulse_rate)
            <span><span class="text-blue-600">Pulse:</span> {{ $vital->pulse_rate }} bpm</span>
        @endif
        @if($vital->spo2)
            <span><span class="text-blue-600">SpO<sub>2</sub>:</span> {{ $vital->spo2 }}%</span>
        @endif
        @if($vital->bsr)
            <span><span class="text-blue-600">BSR:</span> {{ $vital->bsr }}%</span>
        @endif
        @if($vital->weight)
            <span><span class="text-blue-600">Weight:</span> {{ $vital->weight }} kg</span>
        @endif
        @if($vital->height)
            <span><span class="text-blue-600">Height:</span> {{ $vital->height }} ft</span>
        @endif
    </div>
    @if($vital->notes)
        <div class="mt-2 text-sm text-blue-700">
            <span class="font-medium">Notes:</span> {{ $vital->notes }}
        </div>
    @endif
</div>
