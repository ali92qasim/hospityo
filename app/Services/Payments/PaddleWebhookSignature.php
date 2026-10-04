<?php

namespace App\Services\Payments;

/**
 * Verifies the Paddle Billing `Paddle-Signature` header.
 *
 * Header format: "ts=<unix>;h1=<hex>" (several h1 values may appear during
 * secret rotation). h1 = HMAC-SHA256("<ts>:<raw body>", webhook secret).
 * The raw body must be verified exactly as received.
 */
final class PaddleWebhookSignature
{
    /** Reject notifications older (or further in the future) than this, to stop replays. */
    public const TOLERANCE_SECONDS = 300;

    public static function isValid(string $rawBody, ?string $header, string $secret, ?int $now = null): bool
    {
        if ($secret === '' || $header === null || $header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(';', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 'ts') {
                $timestamp = $value;
            } elseif ($key === 'h1' && $value !== '') {
                $signatures[] = strtolower($value);
            }
        }

        if ($timestamp === null || ! ctype_digit($timestamp) || $signatures === []) {
            return false;
        }

        if (abs(($now ?? time()) - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.':'.$rawBody, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
