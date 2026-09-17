<?php

it('does not re-enhance file inputs that filepond creates inside its root', function () {
    $js = file_get_contents(dirname(__DIR__, 2).'/resources/js/file-input.js');

    expect($js)->toMatch("/closest\\(['\"]\\.filepond--root['\"]\\)/");
});
