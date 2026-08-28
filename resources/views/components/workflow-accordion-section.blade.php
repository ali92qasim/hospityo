@props([
    'id',
    'title',
    'icon',
    'iconColor' => 'text-gray-600',
    'state' => 'idle',
    'stateLabel' => null,
    'open' => false,
    'disabled' => false,
])

@php
    $chips = [
        'done' => ['Done', 'bg-green-100 text-green-800'],
        'next' => ['Next', 'bg-blue-100 text-blue-800'],
        'locked' => [$stateLabel ?? 'Locked', 'bg-gray-100 text-gray-600'],
        'idle' => ['', ''],
    ];
    [$chipText, $chipClass] = $chips[$state] ?? $chips['idle'];
@endphp

<section
    data-workflow-section="{{ $id }}"
    data-open="{{ $open ? '1' : '0' }}"
    @if($disabled) data-section-disabled="1" @endif
    class="bg-white rounded-lg shadow-sm border border-gray-200"
>
    <button
        type="button"
        data-workflow-section-toggle="{{ $id }}"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        aria-controls="workflow-section-panel-{{ $id }}"
        class="w-full flex items-center gap-3 px-4 py-3 min-h-[44px] text-left hover:bg-gray-50"
    >
        <i class="fas {{ $icon }} {{ $iconColor }} w-5"></i>
        <span class="flex-1 text-sm font-medium text-gray-800">{{ $title }}</span>
        @if($chipText)
            <span class="text-xs px-2 py-1 rounded-full {{ $chipClass }}">{{ $chipText }}</span>
        @endif
        <i class="fas fa-chevron-down text-gray-400 transition-transform {{ $open ? 'rotate-180' : '' }}" data-workflow-section-chevron></i>
    </button>
    <div
        id="{{ $id }}-content"
        data-workflow-section-panel="{{ $id }}"
        class="{{ $open ? '' : 'hidden' }} px-4 pb-4 pt-1 {{ $disabled ? 'opacity-50 pointer-events-none' : '' }}"
    >
        {{ $slot }}
    </div>
</section>
