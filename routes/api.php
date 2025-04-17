<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;

Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'OK',
        'message' => 'Laravel API is running 🚀'
    ]);
});

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 👇 Project Routes (CRUD)
    Route::apiResource('projects', ProjectController::class);

    // Nested Task routes
    Route::apiResource('projects.tasks', TaskController::class);
});
