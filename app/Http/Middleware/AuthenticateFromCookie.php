<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
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

        if (! $token) {
            return response()->json([
                'message' => 'Unauthenticated',
                'debug' => [
                    'has_cookie' => $request->hasCookie('auth_token'),
                    'all_cookies' => array_keys($request->cookies->all()),
                    'raw_cookie' => isset($_COOKIE['auth_token']),
                ]
            ], 401);
        }

        $tokenModel = PersonalAccessToken::findToken($token);

        if (! $tokenModel) {
            return response()->json([
                'message' => 'Invalid token',
                'debug' => [
                    'token_preview' => substr($token, 0, 20) . '...',
                ]
            ], 401);
        }

        Auth::login($tokenModel->tokenable);

        return $next($request);
    }
}