<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use App\Models\Blogs;
use OpenApi\Attributes as OA;
use Illuminate\Http\Request;
use App\Http\Requests\BlogRequest;
class BlogsController extends Controller
{

    //swagger documentation for index
    //update swagger docs
    #[OA\Get(
        path: "/blogs",
        summary: "Get all blogs",
        tags: ["Blogs"],
        responses: [
            new OA\Response(response: 200, description: "Blogs fetched successfully", content: new OA\MediaType(mediaType: "application/json", schema: new OA\Schema(properties: [new OA\Property(property: "status", type: "boolean", example: true), new OA\Property(property: "message", type: "string", example: "Blogs fetched successfully"), new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object", properties: [new OA\Property(property: "id", type: "integer", example: 1), new OA\Property(property: "title", type: "string", example: "Blog Title"), new OA\Property(property: "description", type: "string", example: "Blog Description"), new OA\Property(property: "image", type: "string", example: "Blog Image"), new OA\Property(property: "status", type: "boolean", example: true), new OA\Property(property: "created_at", type: "string", example: "2021-01-01 00:00:00"), new OA\Property(property: "updated_at", type: "string", example: "2021-01-01 00:00:00")]))]))),
            new OA\Response(response: 500, description: "Failed to fetch blogs", content: new OA\MediaType(mediaType: "application/json", schema: new OA\Schema(properties: [new OA\Property(property: "status", type: "boolean", example: false), new OA\Property(property: "message", type: "string", example: "Failed to fetch blogs"), new OA\Property(property: "error", type: "string", example: "Failed to fetch blogs")]))),
        ],
    )]

    public function index(){
        try {
            $blogs = Blogs::all();
            if (!$blogs) {
                return response()->json([
                    'status' => false,
                    'message' => 'No blogs found',
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => 'Blogs fetched successfully',
                
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch blogs',
                'error' => $e->getMessage(),
            ], 500);
        }   
    }

    #[OA\Post(
        path: "/blogs",
        summary: "Create a new blog",
        tags: ["Blogs"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["title", "description", "image", "excerpt", "is_active"],
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "Blog Title"),
                        new OA\Property(property: "description", type: "string", example: "Blog Description"),
                        new OA\Property(property: "image", type: "string", example: "https://example.com/image.jpg"),
                        new OA\Property(property: "excerpt", type: "string", example: "Blog Excerpt"),
                        new OA\Property(property: "is_active", type: "boolean", example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Blog created successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Blog created successfully"),
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
                            new OA\Property(property: "message", type: "string", example: "The title field is required."),
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
            ),
            new OA\Response(
                response: 500,
                description: "Failed to create blog",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to create blog"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function store(BlogRequest $request){
        try {
            $blog = Blogs::create($request->all());
            return response()->json([
                'status' => true,
                'message' => 'Blog created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create blog',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: "/blogs/{id}",
        summary: "Get a blog by ID",
        tags: ["Blogs"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Blog ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Blog fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Blog fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "title", type: "string", example: "Blog Title"),
                                    new OA\Property(property: "description", type: "string", example: "Blog Description"),
                                    new OA\Property(property: "image", type: "string", example: "https://example.com/image.jpg"),
                                    new OA\Property(property: "excerpt", type: "string", example: "Blog Excerpt"),
                                    new OA\Property(property: "created_at", type: "string", example: "2021-01-01 00:00:00"),
                                    new OA\Property(property: "updated_at", type: "string", example: "2021-01-01 00:00:00")
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Blog not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Blog not found")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to fetch blog",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to fetch blog"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function show($id){
        try {
            $blog = Blogs::find($id);
            if (!$blog) {
                return response()->json([
                    'status' => false,
                    'message' => 'Blog not found',
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => 'Blog fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch blog',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Put(
        path: "/blogs/{id}",
        summary: "Update a blog",
        tags: ["Blogs"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Blog ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["title", "description", "image", "excerpt", "is_active"],
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "Updated Blog Title"),
                        new OA\Property(property: "description", type: "string", example: "Updated Blog Description"),
                        new OA\Property(property: "image", type: "string", example: "https://example.com/updated-image.jpg"),
                        new OA\Property(property: "excerpt", type: "string", example: "Updated Blog Excerpt"),
                        new OA\Property(property: "is_active", type: "boolean", example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Blog updated successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Blog updated successfully"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Blog not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Blog not found")
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
                            new OA\Property(property: "message", type: "string", example: "The title field is required."),
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
            ),
            new OA\Response(
                response: 500,
                description: "Failed to update blog",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to update blog"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function update(BlogRequest $request, $id){
        try {
            $blog = Blogs::find($id);
            if (!$blog) {
                return response()->json([
                    'status' => false,
                    'message' => 'Blog not found',
                ], 404);
            }
            $blog->update($request->all()); 
            return response()->json([
                'status' => true,
                'message' => 'Blog updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update blog',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/blogs/{id}",
        summary: "Delete a blog",
        tags: ["Blogs"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Blog ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Blog deleted successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Blog deleted successfully"),     
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Blog not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Blog not found")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to delete blog",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to delete blog"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function destroy($id){
        try {
            $blog = Blogs::find($id);
            if (!$blog) {
                return response()->json([
                    'status' => false,
                    'message' => 'Blog not found',
                ], 404);
            }
            $blog->delete();
            return response()->json([
                'status' => true,
                'message' => 'Blog deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete blog',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}