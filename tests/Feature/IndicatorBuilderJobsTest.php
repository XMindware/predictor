<?php

namespace Tests\Feature;

use App\Jobs\BuildAirportIndicatorsJob;
use App\Jobs\BuildCityIndicatorsJob;
use App\Jobs\BuildRouteIndicatorsJob;
use App\Jobs\FetchFlightDataJob;
use App\Jobs\FetchNewsDataJob;
use App\Jobs\FetchWeatherDataJob;
use App\Jobs\WarmPopularRoutesCacheJob;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\FlightEvent;
use App\Models\IngestionRun;
use App\Models\NewsEvent;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\Route;
use App\Models\WeatherEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IndicatorBuilderJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_airport_indicators_job_creates_latest_snapshot_from_recent_events(): void
    {
        Carbon::setTestNow('2026-03-19 12:34:00');

        [$provider, $rawPayload, $originAirport] = $this->setUpFixture();

        WeatherEvent::create([
            'city_id' => $originAirport->city_id,
            'airport_id' => $originAirport->id,
            'event_time' => '2026-03-19 10:00:00',
            'forecast_for' => '2026-03-19 11:00:00',
            'severity_score' => 8.0,
            'condition_code' => 'RAIN',
            'summary' => 'Heavy rain expected.',
            'temperature' => 28.5,
            'precipitation_mm' => 9.1,
            'wind_speed' => 14.0,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        FlightEvent::create([
            'route_id' => null,
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => null,
            'airline_code' => 'AM',
            'event_time' => '2026-03-19 09:00:00',
            'travel_date' => '2026-03-20',
            'cancellation_rate' => 2.0,
            'delay_average_minutes' => 18.0,
            'disruption_score' => 6.0,
            'summary' => 'Moderate delays expected.',
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        NewsEvent::create([
            'city_id' => $originAirport->city_id,
            'airport_id' => $originAirport->id,
            'airline_code' => null,
            'published_at' => '2026-03-19 08:00:00',
            'title' => 'Airport alert issued',
            'summary' => 'Operations may be affected.',
            'url' => 'https://example.com/news/airport-alert',
            'category' => 'operations',
            'severity_score' => 6.0,
            'relevance_score' => 8.0,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        WeatherEvent::create([
            'city_id' => $originAirport->city_id,
            'airport_id' => $originAirport->id,
            'event_time' => '2026-03-18 10:00:00',
            'forecast_for' => '2026-03-18 11:00:00',
            'severity_score' => 1.0,
            'condition_code' => 'CLEAR',
            'summary' => 'Old event.',
            'temperature' => 29.0,
            'precipitation_mm' => 0,
            'wind_speed' => 2.0,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        app()->call([new BuildAirportIndicatorsJob(), 'handle']);
        app()->call([new BuildAirportIndicatorsJob(), 'handle']);

        $indicator = $originAirport->fresh()->indicators()->where('window_hours', 24)->sole();

        $this->assertSame('2026-03-19 12:00:00', $indicator->as_of->format('Y-m-d H:i:s'));
        $this->assertSame(8.0, $indicator->weather_score);
        $this->assertSame(6.0, $indicator->flight_score);
        $this->assertSame(7.0, $indicator->news_score);
        $this->assertSame(7.0, $indicator->combined_score);
        $this->assertSame(1, $indicator->supporting_factors['weather']['events_count']);
    }

    public function test_build_city_indicators_job_creates_latest_snapshot_from_recent_events(): void
    {
        Carbon::setTestNow('2026-03-19 12:34:00');

        [$provider, $rawPayload, $originAirport] = $this->setUpFixture();

        WeatherEvent::create([
            'city_id' => $originAirport->city_id,
            'airport_id' => null,
            'event_time' => '2026-03-19 09:00:00',
            'forecast_for' => '2026-03-19 12:00:00',
            'severity_score' => 9.0,
            'condition_code' => 'STORM',
            'summary' => 'Storm conditions expected.',
            'temperature' => 26.0,
            'precipitation_mm' => 20.0,
            'wind_speed' => 25.0,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        NewsEvent::create([
            'city_id' => $originAirport->city_id,
            'airport_id' => null,
            'airline_code' => null,
            'published_at' => '2026-03-19 07:00:00',
            'title' => 'City transit disruptions expected',
            'summary' => 'Heavy traffic and closures expected.',
            'url' => 'https://example.com/news/city-alert',
            'category' => 'operations',
            'severity_score' => 4.0,
            'relevance_score' => 8.0,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        app()->call([new BuildCityIndicatorsJob(), 'handle']);

        $indicator = City::query()->findOrFail($originAirport->city_id)
            ->indicators()
            ->where('window_hours', 24)
            ->sole();

        $this->assertSame('2026-03-19 12:00:00', $indicator->as_of->format('Y-m-d H:i:s'));
        $this->assertSame(9.0, $indicator->weather_score);
        $this->assertSame(6.0, $indicator->news_score);
        $this->assertSame(7.5, $indicator->combined_score);
        $this->assertSame(1, $indicator->supporting_factors['news']['events_count']);
    }

    public function test_build_route_indicators_job_creates_overall_and_travel_date_snapshots(): void
    {
        Carbon::setTestNow('2026-03-19 12:34:00');

        [$provider, $rawPayload, $originAirport, $destinationAirport, $route] = $this->setUpFixture(withRoute: true);

        FlightEvent::create([
            'route_id' => $route->id,
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'airline_code' => 'AM',
            'event_time' => '2026-03-19 09:00:00',
            'travel_date' => '2026-03-21',
            'cancellation_rate' => 1.5,
            'delay_average_minutes' => 16.0,
            'disruption_score' => 6.0,
            'summary' => 'Moderate disruption.',
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        FlightEvent::create([
            'route_id' => $route->id,
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'airline_code' => 'AM',
            'event_time' => '2026-03-19 10:30:00',
            'travel_date' => '2026-03-21',
            'cancellation_rate' => 2.0,
            'delay_average_minutes' => 20.0,
            'disruption_score' => 8.0,
            'summary' => 'Higher disruption.',
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        NewsEvent::create([
            'city_id' => $originAirport->city_id,
            'airport_id' => $originAirport->id,
            'airline_code' => null,
            'published_at' => '2026-03-19 06:00:00',
            'title' => 'Route disruption warning',
            'summary' => 'Travelers may experience delays.',
            'url' => 'https://example.com/news/route-warning',
            'category' => 'operations',
            'severity_score' => 5.0,
            'relevance_score' => 9.0,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        app()->call([new BuildRouteIndicatorsJob(), 'handle']);

        $overall = $route->fresh()->indicators()->whereNull('travel_date')->sole();
        $dated = $route->fresh()->indicators()->whereDate('travel_date', '2026-03-21')->sole();

        $this->assertSame('2026-03-19 12:00:00', $overall->as_of->format('Y-m-d H:i:s'));
        $this->assertSame(7.0, $overall->flight_score);
        $this->assertSame(7.0, $overall->news_score);
        $this->assertSame(7.0, $overall->combined_score);
        $this->assertSame('2026-03-21', $dated->travel_date?->format('Y-m-d'));
        $this->assertSame(2, $dated->supporting_factors['flight']['events_count']);
    }

    public function test_indicator_jobs_are_registered_with_the_scheduler(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->description ?? $event->getSummaryForDisplay())
            ->filter()
            ->values()
            ->all();

        $this->assertContains('ingestion:flights', $events);
        $this->assertContains('ingestion:weather', $events);
        $this->assertContains('ingestion:news', $events);
        $this->assertContains('normalization:retry', $events);
        $this->assertContains('indicators:airports', $events);
        $this->assertContains('indicators:cities', $events);
        $this->assertContains('indicators:routes', $events);
        $this->assertContains('health:stale-data-check', $events);
        $this->assertContains('monitoring:failure-alerts', $events);
        $this->assertContains('cache:warm-popular-routes', $events);
    }

    /**
     * @return array{0: Provider, 1: RawProviderPayload, 2: Airport, 3: ?Airport, 4: ?Route}
     */
    private function setUpFixture(bool $withRoute = false): array
    {
        $provider = Provider::create([
            'name' => 'OpenWeather',
            'slug' => 'openweather',
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

        $originAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $originCity->id,
            'name' => 'Cancun International Airport',
            'iata' => 'CUN',
            'icao' => 'MMUN',
            'timezone' => 'America/Cancun',
        ]);

        $rawPayload = RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'external_reference' => 'payload-1',
            'payload' => ['sample' => true],
            'fetched_at' => now(),
            'ingestion_run_id' => IngestionRun::create([
                'provider_id' => $provider->id,
                'source_type' => 'weather',
                'status' => 'completed',
                'started_at' => now()->subMinute(),
                'finished_at' => now(),
            ])->id,
        ]);

        if (! $withRoute) {
            return [$provider, $rawPayload, $originAirport, null, null];
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
        ]);

        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        return [$provider, $rawPayload, $originAirport, $destinationAirport, $route];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
