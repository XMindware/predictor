<?php

namespace Tests\Feature;

use App\Jobs\FetchFlightDataJob;
use App\Jobs\FetchNewsDataJob;
use App\Jobs\FetchWeatherDataJob;
use App\Jobs\NormalizeFlightPayloadJob;
use App\Jobs\NormalizeNewsPayloadJob;
use App\Jobs\NormalizeWeatherPayloadJob;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\Provider;
use App\Models\WatchTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class IngestionJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_weather_data_job_creates_run_raw_payload_and_dispatches_normalization(): void
    {
        Bus::fake();

        [$provider] = $this->setUpIngestionFixture('weather', 'openweather');

        app()->call([new FetchWeatherDataJob(), 'handle']);

        $this->assertDatabaseHas('ingestion_runs', [
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('raw_provider_payloads', [
            'provider_id' => $provider->id,
            'source_type' => 'weather',
        ]);

        Bus::assertDispatched(NormalizeWeatherPayloadJob::class);
    }

    public function test_fetch_flight_data_job_creates_run_raw_payload_and_dispatches_normalization(): void
    {
        Bus::fake();

        [$provider] = $this->setUpIngestionFixture('flights', 'flightstats');

        app()->call([new FetchFlightDataJob(), 'handle']);

        $this->assertDatabaseHas('ingestion_runs', [
            'provider_id' => $provider->id,
            'source_type' => 'flights',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('raw_provider_payloads', [
            'provider_id' => $provider->id,
            'source_type' => 'flights',
        ]);

        Bus::assertDispatched(NormalizeFlightPayloadJob::class);
    }

    public function test_fetch_news_data_job_creates_run_raw_payload_and_dispatches_normalization(): void
    {
        Bus::fake();

        [$provider] = $this->setUpIngestionFixture('news', 'newsapi');

        app()->call([new FetchNewsDataJob(), 'handle']);

        $this->assertDatabaseHas('ingestion_runs', [
            'provider_id' => $provider->id,
            'source_type' => 'news',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('raw_provider_payloads', [
            'provider_id' => $provider->id,
            'source_type' => 'news',
        ]);

        Bus::assertDispatched(NormalizeNewsPayloadJob::class);
    }

    /**
     * @return array{0: Provider, 1: WatchTarget}
     */
    private function setUpIngestionFixture(string $service, string $slug): array
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

        return [$provider, $watchTarget];
    }
}
