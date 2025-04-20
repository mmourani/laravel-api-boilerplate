<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
            // Run fresh migrations manually
            $this->artisan('migrate:fresh', ['--seed' => true])->run();
            DB::statement('PRAGMA foreign_keys=ON;');
        }

        $this->disableTelescope();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        try {
            DB::rollBack(); // In case a transaction was started
        } catch (\Throwable $e) {
            // Prevent errors if no active transaction
        }

        DB::disconnect();

        parent::tearDown();
    }

    protected function disableTelescope(): void
    {
        config(['telescope.enabled' => false]);
        putenv('TELESCOPE_ENABLED=false');

        if ($this->app->bound('Laravel\Telescope\Telescope')) {
            $this->app->offsetUnset('Laravel\Telescope\Telescope');
        }

        if (class_exists('Laravel\Telescope\Telescope')) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        $this->beforeApplicationDestroyed(function () {
            if (class_exists('Laravel\Telescope\Telescope')) {
                $reflection = new \ReflectionClass($this->app);
                if ($reflection->hasProperty('terminatingCallbacks')) {
                    $property = $reflection->getProperty('terminatingCallbacks');
                    $property->setAccessible(true);
                    $property->setValue($this->app, []);
                }
            }
        });
    }

    // Prevent Laravel from wrapping tests in transactions
    protected function beginDatabaseTransaction()
    {
        // Override to skip automatic transaction
    }
}
