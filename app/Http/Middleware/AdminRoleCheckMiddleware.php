<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // auth:sanctum should have already authenticated the user
        // If user is null here, it means auth:sanctum failed
        $user = $request->user();
        
        if (!$user) {
            // Check if there's an authorization header
            $authHeader = $request->header('Authorization');
            $hasToken = !empty($authHeader) && str_starts_with($authHeader, 'Bearer ');
            
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated - Please check your token',
                'debug' => [
                    'has_auth_header' => !empty($authHeader),
                    'has_bearer_token' => $hasToken,
                    'header_preview' => $hasToken ? substr($authHeader, 0, 30) . '...' : 'No Authorization header',
                ],
            ], 401);
        }

        // Check if user has admin role
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to access this resource',
                'debug' => [
                    'user_role' => $user->role,
                    'required_role' => 'admin',
                ],
            ], 403);
        }

        return $next($request);
    }
}
