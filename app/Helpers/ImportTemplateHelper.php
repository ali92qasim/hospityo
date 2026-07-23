<?php

if (! function_exists('import_template_url')) {
    /**
     * Resolve the public URL for an import template by base name.
     * Prefers CSV, then XLSX, then XLS — whichever file exists first.
     */
    function import_template_url(string $basename): ?string
    {
        $directory = public_path('templates');

        foreach (['csv', 'xlsx', 'xls'] as $extension) {
            $filename = "{$basename}.{$extension}";

            if (is_file("{$directory}/{$filename}")) {
                return asset("templates/{$filename}");
            }
        }

        return null;
    }
}
