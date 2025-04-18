<?php

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This file defines all API endpoints available to your application.
| Public and authenticated routes are clearly separated for security.
|
*/

// Health check endpoint
Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'OK',
        'message' => 'Laravel API is running 🚀'
    ]);
});

// -----------------------------------------------------------------------------
// Public Routes (No authentication required)
// -----------------------------------------------------------------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// -----------------------------------------------------------------------------
// Protected Routes (Require auth:sanctum)
// -----------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {

    // -------------------------------------------------------------------------
    // Authenticated user session info
    // -------------------------------------------------------------------------
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // -------------------------------------------------------------------------
    // Project Restore Routes
    // -------------------------------------------------------------------------
    // Note: These must be defined BEFORE the apiResource route
    // to avoid conflicts with implicit route model binding
    // -------------------------------------------------------------------------
    Route::post('projects/{id}/restore', [ProjectController::class, 'restore'])
        ->name('projects.restore');

    // Optional alternative verbs for REST-style clients
    Route::patch('projects/{id}/restore', [ProjectController::class, 'restore']);
    Route::put('projects/{id}/restore', [ProjectController::class, 'restore']);

    // -------------------------------------------------------------------------
    // Project & Task Resource Routes
    // -------------------------------------------------------------------------
    Route::apiResource('projects', ProjectController::class);

    // Nested resource: tasks within a project
    Route::apiResource('projects.tasks', TaskController::class);
});
