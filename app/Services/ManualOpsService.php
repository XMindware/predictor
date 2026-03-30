<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\City;
use App\Models\CityIndicator;
use App\Models\FlightEvent;
use App\Models\IngestionRun;
use App\Models\NewsEvent;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Models\WatchTarget;
use App\Models\WeatherEvent;
use App\Services\Indicators\AirportIndicatorBuilder;
use App\Services\Indicators\CityIndicatorBuilder;
use App\Services\Indicators\RouteIndicatorBuilder;
use App\Services\MonitoredRouteService;
use App\Services\Providers\Adapters\ConfiguredHttpProvider;
use App\Services\Providers\Normalizers\FlightPayloadNormalizer;
use App\Services\Providers\Normalizers\NewsPayloadNormalizer;
use App\Services\Providers\Normalizers\WeatherPayloadNormalizer;
use App\Services\Providers\ProviderAdapterRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class ManualOpsService
{
    public function __construct(
        private readonly ProviderAdapterRegistry $providerAdapterRegistry,
        private readonly WeatherPayloadNormalizer $weatherPayloadNormalizer,
        private readonly FlightPayloadNormalizer $flightPayloadNormalizer,
        private readonly NewsPayloadNormalizer $newsPayloadNormalizer,
        private readonly AirportIndicatorBuilder $airportIndicatorBuilder,
        private readonly CityIndicatorBuilder $cityIndicatorBuilder,
        private readonly RouteIndicatorBuilder $routeIndicatorBuilder,
        private readonly MonitoredRouteService $monitoredRouteService,
        private readonly RiskScoringService $riskScoringService,
    ) {
    }

    /**
     * @return array{city: string, providers: int, payloads: int, normalized_events: int}
     */
    public function refetchWeatherForCity(City $city): array
    {
        $watchTargets = WatchTarget::query()
            ->with(['originCity', 'originAirport', 'destinationCity', 'destinationAirport'])
            ->where('enabled', true)
            ->where('origin_city_id', $city->id)
            ->orderByDesc('monitoring_priority')
            ->orderBy('id')
            ->get();

        if ($watchTargets->isEmpty()) {
            throw new RuntimeException('No enabled watch targets found for the selected city.');
        }

        $result = $this->runManualIngestion('weather', 'weather', $watchTargets);

        return [
            'city' => $city->name,
            ...$result,
        ];
    }

    /**
     * @return array{city: string, providers: int, payloads: int, normalized_events: int}
     */
    public function refetchNewsForCity(City $city): array
    {
        $watchTargets = WatchTarget::query()
            ->with(['originCity', 'originAirport', 'destinationCity', 'destinationAirport'])
            ->where('enabled', true)
            ->where('origin_city_id', $city->id)
            ->orderByDesc('monitoring_priority')
            ->orderBy('id')
            ->get();

        if ($watchTargets->isEmpty()) {
            throw new RuntimeException('No enabled watch targets found for the selected city.');
        }

        $result = $this->runManualIngestion('news', 'news', $watchTargets);

        return [
            'city' => $city->name,
            ...$result,
        ];
    }

    /**
     * @return array{
     *     route: string,
     *     providers: int,
     *     payloads: int,
     *     normalized_events: int,
     *     fetched_flights: array<int, array<string, mixed>>
     * }
     */
    public function refetchFlightsForRoute(Route $route): array
    {
        $route->loadMissing(['originAirport.city', 'destinationAirport.city']);

        $watchTargets = WatchTarget::query()
            ->with(['originCity', 'originAirport', 'destinationCity', 'destinationAirport'])
            ->where('enabled', true)
            ->where(function ($query) use ($route) {
                $query
                    ->where(function ($exactAirports) use ($route) {
                        $exactAirports
                            ->where('origin_airport_id', $route->origin_airport_id)
                            ->where('destination_airport_id', $route->destination_airport_id);
                    })
                    ->orWhere(function ($cityFallback) use ($route) {
                        $cityFallback
                            ->where('origin_city_id', $route->originAirport?->city_id)
                            ->where('destination_city_id', $route->destinationAirport?->city_id);
                    });
            })
            ->orderByDesc('monitoring_priority')
            ->orderBy('id')
            ->get();

        if ($watchTargets->isEmpty()) {
            throw new RuntimeException('No enabled watch targets found for the selected route.');
        }

        $result = $this->runManualIngestion('flights', 'flights', $watchTargets);

        return [
            'route' => sprintf(
                '%s → %s',
                $route->originAirport?->iata ?? 'n/a',
                $route->destinationAirport?->iata ?? 'n/a'
            ),
            'fetched_flights' => $this->fetchedFlightsForPayloads(
                $route,
                collect($result['payload_ids'] ?? [])->filter()->map(fn ($id): int => (int) $id)->values(),
            ),
            ...$result,
        ];
    }

    /**
     * @return array{airport_snapshots: int, city_snapshots: int, route_snapshots: int}
     */
    public function rebuildIndicators(): array
    {
        return [
            'airport_snapshots' => $this->airportIndicatorBuilder->build(),
            'city_snapshots' => $this->cityIndicatorBuilder->build(),
            'route_snapshots' => $this->routeIndicatorBuilder->build(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function recomputeRisk(Route $route, string $travelDate): array
    {
        $route->loadMissing(['originAirport', 'destinationAirport']);

        if (! $route->originAirport || ! $route->destinationAirport) {
            throw new RuntimeException('The selected route must have origin and destination airports.');
        }

        return $this->riskScoringService->calculate(
            $route->originAirport,
            $route->destinationAirport,
            $travelDate,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function queryCityScore(City $city, ?int $timeWindowHours = null): array
    {
        $city->loadMissing(['country', 'airports']);
        $baseAirport = $this->baseAirport();
        $routesToBaseAirport = $this->monitoredRouteService->prioritized(
            $this->routesToBaseAirport($city, $baseAirport),
            (int) config('operations.v1_route_risk_limit', 10),
        );

        if ($routesToBaseAirport->isEmpty()) {
            throw new RuntimeException('No monitored routes into the base airport are available for the selected city.');
        }

        $windowHours = $timeWindowHours === null
            ? (int) config('operations.v1_risk_window_hours', 72)
            : min((int) config('operations.v1_risk_window_hours', 72), max(1, $timeWindowHours));
        $windowStart = now();
        $windowEnd = now()->copy()->addHours($windowHours);
        $travelDates = collect(range(
            0,
            $windowStart->copy()->startOfDay()->diffInDays($windowEnd->copy()->startOfDay())
        ))
            ->map(fn (int $offset): Carbon => $windowStart->copy()->startOfDay()->addDays($offset))
            ->values();

        $assessments = $routesToBaseAirport
            ->flatMap(function (Route $route) use ($travelDates): Collection {
                $route->loadMissing(['originAirport.city', 'destinationAirport.city']);

                return $travelDates->map(function (Carbon $travelDate) use ($route): array {
                    $assessment = $this->riskScoringService->calculate(
                        $route->originAirport,
                        $route->destinationAirport,
                        $travelDate->toDateString(),
                    );

                    $assessment['route_label'] = sprintf(
                        '%s → %s',
                        $route->originAirport?->iata ?? 'n/a',
                        $route->destinationAirport?->iata ?? 'n/a'
                    );
                    $assessment['travel_date'] = $travelDate->toDateString();

                    return $assessment;
                });
            })
            ->sortByDesc('score')
            ->values();

        $primaryAssessment = $assessments->first();

        if (! $primaryAssessment) {
            throw new RuntimeException('No risk assessments could be produced for the selected city.');
        }

        return [
            'city' => $city->name,
            'country' => $city->country?->name,
            'base_airport_iata' => $baseAirport->iata,
            'window_hours' => $windowHours,
            'from_time' => $windowStart->toIso8601String(),
            'to_time' => $windowEnd->toIso8601String(),
            'routes_evaluated' => $routesToBaseAirport->count(),
            'assessments_evaluated' => $assessments->count(),
            'assessment_type' => $primaryAssessment['assessment_type'],
            'scoring_mode' => $primaryAssessment['scoring_mode'],
            'product_framing' => $primaryAssessment['product_framing'],
            'primary_assessment' => $primaryAssessment,
            'source_details' => $this->cityRiskSourceDetails(
                $city,
                $routesToBaseAirport,
                $windowStart,
                $windowEnd,
            ),
            'daily_outlook' => $assessments
                ->groupBy('travel_date')
                ->map(function (Collection $items, string $travelDate): array {
                    $top = $items->sortByDesc('score')->first();

                    return [
                        'travel_date' => $travelDate,
                        'route_label' => $top['route_label'],
                        'score' => $top['score'],
                        'risk_level' => $top['risk_level'],
                        'recommended_action' => $top['recommended_action']['code'] ?? null,
                    ];
                })
                ->sortBy('travel_date')
                ->values()
                ->all(),
            'route_outlook' => $assessments
                ->groupBy('route_label')
                ->map(function (Collection $items, string $routeLabel): array {
                    $top = $items->sortByDesc('score')->first();

                    return [
                        'route_label' => $routeLabel,
                        'top_score' => $top['score'],
                        'risk_level' => $top['risk_level'],
                        'travel_date' => $top['travel_date'],
                    ];
                })
                ->sortByDesc('top_score')
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Route>  $routesToBaseAirport
     * @return array<string, array<string, mixed>>
     */
    private function cityRiskSourceDetails(
        City $city,
        Collection $routesToBaseAirport,
        Carbon $windowStart,
        Carbon $windowEnd,
    ): array {
        $airportIds = $city->airports->pluck('id')->filter()->values();
        $weatherEvents = WeatherEvent::query()
            ->whereBetween('forecast_for', [$windowStart, $windowEnd])
            ->where(function ($query) use ($airportIds, $city): void {
                $query->where('city_id', $city->id);

                if ($airportIds->isNotEmpty()) {
                    $query->orWhereIn('airport_id', $airportIds);
                }
            })
            ->get();

        $newsEvents = NewsEvent::query()
            ->whereBetween('published_at', [$windowStart, $windowEnd])
            ->where(function ($query) use ($airportIds, $city): void {
                $query->where('city_id', $city->id);

                if ($airportIds->isNotEmpty()) {
                    $query->orWhereIn('airport_id', $airportIds);
                }
            })
            ->get();

        $flightEvents = FlightEvent::query()
            ->whereIn('route_id', $routesToBaseAirport->pluck('id'))
            ->whereBetween('travel_date', [
                $windowStart->copy()->startOfDay()->toDateString(),
                $windowEnd->copy()->endOfDay()->toDateString(),
            ])
            ->get();

        return [
            'weather' => [
                'source' => 'weather_events',
                'events_found' => $weatherEvents->count(),
                'top_condition' => $weatherEvents->groupBy('condition_code')
                    ->sortByDesc(fn (Collection $items): int => $items->count())
                    ->keys()
                    ->first(),
                'average_temperature' => $weatherEvents->isEmpty()
                    ? null
                    : round((float) $weatherEvents->avg('temperature'), 1),
            ],
            'news' => [
                'source' => 'news_events',
                'articles_found' => $newsEvents->count(),
                'top_category' => $newsEvents->groupBy('category')
                    ->sortByDesc(fn (Collection $items): int => $items->count())
                    ->keys()
                    ->first(),
            ],
            'flights' => [
                'source' => 'flight_events',
                'total_records' => $flightEvents->count(),
                'delayed_records' => $flightEvents
                    ->filter(fn (FlightEvent $event): bool => (float) ($event->delay_average_minutes ?? 0) > 0)
                    ->count(),
                'cancelled_records' => $flightEvents
                    ->filter(fn (FlightEvent $event): bool => (float) ($event->cancellation_rate ?? 0) > 0)
                    ->count(),
                'average_delay_minutes' => $flightEvents->isEmpty()
                    ? null
                    : round((float) $flightEvents->avg('delay_average_minutes'), 1),
                'airlines' => $flightEvents
                    ->groupBy(fn (FlightEvent $event): string => $event->airline_code ?: 'UNKNOWN')
                    ->map(fn (Collection $items, string $airlineCode): array => [
                        'code' => $airlineCode,
                        'records' => $items->count(),
                    ])
                    ->sortByDesc('records')
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Route>  $routesToBaseAirport
     * @return array<string, mixed>
     */
    private function routeBackedCityScore(City $city, Airport $baseAirport, Collection $routesToBaseAirport, ?string $date): array
    {
        $routeLabel = $routesToBaseAirport->count() === 1
            ? sprintf(
                '%s → %s',
                $routesToBaseAirport->first()?->originAirport?->iata ?? $city->name,
                $baseAirport->iata
            )
            : sprintf('%d active routes into %s', $routesToBaseAirport->count(), $baseAirport->iata);

        if ($date !== null && $date !== '') {
            $requestedDate = Carbon::parse($date)->toDateString();
            $metrics = $this->routeIndicatorMetricsForTravelDate($routesToBaseAirport, $requestedDate);

            if ($metrics['combined_score'] === null) {
                $metrics = $this->overallRouteIndicatorMetrics($routesToBaseAirport);
            }

            if ($metrics['combined_score'] === null) {
                throw new RuntimeException('No route indicator snapshot is available for the selected city and date.');
            }

            return [
                'mode' => 'single',
                'score_scope' => 'route',
                'city' => $city->name,
                'country' => $city->country?->name,
                'base_airport_iata' => $baseAirport->iata,
                'route_label' => $routeLabel,
                'requested_date' => $requestedDate,
                'snapshot_as_of' => $metrics['snapshot_as_of'],
                'window_hours' => $metrics['window_hours'],
                'combined_score' => $metrics['combined_score'] ?? 0.0,
                'news_score' => $metrics['news_score'] ?? 0.0,
                'flight_score' => $metrics['flight_score'] ?? 0.0,
                'news_events' => $metrics['news_events'],
                'flight_events' => $metrics['flight_events'],
            ];
        }

        $rangeStart = Carbon::today();
        $rangeEnd = $rangeStart->copy()->addDays(29);
        $baselineMetrics = $this->overallRouteIndicatorMetrics($routesToBaseAirport);

        $points = collect(range(0, 29))
            ->map(function (int $offset) use ($rangeStart, $routesToBaseAirport): array {
                $day = $rangeStart->copy()->addDays($offset);
                $metrics = $this->routeIndicatorMetricsForTravelDate($routesToBaseAirport, $day->toDateString());

                return [
                    'label' => $day->format('M d'),
                    'date' => $day->toDateString(),
                    'combined_score' => $metrics['combined_score'],
                    'news_score' => $metrics['news_score'],
                    'flight_score' => $metrics['flight_score'],
                    'news_events' => $metrics['news_events'],
                    'flight_events' => $metrics['flight_events'],
                ];
            })
            ->map(function (array $point) use ($baselineMetrics): array {
                if ($point['combined_score'] !== null) {
                    return $point;
                }

                return [
                    'label' => $point['label'],
                    'date' => $point['date'],
                    'combined_score' => $baselineMetrics['combined_score'] ?? 0.0,
                    'news_score' => $baselineMetrics['news_score'] ?? 0.0,
                    'flight_score' => $baselineMetrics['flight_score'] ?? 0.0,
                    'news_events' => $baselineMetrics['news_events'],
                    'flight_events' => $baselineMetrics['flight_events'],
                ];
            });

        if ($points->every(fn (array $point): bool => (float) $point['combined_score'] === 0.0)) {
            throw new RuntimeException('No route indicator snapshots are available for the selected city.');
        }

        return [
            'mode' => 'range',
            'score_scope' => 'route',
            'city' => $city->name,
            'country' => $city->country?->name,
            'base_airport_iata' => $baseAirport->iata,
            'route_label' => $routeLabel,
            'range_kind' => 'projection',
            'projection_days' => 30,
            'from_date' => $rangeStart->toDateString(),
            'to_date' => $rangeEnd->toDateString(),
            'baseline_snapshot_as_of' => $baselineMetrics['snapshot_as_of'],
            'points' => $points->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Route>  $routesToBaseAirport
     * @return array<string, mixed>
     */
    private function cityBackedCityScore(City $city, Airport $baseAirport, Collection $routesToBaseAirport, ?string $date): array
    {
        if ($date !== null && $date !== '') {
            $asOf = Carbon::parse($date)->endOfDay();

            $indicator = CityIndicator::query()
                ->where('city_id', $city->id)
                ->where('as_of', '<=', $asOf)
                ->latest('as_of')
                ->first();

            if (! $indicator) {
                throw new RuntimeException('No city indicator snapshot is available for the selected city and date.');
            }

            $flightMetrics = $this->flightMetricsForTravelDate($routesToBaseAirport, Carbon::parse($date)->toDateString());
            $weatherMetrics = [
                'score' => $indicator->weather_score,
                'events_count' => (int) ($indicator->supporting_factors['weather']['events_count'] ?? 0),
            ];
            $newsMetrics = [
                'score' => $indicator->news_score,
                'events_count' => (int) ($indicator->supporting_factors['news']['events_count'] ?? 0),
            ];

            return [
                'mode' => 'single',
                'score_scope' => 'city',
                'city' => $city->name,
                'country' => $city->country?->name,
                'base_airport_iata' => $baseAirport->iata,
                'requested_date' => Carbon::parse($date)->toDateString(),
                'snapshot_as_of' => $indicator->as_of?->toIso8601String(),
                'window_hours' => $indicator->window_hours,
                'combined_score' => $this->combinedScore([
                    $weatherMetrics,
                    $newsMetrics,
                    $flightMetrics,
                ]),
                'weather_score' => $indicator->weather_score,
                'news_score' => $indicator->news_score,
                'flight_score' => $flightMetrics['score'] ?? 0.0,
                'weather_events' => $weatherMetrics['events_count'],
                'news_events' => $newsMetrics['events_count'],
                'flight_events' => $flightMetrics['events_count'],
            ];
        }

        $latestIndicator = CityIndicator::query()
            ->where('city_id', $city->id)
            ->latest('as_of')
            ->first();

        $rangeStart = Carbon::today();
        $rangeEnd = $rangeStart->copy()->addDays(29);
        $airportIds = $city->airports->pluck('id')->filter()->values();

        $forecastWeatherByDate = WeatherEvent::query()
            ->whereBetween('forecast_for', [$rangeStart->copy()->startOfDay(), $rangeEnd->copy()->endOfDay()])
            ->where(function ($query) use ($airportIds, $city): void {
                $query->where('city_id', $city->id);

                if ($airportIds->isNotEmpty()) {
                    $query->orWhereIn('airport_id', $airportIds);
                }
            })
            ->orderBy('forecast_for')
            ->get()
            ->groupBy(fn (WeatherEvent $event): ?string => $event->forecast_for?->toDateString());

        if (! $latestIndicator && $forecastWeatherByDate->isEmpty()) {
            $hasFlightSignal = $this->overallFlightMetrics($routesToBaseAirport)['events_count'] > 0;

            if (! $hasFlightSignal) {
                throw new RuntimeException('No city indicator snapshots, route flight data, or forecast weather events are available for the selected city.');
            }
        }

        $baselineWeatherScore = $latestIndicator?->weather_score;
        $baselineNewsScore = $latestIndicator?->news_score;
        $baselineWeatherEvents = $latestIndicator?->supporting_factors['weather']['events_count'] ?? 0;
        $baselineNewsEvents = $latestIndicator?->supporting_factors['news']['events_count'] ?? 0;
        $baselineFlightMetrics = $this->overallFlightMetrics($routesToBaseAirport);

        return [
            'mode' => 'range',
            'score_scope' => 'city',
            'city' => $city->name,
            'country' => $city->country?->name,
            'base_airport_iata' => $baseAirport->iata,
            'range_kind' => 'projection',
            'projection_days' => 30,
            'from_date' => $rangeStart->toDateString(),
            'to_date' => $rangeEnd->toDateString(),
            'baseline_snapshot_as_of' => $latestIndicator?->as_of?->toIso8601String(),
            'points' => collect(range(0, 29))
                ->map(function (int $offset) use (
                    $baselineNewsEvents,
                    $baselineNewsScore,
                    $baselineFlightMetrics,
                    $baselineWeatherEvents,
                    $baselineWeatherScore,
                    $forecastWeatherByDate,
                    $routesToBaseAirport,
                    $rangeStart
                ): array {
                    $day = $rangeStart->copy()->addDays($offset);
                    $date = $day->toDateString();
                    $weatherEvents = $forecastWeatherByDate->get($date, collect());
                    $weatherScore = $weatherEvents->isNotEmpty()
                        ? round((float) $weatherEvents->avg('severity_score'), 2)
                        : $baselineWeatherScore;
                    $weatherEventCount = $weatherEvents->isNotEmpty()
                        ? $weatherEvents->count()
                        : $baselineWeatherEvents;
                    $flightMetrics = $this->flightMetricsForTravelDate($routesToBaseAirport, $date);

                    if ((int) $flightMetrics['events_count'] === 0) {
                        $flightMetrics = $baselineFlightMetrics;
                    }

                    $scores = collect([
                        ['score' => $weatherScore, 'events_count' => $weatherEventCount],
                        ['score' => $baselineNewsScore, 'events_count' => $baselineNewsEvents],
                        $flightMetrics,
                    ]);

                    return [
                        'label' => $day->format('M d'),
                        'date' => $date,
                        'combined_score' => $this->combinedScore($scores->all()),
                        'weather_score' => $weatherScore ?? 0.0,
                        'news_score' => $baselineNewsScore ?? 0.0,
                        'flight_score' => $flightMetrics['score'] ?? 0.0,
                        'weather_events' => $weatherEventCount,
                        'news_events' => $baselineNewsEvents,
                        'flight_events' => $flightMetrics['events_count'] ?? 0,
                    ];
                })
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Route>  $routes
     * @return array{
     *     combined_score: ?float,
     *     flight_score: ?float,
     *     news_score: ?float,
     *     flight_events: int,
     *     news_events: int,
     *     window_hours: ?int,
     *     snapshot_as_of: ?string
     * }
     */
    private function routeIndicatorMetricsForTravelDate(Collection $routes, string $travelDate): array
    {
        return $this->aggregateRouteIndicators(
            RouteIndicator::query()
                ->whereIn('route_id', $routes->pluck('id'))
                ->whereDate('travel_date', $travelDate)
                ->orderByDesc('as_of')
                ->get()
                ->groupBy('route_id')
                ->map(fn (Collection $items) => $items->sortByDesc('as_of')->first())
                ->filter()
                ->values()
        );
    }

    /**
     * @param  Collection<int, Route>  $routes
     * @return array{
     *     combined_score: ?float,
     *     flight_score: ?float,
     *     news_score: ?float,
     *     flight_events: int,
     *     news_events: int,
     *     window_hours: ?int,
     *     snapshot_as_of: ?string
     * }
     */
    private function overallRouteIndicatorMetrics(Collection $routes): array
    {
        return $this->aggregateRouteIndicators(
            RouteIndicator::query()
                ->whereIn('route_id', $routes->pluck('id'))
                ->whereNull('travel_date')
                ->orderByDesc('as_of')
                ->get()
                ->groupBy('route_id')
                ->map(fn (Collection $items) => $items->sortByDesc('as_of')->first())
                ->filter()
                ->values()
        );
    }

    /**
     * @param  Collection<int, RouteIndicator>  $indicators
     * @return array{
     *     combined_score: ?float,
     *     flight_score: ?float,
     *     news_score: ?float,
     *     flight_events: int,
     *     news_events: int,
     *     window_hours: ?int,
     *     snapshot_as_of: ?string
     * }
     */
    private function aggregateRouteIndicators(Collection $indicators): array
    {
        if ($indicators->isEmpty()) {
            return [
                'combined_score' => null,
                'flight_score' => null,
                'news_score' => null,
                'flight_events' => 0,
                'news_events' => 0,
                'window_hours' => null,
                'snapshot_as_of' => null,
            ];
        }

        $latestIndicator = $indicators->sortByDesc('as_of')->first();
        $flightEvents = $indicators
            ->sum(fn (RouteIndicator $indicator): int => (int) ($indicator->supporting_factors['flight']['events_count'] ?? 0));
        $newsEvents = $indicators
            ->sum(fn (RouteIndicator $indicator): int => (int) ($indicator->supporting_factors['news']['events_count'] ?? 0));
        $flightScore = $indicators
            ->filter(fn (RouteIndicator $indicator): bool => (int) ($indicator->supporting_factors['flight']['events_count'] ?? 0) > 0)
            ->pluck('flight_score')
            ->map(fn ($score): float => (float) $score)
            ->avg();
        $newsScore = $indicators
            ->filter(fn (RouteIndicator $indicator): bool => (int) ($indicator->supporting_factors['news']['events_count'] ?? 0) > 0)
            ->pluck('news_score')
            ->map(fn ($score): float => (float) $score)
            ->avg();

        return [
            'combined_score' => $this->combinedScore([
                ['score' => $flightScore, 'events_count' => $flightEvents],
                ['score' => $newsScore, 'events_count' => $newsEvents],
            ]),
            'flight_score' => $flightEvents > 0 ? $this->rounded($flightScore) : null,
            'news_score' => $newsEvents > 0 ? $this->rounded($newsScore) : null,
            'flight_events' => $flightEvents,
            'news_events' => $newsEvents,
            'window_hours' => $latestIndicator?->window_hours,
            'snapshot_as_of' => $latestIndicator?->as_of?->toIso8601String(),
        ];
    }

    private function baseAirport(): Airport
    {
        $iata = strtoupper((string) config('operations.base_airport_iata', 'CUN'));

        $airport = Airport::query()
            ->with('city')
            ->where('iata', $iata)
            ->first();

        if ($airport) {
            return $airport;
        }

        throw new RuntimeException(sprintf('Configured base airport "%s" could not be found.', $iata));
    }

    /**
     * @return Collection<int, Route>
     */
    private function routesToBaseAirport(City $city, Airport $baseAirport): Collection
    {
        return Route::query()
            ->with(['originAirport.city', 'destinationAirport.city'])
            ->where('active', true)
            ->where('destination_airport_id', $baseAirport->id)
            ->whereHas('originAirport', fn ($query) => $query->where('city_id', $city->id))
            ->get();
    }

    /**
     * @param  Collection<int, Route>  $routes
     * @return array{score: ?float, events_count: int, route_count: int}
     */
    private function flightMetricsForTravelDate(Collection $routes, string $travelDate): array
    {
        if ($routes->isEmpty()) {
            return [
                'score' => null,
                'events_count' => 0,
                'route_count' => 0,
            ];
        }

        $query = FlightEvent::query()
            ->whereIn('route_id', $routes->pluck('id'))
            ->whereDate('travel_date', $travelDate);

        $count = (clone $query)->count();

        return [
            'score' => $count > 0 ? $this->rounded((clone $query)->avg('disruption_score')) : null,
            'events_count' => $count,
            'route_count' => $routes->count(),
        ];
    }

    /**
     * @param  Collection<int, Route>  $routes
     * @return array{score: ?float, events_count: int, route_count: int}
     */
    private function overallFlightMetrics(Collection $routes): array
    {
        if ($routes->isEmpty()) {
            return [
                'score' => null,
                'events_count' => 0,
                'route_count' => 0,
            ];
        }

        $overallIndicators = $routes
            ->map(function (Route $route): ?RouteIndicator {
                return RouteIndicator::query()
                    ->where('route_id', $route->id)
                    ->whereNull('travel_date')
                    ->latest('as_of')
                    ->first();
            })
            ->filter(fn (?RouteIndicator $indicator): bool => $indicator !== null)
            ->values();

        if ($overallIndicators->isNotEmpty()) {
            $eventsCount = $overallIndicators
                ->sum(fn (RouteIndicator $indicator): int => (int) ($indicator->supporting_factors['flight']['events_count'] ?? 0));
            $scores = $overallIndicators
                ->filter(fn (RouteIndicator $indicator): bool => (int) ($indicator->supporting_factors['flight']['events_count'] ?? 0) > 0)
                ->pluck('flight_score')
                ->map(fn ($score): float => (float) $score)
                ->values();

            return [
                'score' => $scores->isEmpty() ? null : $this->rounded($scores->avg()),
                'events_count' => $eventsCount,
                'route_count' => $routes->count(),
            ];
        }

        $query = FlightEvent::query()->whereIn('route_id', $routes->pluck('id'));
        $count = (clone $query)->count();

        return [
            'score' => $count > 0 ? $this->rounded((clone $query)->avg('disruption_score')) : null,
            'events_count' => $count,
            'route_count' => $routes->count(),
        ];
    }

    /**
     * @param  list<array{score: ?float, events_count: int}>  $metrics
     */
    private function combinedScore(array $metrics): float
    {
        $scores = collect($metrics)
            ->filter(fn (array $metric): bool => (int) ($metric['events_count'] ?? 0) > 0 && $metric['score'] !== null)
            ->pluck('score')
            ->map(fn ($score): float => (float) $score)
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

    /**
     * @param  Collection<int, WatchTarget>  $watchTargets
     * @return array{
     *     providers: int,
     *     payloads: int,
     *     normalized_events: int,
     *     payload_ids: array<int, int>,
     *     ingestion_run_ids: array<int, int>
     * }
     */
    private function runManualIngestion(string $providerService, string $sourceType, Collection $watchTargets): array
    {
        $providers = Provider::query()
            ->where('service', $providerService)
            ->where('active', true)
            ->orderBy('id')
            ->get();

        if ($providers->isEmpty()) {
            throw new RuntimeException('No active providers are configured for this manual trigger.');
        }

        $payloadCount = 0;
        $normalizedEvents = 0;
        $payloadIds = collect();
        $ingestionRunIds = collect();

        foreach ($providers as $provider) {
            $providerPayloadCount = 0;
            $providerNormalizedEvents = 0;

            $ingestionRun = $provider->ingestionRuns()->create([
                'source_type' => $sourceType,
                'status' => 'running',
                'started_at' => now(),
                'request_meta' => [
                    'manual_trigger' => true,
                    'watch_target_ids' => $watchTargets->pluck('id')->all(),
                    'watch_target_count' => $watchTargets->count(),
                    'provider_slug' => $provider->slug,
                ],
            ]);
            $ingestionRunIds->push($ingestionRun->id);

            try {
                foreach ($watchTargets as $watchTarget) {
                    $fetched = $this->fetchItems($provider, $providerService, $watchTarget);
                    $items = $fetched['items'];
                    $httpExchanges = $fetched['http_exchanges'];

                    if ($items === [] && $httpExchanges === []) {
                        continue;
                    }

                    $payload = $provider->rawPayloads()->create([
                        'source_type' => $sourceType,
                        'external_reference' => $this->externalReference($items),
                        'payload' => [
                            'watch_target_id' => $watchTarget->id,
                            'criteria' => $this->buildCriteria($provider, $providerService, $watchTarget),
                            'http_exchanges' => $httpExchanges,
                            'items' => $this->normalizeItems($items),
                        ],
                        'fetched_at' => now(),
                        'ingestion_run_id' => $ingestionRun->id,
                    ]);

                    $payloadCount++;
                    $providerPayloadCount++;
                    $payloadIds->push($payload->id);

                    $normalizedForPayload = $items === []
                        ? 0
                        : $this->normalizePayload($providerService, $payload);

                    $normalizedEvents += $normalizedForPayload;
                    $providerNormalizedEvents += $normalizedForPayload;
                }

                $ingestionRun->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                    'response_meta' => [
                        'manual_trigger' => true,
                        'payload_count' => $providerPayloadCount,
                        'normalized_events' => $providerNormalizedEvents,
                        'provider_slug' => $provider->slug,
                    ],
                ]);
            } catch (Throwable $exception) {
                $this->markRunFailed($ingestionRun, $providerPayloadCount, $providerNormalizedEvents, $provider, $exception);

                throw $exception;
            }
        }

        return [
            'providers' => $providers->count(),
            'payloads' => $payloadCount,
            'normalized_events' => $normalizedEvents,
            'payload_ids' => $payloadIds->values()->all(),
            'ingestion_run_ids' => $ingestionRunIds->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, int>  $payloadIds
     * @return array<int, array<string, mixed>>
     */
    private function fetchedFlightsForPayloads(Route $route, Collection $payloadIds): array
    {
        if ($payloadIds->isEmpty()) {
            return [];
        }

        return FlightEvent::query()
            ->with('sourceProvider')
            ->where('route_id', $route->id)
            ->whereIn('raw_payload_id', $payloadIds->all())
            ->orderBy('travel_date')
            ->orderBy('event_time')
            ->get()
            ->map(fn (FlightEvent $event): array => [
                'travel_date' => $event->travel_date?->toDateString(),
                'airline_code' => $event->airline_code,
                'delay_average_minutes' => $event->delay_average_minutes,
                'cancellation_rate' => $event->cancellation_rate,
                'disruption_score' => $event->disruption_score,
                'summary' => $event->summary,
                'provider' => $event->sourceProvider?->name,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCriteria(Provider $provider, string $providerService, WatchTarget $watchTarget): array
    {
        return match ($providerService) {
            'weather' => [
                'provider_slug' => $provider->slug,
                'watch_target_id' => $watchTarget->id,
                'location_code' => $watchTarget->originAirport?->iata ?? $watchTarget->originCity->name,
                'timezone' => $watchTarget->originAirport?->timezone ?? 'UTC',
                'date_window_days' => $watchTarget->date_window_days,
            ],
            'flights' => [
                'provider_slug' => $provider->slug,
                'watch_target_id' => $watchTarget->id,
                'origin_code' => $watchTarget->originAirport?->iata ?? $watchTarget->originCity->name,
                'destination_code' => $watchTarget->destinationAirport?->iata ?? $watchTarget->destinationCity?->name ?? 'ANY',
                'date_window_days' => $watchTarget->date_window_days,
            ],
            'news' => [
                'provider_slug' => $provider->slug,
                'watch_target_id' => $watchTarget->id,
                'headline_context' => trim(sprintf(
                    '%s travel to %s',
                    $watchTarget->originAirport?->iata
                        ?? $watchTarget->originCity?->name
                        ?? 'origin',
                    $watchTarget->destinationAirport?->iata
                        ?? $watchTarget->destinationCity?->name
                        ?? 'destination'
                )),
                'date_window_days' => $watchTarget->date_window_days,
            ],
            default => throw new RuntimeException("Unsupported manual ingestion service [{$providerService}]."),
        };
    }

    /**
     * @return array{items: array<int, object|array<string, mixed>>, http_exchanges: list<array<string, mixed>>}
     */
    private function fetchItems(Provider $provider, string $providerService, WatchTarget $watchTarget): array
    {
        $criteria = $this->buildCriteria($provider, $providerService, $watchTarget);

        $adapter = match ($providerService) {
            'weather' => $this->providerAdapterRegistry->weather($provider),
            'flights' => $this->providerAdapterRegistry->flights($provider),
            'news' => $this->providerAdapterRegistry->news($provider),
            default => throw new RuntimeException("Unsupported manual ingestion service [{$providerService}]."),
        };

        $items = match ($providerService) {
            'weather' => $adapter->fetchWeather($criteria),
            'flights' => $adapter->searchFlights($criteria),
            'news' => $adapter->fetchNews($criteria),
            default => throw new RuntimeException("Unsupported manual ingestion service [{$providerService}]."),
        };

        return [
            'items' => $items,
            'http_exchanges' => $adapter instanceof ConfiguredHttpProvider
                ? $adapter->pullExchangeLog()
                : [],
        ];
    }

    private function normalizePayload(string $providerService, RawProviderPayload $payload): int
    {
        return match ($providerService) {
            'weather' => $this->weatherPayloadNormalizer->normalize($payload),
            'flights' => $this->flightPayloadNormalizer->normalize($payload),
            'news' => $this->newsPayloadNormalizer->normalize($payload),
            default => throw new RuntimeException("Unsupported manual normalization service [{$providerService}]."),
        };
    }

    /**
     * @param  array<int, object|array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        return collect($items)
            ->map(function (object|array $item): array {
                if (is_array($item)) {
                    return $item;
                }

                if (method_exists($item, 'toArray')) {
                    /** @var array<string, mixed> $normalized */
                    $normalized = $item->toArray();

                    return $normalized;
                }

                return get_object_vars($item);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, object|array<string, mixed>>  $items
     */
    private function externalReference(array $items): ?string
    {
        $first = $items[0] ?? null;

        if ($first === null) {
            return null;
        }

        if (is_array($first)) {
            return $first['external_reference'] ?? null;
        }

        return property_exists($first, 'externalReference') ? $first->externalReference : null;
    }

    private function markRunFailed(
        IngestionRun $ingestionRun,
        int $payloadCount,
        int $normalizedEvents,
        Provider $provider,
        Throwable $exception,
    ): void {
        $ingestionRun->update([
            'status' => 'failed',
            'finished_at' => now(),
            'response_meta' => [
                'manual_trigger' => true,
                'payload_count' => $payloadCount,
                'normalized_events' => $normalizedEvents,
                'provider_slug' => $provider->slug,
            ],
            'error_message' => $exception->getMessage(),
        ]);
    }
}
