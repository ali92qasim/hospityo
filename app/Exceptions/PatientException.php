<?php

namespace App\Exceptions;

use Exception;

class PatientException extends Exception
{
    /**
     * Patient not found exception
     */
    public static function notFound($id)
    {
        return new static("Patient with ID {$id} not found.");
    }

    /**
     * Patient has active visits exception
     */
    public static function hasActiveVisits($patient)
    {
        return new static(
            "Cannot delete patient {$patient->name} ({$patient->patient_no}) " .
            "as they have active visits."
        );
    }

    /**
     * Patient has outstanding bills exception
     */
    public static function hasOutstandingBills($patient, $amount)
    {
        return new static(
            "Patient {$patient->name} has outstanding bills totaling ₨{$amount}. " .
            "Please clear all dues before proceeding."
        );
    }

    /**
     * Invalid patient data exception
     */
    public static function invalidData($field, $message)
    {
        return new static("Invalid {$field}: {$message}");
    }
}
