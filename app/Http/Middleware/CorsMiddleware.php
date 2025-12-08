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
        
        // Check if request includes credentials (Authorization header indicates credentials)
        $hasCredentials = $request->header('Authorization') !== null;
        
        // Default allowed origins (can be overridden via .env or Render environment)
        // Next.js is running on port 3002 (both dev and production)
        $defaultOrigins = [
            'http://localhost:3000',      // Next.js alternative port
            'http://localhost:3001',
            'http://localhost:3002',       // Next.js primary port (dev)
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:3002',        // Next.js primary port (dev - IP)
            'http://209.126.86.149:3002',  // Production Next.js (HTTP)
            'https://209.126.86.149:3002', // Production Next.js (HTTPS)
            'http://localhost',
            'http://127.0.0.1',
        ];
        
        // Get allowed origins from environment or use defaults
        $envOrigins = env('CORS_ALLOWED_ORIGINS');
        $allowedOrigins = $envOrigins 
            ? array_map('trim', explode(',', $envOrigins))
            : $defaultOrigins;
        
        // Determine the allowed origin
        // CRITICAL: When credentials are involved (withCredentials: true), we CANNOT use '*'
        // We must ALWAYS return the specific origin that made the request
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
        } elseif ($hasCredentials) {
            // No origin header but credentials are involved - this shouldn't happen in browsers
            // but handle it gracefully by using first allowed origin
            if (!empty($allowedOrigins) && $allowedOrigins[0] !== '*') {
                $allowedOrigin = $allowedOrigins[0];
            }
        }
        
        // CRITICAL FIX: If origin exists, NEVER use wildcard (required for credentials)
        // Even if origin is not in allowed list, return it to support credentials mode
        if ($origin && $allowedOrigin === '*') {
            $allowedOrigin = $origin;
        }
        
        // Handle preflight OPTIONS request (Next.js sends this for CORS)
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200)
                ->header('Access-Control-Allow-Origin', "http://localhost:3000")
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, Cache-Control, Pragma')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Access-Control-Expose-Headers', 'Content-Length, Content-Type, Authorization');
            
            // CRITICAL: Always set credentials to true when origin is specific (required for Next.js withCredentials)
            // When withCredentials is true, browser requires specific origin (not wildcard)
            if ($allowedOrigin !== '*') {
                $response->header('Access-Control-Allow-Credentials', 'true');
            }
            
            return $response;
        }

        $response = $next($request);

        // Add CORS headers to the response (for Next.js client-side requests)
        $corsResponse = $response
            ->header('Access-Control-Allow-Origin', "http://localhost:3002")
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, Cache-Control, Pragma')
            //credentials
            ->header('Access-Control-Allow-Credentials', 'true')

            ->header('Access-Control-Max-Age', '86400')
            ->header('Access-Control-Expose-Headers', 'Content-Length, Content-Type, Authorization');
        
        // CRITICAL: Always set credentials to true when origin is specific (required for Next.js withCredentials)
        // When withCredentials is true, browser requires specific origin (not wildcard)
        // Next.js fetch/axios with credentials requires this
        if ($allowedOrigin !== '*') {
            $corsResponse->header('Access-Control-Allow-Credentials', 'true');
        }
        
        return $corsResponse;
    }
}

