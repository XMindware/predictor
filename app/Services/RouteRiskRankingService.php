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
        $routes = $this->routesForDestination($resolvedDestination)
            ->take($limit);

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
            'score' => $assessment['score'],
            'risk_level' => $assessment['risk_level'],
            'confidence' => $assessment['confidence'],
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
            'score' => $snapshot->score,
            'risk_level' => $snapshot->risk_level,
            'confidence' => $snapshot->factors['confidence'] ?? [
                'level' => $snapshot->confidence_level,
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
