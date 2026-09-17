<?php

it('gives filepond a usable width so it cannot collapse inside flex layouts', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/file-input.css');

    expect($css)
        ->toContain('.filepond--root')
        ->and($css)->toContain('width: 100%')
        ->and($css)->toMatch('/min-width:\s*[1-9]/');
});

it('resets native input chrome that filepond copies onto its root', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/file-input.css');

    expect($css)
        ->toMatch('/\.filepond--root\s*\{[^}]*padding:\s*0/s')
        ->and($css)->toMatch('/\.filepond--root\s*\{[^}]*border(?:-width)?:\s*0/s');
});
