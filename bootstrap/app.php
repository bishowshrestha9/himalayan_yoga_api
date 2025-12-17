<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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
            \App\Http\Middleware\SecurityHeaders::class, // Add security headers to all responses
        ]);
        
        // Register middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminRoleCheckMiddleware::class,
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle 404 Not Found exceptions
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'The requested resource could not be found.',
                ], 404);
            }
        });

        // Handle 405 Method Not Allowed exceptions
        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'The HTTP method is not supported for this route.',
                ], 405);
            }
        });

        // Handle all other exceptions - hide details in production
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                // Only show detailed errors in local/development environment
                if (config('app.debug')) {
                    return response()->json([
                        'status' => false,
                        'message' => 'An error occurred',
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace(),
                    ], 500);
                }

                // In production, return generic error message
                return response()->json([
                    'status' => false,
                    'message' => 'An unexpected error occurred. Please try again later.',
                ], 500);
            }
        });
    })->create();
