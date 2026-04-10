<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation — Hospityo</title>
    @vite(['resources/css/app.css'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

<nav class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ url('/') }}" class="flex items-center space-x-2">
                <div class="h-9 w-9 bg-medical-blue rounded-lg flex items-center justify-center">
                    <i class="fas fa-hospital text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-gray-900">Hospityo</span>
                <span class="text-xs bg-blue-100 text-medical-blue px-2 py-0.5 rounded-full font-medium">Docs</span>
            </a>
            <a href="{{ url('/') }}" class="text-sm text-gray-600 hover:text-medical-blue"><i class="fas fa-arrow-left mr-1"></i>Back to Home</a>
        </div>
    </div>
</nav>

<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900">Documentation</h1>
        <p class="mt-2 text-gray-600 max-w-xl mx-auto">Everything you need to get started and make the most of Hospityo.</p>
    </div>

    {{-- Quick Start --}}
    <div class="bg-gradient-to-r from-medical-blue to-indigo-600 rounded-2xl p-8 sm:p-10 text-white mb-12">
        <div class="max-w-2xl">
            <h2 class="text-2xl font-bold mb-3">Quick Start Guide</h2>
            <p class="text-blue-100 mb-6">Get your hospital up and running in under 5 minutes.</p>
            <div class="grid sm:grid-cols-3 gap-4">
                <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm">
                    <div class="text-2xl font-bold mb-1">1</div>
                    <h3 class="font-semibold text-sm mb-1">Register</h3>
                    <p class="text-xs text-blue-100">Create your hospital account with name and admin details.</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm">
                    <div class="text-2xl font-bold mb-1">2</div>
                    <h3 class="font-semibold text-sm mb-1">Configure</h3>
                    <p class="text-xs text-blue-100">Set up departments, doctors, and hospital settings.</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm">
                    <div class="text-2xl font-bold mb-1">3</div>
                    <h3 class="font-semibold text-sm mb-1">Go Live</h3>
                    <p class="text-xs text-blue-100">Start registering patients and managing visits.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Module Guides --}}
    @php
        $modules = [
            ['icon' => 'fa-user-injured', 'color' => 'blue', 'title' => 'Patient Management', 'items' => [
                'Register new patients with demographics and contact info',
                'Search patients by name, phone, or patient number',
                'View complete patient history across all visits',
                'Track allergies and chronic conditions',
            ]],
            ['icon' => 'fa-calendar-check', 'color' => 'green', 'title' => 'Appointments', 'items' => [
                'Schedule appointments from the calendar view',
                'Click any date to create a new appointment',
                'Drag and drop to reschedule appointments',
                'Filter by doctor to view individual schedules',
            ]],
            ['icon' => 'fa-clipboard-list', 'color' => 'indigo', 'title' => 'Visit Workflow', 'items' => [
                'Register visit → Record vitals → Doctor consultation',
                'OPD, IPD, and Emergency visit types supported',
                'Create prescriptions with medicine search and instructions',
                'Order lab investigations directly from the visit',
            ]],
            ['icon' => 'fa-file-invoice-dollar', 'color' => 'purple', 'title' => 'Billing', 'items' => [
                'Create bills with multiple service line items',
                'Track payments and outstanding balances',
                'Print professional invoices and receipts',
                'View revenue reports and daily cash register',
            ]],
            ['icon' => 'fa-pills', 'color' => 'orange', 'title' => 'Pharmacy & Inventory', 'items' => [
                'Manage medicine catalog with categories and brands',
                'Stock in/out with batch tracking and expiry dates',
                'Low stock and expiry alerts on dashboard',
                'Create purchase orders and track supplier deliveries',
            ]],
            ['icon' => 'fa-flask', 'color' => 'red', 'title' => 'Laboratory', 'items' => [
                'Define investigations with parameters and reference ranges',
                'Order tests from visit workflow or standalone',
                'Collect samples and track through to results',
                'Print lab reports with normal/abnormal flagging',
            ]],
            ['icon' => 'fa-bed', 'color' => 'teal', 'title' => 'IPD & Ward Management', 'items' => [
                'Manage wards and beds with real-time availability',
                'Admit patients with bed selection from visual grid',
                'Track daily vitals for admitted patients',
                'Discharge with summary and automatic bed release',
            ]],
            ['icon' => 'fa-shield-alt', 'color' => 'pink', 'title' => 'Users & Permissions', 'items' => [
                'Create roles: Admin, Doctor, Nurse, Receptionist, Lab Tech',
                'Assign granular permissions per role',
                'Doctors see only their assigned patients',
                'Audit log tracks all critical actions',
            ]],
            ['icon' => 'fa-chart-bar', 'color' => 'cyan', 'title' => 'Reports', 'items' => [
                'Patient visit reports with date and doctor filters',
                'Revenue and outstanding payment analysis',
                'Medicine sales and inventory status reports',
                'Doctor performance and department analytics',
            ]],
        ];
    @endphp

    <h2 class="text-xl font-bold text-gray-900 mb-6">Module Guides</h2>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        @foreach($modules as $mod)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-{{ $mod['color'] }}-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas {{ $mod['icon'] }} text-{{ $mod['color'] }}-500"></i>
                </div>
                <h3 class="ml-3 font-semibold text-gray-900">{{ $mod['title'] }}</h3>
            </div>
            <ul class="space-y-2">
                @foreach($mod['items'] as $item)
                <li class="flex items-start text-sm text-gray-600">
                    <i class="fas fa-check text-green-500 mt-1 mr-2 text-xs flex-shrink-0"></i>
                    {{ $item }}
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>

    {{-- FAQ --}}
    <h2 class="text-xl font-bold text-gray-900 mb-6">Frequently Asked Questions</h2>
    <div class="space-y-3 mb-12">
        @php
            $faqs = [
                ['q' => 'How do I add a new doctor?', 'a' => 'Go to Doctors → Add Doctor. Fill in the name, specialization, qualification, and contact details. A user account is automatically created for the doctor with the "Doctor" role.'],
                ['q' => 'Can multiple users work at the same time?', 'a' => 'Yes. Hospityo is a multi-user system. Receptionists, doctors, lab technicians, and pharmacists can all work simultaneously on different modules.'],
                ['q' => 'How do I print a prescription?', 'a' => 'Open the visit workflow, go to the Prescription tab, and click "Print Report" in the top right. The prescription opens in a print-optimized A4 layout.'],
                ['q' => 'Is my data safe?', 'a' => 'Each hospital gets a completely isolated database. Data is encrypted in transit with 256-bit SSL. Passwords are hashed and never stored in plain text. Daily automated backups are performed.'],
                ['q' => 'Can I use this on my phone?', 'a' => 'Yes. The interface is fully responsive and works on tablets and smartphones. All features are accessible from any modern browser.'],
                ['q' => 'How do I upgrade my plan?', 'a' => 'Contact our sales team or visit the billing section in your admin dashboard. Plan changes take effect at the start of the next billing cycle.'],
                ['q' => 'What happens if I cancel my subscription?', 'a' => 'Your data is retained for 90 days after cancellation. During this period you can reactivate or request a data export. After 90 days, data may be permanently deleted.'],
            ];
        @endphp
        @foreach($faqs as $faq)
        <details class="bg-white rounded-xl border border-gray-100 shadow-sm group">
            <summary class="flex items-center justify-between p-5 cursor-pointer text-sm font-medium text-gray-900 hover:text-medical-blue transition-colors">
                {{ $faq['q'] }}
                <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition-transform text-xs ml-4 flex-shrink-0"></i>
            </summary>
            <div class="px-5 pb-5 text-sm text-gray-600 leading-relaxed border-t border-gray-50 pt-3">
                {{ $faq['a'] }}
            </div>
        </details>
        @endforeach
    </div>

    {{-- Still need help --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
        <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-headset text-medical-blue text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Still need help?</h3>
        <p class="text-sm text-gray-600 mb-6">Our support team is ready to assist you.</p>
        <a href="{{ route('contact') }}" class="inline-flex items-center px-6 py-2.5 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
            <i class="fas fa-envelope mr-2"></i>Contact Support
        </a>
    </div>
</main>

<footer class="py-8 text-center text-sm text-gray-500">
    &copy; {{ date('Y') }} Hospityo. All rights reserved.
</footer>

</body>
</html>
