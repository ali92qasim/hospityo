@php
    $draftBill = $workflowData['draft_bill'] ?? null;
@endphp
<div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
    @if($admission)
        <div>
            <span class="text-gray-500">Stay</span>
            <span class="ml-2 font-semibold text-purple-800">Day {{ $lengthOfStay ?? 1 }}</span>
        </div>
        <div>
            <span class="text-gray-500">Bed</span>
            <span class="ml-2 font-semibold text-gray-900">{{ $admission->bed->bed_number ?? '—' }} · {{ $admission->bed->ward->name ?? '—' }}</span>
        </div>
        @if($workflowData['expected_discharge_date'] ?? null)
            <div>
                <span class="text-gray-500">Expected discharge</span>
                <span class="ml-2 font-medium text-gray-900">{{ \Carbon\Carbon::parse($workflowData['expected_discharge_date'])->format('M d, Y') }}</span>
            </div>
        @endif
        @if($draftBill)
            <div>
                <span class="text-gray-500">Draft bill</span>
                <span class="ml-2 font-medium text-gray-900">{{ currency_symbol() }}{{ number_format($draftBill->total_amount ?? 0, 0) }}</span>
            </div>
        @endif
    @else
        <span class="text-amber-700 font-medium"><i class="fas fa-bed mr-1"></i>Awaiting admission — assign a bed to start the episode</span>
    @endif
</div>
