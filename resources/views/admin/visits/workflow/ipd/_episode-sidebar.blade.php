@php
    $admission = $visit->admission;
    $lengthOfStay = $admission?->admitted_at
        ? max(1, $admission->admitted_at->startOfDay()->diffInDays(now()->startOfDay()) + 1)
        : null;
    $draftBill = $workflowData['draft_bill'] ?? null;
@endphp

<aside data-landmark="ipd-episode-sidebar" class="lg:col-span-1 space-y-4">
    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-purple-500">
        <h4 class="text-sm font-semibold text-purple-800 uppercase tracking-wide mb-3">Episode</h4>
        @if($admission)
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-500">Bed / Ward</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->bed->bed_number ?? '—' }} · {{ $admission->bed->ward->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Length of stay</dt>
                    <dd class="font-medium text-gray-900">Day {{ $lengthOfStay ?? 1 }}</dd>
                </div>
                @if($workflowData['expected_discharge_date'] ?? null)
                    <div>
                        <dt class="text-gray-500">Expected discharge</dt>
                        <dd class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($workflowData['expected_discharge_date'])->format('M d, Y') }}</dd>
                    </div>
                @endif
                @if($draftBill)
                    <div>
                        <dt class="text-gray-500">Draft bill</dt>
                        <dd class="font-medium text-gray-900">{{ currency_symbol() }}{{ number_format($draftBill->total_amount ?? 0, 0) }}</dd>
                    </div>
                @endif
            </dl>
        @else
            <p class="text-sm text-gray-600">Patient not yet admitted. Select a bed to begin the inpatient episode.</p>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5">
        <h4 class="text-sm font-semibold text-gray-800 mb-3">
            <i class="fas fa-users text-purple-600 mr-1"></i>Care Team
        </h4>
        @forelse($visit->careTeam as $member)
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                    <p class="text-sm font-medium text-gray-900">Dr. {{ $member->doctor->name }}</p>
                    <p class="text-xs text-gray-500">{{ $member->doctor->specialization }}</p>
                </div>
                @if($member->is_primary)
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Primary</span>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">No care team members yet.</p>
        @endforelse
        @include('admin.visits.workflow.ipd._nav-button', [
            'id' => 'care-team',
            'label' => 'Manage Care Team',
            'icon' => 'fa-user-plus',
            'iconColor' => 'text-purple-600',
            'access' => $workflowData['tab_access']['care-team'] ?? ['unlocked' => true],
            'extraClass' => 'mt-4',
        ])
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 space-y-2">
        @include('admin.visits.workflow.ipd._nav-button', [
            'id' => 'admission',
            'label' => 'Admission',
            'icon' => 'fa-bed',
            'iconColor' => 'text-purple-600',
            'access' => $workflowData['tab_access']['admission'] ?? ['unlocked' => true],
        ])
        @include('admin.visits.workflow.ipd._nav-button', [
            'id' => 'vitals',
            'label' => 'Record Vitals',
            'icon' => 'fa-heartbeat',
            'iconColor' => 'text-red-500',
            'access' => $workflowData['tab_access']['vitals'] ?? ['unlocked' => false, 'lock_reason' => 'Complete the previous step first.'],
        ])
        @include('admin.visits.workflow.ipd._nav-button', [
            'id' => 'consultation',
            'label' => 'Consultation',
            'icon' => 'fa-stethoscope',
            'iconColor' => 'text-medical-blue',
            'access' => $workflowData['tab_access']['consultation'] ?? ['unlocked' => false, 'lock_reason' => 'Complete the previous step first.'],
        ])
        @include('admin.visits.workflow.ipd._nav-button', [
            'id' => 'gpe',
            'label' => 'GPE Records',
            'icon' => 'fa-notes-medical',
            'iconColor' => 'text-indigo-600',
            'access' => $workflowData['tab_access']['gpe'] ?? ['unlocked' => false, 'lock_reason' => 'Complete the previous step first.'],
        ])
        @if($workflowData['show_lab_investigations'] ?? false)
            @include('admin.visits.workflow.ipd._nav-button', [
                'id' => 'lab',
                'label' => 'Lab',
                'icon' => 'fa-flask',
                'iconColor' => 'text-teal-600',
                'access' => $workflowData['tab_access']['lab'] ?? ['unlocked' => false, 'lock_reason' => 'Complete the previous step first.'],
            ])
        @endif
        @if($workflowData['show_imaging_investigations'] ?? false)
            @include('admin.visits.workflow.ipd._nav-button', [
                'id' => 'imaging',
                'label' => 'Imaging',
                'icon' => 'fa-x-ray',
                'iconColor' => 'text-indigo-500',
                'access' => $workflowData['tab_access']['imaging'] ?? ['unlocked' => false, 'lock_reason' => 'Complete the previous step first.'],
            ])
        @endif
        @if($workflowData['show_prescriptions'] ?? true)
            @include('admin.visits.workflow.ipd._nav-button', [
                'id' => 'prescription',
                'label' => 'Prescription',
                'icon' => 'fa-prescription',
                'iconColor' => 'text-green-600',
                'access' => $workflowData['tab_access']['prescription'] ?? ['unlocked' => false, 'lock_reason' => 'Complete the previous step first.'],
            ])
        @endif
    </div>
</aside>
