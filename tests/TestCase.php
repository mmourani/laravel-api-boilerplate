<?php

namespace Tests;

use Throwable;
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
    }

    protected function tearDown(): void
    {
        Mockery::close();

        try {
            DB::rollBack(); // In case a transaction was started
        } catch (Throwable $e) {
            // Prevent errors if no active transaction
        }

        DB::disconnect();

        parent::tearDown();
    }


    // Prevent Laravel from wrapping tests in transactions
    protected function beginDatabaseTransaction()
    {
        // Override to skip automatic transaction
    }
}
