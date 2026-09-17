<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Please confirm you are not a robot.');

            return;
        }

        $secret = config('services.recaptcha.secret_key');

        if (! is_string($secret) || $secret === '') {
            $fail('Captcha is not configured.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (\Throwable) {
            $fail('Captcha verification failed. Please try again.');

            return;
        }

        if (! $response->successful() || $response->json('success') !== true) {
            $fail('Please confirm you are not a robot.');
        }
    }
}
