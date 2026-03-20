<?php

namespace App\Services\Indicators;

use App\Models\FlightEvent;
use App\Models\NewsEvent;
use App\Models\Route;
use App\Models\RouteIndicator;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;

class RouteIndicatorBuilder
{
    public function build(int $windowHours = 24, ?CarbonInterface $asOf = null): int
    {
        $snapshotTime = ($asOf ?? now())->copy()->startOfHour();
        $windowStart = $snapshotTime->copy()->subHours($windowHours);
        $routes = Route::query()
            ->with(['originAirport.city', 'destinationAirport.city'])
            ->get();
        $createdSnapshots = 0;

        foreach ($routes as $route) {
            $travelDates = FlightEvent::query()
                ->where('route_id', $route->id)
                ->whereBetween('event_time', [$windowStart, $snapshotTime])
                ->whereNotNull('travel_date')
                ->distinct()
                ->orderBy('travel_date')
                ->pluck('travel_date')
                ->map(fn ($travelDate) => Carbon::parse((string) $travelDate)->toDateString())
                ->unique()
                ->values()
                ->all();

            $createdSnapshots += $this->upsertSnapshot($route, $windowHours, $snapshotTime, $windowStart, null);

            foreach ($travelDates as $travelDate) {
                $createdSnapshots += $this->upsertSnapshot($route, $windowHours, $snapshotTime, $windowStart, $travelDate);
            }
        }

        return $createdSnapshots;
    }

    private function upsertSnapshot(
        Route $route,
        int $windowHours,
        CarbonInterface $snapshotTime,
        CarbonInterface $windowStart,
        ?string $travelDate,
    ): int {
        $flightMetrics = $this->flightMetrics($route->id, $windowStart, $snapshotTime, $travelDate);
        $newsMetrics = $this->newsMetrics($route, $windowStart, $snapshotTime);

        RouteIndicator::query()->updateOrCreate(
            [
                'route_id' => $route->id,
                'as_of' => $snapshotTime,
                'travel_date' => $travelDate,
                'window_hours' => $windowHours,
            ],
            [
                'flight_score' => $flightMetrics['score'],
                'news_score' => $newsMetrics['score'],
                'combined_score' => $this->combinedScore([
                    $flightMetrics,
                    $newsMetrics,
                ]),
                'supporting_factors' => [
                    'window_start' => $windowStart->toIso8601String(),
                    'window_end' => $snapshotTime->toIso8601String(),
                    'travel_date' => $travelDate,
                    'flight' => $flightMetrics,
                    'news' => $newsMetrics,
                ],
            ]
        );

        return 1;
    }

    private function flightMetrics(
        int $routeId,
        CarbonInterface $windowStart,
        CarbonInterface $snapshotTime,
        ?string $travelDate,
    ): array {
        $query = FlightEvent::query()
            ->where('route_id', $routeId)
            ->whereBetween('event_time', [$windowStart, $snapshotTime]);

        if ($travelDate === null) {
            $filteredQuery = $query;
        } else {
            $filteredQuery = $query->whereDate('travel_date', $travelDate);
        }

        $count = (clone $filteredQuery)->count();

        return [
            'events_count' => $count,
            'score' => $this->rounded((clone $filteredQuery)->avg('disruption_score')),
            'max_disruption_score' => $this->rounded((clone $filteredQuery)->max('disruption_score')),
            'latest_event_time' => (clone $filteredQuery)->max('event_time'),
        ];
    }

    private function newsMetrics(Route $route, CarbonInterface $windowStart, CarbonInterface $snapshotTime): array
    {
        $airportIds = array_values(array_filter([
            $route->origin_airport_id,
            $route->destination_airport_id,
        ]));
        $cityIds = array_values(array_filter([
            $route->originAirport?->city_id,
            $route->destinationAirport?->city_id,
        ]));

        $query = NewsEvent::query()
            ->whereBetween('published_at', [$windowStart, $snapshotTime])
            ->where(function ($builder) use ($airportIds, $cityIds) {
                if ($airportIds !== []) {
                    $builder->whereIn('airport_id', $airportIds);
                }

                if ($cityIds !== []) {
                    $method = $airportIds === [] ? 'whereIn' : 'orWhereIn';
                    $builder->{$method}('city_id', $cityIds);
                }
            });

        $count = (clone $query)->count();
        $scoreQuery = NewsEvent::query()
            ->whereBetween('published_at', [$windowStart, $snapshotTime])
            ->where(function ($builder) use ($airportIds, $cityIds) {
                if ($airportIds !== []) {
                    $builder->whereIn('airport_id', $airportIds);
                }

                if ($cityIds !== []) {
                    $method = $airportIds === [] ? 'whereIn' : 'orWhereIn';
                    $builder->{$method}('city_id', $cityIds);
                }
            });

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
            'matched_airport_ids' => $airportIds,
            'matched_city_ids' => $cityIds,
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
