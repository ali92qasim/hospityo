<?php

function bladeViteAssets(): Illuminate\Support\Collection
{
    $referenced = collect();

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        preg_match_all('/@vite\(\[([^\]]+)\]\)/', file_get_contents($file->getPathname()), $viteCalls);

        foreach ($viteCalls[1] as $list) {
            preg_match_all("/'([^']+)'/", $list, $assets);

            foreach ($assets[1] as $asset) {
                $referenced->push($asset);
            }
        }
    }

    return $referenced->unique()->values();
}

it('registers every blade vite asset as a vite config input', function () {
    $config = file_get_contents(base_path('vite.config.js'));

    preg_match_all("/'resources\/(?:css|js)\/[^']+'/", $config, $configMatches);

    $inputs = collect($configMatches[0])
        ->map(fn (string $quoted) => trim($quoted, "'"))
        ->unique()
        ->values();

    expect(bladeViteAssets()->diff($inputs)->values()->all())->toBe([]);
});

it('includes every blade vite asset in the built manifest when one exists', function () {
    $manifestPath = public_path('build/manifest.json');

    if (! is_file($manifestPath)) {
        $this->markTestSkipped('Vite production manifest is not present.');
    }

    $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

    expect(
        bladeViteAssets()
            ->reject(fn (string $asset) => array_key_exists($asset, $manifest))
            ->values()
            ->all()
    )->toBe([]);
});
