<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\AirportIndicator;
use App\Models\City;
use App\Models\CityIndicator;
use App\Models\Country;
use App\Models\FlightEvent;
use App\Models\IngestionRun;
use App\Models\NewsEvent;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Models\ScoringProfile;
use App\Models\User;
use App\Models\WatchTarget;
use App\Models\WeatherEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ManualOpsTriggersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeLiveProviderResponses();
    }

    public function test_authenticated_users_can_manually_refetch_weather_for_a_city(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');

        $user = User::factory()->create();
        [$originCity, $originAirport] = $this->setUpWeatherFixture();

        $this->actingAs($user)
            ->post(route('admin.ops.triggers.weather'), [
                'city_id' => $originCity->id,
            ])
            ->assertRedirect(route('admin.ops.index'))
            ->assertSessionHas('status', 'Weather re-fetch completed for Cancun.');

        $this->assertDatabaseHas('ingestion_runs', [
            'source_type' => 'weather',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('raw_provider_payloads', [
            'source_type' => 'weather',
        ]);
        $this->assertDatabaseHas('weather_events', [
            'city_id' => $originCity->id,
            'airport_id' => $originAirport->id,
            'condition_code' => 'CLEAR',
        ]);
    }

    public function test_authenticated_users_can_manually_refetch_flights_for_a_route(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');

        $user = User::factory()->create();
        [, , $route] = $this->setUpFlightFixture();

        $this->actingAs($user)
            ->post(route('admin.ops.triggers.flights'), [
                'route_id' => $route->id,
            ])
            ->assertRedirect(route('admin.ops.index'))
            ->assertSessionHas('status', 'Flight re-fetch completed for CUN → MID.');

        $this->assertDatabaseHas('ingestion_runs', [
            'source_type' => 'flights',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('raw_provider_payloads', [
            'source_type' => 'flights',
        ]);
        $this->assertDatabaseHas('flight_events', [
            'route_id' => $route->id,
            'origin_airport_id' => $route->origin_airport_id,
            'destination_airport_id' => $route->destination_airport_id,
            'airline_code' => 'XX',
        ]);
    }

    public function test_authenticated_users_can_manually_refetch_news_for_a_city(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');

        $user = User::factory()->create();
        [$originCity, $originAirport] = $this->setUpNewsFixture();

        $this->actingAs($user)
            ->post(route('admin.ops.triggers.news'), [
                'city_id' => $originCity->id,
            ])
            ->assertRedirect(route('admin.ops.index'))
            ->assertSessionHas('status', 'News re-fetch completed for Cancun.');

        $this->assertDatabaseHas('ingestion_runs', [
            'source_type' => 'news',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('raw_provider_payloads', [
            'source_type' => 'news',
        ]);
        $this->assertDatabaseHas('news_events', [
            'city_id' => $originCity->id,
            'airport_id' => $originAirport->id,
            'title' => 'Operational update for monitored travel',
        ]);
    }

    public function test_authenticated_users_can_rebuild_indicators_from_the_ops_panel(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');

        $user = User::factory()->create();
        [$provider, $rawPayload, $route, $originCity, $originAirport] = $this->setUpIndicatorFixture();

        WeatherEvent::create([
            'city_id' => $originCity->id,
            'airport_id' => $originAirport->id,
            'event_time' => now()->subHour(),
            'forecast_for' => now(),
            'severity_score' => 6.0,
            'condition_code' => 'RAIN',
            'summary' => 'Rain expected.',
            'temperature' => 28.0,
            'precipitation_mm' => 4.0,
            'wind_speed' => 10.0,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        FlightEvent::create([
            'route_id' => $route->id,
            'origin_airport_id' => $route->origin_airport_id,
            'destination_airport_id' => $route->destination_airport_id,
            'airline_code' => 'XX',
            'event_time' => now()->subHour(),
            'travel_date' => now()->addDays(7)->toDateString(),
            'cancellation_rate' => 1.0,
            'delay_average_minutes' => 20.0,
            'disruption_score' => 7.0,
            'summary' => 'Manual rebuild test.',
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        NewsEvent::create([
            'city_id' => $originCity->id,
            'airport_id' => $originAirport->id,
            'airline_code' => null,
            'published_at' => now()->subHour(),
            'title' => 'Airport operations alert',
            'summary' => 'Conditions are monitored.',
            'url' => 'https://example.com/news',
            'category' => 'operations',
            'severity_score' => 5.0,
            'relevance_score' => 6.0,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        $this->actingAs($user)
            ->post(route('admin.ops.triggers.indicators'))
            ->assertRedirect(route('admin.ops.index'))
            ->assertSessionHas('status', 'Indicator rebuild completed.');

        $this->assertDatabaseHas('airport_indicators', [
            'airport_id' => $originAirport->id,
            'as_of' => now()->startOfHour(),
        ]);
        $this->assertDatabaseHas('route_indicators', [
            'route_id' => $route->id,
            'as_of' => now()->startOfHour(),
        ]);
    }

    public function test_authenticated_users_can_recompute_risk_from_the_ops_panel(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');

        $user = User::factory()->create();
        [$originAirport, $destinationAirport, $route] = $this->setUpRiskFixture();
        $travelDate = '2026-03-25';

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('admin.ops.triggers.risk'), [
                'route_id' => $route->id,
                'travel_date' => $travelDate,
            ]);

        $response
            ->assertOk()
            ->assertSee('Risk recompute completed.')
            ->assertSee('Risk Evaluation')
            ->assertSee('Recommended Action')
            ->assertSee('Top Drivers')
            ->assertSee('Probable No-show Uplift')
            ->assertSee('Estimate of short-term travel disruption risk and probable no-show uplift');

        $this->assertDatabaseHas('risk_query_snapshots', [
            'route_id' => $route->id,
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'travel_date' => $travelDate.' 00:00:00',
        ]);
    }

    public function test_authenticated_users_can_query_city_score_from_the_ops_panel(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');

        $user = User::factory()->create();
        [, $city] = $this->setUpCityIndicatorFixture();

        $this->actingAs($user)
            ->followingRedirects()
            ->post(route('admin.ops.triggers.city-score'), [
                'city_id' => $city->id,
                'query_date' => '2026-03-19',
            ])
            ->assertOk()
            ->assertSee('City Score Summary')
            ->assertSee('Score scale: 0 to 3 means low disruption risk');
    }

    public function test_city_score_includes_flights_into_the_configured_base_airport(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');
        config()->set('operations.base_airport_iata', 'CUN');

        $user = User::factory()->create();
        $city = $this->setUpBaseAirportCityScoreFixture();

        $this->actingAs($user)
            ->post(route('admin.ops.triggers.city-score'), [
                'city_id' => $city->id,
                'query_date' => '2026-03-21',
            ])
            ->assertRedirect(route('admin.ops.index'))
            ->assertSessionHas('status', 'City score query completed for New York.')
            ->assertSessionHas('manual_tool_result', function (array $result): bool {
                return $result['tool'] === 'query city score'
                    && $result['details']['city'] === 'New York'
                    && $result['details']['base_airport_iata'] === 'CUN'
                    && $result['details']['score_scope'] === 'route'
                    && $result['details']['route_label'] === 'JFK → CUN'
                    && $result['details']['news_score'] === 7.88
                    && $result['details']['flight_score'] === 6.42
                    && $result['details']['combined_score'] === 7.15
                    && ! array_key_exists('weather_score', $result['details'])
                    && $result['details']['flight_events'] === 1;
            });
    }

    public function test_route_backed_city_score_trend_matches_route_indicator_values(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');
        config()->set('operations.base_airport_iata', 'CUN');

        $user = User::factory()->create();
        $city = $this->setUpBaseAirportCityScoreFixture();

        $this->actingAs($user)
            ->followingRedirects()
            ->post(route('admin.ops.triggers.city-score'), [
                'city_id' => $city->id,
                'query_date' => '',
            ])
            ->assertOk()
            ->assertSee('This graph is route-backed and follows')
            ->assertSee('JFK → CUN')
            ->assertSee('Combined 7.15')
            ->assertSee('News 7.88')
            ->assertSee('Flight 6.42')
            ->assertDontSee('Weather 7.90');
    }

    public function test_authenticated_users_can_query_city_score_trend_when_no_date_is_selected(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');

        $user = User::factory()->create();
        [, $city] = $this->setUpCityIndicatorTrendFixture();

        $this->actingAs($user)
            ->followingRedirects()
            ->post(route('admin.ops.triggers.city-score'), [
                'city_id' => $city->id,
                'query_date' => '',
            ])
            ->assertOk()
            ->assertSee('City Score Trend')
            ->assertSee('Projected Daily Scores (0-10)')
            ->assertSee('Combined')
            ->assertSee('This graph combines city weather, city news, and flight disruption')
            ->assertSee('projected for the next 30 days')
            ->assertSee('from 2026-03-19 to 2026-04-17')
            ->assertSee('Flight')
            ->assertSee('Mar 19')
            ->assertSee('Apr 17');
    }

    /**
     * @return array{0: City, 1: Airport}
     */
    private function setUpWeatherFixture(): array
    {
        $provider = Provider::create([
            'name' => 'OpenWeather',
            'slug' => 'openweather',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
        ]);
        $this->configureLiveProvider($provider);

        [$country, $originCity, $originAirport] = $this->setUpGeography();

        WatchTarget::create([
            'origin_city_id' => $originCity->id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => null,
            'destination_airport_id' => null,
            'enabled' => true,
            'monitoring_priority' => 9,
            'date_window_days' => 7,
        ]);

        return [$originCity, $originAirport];
    }

    /**
     * @return array{0: City, 1: Airport}
     */
    private function setUpNewsFixture(): array
    {
        $provider = Provider::create([
            'name' => 'NewsAPI',
            'slug' => 'newsapi',
            'service' => 'news',
            'driver' => 'rest',
            'active' => true,
        ]);
        $this->configureLiveProvider($provider);

        [$country, $originCity, $originAirport] = $this->setUpGeography();

        WatchTarget::create([
            'origin_city_id' => $originCity->id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => null,
            'destination_airport_id' => null,
            'enabled' => true,
            'monitoring_priority' => 9,
            'date_window_days' => 7,
        ]);

        return [$originCity, $originAirport];
    }

    /**
     * @return array{0: City, 1: Airport, 2: Route}
     */
    private function setUpFlightFixture(): array
    {
        $provider = Provider::create([
            'name' => 'FlightStats',
            'slug' => 'flightstats',
            'service' => 'flights',
            'driver' => 'rest',
            'active' => true,
        ]);
        $this->configureLiveProvider($provider);
        $this->configureLiveProvider($provider);

        [, $originCity, $originAirport, $destinationCity, $destinationAirport] = $this->setUpGeography(withDestination: true);

        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        WatchTarget::create([
            'origin_city_id' => $originCity->id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => $destinationCity->id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
            'monitoring_priority' => 10,
            'date_window_days' => 7,
        ]);

        return [$originCity, $originAirport, $route];
    }

    /**
     * @return array{0: Provider, 1: RawProviderPayload, 2: Route, 3: City, 4: Airport}
     */
    private function setUpIndicatorFixture(): array
    {
        $provider = Provider::create([
            'name' => 'Platform Feed',
            'slug' => 'platform-feed',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
        ]);

        [, $originCity, $originAirport, $destinationCity, $destinationAirport] = $this->setUpGeography(withDestination: true);

        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        $run = IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(9),
        ]);

        $watchTarget = WatchTarget::create([
            'origin_city_id' => $originCity->id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => $destinationCity->id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
            'monitoring_priority' => 8,
            'date_window_days' => 7,
        ]);

        $rawPayload = RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'external_reference' => 'payload-1',
            'payload' => [
                'watch_target_id' => $watchTarget->id,
                'items' => [],
            ],
            'fetched_at' => now()->subMinutes(9),
            'ingestion_run_id' => $run->id,
        ]);

        return [$provider, $rawPayload, $route, $originCity, $originAirport];
    }

    /**
     * @return array{0: Airport, 1: Airport, 2: Route}
     */
    private function setUpRiskFixture(): array
    {
        ScoringProfile::create([
            'name' => 'Default',
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

        [, , $originAirport, , $destinationAirport] = $this->setUpGeography(withDestination: true);

        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        AirportIndicator::create([
            'airport_id' => $originAirport->id,
            'as_of' => now()->startOfHour(),
            'window_hours' => 24,
            'weather_score' => 4.0,
            'flight_score' => 6.0,
            'news_score' => 5.0,
            'combined_score' => 5.0,
            'supporting_factors' => [],
        ]);

        AirportIndicator::create([
            'airport_id' => $destinationAirport->id,
            'as_of' => now()->startOfHour(),
            'window_hours' => 24,
            'weather_score' => 3.0,
            'flight_score' => 4.0,
            'news_score' => 4.0,
            'combined_score' => 3.67,
            'supporting_factors' => [],
        ]);

        RouteIndicator::create([
            'route_id' => $route->id,
            'as_of' => now()->startOfHour(),
            'travel_date' => '2026-03-25',
            'window_hours' => 24,
            'flight_score' => 7.0,
            'news_score' => 6.0,
            'combined_score' => 6.5,
            'supporting_factors' => [],
        ]);

        return [$originAirport, $destinationAirport, $route];
    }

    /**
     * @return array{0: Country, 1: City}
     */
    private function setUpCityIndicatorFixture(): array
    {
        [$country, $city] = $this->setUpGeography();

        CityIndicator::create([
            'city_id' => $city->id,
            'as_of' => now()->startOfHour(),
            'window_hours' => 24,
            'weather_score' => 4.0,
            'news_score' => 5.0,
            'combined_score' => 4.5,
            'supporting_factors' => [
                'weather' => ['events_count' => 2],
                'news' => ['events_count' => 1],
            ],
        ]);

        return [$country, $city];
    }

    /**
     * @return array{0: Country, 1: City}
     */
    private function setUpCityIndicatorTrendFixture(): array
    {
        [$country, $city] = $this->setUpGeography();

        foreach ([
            ['as_of' => '2026-03-17 12:00:00', 'weather' => 3.5, 'news' => 4.5, 'combined' => 4.0],
            ['as_of' => '2026-03-18 12:00:00', 'weather' => 4.0, 'news' => 5.0, 'combined' => 4.5],
            ['as_of' => '2026-03-19 12:00:00', 'weather' => 5.0, 'news' => 6.0, 'combined' => 5.5],
        ] as $snapshot) {
            CityIndicator::create([
                'city_id' => $city->id,
                'as_of' => $snapshot['as_of'],
                'window_hours' => 24,
                'weather_score' => $snapshot['weather'],
                'news_score' => $snapshot['news'],
                'combined_score' => $snapshot['combined'],
                'supporting_factors' => [
                    'weather' => ['events_count' => 2],
                    'news' => ['events_count' => 1],
                ],
            ]);
        }

        return [$country, $city];
    }

    private function setUpBaseAirportCityScoreFixture(): City
    {
        $us = Country::create([
            'name' => 'United States',
        ]);

        $newYork = City::create([
            'country_id' => $us->id,
            'name' => 'New York',
        ]);

        $jfk = Airport::create([
            'country_id' => $us->id,
            'city_id' => $newYork->id,
            'name' => 'John F. Kennedy International Airport',
            'iata' => 'JFK',
            'icao' => 'KJFK',
            'timezone' => 'America/New_York',
            'latitude' => 40.6413,
            'longitude' => -73.7781,
        ]);

        $mx = Country::create([
            'name' => 'Mexico',
        ]);

        $cancun = City::create([
            'country_id' => $mx->id,
            'name' => 'Cancun',
        ]);

        $cun = Airport::create([
            'country_id' => $mx->id,
            'city_id' => $cancun->id,
            'name' => 'Cancun International Airport',
            'iata' => 'CUN',
            'icao' => 'MMUN',
            'timezone' => 'America/Cancun',
            'latitude' => 21.0365,
            'longitude' => -86.8771,
        ]);

        $route = Route::create([
            'origin_airport_id' => $jfk->id,
            'destination_airport_id' => $cun->id,
            'active' => true,
        ]);

        $provider = Provider::create([
            'name' => 'FlightStats',
            'slug' => 'flightstats',
            'service' => 'flights',
            'driver' => 'rest',
            'active' => true,
        ]);

        $run = IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'flights',
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(9),
        ]);

        $payload = RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'flights',
            'external_reference' => 'manual-test:jfk-cun:2026-03-21',
            'payload' => [
                'items' => [],
            ],
            'fetched_at' => now()->subMinutes(9),
            'ingestion_run_id' => $run->id,
        ]);

        FlightEvent::create([
            'route_id' => $route->id,
            'origin_airport_id' => $jfk->id,
            'destination_airport_id' => $cun->id,
            'airline_code' => 'DL',
            'event_time' => now()->subHour(),
            'travel_date' => '2026-03-21',
            'cancellation_rate' => 1.2,
            'delay_average_minutes' => 28.0,
            'disruption_score' => 6.42,
            'summary' => 'Seeded flight disruption outlook for JFK to CUN.',
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $payload->id,
        ]);

        RouteIndicator::create([
            'route_id' => $route->id,
            'as_of' => now()->startOfHour(),
            'travel_date' => null,
            'window_hours' => 24,
            'flight_score' => 7.41,
            'news_score' => 7.88,
            'combined_score' => 7.65,
            'supporting_factors' => [
                'flight' => ['events_count' => 10],
                'news' => ['events_count' => 2],
            ],
        ]);

        RouteIndicator::create([
            'route_id' => $route->id,
            'as_of' => now()->startOfHour(),
            'travel_date' => '2026-03-21',
            'window_hours' => 24,
            'flight_score' => 6.42,
            'news_score' => 7.88,
            'combined_score' => 7.15,
            'supporting_factors' => [
                'flight' => ['events_count' => 1],
                'news' => ['events_count' => 2],
            ],
        ]);

        return $newYork;
    }

    /**
     * @return array{0: Country, 1: City, 2: Airport, 3: ?City, 4: ?Airport}
     */
    private function setUpGeography(bool $withDestination = false): array
    {
        $country = Country::create([
            'name' => 'Mexico',
        ]);

        $originCity = City::create([
            'country_id' => $country->id,
            'name' => 'Cancun',
        ]);

        $originAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $originCity->id,
            'name' => 'Cancun International Airport',
            'iata' => 'CUN',
            'icao' => 'MMUN',
            'timezone' => 'America/Cancun',
            'latitude' => 21.0365,
            'longitude' => -86.8771,
        ]);

        if (! $withDestination) {
            return [$country, $originCity, $originAirport, null, null];
        }

        $destinationCity = City::create([
            'country_id' => $country->id,
            'name' => 'Merida',
        ]);

        $destinationAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $destinationCity->id,
            'name' => 'Merida International Airport',
            'iata' => 'MID',
            'icao' => 'MMMD',
            'timezone' => 'America/Merida',
            'latitude' => 20.9370,
            'longitude' => -89.6577,
        ]);

        return [$country, $originCity, $originAirport, $destinationCity, $destinationAirport];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
