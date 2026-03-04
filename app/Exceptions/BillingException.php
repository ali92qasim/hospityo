<?php

namespace App\Exceptions;

use Exception;

class BillingException extends Exception
{
    /**
     * Bill not found exception
     */
    public static function notFound($id)
    {
        return new static("Bill with ID {$id} not found.");
    }

    /**
     * Bill already paid exception
     */
    public static function alreadyPaid($bill)
    {
        return new static(
            "Bill {$bill->bill_no} is already fully paid. " .
            "No further payments can be added."
        );
    }

    /**
     * Payment exceeds bill amount exception
     */
    public static function paymentExceedsAmount($bill, $payment, $remaining)
    {
        return new static(
            "Payment amount ₨{$payment} exceeds remaining bill amount ₨{$remaining} " .
            "for bill {$bill->bill_no}."
        );
    }

    /**
     * Invalid payment method exception
     */
    public static function invalidPaymentMethod($method)
    {
        return new static("Invalid payment method: {$method}");
    }

    /**
     * Cannot delete bill with payments exception
     */
    public static function hasPayments($bill)
    {
        return new static(
            "Cannot delete bill {$bill->bill_no} as it has payment records. " .
            "Please void the payments first."
        );
    }
}
