<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Jobs\TestQueueJob;

Route::get('/', function () {
    $response = Cache::remember('root-status-response', now()->addMinutes(10), function () {
        $data = [
            'status' => 'Backend & Admin API running ✅',
            'env'    => app()->environment(),
        ];

        if (!app()->isProduction()) {
            $data['app']     = config('app.name');
            $data['version'] = app()->version();
            $data['nova']    = url(config('nova.path', 'nova'));
            $data['debug']   = config('app.debug');
        }

        return $data;
    });

    return response()->json($response, 200)->withHeaders([
        'X-App-Name'    => config('app.name'),
        'X-App-Version' => app()->version(),
        'X-Environment' => app()->environment(),
        'X-Cache'       => 'HIT',
    ]);
});

Route::get('/ping', fn () => response('pong', 200));

Route::get('/version', function () {
    return response()->json([
        'app'     => config('app.name'),
        'version' => app()->version(),
        'build'   => env('APP_BUILD', 'local'),
        'commit'  => env('APP_COMMIT', 'dev'),
        'env'     => app()->environment(),
    ]);
});

// 👇 Removed /login redirect to avoid infinite loop
if (app()->environment('local')) {
    // Route::get('/login', fn () => redirect('/nova')); // ❌ causes redirect loop
    Route::post('/logout', fn () => redirect('/'));
}

if (app()->environment('local', 'testing')) {
    Route::get('/test-job', function () {
        TestQueueJob::dispatch();
        return '✅ TestQueueJob dispatched!';
    });
}