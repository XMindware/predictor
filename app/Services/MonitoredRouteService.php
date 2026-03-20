<?php

namespace App\Services;

use App\Models\Route;
use App\Models\WatchTarget;
use Illuminate\Support\Collection;

class MonitoredRouteService
{
    /**
     * @return Collection<int, Route>
     */
    public function prioritized(Collection $routes, ?int $limit = null): Collection
    {
        $maxRoutes = $limit ?? (int) config('operations.v1_route_risk_limit', 10);

        return $routes
            ->map(function (Route $route): ?array {
                $priority = $this->priorityFor($route);

                if ($priority === null) {
                    return null;
                }

                return [
                    'route' => $route,
                    'priority' => $priority,
                ];
            })
            ->filter()
            ->sortByDesc('priority')
            ->take($maxRoutes)
            ->pluck('route')
            ->values();
    }

    public function isMonitored(Route $route): bool
    {
        return $this->priorityFor($route) !== null;
    }

    public function priorityFor(Route $route): ?int
    {
        $route->loadMissing(['originAirport.city', 'destinationAirport.city']);

        return WatchTarget::query()
            ->where('enabled', true)
            ->get()
            ->filter(fn (WatchTarget $watchTarget): bool => $this->matches($watchTarget, $route))
            ->max('monitoring_priority');
    }

    private function matches(WatchTarget $watchTarget, Route $route): bool
    {
        $matchesAirports = $watchTarget->origin_airport_id === $route->origin_airport_id
            && $watchTarget->destination_airport_id === $route->destination_airport_id;

        $matchesCities = $watchTarget->origin_city_id === $route->originAirport?->city_id
            && $watchTarget->destination_city_id === $route->destinationAirport?->city_id;

        return $matchesAirports || $matchesCities;
    }
}
