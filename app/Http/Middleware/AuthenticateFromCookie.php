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
        $token = $request->cookie('auth_token');

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $tokenModel = PersonalAccessToken::findToken($token);

        if (! $tokenModel) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        Auth::login($tokenModel->tokenable);

        return $next($request);
    }
}

