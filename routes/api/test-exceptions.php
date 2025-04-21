<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Exceptions\HttpResponseException;

if (app()->environment(['local', 'testing'])) {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/simulate-model-not-found', function () {
            throw (new ModelNotFoundException())->setModel(Project::class, 999999);
        });

        // Simulate QueryException with predictable message

        Route::get('/simulate-db-error', function () {
            throw new HttpResponseException(response()->json([
                'message' => 'Simulated SQL error',
            ], 500));
        });

        Route::get('/simulate-http-not-found', function () {
            abort(404, 'Not found');
        });

        Route::get('/simulate-generic-error', function () {
            throw new Exception('Server error');
        });

        Route::post('/simulate-validation-error', function () {
            request()->validate(['name' => 'required']);
        });

        Route::get('/simulate-authorization-error', function () {
            throw new AuthorizationException('You are not allowed to access this resource.');
        });

        Route::get('/simulate-throttle', function () {
            throw new ThrottleRequestsException('Too many requests.');
        });
    });

    Route::get('/simulate-auth-error', function () {
        throw new AuthenticationException();
    });
}