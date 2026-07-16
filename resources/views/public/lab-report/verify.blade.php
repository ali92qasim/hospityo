<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Lab Report — {{ $settings['hospital_name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6">
            <div class="text-center">
                @if($settings['hospital_logo'])
                    <img
                        src="{{ asset('storage/' . $settings['hospital_logo']) }}"
                        alt="{{ $settings['hospital_name'] }}"
                        class="mx-auto h-16 w-16 object-contain mb-3"
                    >
                @else
                    <div class="mx-auto h-16 w-16 rounded-full bg-white shadow flex items-center justify-center mb-3">
                        <i class="fas fa-hospital text-medical-blue text-2xl"></i>
                    </div>
                @endif
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $settings['hospital_name'] }}</h1>
                @if($settings['hospital_address'])
                    <p class="mt-1 text-sm text-gray-600">{{ $settings['hospital_address'] }}</p>
                @endif
                @if($settings['hospital_phone'] || $settings['hospital_email'])
                    <p class="mt-1 text-xs text-gray-500">
                        @if($settings['hospital_phone']){{ $settings['hospital_phone'] }}@endif
                        @if($settings['hospital_phone'] && $settings['hospital_email']) · @endif
                        @if($settings['hospital_email']){{ $settings['hospital_email'] }}@endif
                    </p>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800">Laboratory Report Access</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Enter your Patient Number and Mobile Number to view or download your report.
                    </p>
                </div>

                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('lab-report.verify', $order->share_token) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="patient_no" class="block text-sm font-medium text-gray-700 mb-2">Patient Number</label>
                        <input
                            type="text"
                            id="patient_no"
                            name="patient_no"
                            value="{{ old('patient_no') }}"
                            placeholder="e.g. P000123"
                            autocomplete="off"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Mobile Number</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="e.g. 03001234567"
                            autocomplete="tel"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-medical-blue text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        <i class="fas fa-file-medical mr-2"></i>View Report
                    </button>
                </form>

                <p class="mt-5 text-center text-xs text-gray-500">
                    Order reference: <span class="font-medium text-gray-700">{{ $order->order_number }}</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
