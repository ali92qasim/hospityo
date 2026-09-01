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
        <button type="button" onclick="showTab('care-team')" data-workflow-panel="care-team" class="workflow-action-button mt-4 w-full text-sm text-purple-700 hover:text-purple-900 font-medium">
            <i class="fas fa-user-plus mr-1"></i>Manage Care Team
        </button>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 space-y-2">
        <button type="button" onclick="showTab('admission')" id="admission-tab" data-workflow-panel="admission" class="workflow-action-button w-full text-left px-3 py-2 rounded-lg hover:bg-purple-50 text-sm font-medium text-gray-700">
            <i class="fas fa-bed mr-2 text-purple-600"></i>Admission
        </button>
        <button type="button" onclick="showTab('vitals')" id="vitals-tab" data-workflow-panel="vitals" class="workflow-action-button w-full text-left px-3 py-2 rounded-lg hover:bg-purple-50 text-sm font-medium text-gray-700">
            <i class="fas fa-heartbeat mr-2 text-red-500"></i>Record Vitals
        </button>
        <button type="button" onclick="showTab('consultation')" id="consultation-tab" data-workflow-panel="consultation" class="workflow-action-button w-full text-left px-3 py-2 rounded-lg hover:bg-purple-50 text-sm font-medium text-gray-700">
            <i class="fas fa-stethoscope mr-2 text-medical-blue"></i>Consultation
        </button>
        <button type="button" onclick="showTab('gpe')" id="gpe-tab" data-workflow-panel="gpe" class="workflow-action-button w-full text-left px-3 py-2 rounded-lg hover:bg-purple-50 text-sm font-medium text-gray-700">
            <i class="fas fa-notes-medical mr-2 text-indigo-600"></i>GPE Records
        </button>
        @if($workflowData['show_lab_investigations'] ?? false)
            <button type="button" onclick="showTab('lab')" id="lab-tab" data-workflow-panel="lab" class="workflow-action-button w-full text-left px-3 py-2 rounded-lg hover:bg-purple-50 text-sm font-medium text-gray-700">
                <i class="fas fa-flask mr-2 text-teal-600"></i>Lab
            </button>
        @endif
        @if($workflowData['show_imaging_investigations'] ?? false)
            <button type="button" onclick="showTab('imaging')" id="imaging-tab" data-workflow-panel="imaging" class="workflow-action-button w-full text-left px-3 py-2 rounded-lg hover:bg-purple-50 text-sm font-medium text-gray-700">
                <i class="fas fa-x-ray mr-2 text-indigo-500"></i>Imaging
            </button>
        @endif
        @if($workflowData['show_prescriptions'] ?? true)
        <button type="button" onclick="showTab('prescription')" id="prescription-tab" data-workflow-panel="prescription" class="workflow-action-button w-full text-left px-3 py-2 rounded-lg hover:bg-purple-50 text-sm font-medium text-gray-700">
            <i class="fas fa-prescription mr-2 text-green-600"></i>Prescription
        </button>
        @endif
    </div>
</aside>
