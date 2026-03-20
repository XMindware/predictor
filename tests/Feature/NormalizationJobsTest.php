<?php

namespace Tests\Feature;

use App\Jobs\NormalizeFlightPayloadJob;
use App\Jobs\NormalizeNewsPayloadJob;
use App\Jobs\NormalizeWeatherPayloadJob;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\IngestionRun;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\Route;
use App\Models\WatchTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizationJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_weather_payload_job_maps_raw_items_into_weather_events(): void
    {
        [$provider, $watchTarget] = $this->setUpFixture('weather', 'openweather');
        $payload = $this->makePayload($provider->id, 'weather', $watchTarget->id, [
            [
                'provider_slug' => 'openweather',
                'location_code' => 'CUN',
                'external_reference' => 'weather:forecast:CUN',
                'timezone' => 'America/Cancun',
                'observed_at' => '2026-03-19T12:00:00Z',
                'temperature_celsius' => 29.5,
                'precipitation_probability' => 0.35,
                'condition' => 'rain',
                'meta' => [
                    'wind_speed' => 18.0,
                    'precipitation_mm' => 8.5,
                ],
            ],
        ]);

        app()->call([new NormalizeWeatherPayloadJob($payload->id), 'handle']);

        $this->assertDatabaseHas('weather_events', [
            'raw_payload_id' => $payload->id,
            'city_id' => $watchTarget->origin_city_id,
            'airport_id' => $watchTarget->origin_airport_id,
            'condition_code' => 'RAIN',
        ]);

        $payload->refresh();
        $this->assertNotNull($payload->normalized_at);
    }

    public function test_normalize_flight_payload_job_maps_raw_items_into_flight_events(): void
    {
        [$provider, $watchTarget, $route] = $this->setUpFixture('flights', 'flightstats');
        $payload = $this->makePayload($provider->id, 'flights', $watchTarget->id, [
            [
                'provider_slug' => 'flightstats',
                'origin_code' => 'CUN',
                'destination_code' => 'MID',
                'external_reference' => 'flight:offer:123',
                'departure_at' => '2026-03-20T08:00:00Z',
                'arrival_at' => '2026-03-20T10:15:00Z',
                'carrier_code' => 'AM',
                'flight_number' => '100',
                'price_amount' => 199.99,
                'price_currency' => 'USD',
                'stops' => 1,
                'meta' => [
                    'delay_average_minutes' => 20,
                    'cancellation_rate' => 2.5,
                ],
            ],
        ]);

        app()->call([new NormalizeFlightPayloadJob($payload->id), 'handle']);

        $this->assertDatabaseHas('flight_events', [
            'raw_payload_id' => $payload->id,
            'route_id' => $route->id,
            'origin_airport_id' => $watchTarget->origin_airport_id,
            'destination_airport_id' => $watchTarget->destination_airport_id,
            'airline_code' => 'AM',
        ]);

        $payload->refresh();
        $this->assertNotNull($payload->normalized_at);
    }

    public function test_normalize_news_payload_job_maps_raw_items_into_news_events(): void
    {
        [$provider, $watchTarget] = $this->setUpFixture('news', 'newsapi');
        $payload = $this->makePayload($provider->id, 'news', $watchTarget->id, [
            [
                'provider_slug' => 'newsapi',
                'title' => 'Storm warning issued for Cancun routes',
                'external_reference' => 'article:storm-warning',
                'summary' => 'Heavy rain may affect monitored routes.',
                'url' => 'https://example.com/article',
                'published_at' => '2026-03-19T09:00:00Z',
                'topics' => ['weather', 'disruption'],
                'meta' => ['language' => 'en'],
            ],
        ]);

        app()->call([new NormalizeNewsPayloadJob($payload->id), 'handle']);

        $this->assertDatabaseHas('news_events', [
            'raw_payload_id' => $payload->id,
            'city_id' => $watchTarget->origin_city_id,
            'airport_id' => $watchTarget->origin_airport_id,
            'category' => 'weather',
            'title' => 'Storm warning issued for Cancun routes',
        ]);

        $payload->refresh();
        $this->assertNotNull($payload->normalized_at);
    }

    /**
     * @return array{0: Provider, 1: WatchTarget, 2: Route}
     */
    private function setUpFixture(string $service, string $slug): array
    {
        $provider = Provider::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'service' => $service,
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

        $watchTarget = WatchTarget::create([
            'origin_city_id' => $originCity->id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => $destinationCity->id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
            'monitoring_priority' => 10,
            'date_window_days' => 7,
        ]);

        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        return [$provider, $watchTarget, $route];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function makePayload(int $providerId, string $sourceType, int $watchTargetId, array $items): RawProviderPayload
    {
        $run = IngestionRun::create([
            'provider_id' => $providerId,
            'source_type' => $sourceType,
            'status' => 'completed',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);

        return RawProviderPayload::create([
            'provider_id' => $providerId,
            'source_type' => $sourceType,
            'external_reference' => 'payload-1',
            'payload' => [
                'watch_target_id' => $watchTargetId,
                'items' => $items,
            ],
            'fetched_at' => now(),
            'ingestion_run_id' => $run->id,
        ]);
    }
}
