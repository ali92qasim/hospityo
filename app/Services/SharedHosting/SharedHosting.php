<?php

namespace App\Services\SharedHosting;

class SharedHosting
{
    /**
     * Whether tenant DBs should be created via the cPanel API.
     * Always false for local and testing, even if SHARED_HOSTING is set.
     */
    public static function enabled(): bool
    {
        if (! filter_var(config('shared_hosting.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        return ! app()->environment(['local', 'testing']);
    }
}
