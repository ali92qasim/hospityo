@extends('super-admin.layout')

@section('title', 'Payment Gateways')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">Payment Gateways</h3>
        <p class="text-sm text-gray-500 mt-1">Configure payment providers for subscription billing.</p>
    </div>

    <div class="space-y-4">
        @foreach($gateways as $gateway)
        <div class="bg-white rounded-xl shadow-sm border {{ $gateway->is_enabled ? 'border-green-200' : 'border-gray-200' }}">
            <div class="p-5 flex items-center justify-between">
                <div class="flex items-center min-w-0">
                    <div class="w-12 h-12 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0 border border-gray-100">
                        @if($gateway->logo)
                            <img src="{{ $gateway->logo }}" alt="{{ $gateway->name }}" class="w-8 h-8 object-contain">
                        @else
                            <i class="fas fa-credit-card text-gray-400 text-lg"></i>
                        @endif
                    </div>
                    <div class="ml-4 min-w-0">
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-semibold text-gray-900">{{ $gateway->name }}</h4>
                            @if($gateway->is_enabled)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700 font-medium">Active</span>
                                <span class="px-2 py-0.5 text-xs rounded-full {{ $gateway->mode === 'live' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700' }} font-medium">
                                    {{ ucfirst($gateway->mode) }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-500 font-medium">Disabled</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $gateway->description }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                    <form action="{{ route('super-admin.payment-gateways.toggle', $gateway) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $gateway->is_enabled ? 'bg-green-500' : 'bg-gray-300' }}" title="{{ $gateway->is_enabled ? 'Disable' : 'Enable' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow {{ $gateway->is_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </form>
                    <a href="{{ route('super-admin.payment-gateways.edit', $gateway) }}" class="px-3 py-1.5 text-xs font-medium text-medical-blue border border-medical-blue rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-cog mr-1"></i>Configure
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($gateways->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">No payment gateways configured. Run the seeder to add default gateways.</p>
    </div>
    @endif
</div>
@endsection
