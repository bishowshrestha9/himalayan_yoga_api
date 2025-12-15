<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Instructor;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ServiceRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ServiceController extends Controller
{
    #[OA\Get(
        path: "/services",
        summary: "Get all services",
        tags: ["Services"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Services fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Services fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "title", type: "string", example: "Hatha Yoga"),
                                        new OA\Property(property: "description", type: "string", example: "Traditional yoga practice"),
                                        new OA\Property(property: "yoga_type", type: "string", example: "basic", description: "basic, intermediate, or advanced"),
                                        new OA\Property(property: "benefits", type: "array", items: new OA\Items(type: "string")),
                                        new OA\Property(property: "class_schedule", type: "array", items: new OA\Items(type: "string")),
                                        new OA\Property(property: "instructor_id", type: "integer", example: 1),
                                        new OA\Property(property: "instructor", type: "object"),
                                        new OA\Property(property: "image_url", type: "string")
                                    ]
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "No services found")
        ]
    )]
    public function index()
    {
        try {
            $services = Service::with('instructor')->get();
            
            if ($services->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No services found',
                ], 404);
            }
            
            $services->transform(function ($service) {
                // Convert image paths to full URLs
                $service->image_urls = $service->images ? array_map(function($image) {
                    return asset('storage/' . $image);
                }, $service->images) : [];
                
                // Replace instructor object with just the name
                $service->instructor_name = $service->instructor ? $service->instructor->name : null;
                unset($service->instructor);
                
                // Remove instructor_id and raw images from response
                unset($service->instructor_id);
                unset($service->images);
                
                return $service;
            });
            
            return response()->json([
                'status' => true,
                'message' => 'Services fetched successfully',
                'data' => $services,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch services', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: "/services",
        summary: "Create a new service",
        tags: ["Services"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["title", "description", "yoga_type", "instructor_id"],
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "Hatha Yoga"),
                        new OA\Property(property: "description", type: "string", example: "Traditional yoga practice focusing on physical postures"),
                        new OA\Property(property: "yoga_type", type: "string", enum: ["basic", "intermediate", "advanced"], example: "basic"),
                        new OA\Property(property: "benefits", type: "string", example: "Flexibility, Strength, Mental clarity", description: "Comma-separated benefits"),
                        new OA\Property(property: "class_schedule", type: "string", example: "Monday 9AM, Wednesday 9AM, Friday 9AM", description: "Comma-separated schedule"),
                        new OA\Property(property: "session_time", type: "string", example: "60 minutes", description: "Duration of each session"),
                        new OA\Property(property: "instructor_id", type: "integer", example: 1),
                        new OA\Property(property: "price", type: "number", format: "float", example: 50.00),
                        new OA\Property(property: "capacity", type: "integer", example: 20),
                        new OA\Property(
                            property: "images", 
                            type: "array",
                            items: new OA\Items(type: "string", format: "binary"),
                            description: "Multiple image files (jpeg, jpg, png, gif, webp, max 5MB each)"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Service created successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Service created successfully")
                        ]
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 500, description: "Failed to create service")
        ]
    )]
    public function store(ServiceRequest $request)
    {
        try {
            $validatedData = $request->validated();
          
            
            // Convert comma-separated strings to arrays
            if (isset($validatedData['benefits']) && is_string($validatedData['benefits'])) {
                $validatedData['benefits'] = array_map('trim', explode(',', $validatedData['benefits']));
            }
            
            if (isset($validatedData['class_schedule']) && is_string($validatedData['class_schedule'])) {
                $validatedData['class_schedule'] = array_map('trim', explode(',', $validatedData['class_schedule']));
            }

            // Handle multiple image uploads
            if ($request->hasFile('images')) {
                $imagePaths = [];
                foreach ($request->file('images') as $image) {
                    $tempPath = $image->store('temp/services', 'public');
                    $extension = $image->getClientOriginalExtension();
                    $filename = 'service_' . time() . '_' . Str::random(10) . '.' . $extension;
                    $permanentPath = 'services/' . $filename;
                    Storage::disk('public')->move($tempPath, $permanentPath);
                    $imagePaths[] = $permanentPath;
                }
                $validatedData['images'] = $imagePaths;
            }

            Service::create($validatedData);

            return response()->json([
                'status' => true,
                'message' => 'Service created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->except(['image'])
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to create service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: "/services/{slug}",
        summary: "Get a service by slug",
        tags: ["Services"],
        parameters: [
            new OA\Parameter(
                name: "slug",
                in: "path",
                required: true,
                description: "Service slug",
                schema: new OA\Schema(type: "string", example: "hatha-yoga")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Service fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Service fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "title", type: "string"),
                                    new OA\Property(property: "description", type: "string"),
                                    new OA\Property(property: "yoga_type", type: "string"),
                                    new OA\Property(property: "benefits", type: "array", items: new OA\Items(type: "string")),
                                    new OA\Property(property: "class_schedule", type: "array", items: new OA\Items(type: "string")),
                                    new OA\Property(property: "instructor", type: "object"),
                                    new OA\Property(property: "image_url", type: "string")
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Service not found")
        ]
    )]
    public function show($slug)
    {
        try {
            $service = Service::with('instructor')->where('slug', $slug)->first();
            
            if (!$service) {
                return response()->json([
                    'status' => false,
                    'message' => 'Service not found',
                ], 404);
            }
            
            // Convert image paths to full URLs
            $service->image_urls = $service->images ? array_map(function($image) {
                return url('storage/' . $image);
            }, $service->images) : [];
            
            // Replace instructor object with just the name
            $service->instructor_name = $service->instructor ? $service->instructor->name : null;
            unset($service->instructor);
            
            // Remove instructor_id and raw images from response
            unset($service->instructor_id);
            unset($service->images);
            
            return response()->json([
                'status' => true,
                'message' => 'Service fetched successfully',
                'data' => $service,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: "/services/{id}",
        summary: "Update a service",
        tags: ["Services"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Service ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "Hatha Yoga"),
                        new OA\Property(property: "description", type: "string"),
                        new OA\Property(property: "yoga_type", type: "string", enum: ["basic", "intermediate", "advanced"]),
                        new OA\Property(property: "benefits", type: "string", description: "Comma-separated benefits"),
                        new OA\Property(property: "class_schedule", type: "string", description: "Comma-separated schedule"),
                        new OA\Property(property: "instructor_id", type: "integer"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Image file (optional)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Service updated successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Service updated successfully")
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Service not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(ServiceRequest $request, $id)
    {
        try {
            $service = Service::find($id);
            
            if (!$service) {
                return response()->json([
                    'status' => false,
                    'message' => 'Service not found',
                ], 404);
            }

      

            $validatedData = $request->validated();
            
            // Convert comma-separated strings to arrays
            if (isset($validatedData['benefits']) && is_string($validatedData['benefits'])) {
                $validatedData['benefits'] = array_map('trim', explode(',', $validatedData['benefits']));
            }
            
            if (isset($validatedData['class_schedule']) && is_string($validatedData['class_schedule'])) {
                $validatedData['class_schedule'] = array_map('trim', explode(',', $validatedData['class_schedule']));
            }

            // Handle multiple image uploads
            if ($request->hasFile('images')) {
                // Delete old images
                if ($service->images) {
                    foreach ($service->images as $oldImage) {
                        if (Storage::disk('public')->exists($oldImage)) {
                            Storage::disk('public')->delete($oldImage);
                        }
                    }
                }

                $imagePaths = [];
                foreach ($request->file('images') as $image) {
                    $tempPath = $image->store('temp/services', 'public');
                    $extension = $image->getClientOriginalExtension();
                    $filename = 'service_' . time() . '_' . Str::random(10) . '.' . $extension;
                    $permanentPath = 'services/' . $filename;
                    Storage::disk('public')->move($tempPath, $permanentPath);
                    $imagePaths[] = $permanentPath;
                }
                $validatedData['images'] = $imagePaths;
            }

            $service->update($validatedData);

            return response()->json([
                'status' => true,
                'message' => 'Service updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id,
                'data' => $request->except(['image'])
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to update service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/services/{id}",
        summary: "Delete a service",
        tags: ["Services"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Service ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Service deleted successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Service deleted successfully")
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Service not found")
        ]
    )]
    public function destroy($id)
    {
        try {
            $service = Service::find($id);
            
            if (!$service) {
                return response()->json([
                    'status' => false,
                    'message' => 'Service not found',
                ], 404);
            }

            // Delete associated images
            if ($service->images) {
                foreach ($service->images as $image) {
                    if (Storage::disk('public')->exists($image)) {
                        Storage::disk('public')->delete($image);
                    }
                }
            }

            $service->delete();

            return response()->json([
                'status' => true,
                'message' => 'Service deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: "/services/{id}/toggle-status",
        summary: "Toggle service active status",
        tags: ["Services"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Service ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Service status toggled successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Service status updated successfully"),
                            new OA\Property(property: "new_status", type: "boolean", example: true)
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Service not found")
        ]
    )]
    public function toggleStatus($id)
    {
        try {
            $service = Service::find($id);
            
            if (!$service) {
                return response()->json([
                    'status' => false,
                    'message' => 'Service not found',
                ], 404);
            }

            $service->is_active = !$service->is_active;
            $service->save();

            return response()->json([
                'status' => true,
                'message' => 'Service status updated successfully',
                'new_status' => $service->is_active,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to toggle service status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to update service status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getServiceIdAndName(){
        try{
            $services = Service::select('id', 'title')->get();
            return response()->json([
                'status' => true,
                'data' => $services
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get service IDs and names', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to get service IDs and names',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function getTopSixServices()
    {
        //latest 
        try {
            $services = Service::where('is_active', true)
                ->with('instructor')
                ->orderBy('created_at', 'desc')
                ->take(6)
                ->get();

            $services->transform(function ($service) {
                // Convert image paths to full URLs
                $service->image_urls = $service->images ? array_map(function($image) {
                    return url('storage/' . $image);
                }, $service->images) : [];

                // Replace instructor object with just the name
                $service->instructor_name = $service->instructor ? $service->instructor->name : null;
                unset($service->instructor);

                // Remove instructor_id and raw images from response
                unset($service->instructor_id);
                unset($service->images);

                return $service;
            });

            return response()->json([
                'status' => true,
                'message' => 'Top six services fetched successfully',
                'data' => $services,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch top six services', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch top six services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}


