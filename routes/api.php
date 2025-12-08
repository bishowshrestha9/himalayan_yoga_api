<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\BlogsController;
use App\Http\Middleware\AdminRoleCheckMiddleware;


Route::middleware('auth.cookie')->group(function () {      
    Route::get('logout', [AuthController::class, 'logout']);
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::prefix('blogs')->middleware(['auth.cookie', 'admin'])->group(function () {
    Route::post('/', [BlogsController::class, 'store']);
    Route::put('/{id}', [BlogsController::class, 'update']);
    Route::delete('/{id}', [BlogsController::class, 'destroy']);
});


Route::group(['prefix' => 'blogs'], function () {
    Route::get('/{id}', [BlogsController::class, 'show']);
    Route::get('/', [BlogsController::class, 'index']);
});

// Public review submission (no auth required)
Route::prefix('reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'submitReview']);
});

// Admin-only review management (requires auth + admin role)
Route::prefix('reviews')->middleware(['auth.cookie', 'admin'])->group(function () {
    Route::get('/', [ReviewController::class, 'getReviews']);
    Route::get('/publishable', [ReviewController::class, 'getPublishableReviews']);
    Route::delete('/', [ReviewController::class, 'deleteMultipleReviews']);
});