<?php

use Illuminate\Support\Facades\Route;

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status'    => 'OK',
        'timestamp' => now(),
    ]);
});

// Load API modules
require __DIR__ . '/auth.php';
require __DIR__ . '/project.php';
require __DIR__ . '/task.php';

// Load test exception routes (only in local/testing)
if (app()->environment(['local', 'testing'])) {
    require __DIR__ . '/test-exceptions.php';
}