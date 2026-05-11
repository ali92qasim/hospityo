<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Get Started — Hospityo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen">

<nav class="bg-white/80 backdrop-blur-sm border-b border-gray-100">
    <div class="max-w-xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-14">
            <a href="{{ url('/') }}" class="flex items-center space-x-2">
                <div class="h-8 w-8 bg-medical-blue rounded-lg flex items-center justify-center">
                    <i class="fas fa-hospital text-white text-xs"></i>
                </div>
                <span class="text-lg font-bold text-gray-900">Hospityo</span>
            </a>
            <span class="text-sm text-gray-400">
                Already have an account?
                <a href="{{ url('/signin') }}" class="text-medical-blue hover:underline font-medium">Sign in</a>
            </span>
        </div>
    </div>
</nav>

<main class="max-w-xl mx-auto px-4 sm:px-6 py-10">

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
        <p class="font-medium mb-1"><i class="fas fa-exclamation-circle mr-2"></i>Please fix the errors below and try again.</p>
        <ul class="list-disc list-inside space-y-0.5 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Progress Steps --}}
    <div class="flex items-center justify-center mb-8">
        <div class="flex items-center">
            <div id="step-dot-1" class="w-9 h-9 rounded-full bg-medical-blue text-white flex items-center justify-center text-sm font-semibold">1</div>
            <span id="step-label-1" class="ml-2 text-sm font-medium text-medical-blue hidden sm:inline">Account</span>
        </div>
        <div id="step-line-1" class="w-12 sm:w-20 h-0.5 mx-2 bg-gray-200"></div>
        <div class="flex items-center">
            <div id="step-dot-2" class="w-9 h-9 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm font-semibold">2</div>
            <span id="step-label-2" class="ml-2 text-sm font-medium text-gray-400 hidden sm:inline">Hospital</span>
        </div>
        <div id="step-line-2" class="w-12 sm:w-20 h-0.5 mx-2 bg-gray-200"></div>
        <div class="flex items-center">
            <div id="step-dot-3" class="w-9 h-9 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm font-semibold">3</div>
            <span id="step-label-3" class="ml-2 text-sm font-medium text-gray-400 hidden sm:inline">Plan</span>
        </div>
    </div>

    <form method="POST" action="{{ route('tenant.register.store') }}" id="wizard-form">
        @csrf

        {{-- ═══ STEP 1: Your Account ═══ --}}
        <div id="step-1" class="wizard-step">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="text-center mb-6">
                    <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-user-plus text-purple-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Create your account</h2>
                    <p class="text-sm text-gray-500 mt-1">This will be the admin account for your hospital</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" id="f-admin_name"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm"
                               placeholder="Dr. Ahmed Khan" required>
                        @error('admin_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" id="f-admin_email"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm"
                               placeholder="you@example.com" required>
                        @error('admin_email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <input type="password" name="admin_password" id="f-admin_password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm"
                               placeholder="Min 8 characters" required>
                        @error('admin_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                        <input type="password" name="admin_password_confirmation" id="f-admin_password_confirmation"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm"
                               placeholder="Repeat password" required>
                    </div>
                </div>

                <button type="button" onclick="goToStep(2)"
                        class="w-full mt-6 bg-medical-blue text-white py-3 rounded-xl hover:bg-blue-700 transition-colors text-sm font-semibold">
                    Continue <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>

        {{-- ═══ STEP 2: Hospital Details ═══ --}}
        <div id="step-2" class="wizard-step hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="text-center mb-6">
                    <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-hospital text-medical-blue text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">About your hospital</h2>
                    <p class="text-sm text-gray-500 mt-1">Tell us about your healthcare facility</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Hospital / Clinic Name</label>
                        <input type="text" name="hospital_name" value="{{ old('hospital_name') }}" id="f-hospital_name"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm"
                               placeholder="e.g. City General Hospital" required>
                        @error('hospital_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Hospital Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" id="f-email"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm"
                                   placeholder="info@hospital.com" required>
                            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone <span class="text-gray-400">(optional)</span></label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm"
                                   placeholder="+92 300 1234567">
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="goToStep(1)"
                            class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </button>
                    <button type="button" onclick="goToStep(3)"
                            class="flex-[2] py-3 bg-medical-blue text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-semibold">
                        Continue <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ STEP 3: Choose Plan ═══ --}}
        <div id="step-3" class="wizard-step hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="text-center mb-6">
                    <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-rocket text-green-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Choose your plan</h2>
                    <p class="text-sm text-gray-500 mt-1">Start with a free trial, upgrade anytime</p>
                </div>

                <div class="space-y-3 mb-6">
                    @foreach($plans as $plan)
                    <label class="group flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200
                        {{ old('plan', 'starter') === $plan->slug ? 'border-medical-blue bg-blue-50/50' : 'border-gray-200 hover:border-gray-300' }}"
                        id="plan-label-{{ $plan->slug }}">
                        <input type="radio" name="plan" value="{{ $plan->slug }}"
                               class="text-medical-blue focus:ring-medical-blue h-4 w-4 flex-shrink-0"
                               {{ old('plan', 'starter') === $plan->slug ? 'checked' : '' }}
                               onchange="document.querySelectorAll('[id^=plan-label-]').forEach(l=>{l.classList.remove('border-medical-blue','bg-blue-50/50');l.classList.add('border-gray-200')});this.closest('label').classList.remove('border-gray-200');this.closest('label').classList.add('border-medical-blue','bg-blue-50/50');">
                        <div class="flex-1 ml-4 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-900">{{ $plan->name }}</span>
                                <span class="text-sm font-bold {{ $plan->price > 0 ? 'text-medical-blue' : 'text-green-600' }}">
                                    {{ $plan->price > 0 ? currency_symbol('PKR') . ' ' . number_format($plan->price) . '/mo' : 'Free' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $plan->description }}</p>
                            @if($plan->trial_days)
                            <span class="inline-flex items-center mt-1.5 text-xs text-medical-blue bg-blue-50 px-2 py-0.5 rounded-full">
                                <i class="fas fa-clock mr-1"></i>{{ $plan->trial_days }}-day free trial
                            </span>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>

                <div class="flex items-start mb-6 p-3 bg-gray-50 rounded-xl">
                    <input type="checkbox" name="terms" id="terms" class="h-4 w-4 text-medical-blue border-gray-300 rounded mt-0.5 flex-shrink-0" required>
                    <label for="terms" class="ml-3 text-xs text-gray-600 leading-relaxed">
                        I agree to the <a href="{{ route('page.show', 'terms-and-conditions') }}" target="_blank" class="text-medical-blue hover:underline">Terms &amp; Conditions</a>
                        and <a href="{{ route('page.show', 'privacy-policy') }}" target="_blank" class="text-medical-blue hover:underline">Privacy Policy</a>
                    </label>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="goToStep(2)"
                            class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </button>
                    <button type="submit" id="submit-btn"
                            class="flex-[2] py-3 bg-medical-blue text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-semibold shadow-lg shadow-blue-200/50">
                        <i class="fas fa-rocket mr-2"></i>Create My Hospital
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Summary preview (shown on steps 2 & 3) --}}
    <div id="step-summary" class="hidden mt-4 text-center">
        <p class="text-xs text-gray-400">
            <span id="summary-name"></span> • <span id="summary-email"></span>
        </p>
    </div>
</main>

<footer class="py-6 text-center text-xs text-gray-400">
    &copy; {{ date('Y') }} Hospityo. All rights reserved.
</footer>

<script>
var currentStep = 1;

function goToStep(step) {
    // Validate current step before moving forward
    if (step > currentStep && !validateStep(currentStep)) return;

    // Hide all steps
    document.querySelectorAll('.wizard-step').forEach(s => s.classList.add('hidden'));
    document.getElementById('step-' + step).classList.remove('hidden');

    // Update progress dots
    for (var i = 1; i <= 3; i++) {
        var dot = document.getElementById('step-dot-' + i);
        var label = document.getElementById('step-label-' + i);
        var line = document.getElementById('step-line-' + (i - 1));

        if (i < step) {
            // Completed
            dot.className = 'w-9 h-9 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-semibold';
            dot.innerHTML = '<i class="fas fa-check text-xs"></i>';
            if (label) { label.className = 'ml-2 text-sm font-medium text-green-600 hidden sm:inline'; }
            if (line) { line.className = 'w-12 sm:w-20 h-0.5 mx-2 bg-green-500'; }
        } else if (i === step) {
            // Current
            dot.className = 'w-9 h-9 rounded-full bg-medical-blue text-white flex items-center justify-center text-sm font-semibold';
            dot.textContent = i;
            if (label) { label.className = 'ml-2 text-sm font-medium text-medical-blue hidden sm:inline'; }
            if (line) { line.className = 'w-12 sm:w-20 h-0.5 mx-2 bg-gray-200'; }
        } else {
            // Upcoming
            dot.className = 'w-9 h-9 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm font-semibold';
            dot.textContent = i;
            if (label) { label.className = 'ml-2 text-sm font-medium text-gray-400 hidden sm:inline'; }
            if (line) { line.className = 'w-12 sm:w-20 h-0.5 mx-2 bg-gray-200'; }
        }
    }

    // Show summary on steps 2 & 3
    var summary = document.getElementById('step-summary');
    if (step > 1) {
        summary.classList.remove('hidden');
        document.getElementById('summary-name').textContent = document.getElementById('f-admin_name').value || '';
        document.getElementById('summary-email').textContent = document.getElementById('f-admin_email').value || '';
    } else {
        summary.classList.add('hidden');
    }

    currentStep = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep(step) {
    if (step === 1) {
        var name = document.getElementById('f-admin_name').value.trim();
        var email = document.getElementById('f-admin_email').value.trim();
        var pass = document.getElementById('f-admin_password').value;
        var confirm = document.getElementById('f-admin_password_confirmation').value;

        if (!name) { shake('f-admin_name'); return false; }
        if (!email || !email.includes('@')) { shake('f-admin_email'); return false; }
        if (pass.length < 8) { shake('f-admin_password'); return false; }
        if (pass !== confirm) { shake('f-admin_password_confirmation'); return false; }
        return true;
    }
    if (step === 2) {
        var hospital = document.getElementById('f-hospital_name').value.trim();
        var hEmail = document.getElementById('f-email').value.trim();
        if (!hospital) { shake('f-hospital_name'); return false; }
        if (!hEmail || !hEmail.includes('@')) { shake('f-email'); return false; }
        return true;
    }
    return true;
}

function shake(id) {
    var el = document.getElementById(id);
    el.classList.add('border-red-400', 'ring-2', 'ring-red-200');
    el.focus();
    setTimeout(function() { el.classList.remove('border-red-400', 'ring-2', 'ring-red-200'); }, 2000);
}

// If server-side validation failed, jump to the right step
document.addEventListener('DOMContentLoaded', function() {
    @if($errors->has('admin_name') || $errors->has('admin_email') || $errors->has('admin_password'))
        goToStep(1);
    @elseif($errors->has('hospital_name') || $errors->has('email'))
        goToStep(2);
    @elseif($errors->has('plan'))
        goToStep(3);
    @endif
});
</script>

</body>
</html>
