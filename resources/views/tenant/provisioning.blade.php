@extends('auth.layout')

@section('title', 'Setting Up Your Hospital - Hospityo')
@section('subtitle', 'Please wait while we prepare your workspace')

@section('content')
<div id="provisioning-status" class="text-center py-6">
    {{-- Spinner --}}
    <div id="spinner" class="mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50">
            <i class="fas fa-cog fa-spin text-medical-blue text-2xl"></i>
        </div>
    </div>

    {{-- Success icon (hidden initially) --}}
    <div id="success-icon" class="mb-6 hidden">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-50">
            <i class="fas fa-check-circle text-green-500 text-3xl"></i>
        </div>
    </div>

    {{-- Error icon (hidden initially) --}}
    <div id="error-icon" class="mb-6 hidden">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-50">
            <i class="fas fa-exclamation-circle text-red-500 text-3xl"></i>
        </div>
    </div>

    <h3 id="status-title" class="text-lg font-semibold text-gray-800 mb-2">
        Setting up {{ $tenant->name }}...
    </h3>

    <p id="status-message" class="text-sm text-gray-500 mb-6">
        Creating database, running migrations, and seeding default data.
    </p>

    {{-- Progress steps --}}
    <div class="text-left max-w-xs mx-auto space-y-3 mb-6">
        <div class="flex items-center text-sm" id="step-db">
            <i class="fas fa-circle-notch fa-spin text-blue-400 mr-3 w-4"></i>
            <span class="text-gray-600">Creating database</span>
        </div>
        <div class="flex items-center text-sm" id="step-migrate">
            <i class="fas fa-circle text-gray-300 mr-3 w-4"></i>
            <span class="text-gray-400">Running migrations</span>
        </div>
        <div class="flex items-center text-sm" id="step-seed">
            <i class="fas fa-circle text-gray-300 mr-3 w-4"></i>
            <span class="text-gray-400">Seeding default data</span>
        </div>
        <div class="flex items-center text-sm" id="step-ready">
            <i class="fas fa-circle text-gray-300 mr-3 w-4"></i>
            <span class="text-gray-400">Ready to go</span>
        </div>
    </div>

    {{-- Redirect button (hidden initially) --}}
    <a id="go-btn" href="#" class="hidden inline-flex items-center px-6 py-2.5 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-arrow-right mr-2"></i>
        Go to Your Dashboard
    </a>
</div>

<script>
    (function () {
        const statusUrl = "{{ route('tenant.status', $tenant) }}";
        const steps = ['step-db', 'step-migrate', 'step-seed', 'step-ready'];
        let currentStep = 0;
        let pollCount = 0;
        const maxPolls = 60; // 2 minutes max

        function advanceStep() {
            if (currentStep < steps.length - 1) {
                // Mark current as done
                const el = document.getElementById(steps[currentStep]);
                el.querySelector('i').className = 'fas fa-check-circle text-green-500 mr-3 w-4';
                el.querySelector('span').className = 'text-gray-700';

                currentStep++;

                // Mark next as active
                const next = document.getElementById(steps[currentStep]);
                next.querySelector('i').className = 'fas fa-circle-notch fa-spin text-blue-400 mr-3 w-4';
                next.querySelector('span').className = 'text-gray-600';
            }
        }

        function markAllDone() {
            steps.forEach(id => {
                const el = document.getElementById(id);
                el.querySelector('i').className = 'fas fa-check-circle text-green-500 mr-3 w-4';
                el.querySelector('span').className = 'text-gray-700';
            });
        }

        function showSuccess(url) {
            document.getElementById('spinner').classList.add('hidden');
            document.getElementById('success-icon').classList.remove('hidden');
            document.getElementById('status-title').textContent = 'Your hospital is ready!';
            document.getElementById('status-message').textContent = 'Everything has been set up. You can now sign in.';
            const btn = document.getElementById('go-btn');
            btn.href = url;
            btn.classList.remove('hidden');
            markAllDone();
        }

        function showError() {
            document.getElementById('spinner').classList.add('hidden');
            document.getElementById('error-icon').classList.remove('hidden');
            document.getElementById('status-title').textContent = 'Something went wrong';
            document.getElementById('status-message').textContent = 'Provisioning failed. Please contact support.';
        }

        function poll() {
            pollCount++;
            if (pollCount > maxPolls) {
                showError();
                return;
            }

            // Simulate step progress while polling
            if (pollCount % 3 === 0 && currentStep < steps.length - 1) {
                advanceStep();
            }

            fetch(statusUrl)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'active') {
                        showSuccess(data.url);
                    } else if (data.status === 'failed') {
                        showError();
                    } else {
                        setTimeout(poll, 2000);
                    }
                })
                .catch(() => setTimeout(poll, 3000));
        }

        // If already active (sync provisioning), show success immediately
        var initialStatus = "{{ $tenant->status }}";
        if (initialStatus === 'active') {
            showSuccess('http://{{ $tenant->domain }}/login');
        } else if (initialStatus === 'failed') {
            showError();
        } else {
            // Start polling after a short delay
            setTimeout(poll, 1500);
        }
    })();
</script>
@endsection
