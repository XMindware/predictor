<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\AirportIndicator;
use App\Models\City;
use App\Models\Country;
use App\Models\RiskQuerySnapshot;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Models\ScoringProfile;
use App\Models\User;
use App\Models\WatchTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RouteRiskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_risk_endpoint_requires_sanctum_authentication(): void
    {
        $this->getJson('/api/routes/risk?destination=CUN&date=2026-03-22')
            ->assertUnauthorized();
    }

    public function test_route_risk_endpoint_validates_query_inputs(): void
    {
        $token = User::factory()->create()->createToken('api-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/routes/risk')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['destination', 'date']);
    }

    public function test_route_risk_endpoint_returns_ranked_routes_by_risk(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        $token = User::factory()->create()->createToken('api-test')->plainTextToken;
        [$highestRiskRoute, $lowerRiskRoute] = $this->setUpRankedRoutes();

        $this->withToken($token)
            ->getJson('/api/routes/risk?destination=CUN&date=2026-03-22')
            ->assertOk()
            ->assertJson([
                'meta' => [
                    'travel_date' => '2026-03-22',
                    'count' => 2,
                    'scope' => [
                        'travel_window_hours' => 72,
                        'monitored_routes_only' => true,
                        'scoring_mode' => 'deterministic_rules',
                    ],
                    'destination' => [
                        'airport' => [
                            'iata' => 'CUN',
                        ],
                    ],
                ],
                'data' => [
                    [
                        'rank' => 1,
                        'route_id' => $highestRiskRoute->id,
                        'assessment_type' => 'short_term_travel_disruption_risk',
                        'scoring_mode' => 'deterministic_rules',
                        'score' => 6.77,
                        'risk_level' => 'medium',
                        'recommended_action' => [
                            'code' => 'watch_and_adjust',
                        ],
                        'origin' => [
                            'airport' => [
                                'iata' => 'MID',
                            ],
                        ],
                    ],
                    [
                        'rank' => 2,
                        'route_id' => $lowerRiskRoute->id,
                        'score' => 3.57,
                        'risk_level' => 'low',
                        'origin' => [
                            'airport' => [
                                'iata' => 'MEX',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    [
                        'rank',
                        'route_id',
                        'origin',
                        'destination',
                        'travel_date',
                        'assessment_type',
                        'scoring_mode',
                        'product_framing',
                        'scope',
                        'score',
                        'risk_level',
                        'confidence',
                        'freshness',
                        'drivers',
                        'probable_no_show_uplift',
                        'recommended_action',
                        'factors',
                        'summaries',
                        'snapshot',
                    ],
                ],
                'meta' => [
                    'destination',
                    'travel_date',
                    'count',
                    'scope',
                ],
            ]);

        $this->assertDatabaseCount('risk_query_snapshots', 2);
    }

    public function test_route_risk_endpoint_reuses_cached_ranking_for_repeated_queries(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        $token = User::factory()->create()->createToken('api-test')->plainTextToken;
        $this->setUpRankedRoutes();

        $this->withToken($token)
            ->getJson('/api/routes/risk?destination=CUN&date=2026-03-22')
            ->assertOk();

        $firstSnapshots = RiskQuerySnapshot::query()->count();

        $this->withToken($token)
            ->getJson('/api/routes/risk?destination=CUN&date=2026-03-22')
            ->assertOk();

        $this->assertSame($firstSnapshots, RiskQuerySnapshot::query()->count());
    }

    public function test_route_risk_endpoint_rejects_dates_outside_the_v1_window(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        $token = User::factory()->create()->createToken('api-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/routes/risk?destination=CUN&date=2026-03-25')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array{0: Route, 1: Route}
     */
    private function setUpRankedRoutes(): array
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

        $ignoredCity = City::create([
            'country_id' => $country->id,
            'name' => 'Tulum',
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

        $ignoredAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $ignoredCity->id,
            'name' => 'Tulum International Airport',
            'iata' => 'TQO',
            'icao' => 'MMTL',
            'timezone' => 'America/Cancun',
        ]);

        $highestRiskRoute = Route::create([
            'origin_airport_id' => $midAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        $lowerRiskRoute = Route::create([
            'origin_airport_id' => $mexAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        $ignoredRoute = Route::create([
            'origin_airport_id' => $ignoredAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        WatchTarget::create([
            'origin_city_id' => $midCity->id,
            'origin_airport_id' => $midAirport->id,
            'destination_city_id' => $destinationCity->id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
            'monitoring_priority' => 10,
            'date_window_days' => 3,
        ]);

        WatchTarget::create([
            'origin_city_id' => $mexCity->id,
            'origin_airport_id' => $mexAirport->id,
            'destination_city_id' => $destinationCity->id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
            'monitoring_priority' => 7,
            'date_window_days' => 3,
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

        AirportIndicator::create([
            'airport_id' => $ignoredAirport->id,
            'as_of' => '2026-03-20 08:00:00',
            'window_hours' => 24,
            'weather_score' => 9.0,
            'flight_score' => 9.0,
            'news_score' => 9.0,
            'combined_score' => 9.0,
            'supporting_factors' => ['source' => 'ignored'],
        ]);

        RouteIndicator::create([
            'route_id' => $highestRiskRoute->id,
            'as_of' => '2026-03-20 08:00:00',
            'travel_date' => '2026-03-22',
            'window_hours' => 24,
            'flight_score' => 8.0,
            'news_score' => 6.0,
            'combined_score' => 7.0,
            'supporting_factors' => ['route' => 'mid-cun'],
        ]);

        RouteIndicator::create([
            'route_id' => $lowerRiskRoute->id,
            'as_of' => '2026-03-20 08:00:00',
            'travel_date' => '2026-03-22',
            'window_hours' => 24,
            'flight_score' => 4.0,
            'news_score' => 2.0,
            'combined_score' => 3.0,
            'supporting_factors' => ['route' => 'mex-cun'],
        ]);

        RouteIndicator::create([
            'route_id' => $ignoredRoute->id,
            'as_of' => '2026-03-20 08:00:00',
            'travel_date' => '2026-03-22',
            'window_hours' => 24,
            'flight_score' => 9.0,
            'news_score' => 9.0,
            'combined_score' => 9.0,
            'supporting_factors' => ['route' => 'ignored-cun'],
        ]);

        return [$highestRiskRoute, $lowerRiskRoute];
    }
}
