<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\DB;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected $seed = false;
    protected $migrateFreshUsing = true;
    
    /**
     * Register a callback to be run before the application is destroyed.
     *
     * @param  callable  $callback
     * @return void
     */
    public function beforeApplicationDestroyed(callable $callback)
    {
        // Pass the callback to the parent method
        parent::beforeApplicationDestroyed($callback);
    }

    /**
     * Define hooks to migrate the database before and after each test.
     */
    protected function defineDatabaseMigrations()
    {
        // This method runs before the migrations are run but after the database 
        // connection has been established.
        $this->artisan('db:wipe', ['--drop-views' => true, '--drop-types' => true]);
        
        // Enable foreign key constraints for SQLite before any migrations run
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstances();

        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    protected function tearDown(): void
    {
        if (class_exists(Mockery::class)) {
            if ($container = Mockery::getContainer()) {
                $this->addToAssertionCount($container->mockery_getExpectationCount());
            }
            Mockery::close();
        }

        Facade::clearResolvedInstances();

        restore_error_handler();
        restore_exception_handler();

        parent::tearDown();
    }

    protected function debugQueries(): void
    {
        DB::listen(function ($query) {
            file_put_contents(
                'php://stdout',
                '[' . now() . '] ' . $query->sql . ' [' . implode(', ', $query->bindings) . ']' . PHP_EOL
            );
        });
    }
}
