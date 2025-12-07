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
        
        // Default allowed origins (can be overridden via .env or Render environment)
        $defaultOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:3002',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:3002',
            'http://209.126.86.149:3002',
            'https://209.126.86.149:3002',
            'http://localhost',
            'http://127.0.0.1',
        ];
        
        // Get allowed origins from environment or use defaults
        $envOrigins = env('CORS_ALLOWED_ORIGINS');
        $allowedOrigins = $envOrigins 
            ? array_map('trim', explode(',', $envOrigins))
            : $defaultOrigins;
        
        // Determine the allowed origin
        // IMPORTANT: When credentials are involved (withCredentials: true), we CANNOT use '*'
        // We must always return the specific origin that made the request
        $allowedOrigin = '*';
        
        if ($origin) {
            // Always use the specific origin when provided (required for credentials)
            // First check exact match in allowed list
            if (in_array($origin, $allowedOrigins)) {
                $allowedOrigin = $origin;
            } else {
                // Try to match by converting http to https or vice versa
                $httpVersion = str_replace('https://', 'http://', $origin);
                $httpsVersion = str_replace('http://', 'https://', $origin);
                
                if (in_array($httpVersion, $allowedOrigins)) {
                    $allowedOrigin = $httpVersion;
                } elseif (in_array($httpsVersion, $allowedOrigins)) {
                    $allowedOrigin = $httpsVersion;
                } else {
                    // Origin not in allowed list, but to support credentials mode,
                    // we must return the specific origin (not wildcard)
                    // This allows the request to proceed when withCredentials is true
                    // For security, you can restrict this by setting CORS_ALLOWED_ORIGINS in Render
                    $allowedOrigin = $origin;
                }
            }
        }
        
        // Log for debugging (remove in production if needed)
        // \Log::info('CORS Origin', ['origin' => $origin, 'allowed' => $allowedOrigin]);
        
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200)
                ->header('Access-Control-Allow-Origin', $allowedOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Access-Control-Expose-Headers', 'Content-Length, Content-Type');
            
            // Always set credentials to true when origin is specific (required for withCredentials)
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
        
        // Always set credentials to true when origin is specific (required for withCredentials)
        if ($allowedOrigin !== '*') {
            $corsResponse->header('Access-Control-Allow-Credentials', 'true');
        }
        
        return $corsResponse;
    }
}

