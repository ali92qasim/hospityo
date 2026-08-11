<h4 class="text-lg font-medium text-gray-800 mb-4">Vital Signs History</h4>
<div class="space-y-4 max-h-96 overflow-y-auto">
    @forelse($visit->allVitalSigns as $vital)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex justify-between items-start mb-3">
                <h5 class="font-medium text-blue-800">{{ $vital->created_at->format('M d, Y h:i A') }}</h5>
                <span class="text-xs text-blue-600">{{ $vital->user?->name ?? 'Unknown' }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm">
                @if($vital->blood_pressure)
                    <div><span class="text-blue-600">BP:</span> {{ $vital->blood_pressure }}</div>
                @endif
                @if($vital->temperature)
                    <div><span class="text-blue-600">Temp:</span> {{ $vital->temperature }}°F</div>
                @endif
                @if($vital->pulse_rate)
                    <div><span class="text-blue-600">Pulse:</span> {{ $vital->pulse_rate }} bpm</div>
                @endif
                @if($vital->spo2)
                    <div><span class="text-blue-600">SpO<sub>2</sub>:</span> {{ $vital->spo2 }}%</div>
                @endif
                @if($vital->bsr)
                    <div><span class="text-blue-600">BSR:</span> {{ $vital->bsr }}%</div>
                @endif
                @if($vital->weight)
                    <div><span class="text-blue-600">Weight:</span> {{ $vital->weight }} kg</div>
                @endif
                @if($vital->height)
                    <div><span class="text-blue-600">Height:</span> {{ $vital->height }} ft</div>
                @endif
            </div>
            @if($vital->notes)
                <div class="mt-2 text-sm text-blue-700">
                    <span class="font-medium">Notes:</span> {{ $vital->notes }}
                </div>
            @endif
        </div>
    @empty
        <p class="text-gray-500 text-center py-4">No vital signs recorded yet.</p>
    @endforelse
</div>
