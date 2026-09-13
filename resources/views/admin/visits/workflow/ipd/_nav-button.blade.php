@php
    $unlocked = $access['unlocked'] ?? true;
    $lockReason = $access['lock_reason'] ?? 'Complete the previous step first.';
@endphp
<button type="button"
        onclick="showTab('{{ $id }}')"
        id="{{ $id }}-tab"
        data-workflow-panel="{{ $id }}"@unless($unlocked) data-tab-locked="1" data-tab-lock-reason="{{ $lockReason }}" aria-disabled="true" title="{{ $lockReason }}"@endunless
        class="workflow-action-button w-full text-left px-3 py-2 rounded-lg text-sm font-medium {{ $unlocked ? 'hover:bg-purple-50 text-gray-700' : 'text-gray-400 cursor-not-allowed opacity-60' }} {{ $extraClass ?? '' }}">
    <i class="fas {{ $icon }} mr-2 {{ $iconColor }}"></i>{{ $label }}
</button>
