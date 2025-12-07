<?php

use App\Http\Controllers\api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\BlogsController;
use App\Http\Middleware\AdminRoleCheckMiddleware;


Route::middleware('auth:sanctum')->group(function () {      
    Route::get('logout', [AuthController::class, 'logout']);
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::prefix('blogs')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/', [BlogsController::class, 'store']);
    Route::put('/{id}', [BlogsController::class, 'update']);
    Route::delete('/{id}', [BlogsController::class, 'destroy']);
});


Route::group(['prefix' => 'blogs'], function () {
    Route::get('/{id}', [BlogsController::class, 'show']);
    Route::get('/', [BlogsController::class, 'index']);
});
