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
        if (!$request->bearerToken() && $request->hasCookie('auth_token')) {
            $token = $request->cookie('auth_token');
            
            // Set the Authorization header so Sanctum can read it
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}

