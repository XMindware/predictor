<?php

use App\Jobs\BuildAirportIndicatorsJob;
use App\Jobs\BuildCityIndicatorsJob;
use App\Jobs\BuildRouteIndicatorsJob;
use App\Jobs\FetchFlightDataJob;
use App\Jobs\FetchNewsDataJob;
use App\Jobs\FetchWeatherDataJob;
use App\Jobs\NormalizeFlightPayloadJob;
use App\Jobs\NormalizeNewsPayloadJob;
use App\Jobs\NormalizeWeatherPayloadJob;
use App\Jobs\RecordQueueHeartbeat;
use App\Jobs\WarmPopularRoutesCacheJob;
use App\Models\RawProviderPayload;
use App\Services\OperationsMonitoringService;
use App\Services\StaleDataCheckService;
use App\Support\PlatformHealth;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ingestion:fetch-weather', function (): int {
    FetchWeatherDataJob::dispatch();

    $this->info('Dispatched weather ingestion job.');

    return 0;
})->purpose('Queue weather ingestion');

Artisan::command('ingestion:fetch-flights', function (): int {
    FetchFlightDataJob::dispatch();

    $this->info('Dispatched flight ingestion job.');

    return 0;
})->purpose('Queue flight ingestion');

Artisan::command('ingestion:fetch-news', function (): int {
    FetchNewsDataJob::dispatch();

    $this->info('Dispatched news ingestion job.');

    return 0;
})->purpose('Queue news ingestion');

Artisan::command('ingestion:retry-normalization {--limit=100} {--grace-minutes=10}', function (): int {
    $graceMinutes = max(0, (int) $this->option('grace-minutes'));
    $limit = max(1, (int) $this->option('limit'));

    $payloads = RawProviderPayload::query()
        ->whereNull('normalized_at')
        ->where('fetched_at', '<=', now()->subMinutes($graceMinutes))
        ->orderBy('fetched_at')
        ->limit($limit)
        ->get();

    $dispatched = 0;

    foreach ($payloads as $payload) {
        match ($payload->source_type) {
            'weather' => NormalizeWeatherPayloadJob::dispatch($payload->id),
            'flight', 'flights' => NormalizeFlightPayloadJob::dispatch($payload->id),
            'news' => NormalizeNewsPayloadJob::dispatch($payload->id),
            default => null,
        };

        if (in_array($payload->source_type, ['weather', 'flight', 'flights', 'news'], true)) {
            $dispatched++;
        }
    }

    $this->info("Queued {$dispatched} normalization retry jobs.");

    return 0;
})->purpose('Retry normalization for pending raw provider payloads');

Artisan::command('indicators:build-airports', function (): int {
    BuildAirportIndicatorsJob::dispatch();

    $this->info('Dispatched airport indicator aggregation job.');

    return 0;
})->purpose('Queue airport indicator aggregation');

Artisan::command('indicators:build-cities', function (): int {
    BuildCityIndicatorsJob::dispatch();

    $this->info('Dispatched city indicator aggregation job.');

    return 0;
})->purpose('Queue city indicator aggregation');

Artisan::command('indicators:build-routes', function (): int {
    BuildRouteIndicatorsJob::dispatch();

    $this->info('Dispatched route indicator aggregation job.');

    return 0;
})->purpose('Queue route indicator aggregation');

Artisan::command('health:check-stale-data', function (StaleDataCheckService $staleDataCheckService): int {
    $report = $staleDataCheckService->inspectAndCache();

    $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $report['status'] === 'ok' ? 0 : 1;
})->purpose('Inspect ingestion and indicator freshness');

Artisan::command('monitoring:refresh-alerts', function (OperationsMonitoringService $operationsMonitoringService): int {
    $alerts = $operationsMonitoringService->cacheFailureAlerts();

    $this->info('Cached '.count($alerts).' monitoring alert(s).');

    return 0;
})->purpose('Refresh cached monitoring alerts for failed jobs and stale providers');

Schedule::call(function (): void {
    Cache::forever(PlatformHealth::SCHEDULER_HEARTBEAT_CACHE_KEY, now()->toIso8601String());

    RecordQueueHeartbeat::dispatch();
})
    ->name('health:heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('ingestion:fetch-flights')
    ->name('ingestion:flights')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('ingestion:fetch-weather')
    ->name('ingestion:weather')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

Schedule::command('ingestion:fetch-news')
    ->name('ingestion:news')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

Schedule::command('ingestion:retry-normalization')
    ->name('normalization:retry')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('indicators:build-airports')
    ->name('indicators:airports')
    ->hourlyAt(5)
    ->withoutOverlapping();

Schedule::command('indicators:build-cities')
    ->name('indicators:cities')
    ->hourlyAt(10)
    ->withoutOverlapping();

Schedule::command('indicators:build-routes')
    ->name('indicators:routes')
    ->hourlyAt(15)
    ->withoutOverlapping();

Schedule::command('health:check-stale-data')
    ->name('health:stale-data-check')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('monitoring:refresh-alerts')
    ->name('monitoring:failure-alerts')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::job(new WarmPopularRoutesCacheJob())
    ->name('cache:warm-popular-routes')
    ->hourlyAt(20)
    ->withoutOverlapping();
