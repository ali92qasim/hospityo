{{--
    Usage:
    @include('partials.logo')                   → sidebar (md, text-2xl)
    @include('partials.logo', ['size' => 'sm']) → navbars (text-xl)
    @include('partials.logo', ['size' => 'lg']) → auth / landing hero (text-3xl)
    @include('partials.logo', ['dark' => true]) → dark backgrounds
--}}
@php
    $size = $size ?? 'md';
    $dark = $dark ?? false;

    $wrapperClass = match($size) {
        'sm'    => 'text-xl',
        'lg'    => 'text-3xl',
        default => 'text-2xl',
    };

    $dotClass = match($size) {
        'sm'    => 'text-2xl',
        'lg'    => 'text-4xl',
        default => 'text-3xl',
    };
@endphp

<span class="inline-flex items-baseline font-semibold tracking-tight leading-none select-none {{ $wrapperClass }}">
    <span class="{{ $dark ? 'text-white' : 'text-medical-blue' }}">Use</span><span class="{{ $dark ? 'text-green-400' : 'text-medical-green' }}">Clinic</span><span class="{{ $dark ? 'text-white' : 'text-medical-blue' }}">Sync</span><span class="{{ $dark ? 'text-green-400' : 'text-medical-green' }} {{ $dotClass }}" style="line-height:0;position:relative;top:0.1em;margin-left:1px">·</span>
</span>
