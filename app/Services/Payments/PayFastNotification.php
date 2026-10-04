<?php

namespace App\Services\Payments;

use App\Exceptions\InvalidPaymentNotification;

/**
 * Verifies a PayFast (Pakistan) return / IPN notification.
 *
 * Per PayFast Merchant Integration Guide v2.3 §3.2.3, PayFast signs every
 * SUCCESS_URL, FAILURE_URL and CHECKOUT_URL notification with
 *   validation_hash = SHA256("basket_id|secured_key|merchant_id|err_code")
 * and err_code "000" means the transaction was approved.
 */
final class PayFastNotification
{
    public const APPROVED_CODE = '000';

    private function __construct(
        public readonly string $basketId,
        public readonly string $errCode,
        public readonly ?string $transactionId,
        public readonly array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidPaymentNotification when the notification is not signed by PayFast
     */
    public static function verify(array $params): self
    {
        $normalized = array_change_key_case($params, CASE_LOWER);

        $basketId = (string) ($normalized['basket_id'] ?? '');
        $errCode = (string) ($normalized['err_code'] ?? '');
        $hash = strtolower((string) ($normalized['validation_hash'] ?? ''));

        if ($basketId === '' || $errCode === '' || $hash === '') {
            throw new InvalidPaymentNotification('PayFast notification is missing basket_id, err_code or validation_hash.');
        }

        $merchantId = (string) config('payfast.merchant_id');
        $securedKey = (string) config('payfast.secured_key');

        if ($merchantId === '' || $securedKey === '') {
            throw new InvalidPaymentNotification('PayFast merchant credentials are not configured.');
        }

        $expected = hash('sha256', implode('|', [$basketId, $securedKey, $merchantId, $errCode]));

        if (! hash_equals($expected, $hash)) {
            throw new InvalidPaymentNotification('PayFast validation_hash does not match.');
        }

        return new self($basketId, $errCode, $normalized['transaction_id'] ?? null, $params);
    }

    public function approved(): bool
    {
        return $this->errCode === self::APPROVED_CODE;
    }
}
