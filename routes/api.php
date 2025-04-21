<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are automatically prefixed with /api
| They are loaded by the RouteServiceProvider within the "api" middleware group.
*/

Route::get('/health', function () {
    return response()->json([
        'status'    => 'OK',
        'timestamp' => now(),
    ]);
});

// Public Auth Routes
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Project Routes
    Route::apiResource('projects', ProjectController::class);
    Route::patch('/projects/{project}/restore', [ProjectController::class, 'restore']);

    // Task Routes
    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('projects.tasks', TaskController::class);

    // Authenticated User Route
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
