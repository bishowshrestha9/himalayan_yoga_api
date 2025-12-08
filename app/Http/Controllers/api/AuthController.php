<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Laravel\Sanctum\PersonalAccessToken;
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
        
        // Detect environment for cookie settings
        // Check multiple sources: Origin header, Referer header, and request host
        $origin = $request->header('Origin') ?? $request->header('Referer') ?? '';
        $requestHost = $request->getHost();
        $requestScheme = $request->getScheme();
        
        // Check if this is localhost (from any source)
        $isLocalhost = (
            str_contains($origin, 'localhost') || 
            str_contains($origin, '127.0.0.1') ||
            str_contains($requestHost, 'localhost') ||
            str_contains($requestHost, '127.0.0.1') ||
            $requestHost === '127.0.0.1' ||
            $requestHost === 'localhost'
        );
        
        $isHttps = $request->isSecure() || $requestScheme === 'https' || str_starts_with(config('app.url'), 'https://');
        $isProduction = config('app.env') === 'production';
        
        // Determine if this is a cross-origin request
        $origin = $request->header('Origin');
        $isCrossOrigin = $origin && $origin !== $request->getSchemeAndHttpHost();
        
        // Cookie settings based on context
        if ($isCrossOrigin) {
            // Cross-origin: MUST use SameSite=None with Secure=true
            // Note: This requires HTTPS even on localhost
            $sameSite = 'none';
            $secure = true;
        } else {
            // Same-origin: Can use SameSite=Lax without Secure requirement
            $sameSite = 'lax';
            $secure = $isHttps; // Only set secure if using HTTPS
        }
        $domain = null; // null for host-only cookie
        
        $response = response()->json([
            'status' => true,   
            'message' => 'Login successful',
            'token' => $token, // Include token as fallback
        ], 200)->cookie(
            'auth_token',           // Cookie name
            $token,                 // Token value
            60 * 24 * 7,            // 7 days expiration (in minutes)
            '/',                     // Path (available to all paths)
            $domain,                // Domain: null for localhost
            $secure,                // Secure flag
            true,                    // HttpOnly (not accessible via JavaScript)
            false,                   // Raw (false = URL encode)
            $sameSite               // SameSite setting
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
        // With auth:sanctum middleware, user is already authenticated
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        // Delete the current access token
        // Get the token from the request (either from header or cookie via ReadTokenFromCookie)
        $token = $request->bearerToken();
        
        if ($token) {
            $tokenModel = PersonalAccessToken::findToken($token);
            if ($tokenModel) {
                $tokenModel->delete();
            }
        } else {
            // Fallback: delete all tokens for the user (if token not found)
            $user->tokens()->delete();
        }
        
        // Clear cookie with same settings as login
        $origin = $request->header('Origin');
        $isCrossOrigin = $origin && $origin !== $request->getSchemeAndHttpHost();
        $isHttps = $request->isSecure() || str_starts_with(config('app.url'), 'https://');
        
        // Match cookie settings with login
        if ($isCrossOrigin) {
            $sameSite = 'none';
            $secure = true;
        } else {
            $sameSite = 'lax';
            $secure = $isHttps;
        }
        $domain = null;
        
        $cookie = cookie(
            'auth_token',
            '',
            -1, // Expire immediately
            '/',
            $domain,
            $secure,
            true,
            false,
            $sameSite
        );
        
        return response()->json([
            'status' => true,
            'message' => 'Logout successful'
        ], 200)->cookie($cookie);
    }
}