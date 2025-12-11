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
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid login details'
            ], 401);
        }
        
        // Check if account is locked
        if ($user->locked_until && now()->lessThan($user->locked_until)) {
            $minutesLeft = now()->diffInMinutes($user->locked_until);
            return response()->json([
                'status' => false,
                'message' => "Account is locked due to too many failed login attempts. Try again in {$minutesLeft} minutes."
            ], 423); // 423 Locked
        }
        
        // Reset lock if time has passed
        if ($user->locked_until && now()->greaterThanOrEqualTo($user->locked_until)) {
            $user->login_attempts = 0;
            $user->locked_until = null;
            $user->save();
        }
        
        if (!Auth::attempt($request->only('email', 'password'))) {
            // Increment failed login attempts
            $user->increment('login_attempts');
            
            // Lock account after 5 failed attempts for 15 minutes
            if ($user->login_attempts >= 5) {
                $user->locked_until = now()->addMinutes(15);
                $user->save();
                
                return response()->json([
                    'status' => false,
                    'message' => 'Account locked due to too many failed login attempts. Try again in 15 minutes.'
                ], 423);
            }
            
            $attemptsLeft = 5 - $user->login_attempts;
            return response()->json([
                'status' => false,
                'message' => "Invalid login details. {$attemptsLeft} attempts remaining before account lockout."
            ], 401);
        }
        
        // Reset login attempts on successful login
        $user->login_attempts = 0;
        $user->locked_until = null;
        $user->save();
        
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
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'status' => true,
        'message' => 'Logged out'
    ]); 
    }



    public function me(Request $request){
        return response()->json([
            'status' => true,
            'role' => $request->user()->role
            
        ]);
    }
}