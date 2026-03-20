<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\City;
use App\Models\RiskQuerySnapshot;
use App\Models\Route;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class RouteRiskRankingService
{
    public function __construct(
        private readonly RiskScoringService $riskScoringService,
        private readonly MonitoredRouteService $monitoredRouteService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function rank(string $destination, CarbonInterface|string $travelDate, int $limit = 10): array
    {
        $resolvedDestination = $this->resolveDestination($destination);
        $normalizedTravelDate = $travelDate instanceof CarbonInterface
            ? $travelDate->copy()->startOfDay()
            : Carbon::parse($travelDate)->startOfDay();
        $maxRoutes = min($limit, (int) config('operations.v1_route_risk_limit', 10));
        $routes = $this->monitoredRouteService->prioritized(
            $this->routesForDestination($resolvedDestination),
            $maxRoutes
        );

        $ranked = $routes
            ->map(function (Route $route) use ($normalizedTravelDate) {
                $snapshot = RiskQuerySnapshot::query()
                    ->where('route_id', $route->id)
                    ->whereDate('travel_date', $normalizedTravelDate->toDateString())
                    ->latest('generated_at')
                    ->latest('id')
                    ->first();

                if ($snapshot) {
                    return $this->fromSnapshot($route, $snapshot, $normalizedTravelDate);
                }

                $assessment = $this->riskScoringService->calculate(
                    $route->originAirport,
                    $route->destinationAirport,
                    $normalizedTravelDate,
                );

                return $this->fromAssessment($route, $assessment, $normalizedTravelDate);
            })
            ->sortByDesc('score')
            ->values()
            ->map(function (array $item, int $index): array {
                $item['rank'] = $index + 1;

                return $item;
            });

        return [
            'destination' => [
                'input' => $destination,
                'airport' => $resolvedDestination['airport'] ? [
                    'id' => $resolvedDestination['airport']->id,
                    'iata' => $resolvedDestination['airport']->iata,
                    'name' => $resolvedDestination['airport']->name,
                ] : null,
                'city' => $resolvedDestination['city'] ? [
                    'id' => $resolvedDestination['city']->id,
                    'name' => $resolvedDestination['city']->name,
                ] : null,
            ],
            'travel_date' => $normalizedTravelDate->toDateString(),
            'count' => $ranked->count(),
            'scope' => [
                'travel_window_hours' => (int) config('operations.v1_risk_window_hours', 72),
                'monitored_routes_only' => true,
                'entity_level' => 'route_and_airport',
                'max_routes' => $maxRoutes,
                'scoring_mode' => 'deterministic_rules',
            ],
            'data' => $ranked->all(),
        ];
    }

    /**
     * @param  array{airport: ?Airport, city: ?City}  $destination
     * @return \Illuminate\Support\Collection<int, Route>
     */
    private function routesForDestination(array $destination)
    {
        $query = Route::query()
            ->with(['originAirport.city', 'destinationAirport.city'])
            ->where('active', true);

        if ($destination['airport']) {
            $query->where('destination_airport_id', $destination['airport']->id);
        } elseif ($destination['city']) {
            $query->whereHas('destinationAirport', fn ($builder) => $builder->where('city_id', $destination['city']->id));
        }

        return $query->get();
    }

    /**
     * @return array{airport: ?Airport, city: ?City}
     */
    private function resolveDestination(string $destination): array
    {
        $normalized = trim($destination);

        $airport = Airport::query()
            ->with('city')
            ->whereRaw('lower(iata) = ?', [strtolower($normalized)])
            ->orWhereRaw('lower(icao) = ?', [strtolower($normalized)])
            ->orWhereRaw('lower(name) = ?', [strtolower($normalized)])
            ->first();

        if ($airport) {
            return [
                'airport' => $airport,
                'city' => $airport->city,
            ];
        }

        $city = City::query()
            ->whereRaw('lower(name) = ?', [strtolower($normalized)])
            ->first();

        if ($city) {
            return [
                'airport' => null,
                'city' => $city,
            ];
        }

        throw new InvalidArgumentException(sprintf('Unable to resolve destination "%s".', $destination));
    }

    /**
     * @param  array<string, mixed>  $assessment
     * @return array<string, mixed>
     */
    private function fromAssessment(Route $route, array $assessment, CarbonInterface $travelDate): array
    {
        return [
            'route_id' => $route->id,
            'origin' => $assessment['resolved']['origin'],
            'destination' => $assessment['resolved']['destination'],
            'travel_date' => $travelDate->toDateString(),
            'assessment_type' => $assessment['assessment_type'],
            'scoring_mode' => $assessment['scoring_mode'] ?? 'deterministic_rules',
            'product_framing' => $assessment['product_framing'],
            'scope' => $assessment['scope'] ?? [
                'travel_window_hours' => (int) config('operations.v1_risk_window_hours', 72),
                'monitored_routes_only' => true,
                'entity_level' => 'route_and_airport',
            ],
            'score' => $assessment['score'],
            'risk_level' => $assessment['risk_level'],
            'confidence' => $assessment['confidence'],
            'freshness' => $assessment['freshness'],
            'drivers' => $assessment['drivers'],
            'probable_no_show_uplift' => $assessment['probable_no_show_uplift'],
            'recommended_action' => $assessment['recommended_action'],
            'factors' => [
                'components' => $assessment['components'],
                'weighted_contributions' => $assessment['weighted_contributions'],
            ],
            'summaries' => $assessment['explanations'],
            'snapshot' => $assessment['snapshot'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fromSnapshot(Route $route, RiskQuerySnapshot $snapshot, CarbonInterface $travelDate): array
    {
        return [
            'route_id' => $route->id,
            'origin' => [
                'airport' => $route->originAirport ? [
                    'id' => $route->originAirport->id,
                    'iata' => $route->originAirport->iata,
                    'name' => $route->originAirport->name,
                ] : null,
                'city' => $route->originAirport?->city ? [
                    'id' => $route->originAirport->city->id,
                    'name' => $route->originAirport->city->name,
                ] : null,
            ],
            'destination' => [
                'airport' => $route->destinationAirport ? [
                    'id' => $route->destinationAirport->id,
                    'iata' => $route->destinationAirport->iata,
                    'name' => $route->destinationAirport->name,
                ] : null,
                'city' => $route->destinationAirport?->city ? [
                    'id' => $route->destinationAirport->city->id,
                    'name' => $route->destinationAirport->city->name,
                ] : null,
            ],
            'travel_date' => $travelDate->toDateString(),
            'assessment_type' => $snapshot->factors['assessment_type'] ?? 'short_term_travel_disruption_risk',
            'scoring_mode' => $snapshot->factors['scoring_mode'] ?? 'deterministic_rules',
            'product_framing' => $snapshot->factors['product_framing']
                ?? 'Estimate of short-term travel disruption risk and probable no-show uplift, not a deterministic no-show prediction.',
            'scope' => $snapshot->factors['scope'] ?? [
                'travel_window_hours' => (int) config('operations.v1_risk_window_hours', 72),
                'monitored_routes_only' => true,
                'entity_level' => 'route_and_airport',
            ],
            'score' => $snapshot->score,
            'risk_level' => $snapshot->risk_level,
            'confidence' => $snapshot->factors['confidence'] ?? [
                'level' => $snapshot->confidence_level,
            ],
            'freshness' => $snapshot->factors['freshness'] ?? [
                'level' => 'unknown',
            ],
            'drivers' => $snapshot->factors['drivers'] ?? [],
            'probable_no_show_uplift' => $snapshot->factors['probable_no_show_uplift'] ?? [
                'estimate_percent' => null,
                'range_percent' => [
                    'low' => null,
                    'high' => null,
                ],
                'method' => 'heuristic_from_disruption_risk',
                'framing' => 'Directional estimate of probable no-show uplift from short-term disruption risk, not a deterministic no-show forecast.',
            ],
            'recommended_action' => $snapshot->factors['recommended_action'] ?? [
                'code' => 'manual_review',
                'summary' => 'Review this market manually before changing inventory or pricing because the stored decision context is incomplete.',
                'primary_driver' => null,
            ],
            'factors' => [
                'components' => $snapshot->factors['components'] ?? [],
                'weighted_contributions' => $snapshot->factors['weighted_contributions'] ?? [],
            ],
            'summaries' => $snapshot->factors['explanations'] ?? [],
            'snapshot' => [
                'persisted' => true,
                'id' => $snapshot->id,
                'generated_at' => $snapshot->generated_at?->toIso8601String(),
            ],
        ];
    }
}
