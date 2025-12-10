<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use App\Http\Requests\InstructorRequest;
use Illuminate\Http\Request;
use App\Models\Instructor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class InstructorController extends Controller
{
    #[OA\Get(
        path: "/instructors",
        summary: "Get all instructors",
        tags: ["Instructors"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Instructors fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Instructors fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                                        new OA\Property(property: "profession", type: "string", example: "Yoga Instructor"),
                                        new OA\Property(property: "experience", type: "integer", example: 5),
                                        new OA\Property(property: "bio", type: "string", example: "Experienced yoga instructor"),
                                        new OA\Property(property: "specialities", type: "string", example: "Hatha, Vinyasa"),
                                        new OA\Property(property: "certifications", type: "string", example: "RYT-200, RYT-500"),
                                        new OA\Property(property: "image_url", type: "string", example: "https://example.com/storage/instructors/image.jpg")
                                    ]
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "No instructors found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "No instructors found")
                        ]
                    )
                )
            )
        ]
    )]
    public function index()
    {
        try {
            $instructors = Instructor::all();
            if ($instructors->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No instructors found',
                ], 404);
            }
            
            // Add full image URL to each instructor
            $instructors->transform(function ($instructor) {
                $instructor->image_url = $instructor->image ? asset('storage/' . $instructor->image) : null;
                return $instructor;
            });
            
            return response()->json([
                'status' => true,
                'message' => 'Instructors fetched successfully',
                'data' => $instructors,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch instructors', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch instructors',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: "/instructors",
        summary: "Create a new instructor",
        tags: ["Instructors"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["name", "profession", "experience"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                        new OA\Property(property: "profession", type: "string", example: "Yoga Instructor"),
                        new OA\Property(property: "experience", type: "integer", example: 5),
                        new OA\Property(property: "bio", type: "string", example: "Experienced yoga instructor with passion for teaching"),
                        new OA\Property(property: "specialities", type: "string", example: "Hatha, Vinyasa, Meditation"),
                        new OA\Property(
                            property: "certifications", 
                            type: "string", 
                            example: "RYT-200, RYT-500, E-RYT",
                            description: "Comma-separated certifications"
                        ),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Image file (jpeg, jpg, png, gif, webp, max 5MB)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Instructor created successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Instructor created successfully")
                        ]
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 500, description: "Failed to create instructor")
        ]
    )]
    public function store(InstructorRequest $request)
    {
        try {
            $validatedData = $request->validated();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $tempPath = $image->store('temp/instructors', 'public');
                $extension = $image->getClientOriginalExtension();
                $filename = 'instructor_' . time() . '_' . Str::random(10) . '.' . $extension;
                $permanentPath = 'instructors/' . $filename;
                Storage::disk('public')->move($tempPath, $permanentPath);
                $validatedData['image'] = $permanentPath;
            }
            
            Instructor::create($validatedData);
           
            
            return response()->json([
                'status' => true,
                'message' => 'Instructor created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create instructor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->except(['image'])
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to create instructor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: "/instructors/{id}",
        summary: "Get an instructor by ID",
        tags: ["Instructors"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Instructor ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Instructor fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Instructor fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                                    new OA\Property(property: "profession", type: "string", example: "Yoga Instructor"),
                                    new OA\Property(property: "experience", type: "integer", example: 5),
                                    new OA\Property(property: "bio", type: "string"),
                                    new OA\Property(property: "specialities", type: "string"),
                                    new OA\Property(property: "certifications", type: "string"),
                                    new OA\Property(property: "image_url", type: "string")
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Instructor not found")
        ]
    )]
    public function show($id)
    {
        try {
            $instructor = Instructor::find($id);
            if (!$instructor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Instructor not found',
                ], 404);
            }
            
            $instructor->image_url = $instructor->image ? asset('storage/' . $instructor->image) : null;
            
            return response()->json([
                'status' => true,
                'message' => 'Instructor fetched successfully',
                'data' => $instructor,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch instructor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'instructor_id' => $id
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch instructor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: "/instructors/{id}",
        summary: "Update an instructor",
        tags: ["Instructors"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Instructor ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                        new OA\Property(property: "profession", type: "string", example: "Yoga Instructor"),
                        new OA\Property(property: "experience", type: "integer", example: 5),
                        new OA\Property(property: "bio", type: "string"),
                        new OA\Property(property: "specialities", type: "string"),
                        new OA\Property(
                            property: "certifications", 
                            type: "string", 
                            example: "RYT-200, RYT-500, E-RYT",
                            description: "Comma-separated certifications"
                        ),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Image file (optional)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Instructor updated successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Instructor updated successfully")
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Instructor not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(InstructorRequest $request, $id)
    {
        try {
            $instructor = Instructor::find($id);
            if (!$instructor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Instructor not found',
                ], 404);
            }
            
            $validatedData = $request->validated();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($instructor->image && Storage::disk('public')->exists($instructor->image)) {
                    Storage::disk('public')->delete($instructor->image);
                }
                
                $image = $request->file('image');
                $tempPath = $image->store('temp/instructors', 'public');
                $extension = $image->getClientOriginalExtension();
                $filename = 'instructor_' . time() . '_' . Str::random(10) . '.' . $extension;
                $permanentPath = 'instructors/' . $filename;
                Storage::disk('public')->move($tempPath, $permanentPath);
                $validatedData['image'] = $permanentPath;
            }
            
            $instructor->update($validatedData);
            
            return response()->json([
                'status' => true,
                'message' => 'Instructor updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update instructor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'instructor_id' => $id,
                'data' => $request->except(['image'])
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to update instructor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/instructors/{id}",
        summary: "Delete an instructor",
        tags: ["Instructors"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Instructor ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Instructor deleted successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Instructor deleted successfully")
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Instructor not found")
        ]
    )]
    public function destroy($id)
    {
        try {
            $instructor = Instructor::find($id);
            if (!$instructor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Instructor not found',
                ], 404);
            }
            
            // Delete associated image
            if ($instructor->image && Storage::disk('public')->exists($instructor->image)) {
                Storage::disk('public')->delete($instructor->image);
            }
            
            $instructor->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'Instructor deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete instructor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'instructor_id' => $id
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete instructor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
