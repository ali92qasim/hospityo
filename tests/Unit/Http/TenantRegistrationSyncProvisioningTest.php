<?php

uses(Tests\TestCase::class);

it('registers clinics with synchronous provisioning so shared hosting can finish setup', function () {
    $source = file_get_contents(app_path('Http/Controllers/TenantRegistrationController.php'));

    expect($source)
        ->toContain('async: false')
        ->not->toContain('async: true');
});
