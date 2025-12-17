<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ReviewsRequest;
use App\Models\Reviews;
use App\Models\Lead;
use OpenApi\Attributes as OA;
//only send status and message in response 

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
                        new OA\Property(property: "service_id", type: "integer", example: 1, nullable: true),
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
                            new OA\Property(property: "message", type: "string", example: "Review created successfully and stored as lead")
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
            
   
            return response()->json([
                'status' => true,
                'message' => 'Review created successfully',

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
                                        new OA\Property(property: "status", type: "boolean", example: true),
                                        new OA\Property(property: "service_name", type: "string", example: "Hatha Yoga", nullable: true)
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
    public function getReviews(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $reviews = Reviews::with('service')->paginate($perPage);
            
            if ($reviews->isEmpty()) {
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
                    'service' => $review->service ? $review->service->title : null,
                    'created_at' => $review->created_at->toDateString(),
                ];
            }
            return response()->json([
                'status' => true,
                'message' => 'Reviews fetched successfully',  
                'data' => $data,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ]
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
        summary: "Get publishable reviews (latest 3 approved reviews) - Public endpoint",
        tags: ["Reviews"],
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
                                        new OA\Property(property: "rating", type: "number", example: 4.5),
                                        new OA\Property(property: "service_name", type: "string", example: "Hatha Yoga", nullable: true)
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
    public function getPublishableReviews(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 8);
            $reviews = Reviews::with('service')->where('status', 1)->orderBy('created_at', 'desc')->paginate($perPage);
            
            if ($reviews->isEmpty()) {
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
                    'service' => $review->service ? $review->service->title : null,
                    'created_at' => $review->created_at->toDateString(),
                ];
            }
            return response()->json([
                'status' => true,
                'message' => 'Publishable reviews fetched successfully',
                'data' => $data,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ]
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
        path: "/reviews/{id}",
        summary: "Delete a single review by ID",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "The ID of the review to delete",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Review deleted successfully",
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
                description: "Review not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Review not found")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to delete review",
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
    public function delete($id)
    {
        try {
            $review= Reviews::where('id', $id)->delete();
           
            
            
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


    public function approveReview($id)
    {
        try {
            $review = Reviews::find($id);
            if (!$review) {
                return response()->json([
                    'status' => false,
                    'message' => 'Review not found',
                ], 404);
            }
            $review->status = true;
            $review->save();
            return response()->json([
                'status' => true,
                'message' => 'Review approved successfully',
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to approve review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getFourReviews(){
        try {
            $reviews = Reviews::with('service')->where('status', true)->orderBy('created_at', 'desc')->take(4)->get();
            if ($reviews->isEmpty()) {
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
                    'service' => $review->service ? $review->service->title : null,
                    'created_at' => $review->created_at->toDateString(),
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
        path: "/reviews/pn",
        summary: "Get count of positive and negative reviews",
        tags: ["Reviews"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Positive and negative reviews count fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "data", type: "object",
                                properties: [
                                    new OA\Property(property: "positive_reviews", type: "integer", example:
                                        15),
                                    new OA\Property(property: "negative_reviews", type: "integer", example: 3),
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to retrieve reviews count",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to retrieve reviews count"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function getPositiveAndNegativeReviewsCount(){
        try {
            $positiveCount = \App\Models\Review::where('rating', '>=', 4)->count();
            $negativeCount = \App\Models\Review::where('rating', '<', 3)->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'positive_reviews' => $positiveCount,
                    'negative_reviews' => $negativeCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve reviews count: ' . $e->getMessage()
            ], 500);
        }
    }
}
