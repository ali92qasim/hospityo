<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting Up Your Hospital — UseClinicSync</title>
    @vite(['resources/css/app.css'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen flex items-center justify-center">

<div class="max-w-md w-full mx-4">
        <a href="{{ url('/') }}" class="inline-flex items-center mb-6">
            @include('partials.logo')
        </a>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
        {{-- Spinner State --}}
        <div id="state-loading" class="text-center">
            <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-cog fa-spin text-medical-blue text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Setting up {{ $tenant->name }}</h2>
            <p class="text-sm text-gray-500 mb-8">This usually takes less than a minute</p>

            <div class="text-left space-y-3 mb-4">
                <div class="flex items-center text-sm" id="step-db">
                    <i class="fas fa-circle-notch fa-spin text-medical-blue mr-3 w-5 text-center"></i>
                    <span class="text-gray-600">Creating database</span>
                </div>
                <div class="flex items-center text-sm" id="step-migrate">
                    <i class="fas fa-circle text-gray-300 mr-3 w-5 text-center text-xs"></i>
                    <span class="text-gray-400">Running migrations</span>
                </div>
                <div class="flex items-center text-sm" id="step-seed">
                    <i class="fas fa-circle text-gray-300 mr-3 w-5 text-center text-xs"></i>
                    <span class="text-gray-400">Configuring your workspace</span>
                </div>
                <div class="flex items-center text-sm" id="step-ready">
                    <i class="fas fa-circle text-gray-300 mr-3 w-5 text-center text-xs"></i>
                    <span class="text-gray-400">Finalizing setup</span>
                </div>
            </div>
        </div>

        {{-- Success State (hidden) --}}
        <div id="state-success" class="text-center hidden">
            <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-check text-green-500 text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">You're all set!</h2>
            <p class="text-sm text-gray-500 mb-2">{{ $tenant->name }} is ready to use.</p>
            <p class="text-sm text-gray-500 mb-6">Redirecting you to sign in<span id="dots">...</span></p>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-center text-sm text-medical-blue">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span>Use your email and password to sign in</span>
                </div>
            </div>

            <div class="w-full bg-gray-200 rounded-full h-1 overflow-hidden">
                <div id="redirect-bar" class="bg-medical-blue h-1 rounded-full transition-all duration-[4000ms] ease-linear" style="width: 0%"></div>
            </div>
        </div>

        {{-- Error State (hidden) --}}
        <div id="state-error" class="text-center hidden">
            <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Setup failed</h2>
            <p class="text-sm text-gray-500 mb-6">Something went wrong during provisioning. Please contact support or try again.</p>
            <a href="{{ url('/contact') }}" class="inline-flex items-center px-6 py-2.5 bg-medical-blue text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium">
                <i class="fas fa-envelope mr-2"></i>Contact Support
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    var statusUrl = "{{ route('tenant.status', $tenant) }}";
    var signinUrl = "{{ url('/signin') }}";
    var steps = ['step-db', 'step-migrate', 'step-seed', 'step-ready'];
    var currentStep = 0;
    var pollCount = 0;
    var maxPolls = 60;

    function advanceStep() {
        if (currentStep < steps.length - 1) {
            var el = document.getElementById(steps[currentStep]);
            el.querySelector('i').className = 'fas fa-check-circle text-green-500 mr-3 w-5 text-center';
            el.querySelector('span').className = 'text-gray-700';
            currentStep++;
            var next = document.getElementById(steps[currentStep]);
            next.querySelector('i').className = 'fas fa-circle-notch fa-spin text-medical-blue mr-3 w-5 text-center';
            next.querySelector('span').className = 'text-gray-600';
        }
    }

    function markAllDone() {
        steps.forEach(function(id) {
            var el = document.getElementById(id);
            el.querySelector('i').className = 'fas fa-check-circle text-green-500 mr-3 w-5 text-center';
            el.querySelector('span').className = 'text-gray-700';
        });
    }

    function showSuccess() {
        markAllDone();
        setTimeout(function() {
            document.getElementById('state-loading').classList.add('hidden');
            document.getElementById('state-success').classList.remove('hidden');

            // Start progress bar animation
            setTimeout(function() {
                document.getElementById('redirect-bar').style.width = '100%';
            }, 100);

            // Redirect after 4 seconds
            setTimeout(function() {
                window.location.href = signinUrl + '?welcome=1';
            }, 4000);
        }, 800);
    }

    function showError() {
        document.getElementById('state-loading').classList.add('hidden');
        document.getElementById('state-error').classList.remove('hidden');
    }

    function poll() {
        pollCount++;
        if (pollCount > maxPolls) { showError(); return; }
        if (pollCount % 3 === 0 && currentStep < steps.length - 1) advanceStep();

        fetch(statusUrl)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'active') { showSuccess(); }
                else if (data.status === 'failed') { showError(); }
                else { setTimeout(poll, 2000); }
            })
            .catch(function() { setTimeout(poll, 3000); });
    }

    var initialStatus = "{{ $tenant->status }}";
    if (initialStatus === 'active') { showSuccess(); }
    else if (initialStatus === 'failed') { showError(); }
    else { setTimeout(poll, 1500); }
})();
</script>

</body>
</html>
