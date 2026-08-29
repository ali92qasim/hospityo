@extends('admin.layout')

@section('title', 'Settings')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Settings</h1>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Settings sections">
        @foreach($settingsTabs as $section)
            <a href="{{ route($section['route']) }}"
               class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium {{ request()->routeIs($section['route']) ? 'border-medical-blue text-medical-blue' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                <i class="fas {{ $section['icon'] }} mr-2"></i>{{ $section['label'] }}
            </a>
        @endforeach
    </nav>
</div>

@yield('settings-section')
@endsection
