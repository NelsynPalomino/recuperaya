<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Apache en Windows atiende varias peticiones en hilos del mismo proceso y putenv() no es seguro entre hilos:
// bajo carga algunas peticiones no veían el .env (DEF-08, prueba de estrés). Las variables se leen solo de $_ENV/$_SERVER.
Illuminate\Support\Env::disablePutenv();

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
