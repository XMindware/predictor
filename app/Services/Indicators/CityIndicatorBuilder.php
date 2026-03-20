<?php

namespace App\Services\Indicators;

use App\Models\City;
use App\Models\CityIndicator;
use App\Models\NewsEvent;
use App\Models\WeatherEvent;
use Carbon\CarbonInterface;

class CityIndicatorBuilder
{
    public function build(int $windowHours = 24, ?CarbonInterface $asOf = null): int
    {
        $snapshotTime = ($asOf ?? now())->copy()->startOfHour();
        $windowStart = $snapshotTime->copy()->subHours($windowHours);
        $cities = City::query()->get();

        foreach ($cities as $city) {
            $weatherMetrics = $this->weatherMetrics($city->id, $windowStart, $snapshotTime);
            $newsMetrics = $this->newsMetrics($city->id, $windowStart, $snapshotTime);

            CityIndicator::query()->updateOrCreate(
                [
                    'city_id' => $city->id,
                    'as_of' => $snapshotTime,
                    'window_hours' => $windowHours,
                ],
                [
                    'weather_score' => $weatherMetrics['score'],
                    'news_score' => $newsMetrics['score'],
                    'combined_score' => $this->combinedScore([
                        $weatherMetrics,
                        $newsMetrics,
                    ]),
                    'supporting_factors' => [
                        'window_start' => $windowStart->toIso8601String(),
                        'window_end' => $snapshotTime->toIso8601String(),
                        'weather' => $weatherMetrics,
                        'news' => $newsMetrics,
                    ],
                ]
            );
        }

        return $cities->count();
    }

    private function weatherMetrics(int $cityId, CarbonInterface $windowStart, CarbonInterface $snapshotTime): array
    {
        $query = WeatherEvent::query()
            ->where('city_id', $cityId)
            ->whereBetween('event_time', [$windowStart, $snapshotTime]);

        $count = (clone $query)->count();

        return [
            'events_count' => $count,
            'score' => $this->rounded((clone $query)->avg('severity_score')),
            'max_severity_score' => $this->rounded((clone $query)->max('severity_score')),
            'latest_event_time' => (clone $query)->max('event_time'),
        ];
    }

    private function newsMetrics(int $cityId, CarbonInterface $windowStart, CarbonInterface $snapshotTime): array
    {
        $query = NewsEvent::query()
            ->where('city_id', $cityId)
            ->whereBetween('published_at', [$windowStart, $snapshotTime]);

        $count = (clone $query)->count();
        $scoreQuery = NewsEvent::query()
            ->where('city_id', $cityId)
            ->whereBetween('published_at', [$windowStart, $snapshotTime]);

        $score = $count > 0
            ? $this->rounded(
                ((float) ((clone $scoreQuery)->avg('severity_score') ?? 0)
                    + (float) ((clone $scoreQuery)->avg('relevance_score') ?? 0)) / 2
            )
            : 0.0;

        return [
            'events_count' => $count,
            'score' => $score,
            'max_severity_score' => $this->rounded((clone $query)->max('severity_score')),
            'max_relevance_score' => $this->rounded((clone $query)->max('relevance_score')),
            'latest_published_at' => (clone $query)->max('published_at'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $metrics
     */
    private function combinedScore(array $metrics): float
    {
        $scores = collect($metrics)
            ->filter(fn (array $metric) => (int) ($metric['events_count'] ?? 0) > 0)
            ->pluck('score')
            ->map(fn ($score) => (float) $score)
            ->values();

        if ($scores->isEmpty()) {
            return 0.0;
        }

        return $this->rounded($scores->avg());
    }

    private function rounded(float|int|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
