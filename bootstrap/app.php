<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add middleware for API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\EncryptCookies::class, // Custom encrypt cookies (excludes auth_token)
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, // Ensure cookies are added to response
            \App\Http\Middleware\ReadTokenFromCookie::class, // Read token from cookie before Sanctum
        ]);
        
        // Register middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminRoleCheckMiddleware::class,
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
