<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\TestQueueJob;
use Illuminate\Support\Facades\Queue;

class HorizonQueueTest extends TestCase
{
    public function test_dispatching_test_job_via_http(): void
    {
        $this->get('/test-job')
            ->assertOk()
            ->assertSee('✅ TestQueueJob dispatched!');
    }

    public function test_queue_job_is_dispatched_with_delay(): void
    {
        Queue::fake();

        TestQueueJob::dispatch()->delay(now()->addMinutes(2));

        Queue::assertPushed(TestQueueJob::class, function ($job) {
            return $job->delay !== null;
        });
    }

    public function test_only_this_job_is_dispatched(): void
    {
        Queue::fake();

        TestQueueJob::dispatch();

        $this->assertCount(1, Queue::pushed(TestQueueJob::class));
    }
    public function test_queue_job_is_dispatched_to_default_queue(): void
    {
        Queue::fake();
    
        TestQueueJob::dispatch()->onQueue('default');
    
        Queue::assertPushedOn('default', TestQueueJob::class);
    }
}