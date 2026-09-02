@if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        {{ session('error') }}
    </div>
@endif

@if(session('pending_module_grant') && isset($tenant) && request()->routeIs('super-admin.*'))
    @php $pendingGrant = session('pending_module_grant'); @endphp
    <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 rounded-lg">
        <p class="text-sm font-medium mb-2">Grant default-role permissions for {{ implode(', ', $pendingGrant['labels'] ?? []) }}?</p>
        <p class="text-xs text-blue-800 mb-3">The plan is already applied. Skipping leaves Spatie roles unchanged.</p>
        <form method="POST" action="{{ route('super-admin.tenants.grant-modules', $tenant) }}">
            @csrf
            @foreach($pendingGrant['modules'] ?? [] as $module)
                <input type="hidden" name="modules[]" value="{{ $module }}">
            @endforeach
            <button type="submit" class="px-4 py-2 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                Grant permissions
            </button>
        </form>
    </div>
@endif

@if(session('pending_plan_module_grant') && request()->routeIs('super-admin.*'))
    @php $pendingPlanGrant = session('pending_plan_module_grant'); @endphp
    <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 rounded-lg">
        <p class="text-sm font-medium mb-2">Grant {{ implode(', ', $pendingPlanGrant['labels'] ?? []) }} to {{ $pendingPlanGrant['tenant_count'] ?? 0 }} hospital(s) on this plan?</p>
        <p class="text-xs text-blue-800 mb-3">Plan modules are already saved. Skipping leaves Spatie roles unchanged.</p>
        <form method="POST" action="{{ route('super-admin.plans.grant-modules', $pendingPlanGrant['plan_id']) }}">
            @csrf
            @foreach($pendingPlanGrant['modules'] ?? [] as $module)
                <input type="hidden" name="modules[]" value="{{ $module }}">
            @endforeach
            <button type="submit" class="px-4 py-2 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                Grant permissions
            </button>
        </form>
    </div>
@endif

@if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <div class="flex items-center mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Please fix the following errors:</strong>
        </div>
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif