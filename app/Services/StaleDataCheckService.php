<?php

namespace App\Services;

use App\Models\AirportIndicator;
use App\Models\CityIndicator;
use App\Models\RawProviderPayload;
use App\Models\RouteIndicator;
use App\Support\PlatformHealth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class StaleDataCheckService
{
    /**
     * @return array<string, mixed>
     */
    public function inspectAndCache(): array
    {
        $report = [
            'status' => 'ok',
            'checked_at' => now()->toIso8601String(),
            'checks' => [
                'weather_payloads' => $this->latestPayloadCheck('weather', 60),
                'flight_payloads' => $this->latestPayloadCheck('flight', 30),
                'news_payloads' => $this->latestPayloadCheck('news', 90),
                'normalization_backlog' => $this->normalizationBacklogCheck(10),
                'airport_indicators' => $this->latestIndicatorCheck(AirportIndicator::class, 120),
                'city_indicators' => $this->latestIndicatorCheck(CityIndicator::class, 120),
                'route_indicators' => $this->latestIndicatorCheck(RouteIndicator::class, 120),
            ],
        ];

        $report['status'] = collect($report['checks'])
            ->contains(fn (array $check): bool => $check['status'] !== 'ok')
            ? 'degraded'
            : 'ok';

        Cache::forever(PlatformHealth::STALE_DATA_REPORT_CACHE_KEY, $report);

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function latestPayloadCheck(string $sourceType, int $maxAgeMinutes): array
    {
        /** @var string|null $lastFetchedAt */
        $lastFetchedAt = RawProviderPayload::query()
            ->where('source_type', $sourceType)
            ->max('fetched_at');

        return $this->buildFreshnessCheck($lastFetchedAt, $maxAgeMinutes, 'fetched_at');
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizationBacklogCheck(int $graceMinutes): array
    {
        $count = RawProviderPayload::query()
            ->whereNull('normalized_at')
            ->where('fetched_at', '<=', now()->subMinutes($graceMinutes))
            ->count();

        return [
            'status' => $count === 0 ? 'ok' : 'error',
            'pending_count' => $count,
            'grace_minutes' => $graceMinutes,
        ];
    }

    /**
     * @param  class-string<AirportIndicator|CityIndicator|RouteIndicator>  $modelClass
     * @return array<string, mixed>
     */
    private function latestIndicatorCheck(string $modelClass, int $maxAgeMinutes): array
    {
        /** @var string|null $lastComputedAt */
        $lastComputedAt = $modelClass::query()->max('as_of');

        return $this->buildFreshnessCheck($lastComputedAt, $maxAgeMinutes, 'as_of');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFreshnessCheck(?string $timestamp, int $maxAgeMinutes, string $field): array
    {
        if ($timestamp === null) {
            return [
                'status' => 'error',
                $field => null,
                'max_age_minutes' => $maxAgeMinutes,
                'message' => 'No data recorded yet.',
            ];
        }

        $resolvedTimestamp = Carbon::parse($timestamp);
        $ageMinutes = $resolvedTimestamp->diffInMinutes(now());

        return [
            'status' => $ageMinutes <= $maxAgeMinutes ? 'ok' : 'error',
            $field => $resolvedTimestamp->toIso8601String(),
            'age_minutes' => $ageMinutes,
            'max_age_minutes' => $maxAgeMinutes,
        ];
    }
}
