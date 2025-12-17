<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use App\Http\Requests\BookingRequest;

class BookingController extends Controller
{
    #[OA\Get(
        path: "/bookings",
        summary: "Get all bookings",
        tags: ["Bookings"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Bookings retrieved successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Bookings retrieved successfully"),
                            new OA\Property(
                                property: "bookings",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "user_name", type: "string", example: "John Doe"),
                                        new OA\Property(property: "user_email", type: "string", example: "john@example.com"),
                                        new OA\Property(property: "service_name", type: "string", example: "Hatha Yoga"),
                                        new OA\Property(property: "booking_date", type: "string", format: "date", example: "2025-12-15"),
                                        new OA\Property(property: "status", type: "string", enum: ["pending", "confirmed", "cancelled"], example: "pending"),
                                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                    ]
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "No bookings found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "No bookings found")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to retrieve bookings",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to retrieve bookings: Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function index(){
        try {
        $bookings = \App\Models\Booking::with('service')->orderBy('fromDate', 'desc')->get();
        if ($bookings->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No bookings found'
            ], 404);
        }
        
        $data = $bookings->map(function($booking) {
            return [
                'id' => $booking->id,
                'userName' => $booking->userName,
                'userEmail' => $booking->userEmail,
                'service_name' => $booking->service ? $booking->service->title : null,
                'fromDate' => $booking->fromDate,
                'toDate' => $booking->toDate,
                'time' => $booking->time,
                'status' => $booking->status,
                'participants' => $booking->participants,
                'price' => $booking->price,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,
            ];
        });
        
        return response()->json([
            'status' => true,
            'data' => $data,
            
        ]);
        }
        catch (\Exception $e) {
             return response()->json([
                 'status' => false,
                 'message' => 'Failed to retrieve bookings: '
        ], 500);
    }
    }

    #[OA\Put(
        path: "/bookings/{id}/status",
        summary: "Update booking status",
        tags: ["Bookings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "The ID of the booking to update",
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
                        new OA\Property(
                            property: "status",
                            type: "string",
                            enum: ["confirmed", "pending", "cancelled"],
                            example: "confirmed"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Booking status updated successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Booking status updated successfully"),
                            new OA\Property(
                                property: "booking",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "user_name", type: "string", example: "John Doe"),
                                    new OA\Property(property: "user_email", type: "string", example: "john@example.com"),
                                    new OA\Property(property: "service_id", type: "integer", example: 1),
                                    new OA\Property(property: "booking_date", type: "string", format: "date", example: "2025-12-15"),
                                    new OA\Property(property: "status", type: "string", example: "confirmed"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Booking not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Booking not found")
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
                            new OA\Property(property: "message", type: "string", example: "The status field is required."),
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
                description: "Failed to update booking status",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to update booking status: Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function updateStatus(Request $request, $id)
    {
        try{
        $request->validate([
            'status' => 'required|in:confirmed,pending,cancelled',
        ]);

        $booking = \App\Models\Booking::find($id);
        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found'
        ], 404);
        }
        $booking->status = $request->input('status');
        $booking->save();
        return response()->json([
            'status' => true,
            'message' => 'Booking status updated successfully',
            'data' => $booking
        ]);
        }
        catch (\Exception $e) {
             return response()->json([
                 'status' => false,
                 'message' => 'Failed to update booking status: '
        ], 500);
    
        }
    }


    #[OA\Post(
        path: "/bookings",
        summary: "Create a new booking",
        tags: ["Bookings"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["userName", "userEmail", "service_id", "fromDate", "toDate", "time", "status", "participants", "price"],
                    properties: [
                        new OA\Property(property: "userName", type: "string", example: "John Doe"),
                        new OA\Property(property: "userEmail", type: "string", format: "email", example: "john@example.com"),
                        new OA\Property(property: "service_id", type: "integer", example: 1),
                        new OA\Property(property: "fromDate", type: "string", format: "date", example: "2025-12-15"),
                        new OA\Property(property: "toDate", type: "string", format: "date", example: "2025-12-20"),
                        new OA\Property(property: "time", type: "string", example: "09:00 AM"),
                        new OA\Property(property: "status", type: "string", enum: ["confirmed", "pending", "cancelled"], example: "pending"),
                        new OA\Property(property: "participants", type: "integer", minimum: 1, example: 2),
                        new OA\Property(property: "price", type: "number", format: "float", minimum: 0, example: 150.00)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Booking created successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Booking created successfully"),
                            new OA\Property(
                                property: "booking",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "userName", type: "string", example: "John Doe"),
                                    new OA\Property(property: "userEmail", type: "string", example: "john@example.com"),
                                    new OA\Property(property: "service_id", type: "integer", example: 1),
                                    new OA\Property(property: "fromDate", type: "string", example: "2025-12-15"),
                                    new OA\Property(property: "toDate", type: "string", example: "2025-12-20"),
                                    new OA\Property(property: "time", type: "string", example: "09:00 AM"),
                                    new OA\Property(property: "status", type: "string", example: "pending"),
                                    new OA\Property(property: "participants", type: "integer", example: 2),
                                    new OA\Property(property: "price", type: "number", example: 150.00),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
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
                            new OA\Property(property: "message", type: "string", example: "The userName field is required."),
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
                description: "Failed to create booking",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to create booking: Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function store(BookingRequest $request)
    {
        try {
        $booking = \App\Models\Booking::create($request->validated());
        return response()->json([
            'status' => true,
            'message' => 'Booking created successfully',
            'data' => $booking
        ], 201);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create booking: '
        ], 500);
        }
    }


    #[OA\Get(
        path: "/bookings/total",
        summary: "Get total count of bookings",
        tags: ["Bookings"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Total bookings count fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(
                                property: "data",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "total_bookings", type: "integer", example: 150)
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to retrieve total bookings",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to retrieve total bookings")
                        ]
                    )
                )
            )
        ]
    )]
    public function getTotalBookings()
    {
        try {
            $totalBookings = \App\Models\Booking::count();
            return response()->json([
                'status' => true,
                'data' => [
                    'total_bookings' => $totalBookings
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve total bookings: '
            ], 500);
        }
    }


    #[OA\Get(
        path: "/bookings/monthly-revenue",
        summary: "Get monthly revenue for current year",
        tags: ["Bookings"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Monthly revenue fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(
                                property: "data",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "month", type: "integer", example: 1),
                                        new OA\Property(property: "total_revenue", type: "number", format: "float", example: 15000.50)
                                    ]
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to retrieve monthly revenue",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to retrieve monthly revenue")
                        ]
                    )
                )
            )
        ]
    )]
    public function getMonthlyRevenue(){
        try {
            $currentYear = date('Y');
            $monthlyRevenue = \App\Models\Booking::selectRaw('MONTH(created_at) as month, SUM(price) as total_revenue')
                ->whereYear('created_at', $currentYear)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Format the result to include all months
            $formattedRevenue = [];
            for ($month = 1; $month <= 12; $month++) {
                $revenueData = $monthlyRevenue->firstWhere('month', $month);
                $formattedRevenue[] = [
                    'month' => $month,
                    'total_revenue' => $revenueData ? (float) $revenueData->total_revenue : 0.0
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $formattedRevenue
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve monthly revenue: '
            ], 500);
        }

    }


    

}


    

