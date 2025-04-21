<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Optional: Ensure database is clean before each test
        $this->artisan('migrate:fresh', ['--seed' => true])->run();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        try {
            DB::rollBack();
        } catch (Throwable $e) {
            // Silent fail if no transaction was started
        }

        DB::disconnect();

        parent::tearDown();
    }
}