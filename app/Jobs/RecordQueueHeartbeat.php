<?php

namespace App\Jobs;

use App\Support\PlatformHealth;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RecordQueueHeartbeat implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onConnection('database');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Cache::forever(PlatformHealth::QUEUE_WORKER_HEARTBEAT_CACHE_KEY, now()->toIso8601String());
    }
}
