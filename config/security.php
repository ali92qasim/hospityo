<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Session idle timeout (HIPAA automatic logoff)
    |--------------------------------------------------------------------------
    |
    | Predetermined inactivity period before an authenticated session is
    | terminated (45 CFR § 164.312(a)(2)(iii)). Tenants may override via the
    | session_idle_timeout_minutes setting within the min/max band below.
    |
    */

    'idle_timeout_minutes' => (int) env('SESSION_IDLE_TIMEOUT_MINUTES', 30),

    'idle_timeout_min' => 5,

    'idle_timeout_max' => 60,

    'idle_warning_seconds' => (int) env('SESSION_IDLE_WARNING_SECONDS', 60),

];
