<?php

it('contains no legacy visit spine column reads in app and admin views', function () {
    $legacyColumns = [
        'bed_no',
        'room_no',
        'chief_complaint',
        'diagnosis',
        'treatment',
        'notes',
        'total_charges',
        'priority',
        'discharge_datetime',
    ];

    $allowlisted = [];

    $scanRoots = [
        base_path('app'),
        base_path('resources/views/admin/visits'),
        base_path('resources/views/admin/patients'),
    ];

    $violations = [];

    foreach ($scanRoots as $root) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            if (! str_ends_with($path, '.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($path, strlen(base_path()) + 1));

            if (in_array($relative, $allowlisted, true)) {
                continue;
            }

            $content = file_get_contents($path);

            foreach ($legacyColumns as $column) {
                if (preg_match('/\$visit->'.$column.'\b/', $content)) {
                    $violations[] = "{$relative} reads \$visit->{$column}";
                }

                if (preg_match('/\$this->'.$column.'\b/', $content)) {
                    $violations[] = "{$relative} reads \$this->{$column}";
                }
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});
