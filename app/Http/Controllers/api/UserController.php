<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Post(
        path: "/users/admin",
        summary: "Add a new admin user",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["name", "email"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "John Admin"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Admin user created successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Admin user created successfully")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "The email has already been taken."),
                            new OA\Property(
                                property: "errors",
                                type: "object",
                                additionalProperties: new OA\AdditionalProperties(
                                    type: "array",
                                    items: new OA\Items(type: "string")
                                )
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function addAdmin(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            
        ]);

        $randomPassword = bin2hex(random_bytes(4)); // Generate an 8-character random password
        $user = new \App\Models\User();
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->role = 'admin';
        $user->password = \Hash::make($randomPassword);
        $user->save();

        // Here, you would typically send the password to the admin via email.
        //Mail::to($user->email)->send(new \App\Mail\AdminCredentialsMail($user->email, $randomPassword));

        return response()->json([
            'status' => true,
            'message' => 'Admin user created successfully',
            'password' => $randomPassword
        ], 201);
       
    }

    #[OA\Post(
        path: "/users/change-password",
        summary: "Change user password",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["current_password", "new_password", "new_password_confirmation"],
                    properties: [
                        new OA\Property(property: "current_password", type: "string", format: "password", example: "oldpassword123"),
                        new OA\Property(property: "new_password", type: "string", format: "password", example: "newpassword123"),
                        new OA\Property(property: "new_password_confirmation", type: "string", format: "password", example: "newpassword123")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Password changed successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Password changed successfully")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: "Current password is incorrect",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Current password is incorrect")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "The new password confirmation does not match."),
                            new OA\Property(
                                property: "errors",
                                type: "object",
                                additionalProperties: new OA\AdditionalProperties(
                                    type: "array",
                                    items: new OA\Items(type: "string")
                                )
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function changePassword(Request $request)
    {
        $validatedData = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!\Hash::check($validatedData['current_password'], $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $user->password = \Hash::make($validatedData['new_password']);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully',
        ]);
    }
  

    #[OA\Post(
        path: "/users/{id}/status",
        summary: "Update user status (active/inactive)",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "User ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["status"],
                    properties: [
                        new OA\Property(property: "status", type: "string", enum: ["active", "inactive"], example: "active")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User status updated successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "User status updated successfully")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "User not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "User not found")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "The selected status is invalid."),
                            new OA\Property(
                                property: "errors",
                                type: "object",
                                additionalProperties: new OA\AdditionalProperties(
                                    type: "array",
                                    items: new OA\Items(type: "string")
                                )
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function updateStatus(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status' => 'required|string|in:active,inactive',
        ]);

        $user = \App\Models\User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->status = $validatedData['status'];
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User status updated successfully',
        ]);
    }
   

    #[OA\Get(
        path: "/users/admins",
        summary: "Get all admin users",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Admin users retrieved successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Admin users retrieved successfully"),
                            new OA\Property(
                                property: "data",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "John Admin"),
                                        new OA\Property(property: "email", type: "string", example: "admin@example.com"),
                                        new OA\Property(property: "status", type: "string", example: "active"),
                                        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2025-12-09T10:00:00.000000Z")
                                    ]
                                )
                            )
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
    public function getAdmins(Request $request)
    {
        $admins = \App\Models\User::where('role', 'admin')->get(['id', 'name', 'email', 'status', 'created_at']);

        return response()->json([
            'status' => true,
            'message' => 'Admin users retrieved successfully',
            'data' => $admins,
        ]);
    }
}
