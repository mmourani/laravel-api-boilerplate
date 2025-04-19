<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable DB transactions for SQLite when using Xdebug (coverage)
        if (config('database.default') === 'sqlite') {
            $this->refreshDatabase();
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON;');
        }

        $this->disableTelescope();

        if (class_exists(\Barryvdh\Debugbar\Debugbar::class)) {
            \Barryvdh\Debugbar\Debugbar::disable();
        }
    }

    protected function tearDown(): void
    {
        // Rollback all active transactions
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        // Disconnect all DB connections to release SQLite locks
        DB::disconnect();

        // Close all Mockery expectations
        Mockery::close();

        parent::tearDown();
    }

    protected function disableTelescope(): void
    {
        // Disable config
        config(['telescope.enabled' => false]);
        putenv('TELESCOPE_ENABLED=false');

        // Unbind from container if registered
        if ($this->app->bound('Laravel\Telescope\Telescope')) {
            $this->app->offsetUnset('Laravel\Telescope\Telescope');
        }

        // Stop active recording
        if (class_exists('Laravel\Telescope\Telescope')) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        // Clean up callbacks
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
}
