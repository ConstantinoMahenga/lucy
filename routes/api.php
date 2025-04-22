<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PhotoController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/photos', [PhotoController::class, 'upload']);
    Route::get('/photos', [PhotoController::class, 'listUserPhotos']);
    Route::put('/photos/{id}/main', [PhotoController::class, 'setAsMain']);
    Route::delete('/photos/{id}', [PhotoController::class, 'delete']);
});




