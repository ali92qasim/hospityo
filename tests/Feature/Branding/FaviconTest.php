<?php

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);
});

test('favicon svg ico and apple touch icon exist and are not empty', function () {
    foreach (['favicon.svg', 'favicon.ico', 'apple-touch-icon.png'] as $file) {
        $path = public_path($file);
        expect(is_file($path))->toBeTrue("Missing public/{$file}");
        expect(filesize($path))->toBeGreaterThan(0, "Empty public/{$file}");
    }
});

test('favicon svg uses the logo color scheme', function () {
    $svg = file_get_contents(public_path('favicon.svg'));

    expect($svg)->toContain('#0066CC')
        ->and($svg)->toContain('#00A86B')
        ->and($svg)->toContain('#F0F8FF');
});

function assertFaviconLinks($response): void
{
    $response->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('rel="icon"')
        ->and($html)->toContain('favicon.svg')
        ->and($html)->toContain('favicon.ico')
        ->and($html)->toContain('rel="apple-touch-icon"')
        ->and($html)->toContain('apple-touch-icon.png');
}

test('landing page html includes favicon link tags', function () {
    assertFaviconLinks($this->get('/'));
});

test('sign in page html includes favicon link tags', function () {
    assertFaviconLinks($this->get('/signin'));
});

test('not found page html includes favicon link tags', function () {
    $response = $this->get('/this-route-does-not-exist-favicon');

    $response->assertNotFound();
    $html = $response->getContent();

    expect($html)->toContain('rel="icon"')
        ->and($html)->toContain('favicon.svg')
        ->and($html)->toContain('favicon.ico')
        ->and($html)->toContain('rel="apple-touch-icon"')
        ->and($html)->toContain('apple-touch-icon.png');
});

test('every full html blade document includes the favicon partial', function () {
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
        if (! str_contains($contents, "@include('partials.favicon')")) {
            $missing[] = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    expect($missing)->toBeEmpty('Missing @include(\'partials.favicon\') in: '.implode(', ', $missing));
});
