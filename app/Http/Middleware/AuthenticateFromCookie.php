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
        
       
        if (!$token && isset($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
        }
        
        
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
        
        // Try Sanctum's findToken first (this should work, but let's have a fallback)
        $tokenModel = PersonalAccessToken::findToken($token);

        // If findToken fails, manually validate the token
        // Token format: {id}|{hash}
        // Database stores: SHA256 hash of the hash part
        if (! $tokenModel && str_contains($token, '|')) {
            $parts = explode('|', $token, 2);
            if (count($parts) === 2) {
                [$id, $hashPart] = $parts;
                
                // Clean the ID and hash part
                $id = trim($id);
                $hashPart = trim($hashPart);
                
                // Find token by ID
                $tokenModel = PersonalAccessToken::find($id);
                
                if ($tokenModel) {
                    // Hash the provided hash part and compare with stored token
                    // The database stores: hash('sha256', hash_part)
                    $computedHash = hash('sha256', $hashPart);
                    
                    // Use hash_equals for timing-safe comparison
                    if (!hash_equals($tokenModel->token, $computedHash)) {
                        $tokenModel = null; // Hash doesn't match
                    } else {
                        // Check if token is expired
                        if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
                            $tokenModel = null; // Token expired
                        }
                    }
                }
            }
        }

        if (! $tokenModel) {
            // Enhanced debugging
            $debug = [
                'token_preview' => substr($token, 0, 20) . '...',
                'token_length' => strlen($token),
                'has_pipe' => str_contains($token, '|'),
            ];
            
            if (str_contains($token, '|')) {
                $parts = explode('|', $token, 2);
                $debug['token_id'] = $parts[0] ?? 'N/A';
                $debug['hash_preview'] = isset($parts[1]) ? substr($parts[1], 0, 10) . '...' : 'N/A';
                $debug['hash_length'] = isset($parts[1]) ? strlen($parts[1]) : 0;
                
                // Check if token ID exists in database
                if (isset($parts[0])) {
                    $foundToken = PersonalAccessToken::find($parts[0]);
                    $debug['token_exists_in_db'] = $foundToken ? 'yes' : 'no';
                    if ($foundToken) {
                        $debug['stored_hash_preview'] = substr($foundToken->token, 0, 10) . '...';
                        $debug['stored_hash_length'] = strlen($foundToken->token);
                        $debug['computed_hash_preview'] = isset($parts[1]) ? substr(hash('sha256', $parts[1]), 0, 10) . '...' : 'N/A';
                        $debug['computed_hash_length'] = isset($parts[1]) ? strlen(hash('sha256', $parts[1])) : 0;
                        $debug['hashes_match'] = isset($parts[1]) ? hash_equals($foundToken->token, hash('sha256', $parts[1])) ? 'yes' : 'no' : 'N/A';
                        
                        // Also check if this token belongs to a user
                        if ($foundToken->tokenable) {
                            $debug['token_user_id'] = $foundToken->tokenable->id;
                            $debug['token_user_email'] = $foundToken->tokenable->email;
                        }
                    }
                }
            }
            
            return response()->json([
                'message' => 'Invalid token',
                'debug' => $debug,
            ], 401);
        }


        $user = $tokenModel->tokenable;
        
        // Ensure user exists and is loaded
        if (!$user) {
            return response()->json([
                'message' => 'Invalid token - User not found',
            ], 401);
        }

        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
    
        Auth::setUser($user);

        return $next($request);
    }
}