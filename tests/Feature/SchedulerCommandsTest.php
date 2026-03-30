<?php

namespace Tests\Feature;

use App\Jobs\BuildAirportIndicatorsJob;
use App\Jobs\BuildCityIndicatorsJob;
use App\Jobs\BuildRouteIndicatorsJob;
use App\Jobs\FetchFlightDataJob;
use App\Jobs\FetchNewsDataJob;
use App\Jobs\FetchWeatherDataJob;
use App\Jobs\NormalizeFlightPayloadJob;
use App\Jobs\NormalizeNewsPayloadJob;
use App\Jobs\NormalizeWeatherPayloadJob;
use App\Models\Airport;
use App\Models\AirportIndicator;
use App\Models\City;
use App\Models\CityIndicator;
use App\Models\Country;
use App\Models\FailedJob;
use App\Models\FlightEvent;
use App\Models\IngestionRun;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\RiskQuerySnapshot;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Support\PlatformHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SchedulerCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_commands_dispatch_ingestion_jobs(): void
    {
        Bus::fake();

        $this->artisan('ingestion:fetch-weather')->assertSuccessful();
        $this->artisan('ingestion:fetch-flights')->assertSuccessful();
        $this->artisan('ingestion:fetch-news')->assertSuccessful();

        Bus::assertDispatched(FetchWeatherDataJob::class);
        Bus::assertDispatched(FetchFlightDataJob::class);
        Bus::assertDispatched(FetchNewsDataJob::class);
    }

    public function test_retry_normalization_command_dispatches_pending_old_payloads_only(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-19 14:00:00');

        $provider = Provider::create([
            'name' => 'OpenWeather',
            'slug' => 'openweather',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
        ]);

        $oldRun = IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'finished_at' => now()->subMinutes(50),
        ]);

        RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(30),
            'ingestion_run_id' => $oldRun->id,
        ]);

        RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'flight',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(25),
            'ingestion_run_id' => $oldRun->id,
        ]);

        RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'news',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(5),
            'ingestion_run_id' => $oldRun->id,
        ]);

        RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'news',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(45),
            'normalized_at' => now()->subMinutes(40),
            'ingestion_run_id' => $oldRun->id,
        ]);

        $this->artisan('ingestion:retry-normalization --limit=10 --grace-minutes=10')
            ->assertSuccessful();

        Bus::assertDispatched(NormalizeWeatherPayloadJob::class, 1);
        Bus::assertDispatched(NormalizeFlightPayloadJob::class, 1);
        Bus::assertNotDispatched(NormalizeNewsPayloadJob::class);

        Carbon::setTestNow();
    }

    public function test_indicator_build_commands_dispatch_aggregation_jobs(): void
    {
        Bus::fake();

        $this->artisan('indicators:build-airports')->assertSuccessful();
        $this->artisan('indicators:build-cities')->assertSuccessful();
        $this->artisan('indicators:build-routes')->assertSuccessful();

        Bus::assertDispatched(BuildAirportIndicatorsJob::class);
        Bus::assertDispatched(BuildCityIndicatorsJob::class);
        Bus::assertDispatched(BuildRouteIndicatorsJob::class);
    }

    public function test_stale_data_check_command_caches_fresh_report(): void
    {
        Config::set('cache.default', 'array');
        Carbon::setTestNow('2026-03-19 14:00:00');

        [$provider, $originAirport, $route] = $this->setUpGeographyFixture();

        $run = IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(9),
        ]);

        RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(20),
            'normalized_at' => now()->subMinutes(19),
            'ingestion_run_id' => $run->id,
        ]);

        RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'flight',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(10),
            'normalized_at' => now()->subMinutes(9),
            'ingestion_run_id' => $run->id,
        ]);

        RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'news',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(25),
            'normalized_at' => now()->subMinutes(24),
            'ingestion_run_id' => $run->id,
        ]);

        AirportIndicator::create([
            'airport_id' => $originAirport->id,
            'as_of' => now()->subMinutes(30),
            'window_hours' => 24,
            'weather_score' => 5.0,
            'flight_score' => 4.0,
            'news_score' => 3.0,
            'combined_score' => 4.0,
            'supporting_factors' => [],
        ]);

        CityIndicator::create([
            'city_id' => $originAirport->city_id,
            'as_of' => now()->subMinutes(30),
            'window_hours' => 24,
            'weather_score' => 5.0,
            'news_score' => 3.0,
            'combined_score' => 4.0,
            'supporting_factors' => [],
        ]);

        RouteIndicator::create([
            'route_id' => $route->id,
            'as_of' => now()->subMinutes(30),
            'travel_date' => now()->addDays(7)->toDateString(),
            'window_hours' => 24,
            'flight_score' => 4.0,
            'news_score' => 3.0,
            'combined_score' => 3.5,
            'supporting_factors' => [],
        ]);

        $this->artisan('health:check-stale-data')->assertSuccessful();

        $report = Cache::get(PlatformHealth::STALE_DATA_REPORT_CACHE_KEY);

        $this->assertIsArray($report);
        $this->assertSame('ok', $report['status']);
        $this->assertSame('ok', $report['checks']['weather_payloads']['status']);
        $this->assertSame('ok', $report['checks']['normalization_backlog']['status']);
        $this->assertSame('ok', $report['checks']['route_indicators']['status']);

        Carbon::setTestNow();
    }

    public function test_refresh_alerts_command_caches_failed_job_and_stale_alerts(): void
    {
        Config::set('cache.default', 'array');
        Carbon::setTestNow('2026-03-19 14:00:00');

        $provider = Provider::create([
            'name' => 'OpenSky',
            'slug' => 'opensky',
            'service' => 'flight',
            'driver' => 'rest',
            'active' => true,
        ]);

        IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'flight',
            'status' => 'failed',
            'started_at' => now()->subMinutes(20),
            'finished_at' => now()->subMinutes(18),
            'error_message' => 'Provider timed out.',
        ]);

        FailedJob::create([
            'uuid' => (string) str()->uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{"job":"Example"}',
            'exception' => 'RuntimeException: Queue worker failed.',
            'failed_at' => now()->subMinutes(5),
        ]);

        Cache::forever(PlatformHealth::STALE_DATA_REPORT_CACHE_KEY, [
            'status' => 'degraded',
            'checked_at' => now()->toIso8601String(),
            'checks' => [
                'weather_payloads' => [
                    'status' => 'error',
                    'age_minutes' => 95,
                ],
            ],
        ]);

        $this->artisan('monitoring:refresh-alerts')->assertSuccessful();

        $alerts = Cache::get(PlatformHealth::FAILURE_ALERTS_CACHE_KEY);

        $this->assertIsArray($alerts);
        $this->assertCount(3, $alerts);
        $this->assertSame('failed_jobs', $alerts[0]['type']);
        $this->assertSame('ingestion_run', $alerts[1]['type']);
        $this->assertSame('stale_data', $alerts[2]['type']);

        Carbon::setTestNow();
    }

    public function test_demo_data_clear_flights_command_removes_seeded_and_xx_flight_data_only(): void
    {
        Carbon::setTestNow('2026-03-19 14:00:00');

        [$provider, $originAirport, $route] = $this->setUpGeographyFixture();

        $seedRun = IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'flights',
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'finished_at' => now()->subMinutes(50),
            'error_message' => 'seed:flights',
            'request_meta' => ['seeded' => true],
            'response_meta' => ['seeded' => true],
        ]);

        $demoPayload = RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'flights',
            'external_reference' => 'seed:flight:CUN:MID:day-1',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(50),
            'normalized_at' => now()->subMinutes(49),
            'ingestion_run_id' => $seedRun->id,
        ]);

        FlightEvent::create([
            'route_id' => $route->id,
            'origin_airport_id' => $route->origin_airport_id,
            'destination_airport_id' => $route->destination_airport_id,
            'airline_code' => 'Y4',
            'event_time' => now()->subMinutes(49),
            'travel_date' => now()->addDay()->toDateString(),
            'cancellation_rate' => 0.0,
            'delay_average_minutes' => 18.0,
            'disruption_score' => 4.2,
            'summary' => 'Seeded flight.',
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $demoPayload->id,
        ]);

        RouteIndicator::create([
            'route_id' => $route->id,
            'as_of' => now()->startOfHour(),
            'travel_date' => now()->addDay()->toDateString(),
            'window_hours' => 24,
            'flight_score' => 4.0,
            'news_score' => 2.0,
            'combined_score' => 3.0,
            'supporting_factors' => [],
        ]);

        RiskQuerySnapshot::create([
            'route_id' => $route->id,
            'origin_airport_id' => $route->origin_airport_id,
            'destination_airport_id' => $route->destination_airport_id,
            'travel_date' => now()->addDay()->toDateString(),
            'score' => 3.5,
            'risk_level' => 'moderate',
            'confidence_level' => 'high',
            'factors' => [],
            'generated_at' => now(),
        ]);

        $realRun = IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'flights',
            'status' => 'completed',
            'started_at' => now()->subMinutes(40),
            'finished_at' => now()->subMinutes(39),
        ]);

        $realPayload = RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'flights',
            'external_reference' => 'flight:flightstats:real-123',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(39),
            'normalized_at' => now()->subMinutes(38),
            'ingestion_run_id' => $realRun->id,
        ]);

        FlightEvent::create([
            'route_id' => $route->id,
            'origin_airport_id' => $route->origin_airport_id,
            'destination_airport_id' => $route->destination_airport_id,
            'airline_code' => 'DL',
            'event_time' => now()->subMinutes(38),
            'travel_date' => now()->addDays(2)->toDateString(),
            'cancellation_rate' => 0.0,
            'delay_average_minutes' => 5.0,
            'disruption_score' => 2.2,
            'summary' => 'Real flight.',
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $realPayload->id,
        ]);

        $xxRun = IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'flights',
            'status' => 'completed',
            'started_at' => now()->subMinutes(35),
            'finished_at' => now()->subMinutes(34),
        ]);

        $xxPayload = RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'flights',
            'external_reference' => 'flight:flightstats:xx-456',
            'payload' => ['items' => []],
            'fetched_at' => now()->subMinutes(34),
            'normalized_at' => now()->subMinutes(33),
            'ingestion_run_id' => $xxRun->id,
        ]);

        FlightEvent::create([
            'route_id' => $route->id,
            'origin_airport_id' => $route->origin_airport_id,
            'destination_airport_id' => $route->destination_airport_id,
            'airline_code' => 'XX',
            'event_time' => now()->subMinutes(33),
            'travel_date' => now()->addDays(3)->toDateString(),
            'cancellation_rate' => 0.0,
            'delay_average_minutes' => 0.0,
            'disruption_score' => 1.5,
            'summary' => 'Unknown XX flight.',
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $xxPayload->id,
        ]);

        $this->artisan('demo-data:clear-flights')
            ->expectsOutput('Deleted 2 demo or XX flight event(s).')
            ->expectsOutput('Deleted 2 demo or XX raw payload(s).')
            ->expectsOutput('Deleted 2 empty ingestion run(s).')
            ->expectsOutput('Deleted 1 route indicator snapshot(s).')
            ->expectsOutput('Deleted 1 risk snapshot(s).')
            ->expectsOutput('Routes and watch targets were kept intact.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('raw_provider_payloads', ['id' => $demoPayload->id]);
        $this->assertDatabaseMissing('raw_provider_payloads', ['id' => $xxPayload->id]);
        $this->assertDatabaseMissing('flight_events', ['raw_payload_id' => $demoPayload->id]);
        $this->assertDatabaseMissing('flight_events', ['raw_payload_id' => $xxPayload->id]);
        $this->assertDatabaseMissing('ingestion_runs', ['id' => $seedRun->id]);
        $this->assertDatabaseMissing('ingestion_runs', ['id' => $xxRun->id]);

        $this->assertDatabaseHas('raw_provider_payloads', ['id' => $realPayload->id]);
        $this->assertDatabaseHas('flight_events', ['raw_payload_id' => $realPayload->id]);
        $this->assertDatabaseHas('routes', ['id' => $route->id]);

        Carbon::setTestNow();
    }

    /**
     * @return array{0: Provider, 1: Airport, 2: Route}
     */
    private function setUpGeographyFixture(): array
    {
        $provider = Provider::create([
            'name' => 'Platform Feed',
            'slug' => 'platform-feed',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
        ]);

        $country = Country::create([
            'name' => 'Mexico',
        ]);

        $originCity = City::create([
            'country_id' => $country->id,
            'name' => 'Cancun',
        ]);

        $destinationCity = City::create([
            'country_id' => $country->id,
            'name' => 'Merida',
        ]);

        $originAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $originCity->id,
            'name' => 'Cancun International Airport',
            'iata' => 'CUN',
            'icao' => 'MMUN',
            'timezone' => 'America/Cancun',
        ]);

        $destinationAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $destinationCity->id,
            'name' => 'Merida International Airport',
            'iata' => 'MID',
            'icao' => 'MMMD',
            'timezone' => 'America/Merida',
        ]);

        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        return [$provider, $originAirport, $route];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
