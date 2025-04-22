<?php

use App\Http\Controllers\Api\InteractionController as ApiInteractionController;
use App\Http\Controllers\Api\MatchController as ApiMatchController;
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


    //PHOTOS
    Route::post('/photos', [PhotoController::class, 'upload']);
    Route::get('/photos', [PhotoController::class, 'listUserPhotos']);
    //Definir a foto como principal geralmente a primeira foto
    Route::put('/photos/{id}/main', [PhotoController::class, 'setAsMain']);
    Route::delete('/photos/{id}', [PhotoController::class, 'delete']);

    //INTERACAO

    Route::post('/interactions', [ApiInteractionController::class, 'store']);
    Route::get('/interactions/who-liked-me', [ApiInteractionController::class, 'whoLikedMe']);

    // Rotas de Matches
    Route::get('/matches', [ApiMatchController::class, 'index']);
    Route::delete('/matches/{match}', [ApiMatchController::class, 'destroy']); // Rota para desfazer match


});

