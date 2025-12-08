<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadTokenFromCookie
{
    /**
     * Handle an incoming request.
     * 
     * Reads the auth token from cookie and sets it as Authorization header
     * so Sanctum can authenticate the request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If no Authorization header is present, check for token in cookie
        if (!$request->bearerToken()) {
            // Try multiple methods to get the cookie
            $token = $request->cookie('auth_token');
            
            // Fallback: Check raw cookies (for cross-origin scenarios)
            if (!$token && isset($_COOKIE['auth_token'])) {
                $token = $_COOKIE['auth_token'];
            }
            
            // Also try getting from all cookies
            if (!$token) {
                $allCookies = $request->cookies->all();
                $token = $allCookies['auth_token'] ?? null;
            }
            
            // If we found a token in cookie, decode and set it as Authorization header for Sanctum
            if ($token) {
                // Clean and decode the token (cookies might be URL-encoded)
                $token = trim($token);
                // Decode multiple times in case of double encoding
                $decodedToken = urldecode($token);
                while ($decodedToken !== $token) {
                    $token = $decodedToken;
                    $decodedToken = urldecode($token);
                }
                $token = $decodedToken;
                
                // Check for Sanctum token prefix and remove it if present
                $tokenPrefix = config('sanctum.token_prefix', '');
                if ($tokenPrefix && str_starts_with($token, $tokenPrefix)) {
                    $token = substr($token, strlen($tokenPrefix));
                }
                
                // Set as Authorization header for Sanctum to handle
                // Set it in multiple places to ensure Sanctum can read it
                $request->headers->set('Authorization', 'Bearer ' . $token);
                
                // Also set it in the server array for compatibility (some frameworks read from here)
                $request->server->set('HTTP_AUTHORIZATION', 'Bearer ' . $token);
                
                // Ensure the header is available via get() method
                $request->headers->add(['Authorization' => 'Bearer ' . $token]);
            }
        }

        return $next($request);
    }
}

