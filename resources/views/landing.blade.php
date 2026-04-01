<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hospityo — Cloud-based hospital management system. Manage patients, billing, pharmacy, labs, and more from any device.">
    <title>Hospityo — Hospital Management Made Simple</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-white text-gray-800 font-sans antialiased">

{{-- Navigation --}}
<nav class="fixed top-0 w-full bg-white/90 backdrop-blur-sm border-b border-gray-100 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center space-x-2">
                <div class="h-9 w-9 bg-medical-blue rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-hospital text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-gray-900">Hospityo</span>
            </div>
            <div class="hidden md:flex items-center space-x-8">
                <a href="#features" class="text-sm text-gray-600 hover:text-medical-blue transition-colors">Features</a>
                <a href="#how-it-works" class="text-sm text-gray-600 hover:text-medical-blue transition-colors">How It Works</a>
                <a href="#pricing" class="text-sm text-gray-600 hover:text-medical-blue transition-colors">Pricing</a>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-3">
                <a href="{{ route('tenant.register') }}"
                   class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-medical-blue border border-medical-blue rounded-lg hover:bg-blue-50 transition-colors">
                    Sign Up Free
                </a>
                <button onclick="document.getElementById('login-modal').classList.remove('hidden')"
                        class="inline-flex items-center px-3 sm:px-4 py-2 text-sm font-medium text-white bg-medical-blue rounded-lg hover:bg-blue-700 transition-colors">
                    <span class="hidden xs:inline">Sign In</span>
                    <i class="fas fa-sign-in-alt xs:hidden"></i>
                </button>
                {{-- Mobile menu button --}}
                <button id="mobile-nav-btn" class="md:hidden inline-flex items-center justify-center p-2 text-gray-500 hover:text-gray-700" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        {{-- Mobile nav links --}}
        <div id="mobile-nav" class="hidden md:hidden pb-4 border-t border-gray-100 mt-2 pt-3">
            <div class="flex flex-col space-y-2">
                <a href="#features" class="text-sm text-gray-600 hover:text-medical-blue py-1">Features</a>
                <a href="#how-it-works" class="text-sm text-gray-600 hover:text-medical-blue py-1">How It Works</a>
                <a href="#pricing" class="text-sm text-gray-600 hover:text-medical-blue py-1">Pricing</a>
                <a href="{{ route('tenant.register') }}" class="sm:hidden text-sm text-medical-blue font-medium py-1">Sign Up Free</a>
            </div>
        </div>
    </div>
</nav>

{{-- Hero Section --}}
<section class="pt-28 pb-16 sm:pt-36 sm:pb-24 bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-medical-blue mb-6">
                <i class="fas fa-bolt mr-1.5"></i> Now with multi-tenant architecture
            </span>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight tracking-tight">
                Hospital management
                <span class="text-medical-blue">made simple</span>
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto">
                Run your hospital, clinic, or medical practice from one platform. Patient records, billing, pharmacy, labs — everything in one place.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('tenant.register') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-base font-medium text-white bg-medical-blue rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">
                    <i class="fas fa-rocket mr-2"></i>
                    Start Free Trial
                </a>
                <a href="#features"
                   class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-base font-medium text-gray-700 bg-white rounded-xl hover:bg-gray-50 transition-colors border border-gray-200">
                    See Features
                    <i class="fas fa-arrow-down ml-2 text-sm"></i>
                </a>
            </div>
            <p class="mt-4 text-sm text-gray-500">No credit card required. Set up in under 60 seconds.</p>
        </div>
    </div>
</section>

{{-- Stats Bar --}}
<section class="py-10 bg-white border-y border-gray-100">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-8 text-center">
            <div>
                <div class="text-2xl sm:text-3xl font-bold text-medical-blue">100%</div>
                <div class="mt-1 text-xs sm:text-sm text-gray-500">Cloud Based</div>
            </div>
            <div>
                <div class="text-2xl sm:text-3xl font-bold text-medical-blue">256-bit</div>
                <div class="mt-1 text-xs sm:text-sm text-gray-500">SSL Encryption</div>
            </div>
            <div>
                <div class="text-2xl sm:text-3xl font-bold text-medical-blue">99.9%</div>
                <div class="mt-1 text-xs sm:text-sm text-gray-500">Uptime SLA</div>
            </div>
            <div>
                <div class="text-2xl sm:text-3xl font-bold text-medical-blue">24/7</div>
                <div class="mt-1 text-xs sm:text-sm text-gray-500">Support</div>
            </div>
        </div>
    </div>
</section>

{{-- Features Section --}}
<section id="features" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Everything your hospital needs</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">One platform to manage your entire healthcare facility — from reception to discharge.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-medical-blue/20 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-user-injured text-blue-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Patient Management</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Complete patient records, history tracking, and demographics in one searchable database.</p>
            </div>
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-medical-blue/20 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-calendar-check text-green-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Appointments</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Online scheduling, calendar views, automated reminders, and doctor availability management.</p>
            </div>
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-medical-blue/20 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-file-invoice-dollar text-purple-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Billing & Invoicing</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Generate bills, track payments, manage insurance claims, and produce financial reports.</p>
            </div>
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-medical-blue/20 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-pills text-orange-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Pharmacy & Inventory</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Medicine catalog, stock tracking, expiry alerts, purchase orders, and dispensing workflow.</p>
            </div>
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-medical-blue/20 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-flask text-red-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Laboratory & Radiology</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Order tests, collect samples, enter results, and generate lab reports with parameter tracking.</p>
            </div>
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-medical-blue/20 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-bed text-teal-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">IPD Management</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Ward and bed management, admissions, discharges, and inpatient billing.</p>
            </div>
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-medical-blue/20 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-user-md text-indigo-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Doctor Portal</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Dedicated workspace for doctors — patient assignments, consultations, and prescriptions.</p>
            </div>
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-medical-blue/20 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-pink-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-shield-alt text-pink-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Role-Based Access</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Fine-grained permissions for admins, doctors, nurses, receptionists, and lab technicians.</p>
            </div>
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-medical-blue/20 hover:shadow-lg hover:shadow-blue-50 transition-all duration-300">
                <div class="w-12 h-12 rounded-xl bg-cyan-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-chart-bar text-cyan-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Reports & Analytics</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Revenue reports, patient demographics, doctor performance, and operational dashboards.</p>
            </div>
        </div>
    </div>
</section>

{{-- How It Works --}}
<section id="how-it-works" class="py-20 bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Up and running in 3 steps</h2>
            <p class="mt-4 text-lg text-gray-600">No installation, no servers, no IT team required.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            @foreach([
                ['1', 'Register', 'Enter your hospital name and create your admin account. Takes 30 seconds.'],
                ['2', 'Auto Setup', 'We create your private database, configure roles, and seed essential data automatically.'],
                ['3', 'Start Working', 'Log in to your dedicated subdomain and start managing patients immediately.'],
            ] as [$num, $title, $desc])
            <div class="text-center">
                <div class="w-14 h-14 rounded-full bg-medical-blue text-white text-xl font-bold flex items-center justify-center mx-auto mb-4">
                    {{ $num }}
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $title }}</h3>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-12">
            <a href="{{ route('tenant.register') }}"
               class="inline-flex items-center px-8 py-3.5 text-base font-medium text-white bg-medical-blue rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">
                <i class="fas fa-rocket mr-2"></i>
                Get Started Now
            </a>
        </div>
    </div>
</section>

{{-- Pricing Section --}}
<section id="pricing" class="py-20 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Simple, transparent pricing</h2>
            <p class="mt-4 text-lg text-gray-600">Start free. Upgrade when you're ready.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            {{-- Free --}}
            <div class="rounded-2xl border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-900">Starter</h3>
                <div class="mt-4 flex items-baseline">
                    <span class="text-4xl font-bold text-gray-900">Free</span>
                </div>
                <p class="mt-2 text-sm text-gray-500">For small clinics getting started</p>
                <ul class="mt-6 space-y-3">
                    @foreach(['Up to 100 patients', '1 doctor account', 'Basic billing', 'Patient records', 'Email support'] as $f)
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-medical-green mr-2.5 text-xs"></i>{{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('tenant.register') }}" class="mt-8 block w-full text-center px-4 py-2.5 text-sm font-medium text-medical-blue border border-medical-blue rounded-lg hover:bg-blue-50 transition-colors">
                    Start Free
                </a>
            </div>
            {{-- Pro --}}
            <div class="rounded-2xl border-2 border-medical-blue p-8 relative shadow-lg shadow-blue-100">
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-medical-blue text-white text-xs font-medium rounded-full whitespace-nowrap">Most Popular</span>
                <h3 class="text-lg font-semibold text-gray-900">Professional</h3>
                <div class="mt-4 flex items-baseline">
                    <span class="text-4xl font-bold text-gray-900">$49</span>
                    <span class="ml-1 text-gray-500">/month</span>
                </div>
                <p class="mt-2 text-sm text-gray-500">For growing hospitals</p>
                <ul class="mt-6 space-y-3">
                    @foreach(['Unlimited patients', 'Up to 10 doctors', 'Full billing & reports', 'Pharmacy & inventory', 'Lab management', 'Priority support'] as $f)
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-medical-green mr-2.5 text-xs"></i>{{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('tenant.register') }}" class="mt-8 block w-full text-center px-4 py-2.5 text-sm font-medium text-white bg-medical-blue rounded-lg hover:bg-blue-700 transition-colors">
                    Start Free Trial
                </a>
            </div>
            {{-- Enterprise --}}
            <div class="rounded-2xl border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-900">Enterprise</h3>
                <div class="mt-4 flex items-baseline">
                    <span class="text-4xl font-bold text-gray-900">Custom</span>
                </div>
                <p class="mt-2 text-sm text-gray-500">For hospital networks</p>
                <ul class="mt-6 space-y-3">
                    @foreach(['Everything in Pro', 'Unlimited doctors', 'Custom domain', 'Dedicated database', 'SLA guarantee', 'Dedicated account manager'] as $f)
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-medical-green mr-2.5 text-xs"></i>{{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="mailto:sales@hospityo.com" class="mt-8 block w-full text-center px-4 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Contact Sales
                </a>
            </div>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-20 bg-gradient-to-r from-medical-blue to-indigo-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-white">Ready to modernize your hospital?</h2>
        <p class="mt-4 text-lg text-blue-100 max-w-2xl mx-auto">Join healthcare providers who trust Hospityo to manage their daily operations. Set up takes less than a minute.</p>
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('tenant.register') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-base font-medium text-medical-blue bg-white rounded-xl hover:bg-gray-50 transition-colors">
                <i class="fas fa-rocket mr-2"></i>
                Start Free Trial
            </a>
        </div>
    </div>
</section>

{{-- Footer --}}
<footer class="py-12 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <div class="h-8 w-8 bg-medical-blue rounded-lg flex items-center justify-center">
                        <i class="fas fa-hospital text-white text-xs"></i>
                    </div>
                    <span class="text-lg font-bold text-white">Hospityo</span>
                </div>
                <p class="text-sm text-gray-400 leading-relaxed">Cloud-based hospital management for modern healthcare providers.</p>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">Product</h4>
                <ul class="space-y-2">
                    <li><a href="#features" class="text-sm text-gray-400 hover:text-white transition-colors">Features</a></li>
                    <li><a href="#pricing" class="text-sm text-gray-400 hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="#how-it-works" class="text-sm text-gray-400 hover:text-white transition-colors">How It Works</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">Support</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">Documentation</a></li>
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">Status Page</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">Legal</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">HIPAA Compliance</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-10 pt-8 border-t border-gray-800 text-center">
            <p class="text-sm text-gray-500">&copy; {{ date('Y') }} Hospityo. All rights reserved.</p>
        </div>
    </div>
</footer>

{{-- Login Modal --}}
<div id="login-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="document.getElementById('login-modal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-8 z-10">
            <button onclick="document.getElementById('login-modal').classList.add('hidden')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600" aria-label="Close modal">
                <i class="fas fa-times"></i>
            </button>
            <div class="text-center mb-6">
                <div class="mx-auto h-12 w-12 bg-medical-blue rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-hospital text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Sign in to your hospital</h3>
                <p class="mt-1 text-sm text-gray-500">Enter your hospital's subdomain to continue</p>
            </div>
            <form onsubmit="redirectToTenant(event)">
                <div class="mb-4">
                    <label for="tenant-slug" class="block text-sm font-medium text-gray-700 mb-1">Your subdomain</label>
                    <div class="flex items-center">
                        <input type="text" id="tenant-slug"
                               class="flex-1 px-3 py-2.5 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                               placeholder="your-hospital" required>
                        <span class="px-3 py-2.5 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-sm text-gray-500">
                            .{{ parse_url(config('app.url'), PHP_URL_HOST) }}
                        </span>
                    </div>
                </div>
                <button type="submit"
                        class="w-full bg-medical-blue text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Continue to Login
                </button>
            </form>
            <p class="mt-4 text-center text-sm text-gray-500">
                Don't have an account?
                <a href="{{ route('tenant.register') }}" class="text-medical-blue hover:underline">Register your hospital</a>
            </p>
        </div>
    </div>
</div>

<script>
function redirectToTenant(e) {
    e.preventDefault();
    var slug = document.getElementById('tenant-slug').value.trim().toLowerCase();
    if (slug) {
        var base = '{{ parse_url(config("app.url"), PHP_URL_HOST) }}';
        var protocol = '{{ parse_url(config("app.url"), PHP_URL_SCHEME) ?? "http" }}';
        window.location.href = protocol + '://' + slug + '.' + base + '/login';
    }
}
// Mobile nav toggle
(function() {
    var btn = document.getElementById('mobile-nav-btn');
    var nav = document.getElementById('mobile-nav');
    if (btn && nav) {
        btn.addEventListener('click', function() {
            nav.classList.toggle('hidden');
        });
    }
})();
</script>

</body>
</html>
