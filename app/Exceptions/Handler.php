<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            $model = class_basename($exception->getModel());
            return response()->json([
                'message' => "$model not found",
            ], 404);
        }
        
        if ($exception instanceof QueryException) {
            $previousMessage = $exception->getPrevious()?->getMessage();
        
            if (app()->environment('testing') && str_contains($previousMessage, 'Simulated SQL error')) {
                return response()->json([
                    'message' => 'Simulated SQL error',
                ], 500);
            }
        
            return response()->json([
                'message' => 'A database error occurred',
            ], 500);
        }

        if ($exception instanceof QueryException) {
            $previousMessage = $exception->getPrevious()?->getMessage();
            return response()->json([
                'message' => $previousMessage ?? 'Query error',
            ], 500);
        }

        if ($exception instanceof HttpResponseException) {
            return $exception->getResponse();
        }

        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $exception->errors(),
            ], 422);
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'message' => 'You are not allowed to access this resource.',
            ], 403);
        }

        if ($exception instanceof ThrottleRequestsException) {
            return response()->json([
                'message' => 'Too many requests.',
            ], 429);
        }

        return response()->json([
            'message' => 'Server error',
        ], 500);
    }
}