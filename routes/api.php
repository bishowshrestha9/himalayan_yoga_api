<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\BlogsController;
use App\Http\Middleware\AdminRoleCheckMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Controllers\api\UserController;


// Public routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::group(['prefix' => 'blogs'], function () {
    Route::get('/{id}', [BlogsController::class, 'show']);
    Route::get('/', [BlogsController::class, 'index']);
});

// Public review submission (no auth required)
Route::prefix('reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'submitReview']);
    Route::get('/publishable', [ReviewController::class, 'getPublishableReviews']); // Public endpoint for approved reviews
});

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
});

Route::prefix('users')->middleware(['auth:sanctum', 'super_admin'])->group(function () {
   Route::post('/admin', [UserController::class, 'addAdmin']);
   Route::post('/{id}/status', [UserController::class, 'updateStatus']);
   Route::get('/admins', [UserController::class, 'getAdmins']);
   
});


Route::post('users/change-password', [UserController::class, 'changePassword'])->middleware('auth:sanctum','admin');

