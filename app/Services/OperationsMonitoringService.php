<?php

namespace App\Services;

use App\Models\FailedJob;
use App\Models\IngestionRun;
use App\Support\PlatformHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OperationsMonitoringService
{
    public function __construct(
        private readonly StaleDataCheckService $staleDataCheckService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardData(int $ingestionLimit = 10, int $failedJobLimit = 10): array
    {
        $staleReport = $this->staleReport();
        $alerts = Cache::get(PlatformHealth::FAILURE_ALERTS_CACHE_KEY);

        return [
            'stats' => [
                'failed_jobs_24h' => FailedJob::query()
                    ->where('failed_at', '>=', now()->subDay())
                    ->count(),
                'failed_ingestion_runs_24h' => IngestionRun::query()
                    ->where('status', 'failed')
                    ->where('finished_at', '>=', now()->subDay())
                    ->count(),
                'stale_warning_count' => count($this->staleWarnings($staleReport)),
                'pending_normalization_count' => data_get($staleReport, 'checks.normalization_backlog.pending_count', 0),
            ],
            'recentIngestionRuns' => IngestionRun::query()
                ->with('provider')
                ->latest('started_at')
                ->limit($ingestionLimit)
                ->get(),
            'recentFailedJobs' => FailedJob::query()
                ->latest('failed_at')
                ->limit($failedJobLimit)
                ->get(),
            'staleWarnings' => $this->staleWarnings($staleReport),
            'alerts' => is_array($alerts) ? $alerts : $this->cacheFailureAlerts(),
        ];
    }

    /**
     * @return list<array{level: string, title: string, message: string, type: string}>
     */
    public function cacheFailureAlerts(): array
    {
        $staleReport = $this->staleReport();
        $alerts = [];

        $failedJobCount = FailedJob::query()
            ->where('failed_at', '>=', now()->subHour())
            ->count();

        if ($failedJobCount > 0) {
            $alerts[] = [
                'level' => 'error',
                'title' => 'Recent failed queue jobs',
                'message' => "{$failedJobCount} job(s) failed in the last hour.",
                'type' => 'failed_jobs',
            ];
        }

        $failedRuns = IngestionRun::query()
            ->with('provider')
            ->where('status', 'failed')
            ->where('finished_at', '>=', now()->subHours(6))
            ->latest('finished_at')
            ->take(3)
            ->get();

        foreach ($failedRuns as $run) {
            $provider = $run->provider?->name ?? 'Unknown provider';

            $alerts[] = [
                'level' => 'error',
                'title' => 'Failed ingestion run',
                'message' => "{$provider} {$run->source_type} ingestion failed: ".Str::limit($run->error_message ?? 'No error message recorded.', 140),
                'type' => 'ingestion_run',
            ];
        }

        foreach ($this->staleWarnings($staleReport) as $warning) {
            $alerts[] = [
                'level' => 'warning',
                'title' => $warning['title'],
                'message' => $warning['message'],
                'type' => 'stale_data',
            ];
        }

        Cache::forever(PlatformHealth::FAILURE_ALERTS_CACHE_KEY, $alerts);

        if ($alerts !== []) {
            logger()->warning('Predictor monitoring alerts detected.', ['alerts' => $alerts]);
        }

        return $alerts;
    }

    /**
     * @param  array<string, mixed>  $staleReport
     * @return list<array{title: string, message: string, key: string}>
     */
    public function staleWarnings(array $staleReport): array
    {
        /** @var array<string, array<string, mixed>> $checks */
        $checks = $staleReport['checks'] ?? [];

        return collect($checks)
            ->filter(fn (array $check, string $key): bool => str_contains($key, 'payloads') && ($check['status'] ?? 'error') !== 'ok')
            ->map(function (array $check, string $key): array {
                $source = Str::headline(str_replace('_payloads', '', $key));
                $ageMinutes = $check['age_minutes'] ?? null;

                return [
                    'key' => $key,
                    'title' => "{$source} provider warning",
                    'message' => $ageMinutes === null
                        ? ($check['message'] ?? 'No provider data recorded yet.')
                        : "Latest payload is {$ageMinutes} minute(s) old.",
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function staleReport(): array
    {
        $cached = Cache::get(PlatformHealth::STALE_DATA_REPORT_CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        return $this->staleDataCheckService->inspectAndCache();
    }
}
