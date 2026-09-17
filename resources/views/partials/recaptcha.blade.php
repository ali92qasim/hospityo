@php
    $recaptchaSiteKey = config('services.recaptcha.site_key');
@endphp
@if($recaptchaSiteKey)
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <div class="g-recaptcha mb-4" data-sitekey="{{ $recaptchaSiteKey }}"></div>
    @error('g-recaptcha-response')
        <p class="text-red-500 text-xs mt-1 mb-4">{{ $message }}</p>
    @enderror
@endif
