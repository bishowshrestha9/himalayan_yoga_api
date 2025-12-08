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
     
     
       
        
        $response = response()->json([
            'status' => true,   
            'message' => 'Login successful',
            
        ], 200)->cookie(
            'auth_token',           // Cookie name
            $token,                 // Token value
            60 * 24 * 7,            // 7 days expiration (in minutes)
            '/',                     // Path (available to all paths)
            null,                // Domain: null = host-only cookie (works for cross-origin)
            false,                // Secure flag (false for localhost HTTP, true for HTTPS/production)
            true,                    // HttpOnly (not accessible via JavaScript)
            false,                   // Raw (false = URL encode)
            'Strict'              // SameSite=None (required for cross-origin)
        );
        
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
