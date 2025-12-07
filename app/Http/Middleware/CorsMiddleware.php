<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the origin from the request
        $origin = $request->header('Origin');
        
        // Default allowed origins (can be overridden via .env)
        $defaultOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:3002',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:3002',
            'http://209.126.86.149:3002',
            'http://localhost',
            'http://127.0.0.1',
        ];
        
        // Get allowed origins from environment or use defaults
        $envOrigins = env('CORS_ALLOWED_ORIGINS');
        $allowedOrigins = $envOrigins 
            ? array_map('trim', explode(',', $envOrigins))
            : $defaultOrigins;
        
        // Determine the allowed origin
        $allowedOrigin = '*';
        
        if ($origin) {
            // Check if the request origin is in the allowed list
            if (in_array($origin, $allowedOrigins)) {
                $allowedOrigin = $origin;
            } elseif ($allowedOrigins[0] !== '*') {
                // If specific origins are set and request origin not found, use first allowed
                $allowedOrigin = $allowedOrigins[0];
            }
        } elseif ($allowedOrigins[0] === '*') {
            $allowedOrigin = '*';
        }
        
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200)
                ->header('Access-Control-Allow-Origin', $allowedOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Access-Control-Expose-Headers', 'Content-Length, Content-Type');
            
            // Only set credentials if origin is specified and not wildcard
            if ($allowedOrigin !== '*') {
                $response->header('Access-Control-Allow-Credentials', 'true');
            }
            
            return $response;
        }

        $response = $next($request);

        // Add CORS headers to the response
        $corsResponse = $response
            ->header('Access-Control-Allow-Origin', $allowedOrigin)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
            ->header('Access-Control-Max-Age', '86400')
            ->header('Access-Control-Expose-Headers', 'Content-Length, Content-Type');
        
        // Only set credentials if origin is specified and not wildcard
        if ($allowedOrigin !== '*') {
            $corsResponse->header('Access-Control-Allow-Credentials', 'true');
        }
        
        return $corsResponse;
    }
}

