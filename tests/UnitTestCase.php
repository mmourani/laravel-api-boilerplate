<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Facade;
use Mockery;

/**
 * Base TestCase for Unit Tests
 * 
 * This test case is specifically designed for unit tests that don't interact
 * with the database. It excludes the RefreshDatabase trait to avoid transaction
 * issues when using mocks and stubs to isolate components for testing.
 */
abstract class UnitTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstances();

        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
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
}

