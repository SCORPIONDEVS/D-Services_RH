<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Pasta do projeto Laravel (dentro de htdocs/dservice)
$base = __DIR__ . '/dservice';

if (file_exists($maintenance = $base . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $base . '/vendor/autoload.php';

$app = require_once $base . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
