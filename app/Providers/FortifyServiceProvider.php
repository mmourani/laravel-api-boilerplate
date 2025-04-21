<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Auto-login in local
        Fortify::authenticateUsing(function ($request) {
            if (app()->environment('local')) {
                return \App\Models\User::first(); // auto-login first user
            }
        });
    }
}