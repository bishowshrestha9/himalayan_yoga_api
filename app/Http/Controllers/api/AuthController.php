<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    //update swagger documentation for login
    #[OA\Post(
        path: "/auth/login",
        summary: "User login",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["email", "password"],
                    properties: [
                        new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful - Token is set as HTTP-only cookie",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Login successful")
                        ]
                    )
                )
            ),
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }
        
        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;
        
        // Set token as HTTP-only cookie for security
        // For cross-origin cookies (Next.js on different domain/port):
        // - SameSite=None is required for cross-origin
        // - Secure=true is normally required for SameSite=None, BUT
        // - Browsers allow SameSite=None with Secure=false for localhost (special exception)
        // - For production HTTPS, use Secure=true with SameSite=None
        
        $origin = $request->header('Origin');
        $isLocalhost = $origin && (
            str_contains($origin, 'localhost') || 
            str_contains($origin, '127.0.0.1') ||
            str_contains($origin, '::1')
        );
        $isHttps = $request->isSecure() || str_starts_with(config('app.url'), 'https://');
        $isProduction = config('app.env') === 'production';
        
        // Cookie settings for both localhost HTTP (dev) and production HTTPS:
        // - Localhost HTTP: SameSite=None + Secure=false (browsers allow this exception for localhost)
        // - Production HTTPS: SameSite=None + Secure=true (standard requirement)
        if ($isLocalhost && !$isHttps && !$isProduction) {
            // Localhost HTTP development: Use Secure=false
            // Modern browsers (Chrome, Firefox, Safari) allow SameSite=None with Secure=false for localhost
            $secure = false;
        } else {
            // Production or HTTPS: Use Secure=true (required for SameSite=None)
            $secure = true;
        }
        
        $sameSite = 'none'; // Required for cross-origin cookies
        
        // CRITICAL: For cross-origin cookies, DO NOT set domain
        // Setting domain restricts which domains can send the cookie
        // By leaving domain as null, the cookie is "host-only" and can be sent cross-origin
        // The cookie will be associated with the API server's domain, but can be sent from any origin
        $domain = null;
        
        $cookie = cookie(
            'auth_token',           // Cookie name
            $token,                 // Token value
            60 * 24 * 7,            // 7 days expiration (in minutes)
            '/',                     // Path (available to all paths)
            $domain,                // Domain: null = host-only cookie (works for cross-origin)
            $secure,                // Secure flag (false for localhost HTTP, true for HTTPS/production)
            true,                    // HttpOnly (not accessible via JavaScript)
            false,                   // Raw (false = URL encode)
            $sameSite               // SameSite=None (required for cross-origin)
        );
        
        // Return response with cookie
        // Note: If cookie doesn't work (browser blocks it), frontend can use token from response
        $response = response()->json([
            'status' => true,   
            'message' => 'Login successful',
            // Include token as fallback if cookie doesn't work
            // Frontend should prefer cookie, but can use this if cookie is blocked
            'token' => $token, // Fallback: use this if cookie isn't being sent
        ], 200)->cookie($cookie);
        
        return $response;
    }

//make logout get request
    #[OA\Get(
        path: "/logout",
        summary: "User logout",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logout successful",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Logout successful")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "Unauthenticated.")
                        ]
                    )
                )
            )
        ]
    )]
    public function logout(Request $request)
    {
        // Delete the token from database
        $user = $request->user();
        if ($user) {
            // Delete all tokens for this user (or just the current one)
            $user->currentAccessToken()?->delete();
        }

        // Clear the auth_token cookie with same settings as when it was set
        // This ensures the cookie is properly cleared even if it was set with specific attributes
        $origin = $request->header('Origin');
        $isLocalhost = $origin && (
            str_contains($origin, 'localhost') || 
            str_contains($origin, '127.0.0.1') ||
            str_contains($origin, '::1')
        );
        $isHttps = $request->isSecure() || str_starts_with(config('app.url'), 'https://');
        $isProduction = config('app.env') === 'production';
        
        // Use same secure setting as login
        $secure = $isHttps || $isProduction;
        if ($isLocalhost && !$isHttps && !$isProduction) {
            $secure = false;
        }
        
        // Create cookie with same attributes but expired (to clear it)
        $cookie = cookie(
            'auth_token',
            '',
            -1, // Expire immediately
            '/',
            null, // Same domain as when set
            $secure,
            true, // HttpOnly
            false,
            'none' // SameSite
        );

        return response()->json([
            'status' => true,
            'message' => 'Logout successful',
        ], 200)->cookie($cookie);
    }
}
