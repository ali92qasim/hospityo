<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — Hospityo</title>
    @vite(['resources/css/app.css'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

<nav class="bg-white border-b border-gray-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ url('/') }}" class="flex items-center space-x-2">
                <div class="h-9 w-9 bg-medical-blue rounded-lg flex items-center justify-center">
                    <i class="fas fa-hospital text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-gray-900">Hospityo</span>
            </a>
            <a href="{{ url('/') }}" class="text-sm text-gray-600 hover:text-medical-blue"><i class="fas fa-arrow-left mr-1"></i>Back to Home</a>
        </div>
    </div>
</nav>

<main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-900">Get in Touch</h1>
        <p class="mt-2 text-gray-600">Have a question or need help? We'd love to hear from you.</p>
    </div>

    @if(session('success'))
    <div class="mb-8 bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-lg text-center">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        {{-- Contact Info Cards --}}
        <div class="lg:col-span-2 space-y-4">
            @if($site['office_address'] ?? false)
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start">
                    <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-map-marker-alt text-medical-blue"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-900">Office Address</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $site['office_address'] }}@if($site['office_city'] ?? false), {{ $site['office_city'] }}@endif @if($site['office_country'] ?? false), {{ $site['office_country'] }}@endif</p>
                    </div>
                </div>
            </div>
            @endif

            @if($site['office_phone'] ?? false)
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start">
                    <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-phone-alt text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-900">Phone</h3>
                        <a href="tel:{{ $site['office_phone'] }}" class="text-sm text-medical-blue hover:underline mt-1 block">{{ $site['office_phone'] }}</a>
                    </div>
                </div>
            </div>
            @endif

            @if($site['office_email'] ?? false)
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start">
                    <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-envelope text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-900">Email</h3>
                        <a href="mailto:{{ $site['office_email'] }}" class="text-sm text-medical-blue hover:underline mt-1 block">{{ $site['office_email'] }}</a>
                    </div>
                </div>
            </div>
            @endif

            @if($site['office_hours'] ?? false)
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start">
                    <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clock text-orange-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-900">Office Hours</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $site['office_hours'] }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if($site['whatsapp_number'] ?? false)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $site['whatsapp_number']) }}" target="_blank"
               class="flex items-center justify-center bg-green-500 text-white rounded-xl p-4 shadow-sm hover:bg-green-600 transition-colors">
                <i class="fab fa-whatsapp text-xl mr-3"></i>
                <span class="text-sm font-medium">Chat on WhatsApp</span>
            </a>
            @endif
        </div>

        {{-- Contact Form --}}
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Send us a message</h2>
                <form action="{{ route('contact.submit') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm">
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm">
                            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                        <select name="subject" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm">
                            <option value="">Select a topic</option>
                            <option value="General Inquiry" {{ old('subject') == 'General Inquiry' ? 'selected' : '' }}>General Inquiry</option>
                            <option value="Sales & Pricing" {{ old('subject') == 'Sales & Pricing' ? 'selected' : '' }}>Sales & Pricing</option>
                            <option value="Technical Support" {{ old('subject') == 'Technical Support' ? 'selected' : '' }}>Technical Support</option>
                            <option value="Billing Issue" {{ old('subject') == 'Billing Issue' ? 'selected' : '' }}>Billing Issue</option>
                            <option value="Feature Request" {{ old('subject') == 'Feature Request' ? 'selected' : '' }}>Feature Request</option>
                            <option value="Partnership" {{ old('subject') == 'Partnership' ? 'selected' : '' }}>Partnership</option>
                        </select>
                        @error('subject')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone (Optional)</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                        <textarea name="message" rows="5" required
                                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm">{{ old('message') }}</textarea>
                        @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full bg-medical-blue text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                        <i class="fas fa-paper-plane mr-2"></i>Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<footer class="py-8 text-center text-sm text-gray-500">
    &copy; {{ date('Y') }} Hospityo. All rights reserved.
</footer>

</body>
</html>
