<?php

/*
| Worktree tests use a shared vendor junction; pin Laravel's base path to this
| project so routes and config load from the worktree, not the main tree.
*/
$basePath = dirname(__DIR__);
$_ENV['APP_BASE_PATH'] = $basePath;
$_SERVER['APP_BASE_PATH'] = $basePath;

require $basePath.'/vendor/autoload.php';
