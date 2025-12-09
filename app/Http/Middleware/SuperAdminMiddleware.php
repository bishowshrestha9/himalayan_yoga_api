<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is set
        $user = $request->user();
        
        // Also try Auth::user() as fallback
        if (!$user) {
            $user = \Illuminate\Support\Facades\Auth::user();
        }
        
        if (!$user) {
            // Check if there's a cookie
            $token = $request->cookie('auth_token');
            
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated - User not set',
                'debug' => [
                    'has_cookie' => !empty($token),
                    'request_user' => $request->user() ? 'set' : 'null',
                    'auth_user' => \Illuminate\Support\Facades\Auth::user() ? 'set' : 'null',
                    'cookie_preview' => $token ? substr($token, 0, 20) . '...' : 'no cookie',
                ],
            ], 401);
        }
        
        if ($user->role !== 'super_admin') {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden - Super admin access required',
                'debug' => [
                    'user_role' => $user->role,
                    'required_role' => 'super_admin',
                ],
            ], 403);
        }
        
        return $next($request);
    }
}
