<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inquiry;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\InquiryRequest;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Mail;

class InquiryController extends Controller
{
    #[OA\Get(
        path: "/inquiries",
        summary: "Get all inquiries",
        tags: ["Inquiries"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Inquiries fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Inquiries fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                                        new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                        new OA\Property(property: "phone", type: "string", example: "+1234567890"),
                                        new OA\Property(property: "message", type: "string", example: "I'm interested in your yoga classes"),
                                        new OA\Property(property: "service_name", type: "string", example: "Hatha Yoga"),
                                        new OA\Property(property: "created_at", type: "string", format: "date-time")
                                    ]
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "No inquiries found")
        ]
    )]
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $inquiries = Inquiry::with('service')->orderBy('created_at', 'desc')->paginate($perPage);
            
            if ($inquiries->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No inquiries found',
                ], 404);
            }
            
            $inquiries->getCollection()->transform(function ($inquiry) {
                // Replace service object with just the name
                $inquiry->service_name = $inquiry->service ? $inquiry->service->title : null;
                unset($inquiry->service);
                unset($inquiry->service_id);
                unset($inquiry->updated_at);
                
                return $inquiry;
            });
            
            return response()->json([
                'status' => true,
                'message' => 'Inquiries fetched successfully',
                'data' => $inquiries->items(),
                'pagination' => [
                    'current_page' => $inquiries->currentPage(),
                    'last_page' => $inquiries->lastPage(),
                    'per_page' => $inquiries->perPage(),
                    'total' => $inquiries->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch inquiries', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch inquiries',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/inquiries",
        summary: "Create a new inquiry",
        tags: ["Inquiries"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["name", "email", "phone", "message"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                        new OA\Property(property: "phone", type: "string", example: "+1234567890"),
                        new OA\Property(property: "message", type: "string", example: "I'm interested in your yoga classes"),
                        new OA\Property(property: "service_id", type: "integer", example: 1, description: "Optional service ID")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Inquiry created successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Inquiry submitted successfully")
                        ]
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 500, description: "Failed to create inquiry")
        ]
    )]
    public function store(InquiryRequest $request)
    {
        try {
            $validated = $request->validated();

            // Check for existing inquiry from this email in the last hour
            $recentInquiry = Inquiry::where('email', $validated['email'])
                ->where('created_at', '>=', now()->subHour())
                ->first();
            if ($recentInquiry) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can only submit one inquiry per hour with this email address.',
                ], 429);
            }

            Inquiry::create($validated);
            Mail::to(config('mail.inquiry_recipient'))->send(new \App\Mail\NewInquiryNotification($validated));

            return response()->json([
                'status' => true,
                'message' => 'Inquiry submitted successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create inquiry', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $request->all()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to submit inquiry',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/inquiries/{id}",
        summary: "Get an inquiry by ID",
        tags: ["Inquiries"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Inquiry ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Inquiry fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Inquiry fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string"),
                                    new OA\Property(property: "email", type: "string"),
                                    new OA\Property(property: "phone", type: "string"),
                                    new OA\Property(property: "message", type: "string"),
                                    new OA\Property(property: "service_name", type: "string"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time")
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Inquiry not found")
        ]
    )]
    public function show($id)
    {
        try {
            $inquiry = Inquiry::with('service')->find($id);
            
            if (!$inquiry) {
                return response()->json([
                    'status' => false,
                    'message' => 'Inquiry not found',
                ], 404);
            }
            
            // Replace service object with just the name
            $inquiry->service_name = $inquiry->service ? $inquiry->service->title : null;
            unset($inquiry->service);
            unset($inquiry->service_id);
            unset($inquiry->updated_at);
            
            return response()->json([
                'status' => true,
                'message' => 'Inquiry fetched successfully',
                'data' => $inquiry,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch inquiry', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'inquiry_id' => $id
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch inquiry',
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/inquiries/{id}",
        summary: "Delete an inquiry",
        tags: ["Inquiries"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Inquiry ID",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Inquiry deleted successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Inquiry deleted successfully")
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Inquiry not found")
        ]
    )]
    public function destroy($id)
    {
        try {
            $inquiry = Inquiry::find($id);
            
            if (!$inquiry) {
                return response()->json([
                    'status' => false,
                    'message' => 'Inquiry not found',
                ], 404);
            }

            $inquiry->delete();

            return response()->json([
                'status' => true,
                'message' => 'Inquiry deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete inquiry', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'inquiry_id' => $id
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete inquiry',
            ], 500);
        }
    }


    #[OA\Get(
        path: "/inquiries/total/count",
        summary: "Get total count of inquiries",
        tags: ["Inquiries"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Total inquiries count fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "total_inquiries", type: "integer", example: 42)
                        ]
                    )
                )
            )
        ]
    )]
    public function getTotalInquiry(){
        $total = Inquiry::count();
        return response()->json([
            'status' => true,
            'total_inquiries' => $total
        ]);
    }
}
