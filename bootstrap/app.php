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
        // Completely disable Symfony's error page for API routes
        $exceptions->dontReport([]);
        
        // Force JSON responses for API routes
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Handle all exceptions with priority order
        $exceptions->renderable(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'The HTTP method is not supported for this route.',
                ], 405);
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'The requested resource could not be found.',
                ], 404);
            }
        });

        // Catch-all for any other exception
        $exceptions->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                return response()->json([
                    'status' => false,
                    'message' => config('app.debug') 
                        ? $e->getMessage() 
                        : 'An unexpected error occurred. Please try again later.',
                ], $statusCode);
            }
        });
    })->create();
