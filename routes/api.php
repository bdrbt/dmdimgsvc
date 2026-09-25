<?php

use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function () {
        return response()->json(Auth::user());
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('images', ImageController::class)->only([
        'index', 'show', 'store', 'destroy' // <-- Убедитесь, что 'destroy' указан
    ]);
});

