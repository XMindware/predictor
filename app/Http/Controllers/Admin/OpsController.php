<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AirportIndicator;
use App\Models\CityIndicator;
use App\Models\City;
use App\Models\FailedJob;
use App\Models\IngestionRun;
use App\Models\Provider;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Models\WatchTarget;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OpsController extends Controller
{
    public function index(): View
    {
        $activeWatchTargets = WatchTarget::query()
            ->with([
                'originCity.country',
                'originAirport.city',
                'destinationCity.country',
                'destinationAirport.city',
            ])
            ->where('enabled', true)
            ->orderByDesc('monitoring_priority')
            ->orderBy('id')
            ->get();

        return view('admin.ops.index', [
            'providers' => Provider::query()
                ->withCount(['credentials', 'configs', 'ingestionRuns'])
                ->orderBy('service')
                ->orderBy('name')
                ->get(),
            'watchTargets' => $activeWatchTargets->take(25),
            'ingestionRuns' => IngestionRun::query()
                ->with('provider')
                ->latest('started_at')
                ->limit(25)
                ->get(),
            'airportIndicators' => AirportIndicator::query()
                ->with(['airport.city'])
                ->latest('as_of')
                ->limit(15)
                ->get(),
            'cityIndicators' => CityIndicator::query()
                ->with('city.country')
                ->latest('as_of')
                ->limit(15)
                ->get(),
            'routeIndicators' => RouteIndicator::query()
                ->with(['route.originAirport.city', 'route.destinationAirport.city'])
                ->latest('as_of')
                ->limit(15)
                ->get(),
            'failedJobs' => FailedJob::query()
                ->latest('failed_at')
                ->limit(25)
                ->get(),
            'queryCities' => City::query()
                ->with('country')
                ->orderBy('name')
                ->get(),
            'weatherCities' => $this->weatherCities($activeWatchTargets),
            'newsCities' => $this->weatherCities($activeWatchTargets),
            'flightRoutes' => $this->flightRoutes($activeWatchTargets),
            'riskRoutes' => Route::query()
                ->with(['originAirport.city', 'destinationAirport.city'])
                ->where('active', true)
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * @param  Collection<int, WatchTarget>  $watchTargets
     * @return Collection<int, City>
     */
    private function weatherCities(Collection $watchTargets): Collection
    {
        $cityIds = $watchTargets
            ->pluck('origin_city_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return City::query()
            ->with('country')
            ->whereIn('id', $cityIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, WatchTarget>  $watchTargets
     * @return Collection<int, Route>
     */
    private function flightRoutes(Collection $watchTargets): Collection
    {
        $routeIds = Route::query()
            ->with(['originAirport.city', 'destinationAirport.city'])
            ->where('active', true)
            ->get()
            ->filter(function (Route $route) use ($watchTargets): bool {
                return $watchTargets->contains(function (WatchTarget $watchTarget) use ($route): bool {
                    $matchesAirports = $watchTarget->origin_airport_id === $route->origin_airport_id
                        && $watchTarget->destination_airport_id === $route->destination_airport_id;
                    $matchesCities = $watchTarget->origin_city_id === $route->originAirport?->city_id
                        && $watchTarget->destination_city_id === $route->destinationAirport?->city_id;

                    return $matchesAirports || $matchesCities;
                });
            })
            ->values();

        return $routeIds;
    }
}
