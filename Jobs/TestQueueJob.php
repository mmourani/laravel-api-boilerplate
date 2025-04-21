<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// ✅ TestQueueJob - runs on default queue
class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'default';

    public function handle(): void
    {
        Log::info('✅ Horizon TestQueueJob ran successfully.');
    }
}

// 🤖 ProcessAIResultJob - queued under ai-tasks
class ProcessAIResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'ai-tasks';

    public function __construct(public array $data)
    {
    }

    public function handle(): void
    {
        // Handle AI result processing logic here
        Log::info('🤖 Processing AI Result Job', $this->data);
    }
}

// 🔐 SyncPaymentsJob - critical queue for high-priority logic
class SyncPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'critical';

    public function __construct(public int $paymentId)
    {
    }

    public function handle(): void
    {
        // Handle payment syncing logic here
        Log::info("🔐 Syncing payment ID: {$this->paymentId}");
    }
}
