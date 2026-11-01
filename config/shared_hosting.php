<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shared Hosting Mode
    |--------------------------------------------------------------------------
    |
    | When true (and APP_ENV is not local/testing), tenant MySQL databases are
    | created via the cPanel API instead of SQL CREATE DATABASE.
    | Set SHARED_HOSTING=false (or remove) when you leave shared hosting.
    |
    */

    'enabled' => env('SHARED_HOSTING', false),

    /*
    |--------------------------------------------------------------------------
    | cPanel API
    |--------------------------------------------------------------------------
    */

    'host' => env('CPANEL_HOST'),

    'user' => env('CPANEL_USER'),

    'api_token' => env('CPANEL_API_TOKEN'),

    'timeout' => (int) env('CPANEL_TIMEOUT', 30),

];
