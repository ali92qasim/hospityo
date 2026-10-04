<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A payment callback or webhook that cannot be proven to come from the gateway
 * (missing/invalid signature, unknown basket, wrong tenant). Never acted upon.
 */
class InvalidPaymentNotification extends RuntimeException
{
}
