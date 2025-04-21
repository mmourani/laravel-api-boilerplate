<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Illuminate\Support\Facades\Auth;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->gate();

        // ✅ Auto-login admin on every request (Octane-compatible)
        if (app()->isLocal()) {
            Nova::serving(function () {
                // Octane needs re-auth on each request due to reused workers
                $admin = User::where('email', 'admin@dev.local')->first();

                if ($admin) {
                    Auth::setUser($admin);
                }
            });
        }
    }

    protected function routes(): void
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->withEmailVerificationRoutes()
            ->register();
    }

    protected function gate(): void
    {
        Gate::define('viewNova', function (?User $user = null) {
            return app()->isLocal() || ($user && $user->is_admin);
        });
    }

    public function dashboards(): array
    {
        return [
            new \App\Nova\Dashboards\Main(),
        ];
    }

    public function tools(): array
    {
        return [];
    }
}