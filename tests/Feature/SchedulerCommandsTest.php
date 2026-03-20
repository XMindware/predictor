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
use App\Models\IngestionRun;
use App\Models\Provider;
use App\Models\RawProviderPayload;
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
