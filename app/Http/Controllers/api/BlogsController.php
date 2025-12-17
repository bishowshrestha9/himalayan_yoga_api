<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use App\Models\Blogs;
use OpenApi\Attributes as OA;
use Illuminate\Http\Request;
use App\Http\Requests\BlogRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            if ($blogs->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No blogs found',
                ], 404);
            }
            
            // Add full image URL to each blog
            $blogs->transform(function ($blog) {
                $blog->image_url = $blog->image ? url('storage/' . $blog->image) : null;
                return $blog;
            });
            
            return response()->json([
                'status' => true,
                'message' => 'Blogs fetched successfully',
                'data' => $blogs,
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
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["title", "description", "image", "is_active", "slug"],
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "Introduction to Himalayan Yoga"),
                        new OA\Property(property: "subtitle", type: "string", example: "Ancient Practices for Modern Life"),
                        new OA\Property(property: "description", type: "string", example: "Brief overview of the blog"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Image file (jpeg, jpg, png, gif, webp, max 5MB)"),
                        new OA\Property(property: "excerpt", type: "string", example: "A short preview text..."),
                        new OA\Property(property: "author", type: "string", example: "John Doe"),
                        new OA\Property(
                            property: "content", 
                            type: "string", 
                            example: '[{"heading":"What is Himalayan Yoga?","paragraph":"Himalayan Yoga is a profound..."},{"heading":"Key Benefits","paragraph":"Regular practice brings..."}]',
                            description: "JSON array of objects with heading and paragraph keys"
                        ),
                        new OA\Property(property: "conclusion", type: "string", example: "Himalayan Yoga offers a complete path to wellness..."),
                        new OA\Property(property: "slug", type: "string", example: "introduction-to-himalayan-yoga"),
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
            $data = $request->only(['title', 'subtitle', 'description', 'excerpt', 'author', 'content', 'conclusion', 'is_active', 'slug']);
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                
                // Store in temp directory first
                $tempPath = $image->store('temp/blogs', 'public');
                
                // Generate unique filename
                $extension = $image->getClientOriginalExtension();
                $filename = 'blog_' . time() . '_' . Str::random(10) . '.' . $extension;
                
                // Move from temp to permanent location
                $permanentPath = 'blogs/' . $filename;
                Storage::disk('public')->move($tempPath, $permanentPath);

                Logic::info('Image moved to permanent location: ' . $permanentPath);
                
                // Store the path in database
                $data['image'] = $permanentPath;
            }
            
            $blog = Blogs::create($data);
            
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
        path: "/blogs/{title}",
        summary: "Get a blog by title",
        tags: ["Blogs"],
        parameters: [
            new OA\Parameter(
                name: "title",
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
    public function show($title){
        try {
            
            //lower case and space to dash comparison
            $blog = Blogs::whereRaw('LOWER(REPLACE(title, " ", "-")) = ?', [strtolower($title)])->first();

            if (!$blog) {
                return response()->json([
                    'status' => false,
                    'message' => 'Blog not found',
                ], 404);
            }
            
            // Add full image URL
            $blog->image_url = $blog->image ? url('storage/' . $blog->image) : null;
            
            return response()->json([
                'status' => true,
                'message' => 'Blog fetched successfully',
                'data' => $blog,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch blog',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
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
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["title", "description", "is_active", "slug"],
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "Updated Blog Title"),
                        new OA\Property(property: "subtitle", type: "string", example: "Updated Subtitle"),
                        new OA\Property(property: "description", type: "string", example: "Updated Blog Description"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Image file (jpeg, jpg, png, gif, webp, max 5MB) - optional for update"),
                        new OA\Property(property: "excerpt", type: "string", example: "Updated Blog Excerpt"),
                        new OA\Property(property: "author", type: "string", example: "Jane Doe"),
                        new OA\Property(
                            property: "content", 
                            type: "string", 
                            example: '[{"heading":"Updated Heading","paragraph":"Updated paragraph..."}]',
                            description: "JSON array of objects with heading and paragraph keys"
                        ),
                        new OA\Property(property: "conclusion", type: "string", example: "Updated conclusion..."),
                        new OA\Property(property: "slug", type: "string", example: "updated-blog-title"),
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
            
            $data = $request->only(['title', 'subtitle', 'description', 'excerpt', 'author', 'content', 'conclusion', 'is_active', 'slug']);
            
            // Handle image upload if new image is provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                    Storage::disk('public')->delete($blog->image);
                }
                
                $image = $request->file('image');
                
                // Store in temp directory first
                $tempPath = $image->store('temp/blogs', 'public');
                
                // Generate unique filename
                $extension = $image->getClientOriginalExtension();
                $filename = 'blog_' . time() . '_' . Str::random(10) . '.' . $extension;
                
                // Move from temp to permanent location
                $permanentPath = 'blogs/' . $filename;
                Storage::disk('public')->move($tempPath, $permanentPath);
                
                // Store the path in database
                $data['image'] = $permanentPath;
            }
            
            $blog->update($data);
            
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
            
            // Delete associated image file
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
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