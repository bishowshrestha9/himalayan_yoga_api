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
        // SameSite=None and Secure are required for cross-origin requests
        $isSecure = $request->isSecure() || config('app.env') === 'production';
        
        $cookie = cookie(
            'auth_token',           // Cookie name
            $token,                 // Token value
            60 * 24 * 7,            // 7 days expiration
            '/',                     // Path
            null,                    // Domain (null = current domain)
            $isSecure,              // Secure (HTTPS only in production)
            true,                    // HttpOnly (not accessible via JavaScript)
            false,                   // Raw (false = URL encode)
            'none'                   // SameSite (none for cross-origin)
        );
        
        return response()->json([
            'status' => true,   
            'message' => 'Login successful',
        ], 200)->cookie($cookie);
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
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        // Clear the auth_token cookie
        $cookie = cookie()->forget('auth_token');

        return response()->json([
            'status' => true,
            'message' => 'Logout successful',
        ], 200)->cookie($cookie);
    }
}
