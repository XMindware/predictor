<?php

namespace Tests\Feature;

use App\Jobs\WarmPopularRoutesCacheJob;
use App\Models\Airport;
use App\Models\AirportIndicator;
use App\Models\City;
use App\Models\Country;
use App\Models\RiskQuerySnapshot;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Models\ScoringProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WarmPopularRoutesCacheJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_warm_popular_routes_cache_job_primes_assessment_and_ranking_without_duplicate_snapshots(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        $this->setUpPopularRoutes();

        app()->call([new WarmPopularRoutesCacheJob(limit: 10), 'handle']);
        $this->assertDatabaseCount('risk_query_snapshots', 2);

        app()->call([new WarmPopularRoutesCacheJob(limit: 10), 'handle']);
        $this->assertDatabaseCount('risk_query_snapshots', 2);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function setUpPopularRoutes(): void
    {
        ScoringProfile::create([
            'name' => 'Default Risk Profile',
            'version' => 'v1',
            'weights' => [
                'flight' => 0.45,
                'weather' => 0.30,
                'news' => 0.20,
                'date_proximity' => 0.05,
            ],
            'thresholds' => [
                'low' => 3.0,
                'medium' => 6.0,
                'high' => 8.0,
            ],
            'active' => true,
        ]);

        $country = Country::create([
            'name' => 'Mexico',
        ]);

        $destinationCity = City::create([
            'country_id' => $country->id,
            'name' => 'Cancun',
        ]);

        $midCity = City::create([
            'country_id' => $country->id,
            'name' => 'Merida',
        ]);

        $mexCity = City::create([
            'country_id' => $country->id,
            'name' => 'Mexico City',
        ]);

        $destinationAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $destinationCity->id,
            'name' => 'Cancun International Airport',
            'iata' => 'CUN',
            'icao' => 'MMUN',
            'timezone' => 'America/Cancun',
        ]);

        $midAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $midCity->id,
            'name' => 'Merida International Airport',
            'iata' => 'MID',
            'icao' => 'MMMD',
            'timezone' => 'America/Merida',
        ]);

        $mexAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $mexCity->id,
            'name' => 'Mexico City International Airport',
            'iata' => 'MEX',
            'icao' => 'MMMX',
            'timezone' => 'America/Mexico_City',
        ]);

        $midRoute = Route::create([
            'origin_airport_id' => $midAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        $mexRoute = Route::create([
            'origin_airport_id' => $mexAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        AirportIndicator::create([
            'airport_id' => $destinationAirport->id,
            'as_of' => '2026-03-20 08:00:00',
            'window_hours' => 24,
            'weather_score' => 4.0,
            'flight_score' => 3.0,
            'news_score' => 2.0,
            'combined_score' => 3.0,
            'supporting_factors' => ['source' => 'destination'],
        ]);

        AirportIndicator::create([
            'airport_id' => $midAirport->id,
            'as_of' => '2026-03-20 08:00:00',
            'window_hours' => 24,
            'weather_score' => 6.0,
            'flight_score' => 7.0,
            'news_score' => 4.0,
            'combined_score' => 5.67,
            'supporting_factors' => ['source' => 'mid'],
        ]);

        AirportIndicator::create([
            'airport_id' => $mexAirport->id,
            'as_of' => '2026-03-20 08:00:00',
            'window_hours' => 24,
            'weather_score' => 2.0,
            'flight_score' => 3.0,
            'news_score' => 1.0,
            'combined_score' => 2.0,
            'supporting_factors' => ['source' => 'mex'],
        ]);

        RouteIndicator::create([
            'route_id' => $midRoute->id,
            'as_of' => '2026-03-20 08:00:00',
            'travel_date' => '2026-03-25',
            'window_hours' => 24,
            'flight_score' => 8.0,
            'news_score' => 6.0,
            'combined_score' => 7.0,
            'supporting_factors' => ['route' => 'mid-cun'],
        ]);

        RouteIndicator::create([
            'route_id' => $mexRoute->id,
            'as_of' => '2026-03-20 08:00:00',
            'travel_date' => '2026-03-25',
            'window_hours' => 24,
            'flight_score' => 4.0,
            'news_score' => 2.0,
            'combined_score' => 3.0,
            'supporting_factors' => ['route' => 'mex-cun'],
        ]);
    }
}
