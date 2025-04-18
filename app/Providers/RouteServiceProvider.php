<?php

namespace App\Providers;

use App\Models\Project;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Add explicit model binding for projects, including soft-deleted projects for restore routes
        Route::bind('project', function ($value) {
            try {
                // Check if the current route is for project restoration
                $isRestorePath = request()->is('api/projects/*/restore');
                $isRestoreMethod = in_array(request()->method(), ['POST', 'PUT', 'PATCH']);
                $isRestoreRoute = $isRestorePath && $isRestoreMethod;
                
                // For restore routes, include trashed models
                if ($isRestoreRoute) {
                    // Find the project including trashed models
                    $project = Project::withTrashed()->find($value);
                    
                    // Return 404 if project doesn't exist
                    if (!$project) {
                        abort(404, "Project not found");
                    }
                    
                    return $project;
                }
                
                // For all other routes, just get active (non-trashed) projects
                return Project::findOrFail($value);
            } catch (\Exception $e) {
                throw $e;
            }
        });
        
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}

