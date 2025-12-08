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
            // Get the token from cookie (auth_token is excluded from encryption)
            $token = $request->cookie('auth_token');
            
            // If we found a token in cookie, set it as Authorization header for Sanctum
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}

