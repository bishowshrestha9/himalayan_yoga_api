<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ReviewsRequest;
use App\Models\Reviews;
use App\Models\Lead;
use OpenApi\Attributes as OA;

class ReviewController extends Controller
{
    #[OA\Post(
        path: "/reviews",
        summary: "Submit a new review (also stored as a lead)",
        tags: ["Reviews"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["name", "email", "review", "rating", "status"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                        new OA\Property(property: "review", type: "string", example: "Great service! Highly recommended."),
                        new OA\Property(property: "rating", type: "number", format: "float", minimum: 1, maximum: 5, example: 4.5),
                        new OA\Property(property: "status", type: "boolean", example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Review created successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Review created successfully and stored as lead"),
                            new OA\Property(
                                property: "data",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                                    new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                    new OA\Property(property: "review", type: "string", example: "Great service! Highly recommended."),
                                    new OA\Property(property: "rating", type: "number", example: 4.5)
                                ]
                            )
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
                            new OA\Property(property: "message", type: "string", example: "The name field is required."),
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
                description: "Failed to create review",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to create review"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function submitReview(ReviewsRequest $request)
    {
        try {
            // Create the review
            $review = Reviews::create($request->all());
            
            // Store review as a lead for lead management
            Lead::create([
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->review,
                'source' => 'review',
                'status' => 'pending',
                'metadata' => [
                    'review_id' => $review->id,
                    'rating' => $request->rating,
                    'review_text' => $request->review,
                    'status' => $request->status,
                ],
            ]);
            
            $data = [
                'id' => $review->id,
                'name' => $review->name,
                'email' => $review->email,
                'review' => $review->review,
                'rating' => $review->rating,
            ];
            return response()->json([
                'status' => true,
                'message' => 'Review created successfully and stored as lead',
                'data' => $data,
            ], 201);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    #[OA\Get(
        path: "/reviews",
        summary: "Get all reviews",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Reviews fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Reviews fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                                        new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                        new OA\Property(property: "review", type: "string", example: "Great service! Highly recommended."),
                                        new OA\Property(property: "rating", type: "number", example: 4.5),
                                        new OA\Property(property: "status", type: "boolean", example: true)
                                    ]
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "No reviews found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "No reviews found")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to fetch reviews",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to fetch reviews"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function getReviews()
    {
        try {
            //
            $reviews = Reviews::all();
            if (!$reviews) {
                return response()->json([
                    'status' => false,
                    'message' => 'No reviews found',
                ], 404);
            }
            $data = [];
            foreach ($reviews as $review) {
                $data[] = [
                    'id' => $review->id,
                    'name' => $review->name,
                    'email' => $review->email,
                    'review' => $review->review,
                    'rating' => $review->rating,
                    'status' => $review->status,
                ];
            }
            return response()->json([
                'status' => true,
                'message' => 'Reviews fetched successfully',  
                'data' => $data,
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch reviews',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    #[OA\Get(
        path: "/reviews/publishable",
        summary: "Get publishable reviews (latest 3 approved reviews)",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Publishable reviews fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Publisable reviews fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                                        new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                        new OA\Property(property: "review", type: "string", example: "Great service! Highly recommended."),
                                        new OA\Property(property: "rating", type: "number", example: 4.5)
                                    ]
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "No publishable reviews found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "No publisable reviews found")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to fetch publishable reviews",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to fetch publisable reviews"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function getPublishableReviews()
    {
        try {
            $reviews = Reviews::where('status', true)->orderBy('created_at', 'desc')->take(3)->get();
            if (!$reviews) {
                return response()->json([
                    'status' => false,
                    'message' => 'No publishable reviews found',
                ], 404);
            }
            $data = [];
            foreach ($reviews as $review) {
                $data[] = [
                    'id' => $review->id,
                    'name' => $review->name,
                    'email' => $review->email,
                    'review' => $review->review,
                    'rating' => $review->rating,
                ];
            }
            return response()->json([
                'status' => true,
                'message' => 'Publishable reviews fetched successfully',
                'data' => $data,
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch publishable reviews',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/reviews",
        summary: "Delete multiple reviews at once",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["ids"],
                    properties: [
                        new OA\Property(
                            property: "ids",
                            type: "array",
                            items: new OA\Items(type: "integer"),
                            example: [1, 2, 3],
                            description: "Array of review IDs to delete"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Reviews deleted successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Reviews deleted successfully")
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
            ),
            new OA\Response(
                response: 404,
                description: "No reviews found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "No reviews found")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to delete reviews",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to delete reviews"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function deleteMultipleReviews(Request $request)
    {
        try {
            $reviews = Reviews::whereIn('id', $request->ids)->delete();
            if (!$reviews) {
                return response()->json([
                    'status' => false,
                    'message' => 'No reviews found',
                ], 404);
            }
            
            
            return response()->json([
                'status' => true,
                'message' => 'Reviews deleted successfully',
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete reviews',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
