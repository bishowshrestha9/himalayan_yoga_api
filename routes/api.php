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


// Public routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
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
    Route::get('/{id}', [ServiceController::class, 'show']);
});

// Public review submission (no auth required)
Route::prefix('reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'submitReview']);
    Route::get('/publishable', [ReviewController::class, 'getPublishableReviews']); // Public endpoint for approved reviews
});

// Public inquiry submission (no auth required)
Route::post('/inquiries', [InquiryController::class, 'store']);

// Protected routes - use Sanctum for authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::get('logout', [AuthController::class, 'logout']);
    
    // Admin-only blog management
    Route::prefix('blogs')->middleware('admin')->group(function () {
        Route::post('/', [BlogsController::class, 'store']);
        Route::post('/{id}', [BlogsController::class, 'update']); // Use POST for file uploads
        Route::delete('/{id}', [BlogsController::class, 'destroy']);
    });
    
    // Admin-only review management
    Route::prefix('reviews')->middleware('admin')->group(function () {
        Route::get('/', [ReviewController::class, 'getReviews']);
        Route::get('/publishable', [ReviewController::class, 'getPublishableReviews']);
        Route::delete('/', [ReviewController::class, 'deleteMultipleReviews']);
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
    });
    
    // Admin-only inquiry management
    Route::prefix('inquiries')->middleware('admin')->group(function () {
        Route::get('/', [InquiryController::class, 'index']);
        Route::get('/{id}', [InquiryController::class, 'show']);
        Route::delete('/{id}', [InquiryController::class, 'destroy']);
    });
});

// Super admin only routes for user management
Route::prefix('users')->middleware(SuperAdminMiddleware::class)->group(function () {
   Route::post('/admin', [UserController::class, 'addAdmin']);
   Route::post('/{id}/status', [UserController::class, 'updateStatus']);
   Route::get('/admins', [UserController::class, 'getAdmins']);
});


Route::post('users/change-password', [UserController::class, 'changePassword'])->middleware('auth:sanctum');


Route::get('/user', [AuthController::class, 'me'])->middleware('auth:sanctum');

