<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;// ✅ CORRECT LOCATION

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];
    // App\Exceptions\Handler.php

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Project not found',
            ], 404);
        }

        if ($exception instanceof QueryException && app()->environment('testing')) {
            return response()->json([
                'message' => $exception->getPrevious()?->getMessage() ?? 'Query error',
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
