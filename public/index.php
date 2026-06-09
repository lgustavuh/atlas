<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Manutenção (cria/remove arquivo via php artisan down/up)
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader do Composer
require __DIR__ . '/../vendor/autoload.php';

// Inicia o Laravel
(require_once __DIR__ . '/../bootstrap/app.php')
    ->handleRequest(Request::capture());
