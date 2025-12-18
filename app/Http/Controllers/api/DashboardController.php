<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Blogs;
use App\Models\Service;
use App\Models\Inquiry;
use App\Models\Booking;
use App\Models\Reviews;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: "/main-d",
        summary: "Get all dashboard data in one call",
        tags: ["Dashboard"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dashboard data fetched successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Dashboard data fetched successfully"),
                            new OA\Property(
                                property: "data",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "total_blogs", type: "integer", example: 25),
                                    new OA\Property(property: "total_services", type: "integer", example: 12),
                                    new OA\Property(property: "total_inquiries", type: "integer", example: 42),
                                    new OA\Property(property: "total_bookings", type: "integer", example: 150),
                                    new OA\Property(property: "positive_reviews", type: "integer", example: 85),
                                    new OA\Property(property: "negative_reviews", type: "integer", example: 5),
                                    new OA\Property(
                                        property: "monthly_revenue",
                                        type: "array",
                                        items: new OA\Items(
                                            type: "object",
                                            properties: [
                                                new OA\Property(property: "month", type: "integer", example: 1),
                                                new OA\Property(property: "total_revenue", type: "number", format: "float", example: 15000.50)
                                            ]
                                        )
                                    ),
                                    new OA\Property(
                                        property: "publishable_reviews",
                                        type: "array",
                                        items: new OA\Items(
                                            type: "object",
                                            properties: [
                                                new OA\Property(property: "id", type: "integer", example: 1),
                                                new OA\Property(property: "name", type: "string", example: "John Doe"),
                                                new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                                new OA\Property(property: "review", type: "string", example: "Great service!"),
                                                new OA\Property(property: "rating", type: "number", example: 4.5),
                                                new OA\Property(property: "service", type: "string", example: "Hatha Yoga"),
                                                new OA\Property(property: "created_at", type: "string", example: "2025-01-15")
                                            ]
                                        )
                                    ),
                                    new OA\Property(
                                        property: "reviews_pagination",
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "current_page", type: "integer", example: 1),
                                            new OA\Property(property: "last_page", type: "integer", example: 3),
                                            new OA\Property(property: "per_page", type: "integer", example: 8),
                                            new OA\Property(property: "total", type: "integer", example: 20)
                                        ]
                                    )
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to fetch dashboard data",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Failed to fetch dashboard data"),
                            new OA\Property(property: "error", type: "string", example: "Database error")
                        ]
                    )
                )
            )
        ]
    )]
    public function getDashboardData()
    {
        try {
            // Get total blogs
            $totalBlogs = Blogs::count();
            
            // Get total services
            $totalServices = Service::count();
            
            // Get total inquiries
            $totalInquiries = Inquiry::count();
            
            // Get total bookings
            $totalBookings = Booking::count();
            
            // Get monthly revenue
            $currentYear = date('Y');
            $monthlyRevenue = Booking::selectRaw('MONTH(created_at) as month, SUM(price) as total_revenue')
                ->whereYear('created_at', $currentYear)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $formattedRevenue = [];
            for ($month = 1; $month <= 12; $month++) {
                $revenueData = $monthlyRevenue->firstWhere('month', $month);
                $formattedRevenue[] = [
                    'month' => $month,
                    'total_revenue' => $revenueData ? (float) $revenueData->total_revenue : 0.0
                ];
            }
            
            // Get positive and negative reviews count
            $positiveCount = Reviews::where('rating', '>=', 4)->count();
            $negativeCount = Reviews::where('rating', '<', 3)->count();
            
            
            return response()->json([
                'status' => true,
                'data' => [
                    'total_blogs' => $totalBlogs,
                    'total_services' => $totalServices,
                    'total_inquiries' => $totalInquiries,
                    'total_bookings' => $totalBookings,
                    'monthly_revenue' => $formattedRevenue,
                    'positive_reviews' => $positiveCount,
                    'negative_reviews' => $negativeCount,
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch dashboard data',
            ], 500);
        }
    }
}
