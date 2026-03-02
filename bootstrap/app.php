<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VerifyDeviceApiKey;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware
        $middleware->use([
            \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        ]);
        //add 25-12-25
        //'role' => \App\Http\Middleware\RoleMiddleware::class,
        $middleware->alias([
            //'role' => RoleMiddleware::class,
            'role' => RoleMiddleware::class,
            'device.key' => VerifyDeviceApiKey::class,
        ]);
        // This tells Laravel to trust the headers sent by your Proxy Manager
        $middleware->trustProxies(at: '*'); 

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
