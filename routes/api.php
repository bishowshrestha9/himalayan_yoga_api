<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\BlogsController;
use App\Http\Middleware\AdminRoleCheckMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\InstructorController;
use App\Http\Controllers\api\ServiceController;
use App\Http\Controllers\api\InquiryController;
use App\Http\Controllers\api\BookingController;
use App\Http\Controllers\api\DashboardController;
use App\Http\Controllers\payment\PaymentController;
use App\Http\Controllers\api\BookWithPayment;


// Public routes with rate limiting
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // 5 attempts per minute
});

Route::group(['prefix' => 'blogs'], function () {
    Route::get('/{id}', [BlogsController::class, 'show']);
    Route::get('/', [BlogsController::class, 'index']);
});

// Public instructor routes
Route::group(['prefix' => 'instructors'], function () {
    Route::get('/', [InstructorController::class, 'index']);
    Route::get('/{id}', [InstructorController::class, 'show']);
});

// Public service routes
Route::group(['prefix' => 'services'], function () {
    Route::get('/', [ServiceController::class, 'index']);
    Route::get('/{slug}', [ServiceController::class, 'show']);
});

// Public review submission (no auth required) - rate limited
Route::prefix('reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'submitReview'])->middleware('throttle:3,1'); // 3 reviews per minute
    Route::get('/publishable', [ReviewController::class, 'getPublishableReviews']);
});

// Public inquiry submission (no auth required) - rate limited
Route::post('/inquiries', [InquiryController::class, 'store'])->middleware('throttle:5,1'); // 5 inquiries per minute

// Protected routes - use Sanctum for authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::get('logout', [AuthController::class, 'logout']);
    
    // Admin-only blog management
    Route::prefix('blogs')->middleware('admin')->group(function () {
        Route::post('/', [BlogsController::class, 'store']);
        Route::post('/{id}', [BlogsController::class, 'update']); // Use POST for file uploads
        Route::delete('/{id}', [BlogsController::class, 'destroy']);
        Route::get('/total', [BlogsController::class, 'getTotalBlogs']);
    });
    
    // Admin-only review management
    Route::prefix('reviews')->middleware('admin')->group(function () {
        Route::get('/', [ReviewController::class, 'getReviews']);
        
        Route::delete('/{id}', [ReviewController::class, 'delete']);
        Route::post('/{id}/approve', [ReviewController::class, 'approveReview']);
        Route::get('/pn',[ReviewController::class,'getPositiveAndNegativeReviewsCount']);
    });
    
    // Admin-only instructor management
    Route::prefix('instructors')->middleware('admin')->group(function () {
        Route::post('/', [InstructorController::class, 'store']);
        Route::post('/{id}', [InstructorController::class, 'update']); // Use POST for file uploads
        Route::delete('/{id}', [InstructorController::class, 'destroy']);
    });
    
    // Admin-only service management
    Route::prefix('services')->middleware('admin')->group(function () {
        Route::post('/', [ServiceController::class, 'store']);
        Route::post('/{id}', [ServiceController::class, 'update']); // Use POST for file uploads
        Route::post('/{id}/toggle-status', [ServiceController::class, 'toggleStatus']);
        Route::delete('/{id}', [ServiceController::class, 'destroy']);
        Route::get('/total', [ServiceController::class, 'getTotalServices']);
    });
    
    // Admin-only inquiry management
    Route::prefix('inquiries')->middleware('admin')->group(function () {
        Route::get('/', [InquiryController::class, 'index']);
        Route::get('/{id}', [InquiryController::class, 'show']);
        Route::delete('/{id}', [InquiryController::class, 'destroy']);
        Route::get('/total/count', [InquiryController::class, 'getTotalInquiry']);
    });
    
    // Super admin only routes for user management
    Route::prefix('users')->middleware('admin')->group(function () {
        Route::post('/admin', [UserController::class, 'addAdmin']);
        Route::post('/{id}/status', [UserController::class, 'updateStatus']);
       
    });
    
    // Admin-only booking management
    Route::prefix('bookings')->middleware('admin')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::put('/{id}/status', [BookingController::class, 'updateStatus']);
        Route::get('/total', [BookingController::class, 'getTotalBookings']);
        Route::get('/monthly-revenue', [BookingController::class, 'getMonthlyRevenue']);
    });
    
    Route::post('users/change-password', [UserController::class, 'changePassword']);
    
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Dashboard data - all stats in one call
    Route::get('/main-d', [DashboardController::class, 'getDashboardData'])->middleware('admin');
});


Route::prefix('payment')->group(function () {
    // Public routes - Called by your frontend
    Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);
    Route::post('/cancel-payment-intent/{id}', [PaymentController::class, 'handlePaymentCanceled']); 
    
    
    // Webhook - Called ONLY by Stripe servers (not from frontend!)
    // Configure this URL in your Stripe Dashboard: https://dashboard.stripe.com/webhooks
    Route::post('/webhook', [PaymentController::class, 'webhook']);
});

// Public booking creation - rate limited
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:3,1'); // 3 bookings per minute
Route::get('/service/idname', [ServiceController::class, 'getServiceIdAndName']);


//only super admin can access this route
Route::get('/users/admins', [UserController::class, 'getAdmins'])->middleware('auth:sanctum', 'super_admin');


Route::get('/service/top-six', [ServiceController::class, 'getTopSixServices']);


Route::get('/reviews/four', [ReviewController::class, 'getFourReviews']);

Route::get('/reviews/publishable', [ReviewController::class, 'getPublishableReviews']);



Route::post('/book-with-payment', [BookWithPayment::class, 'bookWithPayment'])->middleware('throttle:3,1'); 
