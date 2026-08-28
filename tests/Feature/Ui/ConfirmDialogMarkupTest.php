<?php

function confirmDialogSourceFiles(): array
{
    $files = [];

    foreach ([resource_path('views'), resource_path('js')] as $root) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = $file->getExtension();
            if (! in_array($extension, ['php', 'js'], true)) {
                continue;
            }

            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function nativeDialogMatches(string $contents): array
{
    preg_match_all('/(?<![\w$])(?:window\.)?(?:confirm|alert)\s*\(/', $contents, $matches, PREG_OFFSET_CAPTURE);

    return $matches[0] ?? [];
}

it('provides a shared confirmation dialog partial', function () {
    $path = resource_path('views/partials/confirm-dialog.blade.php');

    expect(is_file($path))->toBeTrue();

    $contents = file_get_contents($path);

    expect($contents)
        ->toContain('id="confirm-dialog"')
        ->toContain('data-confirm-accept')
        ->toContain('data-confirm-cancel');
});

it('includes the confirmation dialog in every full-page layout that loads app.js', function () {
    $missing = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'))
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if (! str_contains($contents, '<!DOCTYPE html>')) {
            continue;
        }

        if (! str_contains($contents, "resources/js/app.js")) {
            continue;
        }

        if (! str_contains($contents, "@include('partials.confirm-dialog')")) {
            $missing[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    expect($missing)->toBeEmpty('Missing @include(\'partials.confirm-dialog\') in: '.implode(', ', $missing));
});

it('does not use native browser confirm or alert dialogs in app views and javascript', function () {
    $offenders = [];

    foreach (confirmDialogSourceFiles() as $path) {
        $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
        $contents = file_get_contents($path);
        $matches = nativeDialogMatches($contents);

        if ($matches === []) {
            continue;
        }

        $offenders[] = $relative.' ('.count($matches).')';
    }

    expect($offenders)->toBeEmpty('Native confirm/alert still present in: '.implode(', ', $offenders));
});
