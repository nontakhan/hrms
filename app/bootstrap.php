<?php

declare(strict_types=1);

$appConfig = require __DIR__ . '/../config/app.php';
$dbConfig = require __DIR__ . '/../config/database.php';

date_default_timezone_set($appConfig['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_name($appConfig['session_name']);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

function app_config(?string $key = null, mixed $default = null): mixed
{
    global $appConfig;

    if ($key === null) {
        return $appConfig;
    }

    return $appConfig[$key] ?? $default;
}

function db_config(?string $key = null, mixed $default = null): mixed
{
    global $dbConfig;

    if ($key === null) {
        return $dbConfig;
    }

    return $dbConfig[$key] ?? $default;
}
