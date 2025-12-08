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
            // Laravel's cookie() method might not work if cookie wasn't set properly
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
            
            // If we found a token in cookie, set it as Authorization header for Sanctum
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}

