<?php

namespace App\Jobs;

use App\Models\RiskQuerySnapshot;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Services\QueryCacheService;
use App\Services\RiskScoringService;
use App\Services\RouteRiskRankingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class WarmPopularRoutesCacheJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $limit = 10,
        public int $defaultTravelLeadDays = 7,
    ) {
    }

    public function handle(
        QueryCacheService $queryCacheService,
        RiskScoringService $riskScoringService,
        RouteRiskRankingService $routeRiskRankingService,
    ): void {
        $targets = $this->targets();

        foreach ($targets as $target) {
            $payload = [
                'origin_airport' => $target['origin_airport'],
                'destination_airport' => $target['destination_airport'],
                'travel_date' => $target['travel_date'],
                'airline_code' => null,
            ];

            $queryCacheService->remember(
                'risk-assessment',
                $payload,
                function () use ($payload, $riskScoringService): array {
                    $assessment = $riskScoringService->calculate(
                        $payload['origin_airport'],
                        $payload['destination_airport'],
                        $payload['travel_date'],
                    );
                    $assessment['query'] = [
                        'origin_city' => null,
                        'origin_airport' => $payload['origin_airport'],
                        'destination_city' => null,
                        'destination_airport' => $payload['destination_airport'],
                        'travel_date' => $payload['travel_date'],
                        'airline_code' => null,
                    ];

                    return $assessment;
                },
            );
        }

        $targets
            ->map(fn (array $target): array => [
                'destination' => $target['destination_airport'],
                'date' => $target['travel_date'],
                'limit' => $this->limit,
            ])
            ->unique(fn (array $query): string => implode('|', [$query['destination'], $query['date'], (string) $query['limit']]))
            ->each(fn (array $query) => $queryCacheService->remember(
                'route-risk',
                $query,
                fn (): array => $routeRiskRankingService->rank($query['destination'], $query['date'], $query['limit']),
            ));
    }

    /**
     * @return Collection<int, array{route_id: int, origin_airport: string, destination_airport: string, travel_date: string}>
     */
    private function targets(): Collection
    {
        $recentTargets = RiskQuerySnapshot::query()
            ->with(['route.originAirport', 'route.destinationAirport'])
            ->whereNotNull('route_id')
            ->where('generated_at', '>=', now()->subDays(7))
            ->latest('generated_at')
            ->get()
            ->filter(fn (RiskQuerySnapshot $snapshot): bool => $snapshot->route?->originAirport !== null
                && $snapshot->route?->destinationAirport !== null
                && $snapshot->travel_date !== null)
            ->unique(fn (RiskQuerySnapshot $snapshot): string => implode('|', [
                (string) $snapshot->route_id,
                $snapshot->travel_date?->format('Y-m-d'),
            ]))
            ->take($this->limit)
            ->map(fn (RiskQuerySnapshot $snapshot): array => [
                'route_id' => $snapshot->route_id,
                'origin_airport' => $snapshot->route->originAirport->iata,
                'destination_airport' => $snapshot->route->destinationAirport->iata,
                'travel_date' => $snapshot->travel_date->format('Y-m-d'),
            ])
            ->values();

        if ($recentTargets->isNotEmpty()) {
            return $recentTargets;
        }

        $indicatorTargets = RouteIndicator::query()
            ->with(['route.originAirport', 'route.destinationAirport'])
            ->whereNotNull('travel_date')
            ->latest('as_of')
            ->get()
            ->filter(fn (RouteIndicator $indicator): bool => $indicator->route?->originAirport !== null
                && $indicator->route?->destinationAirport !== null
                && $indicator->travel_date !== null)
            ->unique(fn (RouteIndicator $indicator): string => implode('|', [
                (string) $indicator->route_id,
                $indicator->travel_date?->format('Y-m-d'),
            ]))
            ->take($this->limit)
            ->map(fn (RouteIndicator $indicator): array => [
                'route_id' => $indicator->route_id,
                'origin_airport' => $indicator->route->originAirport->iata,
                'destination_airport' => $indicator->route->destinationAirport->iata,
                'travel_date' => $indicator->travel_date->format('Y-m-d'),
            ])
            ->values();

        if ($indicatorTargets->isNotEmpty()) {
            return $indicatorTargets;
        }

        return Route::query()
            ->with(['originAirport', 'destinationAirport'])
            ->where('active', true)
            ->take($this->limit)
            ->get()
            ->filter(fn (Route $route): bool => $route->originAirport !== null && $route->destinationAirport !== null)
            ->map(fn (Route $route): array => [
                'route_id' => $route->id,
                'origin_airport' => $route->originAirport->iata,
                'destination_airport' => $route->destinationAirport->iata,
                'travel_date' => now()->addDays($this->defaultTravelLeadDays)->toDateString(),
            ])
            ->values();
    }
}
