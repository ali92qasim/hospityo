<?php

use App\Support\AppVersion;

it('reads the VERSION file as config app.version', function () {
    expect(config('app.version'))->toBe(AppVersion::read())
        ->and(AppVersion::read())->toMatch('/^\d+\.\d+\.\d+$/');
});

it('writes a new semver into the VERSION file', function () {
    $path = AppVersion::path();
    $backup = is_file($path) ? file_get_contents($path) : null;

    try {
        AppVersion::write('1.2.3');
        expect(trim((string) file_get_contents($path)))->toBe('1.2.3');
    } finally {
        if ($backup === null) {
            @unlink($path);
        } else {
            file_put_contents($path, $backup);
        }
    }
});

it('falls back to 0.0.0 when VERSION does not exist', function () {
    $path = AppVersion::path();
    $backup = is_file($path) ? file_get_contents($path) : null;

    try {
        if (is_file($path)) {
            unlink($path);
        }

        expect(AppVersion::read())->toBe('0.0.0');
    } finally {
        if ($backup === null) {
            @unlink($path);
        } else {
            file_put_contents($path, $backup);
        }
    }
});

it('writes a trailing newline after the semver', function () {
    $path = AppVersion::path();
    $backup = is_file($path) ? file_get_contents($path) : null;

    try {
        AppVersion::write('1.2.3');
        expect(file_get_contents($path))->toBe("1.2.3\n");
    } finally {
        if ($backup === null) {
            @unlink($path);
        } else {
            file_put_contents($path, $backup);
        }
    }
});
