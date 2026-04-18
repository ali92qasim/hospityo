@extends('super-admin.layout')

@section('title', 'Configure ' . $paymentGateway->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center border border-gray-100 mr-3">
                        @if($paymentGateway->logo)
                            <img src="{{ $paymentGateway->logo }}" alt="{{ $paymentGateway->name }}" class="w-7 h-7 object-contain">
                        @else
                            <i class="fas fa-credit-card text-gray-400"></i>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $paymentGateway->name }}</h3>
                        <p class="text-xs text-gray-500">{{ $paymentGateway->description }}</p>
                    </div>
                </div>
                <a href="{{ route('super-admin.payment-gateways.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i>Back
                </a>
            </div>
        </div>

        <form action="{{ route('super-admin.payment-gateways.update', $paymentGateway) }}" method="POST" class="p-6">
            @csrf @method('PUT')

            @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
            @endif

            @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
            @endif

            {{-- Enable / Mode --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_enabled" value="1" {{ $paymentGateway->is_enabled ? 'checked' : '' }}
                               class="h-5 w-5 text-green-500 border-gray-300 rounded focus:ring-green-500">
                        <span class="ml-3 text-sm text-gray-700">Enable this gateway</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mode</label>
                    <div class="flex gap-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="mode" value="sandbox" {{ $paymentGateway->mode === 'sandbox' ? 'checked' : '' }}
                                   class="h-4 w-4 text-yellow-500 border-gray-300 focus:ring-yellow-500">
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="fas fa-flask text-yellow-500 mr-1"></i>Sandbox
                            </span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="mode" value="live" {{ $paymentGateway->mode === 'live' ? 'checked' : '' }}
                                   class="h-4 w-4 text-green-500 border-gray-300 focus:ring-green-500">
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="fas fa-check-circle text-green-500 mr-1"></i>Live
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Dynamic Credential Fields --}}
            <div class="border-t border-gray-200 pt-6">
                <h4 class="text-sm font-semibold text-gray-800 mb-4">
                    <i class="fas fa-key text-gray-400 mr-2"></i>API Credentials
                </h4>
                <div class="space-y-5">
                    @foreach($paymentGateway->config_fields as $field)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            {{ $field['label'] }}
                            @if($field['required'] ?? false)<span class="text-red-400">*</span>@endif
                        </label>
                        @if(($field['type'] ?? 'text') === 'url')
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-link text-gray-400 text-xs"></i>
                                </div>
                                <input type="url" name="credentials[{{ $field['key'] }}]"
                                       value="{{ $paymentGateway->getCredential($field['key']) }}"
                                       placeholder="{{ $field['placeholder'] ?? '' }}"
                                       class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">
                            </div>
                        @elseif(($field['type'] ?? 'text') === 'password')
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400 text-xs"></i>
                                </div>
                                <input type="password" name="credentials[{{ $field['key'] }}]"
                                       value=""
                                       placeholder="{{ $paymentGateway->getCredential($field['key']) ? '••••••••••••' : ($field['placeholder'] ?? '') }}"
                                       class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Leave blank to keep current value</p>
                        @elseif(($field['type'] ?? 'text') === 'select')
                            <select name="credentials[{{ $field['key'] }}]"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">
                                @foreach($field['options'] ?? [] as $optVal => $optLabel)
                                    <option value="{{ $optVal }}" {{ $paymentGateway->getCredential($field['key']) === $optVal ? 'selected' : '' }}>{{ $optLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="credentials[{{ $field['key'] }}]"
                                   value="{{ $paymentGateway->getCredential($field['key']) }}"
                                   placeholder="{{ $field['placeholder'] ?? '' }}"
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">
                        @endif
                        @if($field['hint'] ?? false)
                            <p class="text-xs text-gray-400 mt-1">{{ $field['hint'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('super-admin.payment-gateways.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm mr-3">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                    <i class="fas fa-save mr-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
