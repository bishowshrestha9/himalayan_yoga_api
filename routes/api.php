<?php

use App\Http\Controllers\api\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {      
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::get('/documentation', function () {
    return view('swagger');
})->middleware('auth');
