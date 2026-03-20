<?php

namespace App\Support;

final class PlatformHealth
{
    public const QUEUE_WORKER_HEARTBEAT_CACHE_KEY = 'health:queue-worker:last_seen';
    public const SCHEDULER_HEARTBEAT_CACHE_KEY = 'health:scheduler:last_seen';
    public const STALE_DATA_REPORT_CACHE_KEY = 'health:stale-data:report';
    public const FAILURE_ALERTS_CACHE_KEY = 'health:failure-alerts';
}
