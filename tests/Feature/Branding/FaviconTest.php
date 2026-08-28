<?php

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
