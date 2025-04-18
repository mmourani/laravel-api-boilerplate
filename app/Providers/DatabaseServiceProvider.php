<?php

namespace App\Providers;

use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (DB::connection() instanceof SQLiteConnection) {
            DB::connection()->getPdo()->exec('PRAGMA foreign_keys=ON');
        }
    }
}

