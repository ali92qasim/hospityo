<?php

it('exposes npm run check-build against the freshness script', function () {
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, 512, JSON_THROW_ON_ERROR);
    $script = (string) file_get_contents(base_path('scripts/check-vite-build-freshness.mjs'));

    expect($package['scripts']['check-build'] ?? null)->toBe('node scripts/check-vite-build-freshness.mjs')
        ->and(is_file(base_path('scripts/check-vite-build-freshness.mjs')))->toBeTrue()
        ->and($script)->toContain("spawnSync('npx', ['vite', 'build']")
        ->and($script)->toContain("git', ['ls-files', '--error-unmatch'");
});

it('exposes composer check-build as an alias of the npm script', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($composer['scripts']['check-build'] ?? null)->toBe('npm run check-build');
});
