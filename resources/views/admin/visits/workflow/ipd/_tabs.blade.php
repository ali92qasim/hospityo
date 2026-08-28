@php
    $activeTabName = $workflowData['resolved_initial_tab'] ?? $workflowData['default_tab'] ?? 'admission';
    if ($activeTabName === 'tests') {
        $activeTabName = 'lab';
    }
    if ($activeTabName === 'gpe-tab') {
        $activeTabName = 'gpe';
    }

    $ipdTabs = [
        ['id' => 'admission', 'label' => 'Admission', 'icon' => 'fa-bed'],
        ['id' => 'vitals', 'label' => 'Vitals', 'icon' => 'fa-heartbeat'],
        ['id' => 'consultation', 'label' => 'Round Note', 'icon' => 'fa-stethoscope'],
        ['id' => 'gpe', 'label' => 'GPE', 'icon' => 'fa-notes-medical'],
        ['id' => 'lab', 'label' => 'Lab', 'icon' => 'fa-flask', 'show' => $workflowData['show_investigations'] ?? false],
        ['id' => 'imaging', 'label' => 'Imaging', 'icon' => 'fa-x-ray', 'show' => $workflowData['show_investigations'] ?? false],
        ['id' => 'prescription', 'label' => 'Prescription', 'icon' => 'fa-prescription'],
        ['id' => 'care-team', 'label' => 'Care Team', 'icon' => 'fa-users'],
    ];
@endphp

@foreach($ipdTabs as $tab)
    @if($tab['show'] ?? true)
        @php $isActive = $activeTabName === $tab['id']; @endphp
        <button type="button"
                onclick="showTab('{{ $tab['id'] }}')"
                id="{{ $tab['id'] }}-tab"
                data-workflow-panel="{{ $tab['id'] }}"
                role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                @if($isActive) aria-current="page" @endif
                class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $isActive ? 'border-medical-blue text-medical-blue' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <i class="fas {{ $tab['icon'] }} mr-2"></i>{{ $tab['label'] }}
        </button>
    @endif
@endforeach
