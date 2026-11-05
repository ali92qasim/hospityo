<?php

uses(Tests\TestCase::class);

it('supports json registration responses for in-page fetch submits', function () {
    $source = file_get_contents(app_path('Http/Controllers/TenantRegistrationController.php'));

    expect($source)
        ->toContain('expectsJson()')
        ->toContain("'redirect'")
        ->toContain('async: false');
});
