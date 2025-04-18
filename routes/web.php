<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function (): JsonResponse {
    return response()->json([
        'application' => 'Laravel SaaS Boilerplate',
        'version' => app()->version(),
        'environment' => app()->environment(),
    ]);
});
